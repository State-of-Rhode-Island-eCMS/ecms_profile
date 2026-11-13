<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_search_settings\Kernel;

use Drupal\ecms_search_settings\EventSubscriber\SearchFailureCacheSubscriber;
use Drupal\ecms_search_settings_test\TestTime;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the final HTTP cache behavior of a page whose search failed.
 *
 * Requests go through the full HTTP stack (the http_kernel service, not a
 * browser, so this works on every supported core version): the internal page
 * cache middleware, Dynamic Page Cache, FinishResponseSubscriber and
 * cache_control_override, as on production.
 */
#[Group('ecms_search_settings')]
#[Group('ecms')]
#[CoversClass(SearchFailureCacheSubscriber::class)]
class SearchFailureCacheTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * The path of the test search view.
   */
  const PATH = '/ecms-search-failure-test';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'views',
    'search_api',
    'page_cache',
    'dynamic_page_cache',
    'cache_control_override',
    'ecms_search_settings',
    'ecms_search_settings_test',
  ];

  /**
   * The simulated request time.
   */
  protected int $now;

  /**
   * Cookies sent with each request.
   *
   * @var string[]
   */
  protected array $cookies = [];

  /**
   * The response to the last request.
   */
  protected Response $response;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('search_api_task');
    $this->installSchema('search_api', ['search_api_item']);
    $this->installConfig(['system', 'user', 'views', 'search_api', 'cache_control_override']);
    $this->installConfig(['ecms_search_settings_test']);

    $this->container->get('theme_installer')->install(['stark']);
    $this->config('system.theme')->set('default', 'stark')->save();
    // Same as production: a long page max-age and hidden error messages. A
    // visible error message would start a session and stop page caching.
    $this->config('system.performance')->set('cache.page.max_age', 2764800)->save();
    $this->config('system.logging')->set('error_level', 'hide')->save();

    // User 1 bypasses permission checks, so create it first.
    $this->createUser();
    $this->container->get('router.builder')->rebuild();

    $this->setTime(1794268800);
    $this->container->get('state')->set('ecms_search_settings_test.fail', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    TestTime::$requestTime = NULL;
    parent::tearDown();
  }

  /**
   * Sets the time the next requests are made at.
   *
   * @param int $time
   *   The UNIX timestamp.
   */
  protected function setTime(int $time): void {
    $this->now = $time;
    TestTime::$requestTime = $time;
    // The page cache middleware reads the time from the request (see
    // ::get()), and the render cache from the test's own main request.
    $this->container->get('request_stack')->getMainRequest()->server->set('REQUEST_TIME', $time);
  }

  /**
   * Makes a GET request through the full HTTP stack.
   *
   * @param string $path
   *   The path.
   * @param array $query
   *   The query string parameters.
   *
   * @return string
   *   The response body.
   */
  protected function get(string $path, array $query = []): string {
    $request = Request::create('http://localhost' . $path, 'GET', $query, $this->cookies, [], ['REQUEST_TIME' => $this->now]);
    $http_kernel = $this->container->get('http_kernel');
    $this->response = $http_kernel->handle($request);
    $http_kernel->terminate($request, $this->response);
    return (string) $this->response->getContent();
  }

  /**
   * Asserts the value of a response header.
   *
   * @param string $name
   *   The header name.
   * @param string|null $value
   *   The expected value, or NULL if the header must be missing.
   */
  protected function assertHeader(string $name, ?string $value): void {
    $this->assertSame($value, $this->response->headers->get($name), "Header $name");
  }

  /**
   * Tests that an anonymous failed-search page is cached for the TTL.
   */
  public function testAnonymousFailedSearchIsCachedForTtl(): void {
    $ttl = SearchFailureCacheSubscriber::DEFAULT_MAX_AGE;

    $content = $this->get(self::PATH);
    $this->assertSame(200, $this->response->getStatusCode());
    $this->assertStringContainsString('No search results', $content);
    $this->assertHeader('X-Drupal-Cache', 'MISS');
    $this->assertHeader('Cache-Control', "max-age=$ttl, public");
    $this->assertHeader('Expires', gmdate('D, d M Y H:i:s', $this->now + $ttl) . ' GMT');
    $this->assertHeader('X-Ecms-Search-Failed', '1');

    // A repeated request within the TTL is served from the page cache.
    $this->setTime($this->now + $ttl - 1);
    $this->get(self::PATH);
    $this->assertHeader('X-Drupal-Cache', 'HIT');
    $this->assertHeader('Cache-Control', "max-age=$ttl, public");

    // Once the TTL has passed, the page is built again. Solr is back, so the
    // page is now cached normally.
    $this->container->get('state')->set('ecms_search_settings_test.fail', FALSE);
    $this->setTime($this->now + 2);
    $this->get(self::PATH);
    $this->assertHeader('X-Drupal-Cache', 'MISS');
    $this->assertHeader('X-Ecms-Search-Failed', NULL);
    $this->assertHeader('Cache-Control', 'max-age=2764800, public');
  }

  /**
   * Tests that the TTL from settings.php is used.
   */
  public function testTtlFromSettings(): void {
    $this->setSetting('ecms_search_failure_max_age', 300);

    $this->get(self::PATH);
    $this->assertHeader('Cache-Control', 'max-age=300, public');
    $this->assertHeader('Expires', gmdate('D, d M Y H:i:s', $this->now + 300) . ' GMT');
  }

  /**
   * Tests that other uncacheable content keeps the page uncacheable.
   */
  public function testOtherUncacheableContentIsKept(): void {
    $content = $this->get(self::PATH, ['uncacheable' => 1]);
    $this->assertStringContainsString('Uncacheable content', $content);
    $this->assertHeader('Cache-Control', 'must-revalidate, no-cache, private');
    $this->assertHeader('X-Ecms-Search-Failed', NULL);

    $this->get(self::PATH, ['uncacheable' => 1]);
    $this->assertNotSame('HIT', $this->response->headers->get('X-Drupal-Cache'));
  }

  /**
   * Tests that authenticated responses stay private.
   */
  public function testAuthenticatedResponseIsPrivate(): void {
    // An anonymous visit puts the failed page in the page cache first.
    $this->get(self::PATH);
    $this->assertHeader('Cache-Control', 'max-age=60, public');

    $account = $this->createUser();
    $this->logIn((int) $account->id());

    $content = $this->get(self::PATH);
    $this->assertSame(200, $this->response->getStatusCode());
    $this->assertStringContainsString('Logged in as ' . $account->getAccountName(), $content);
    $this->assertStringContainsString('No search results', $content);
    $this->assertHeader('X-Drupal-Cache', 'UNCACHEABLE (request policy)');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'UNCACHEABLE (poor cacheability)');
    $this->assertHeader('Cache-Control', 'must-revalidate, no-cache, private');
    $this->assertHeader('X-Ecms-Search-Failed', NULL);
  }

  /**
   * Sends a session cookie for a user with the following requests.
   *
   * @param int $uid
   *   The user ID.
   *
   * @see \Drupal\ecms_search_settings_test\Authentication\SessionCookieProvider
   */
  protected function logIn(int $uid): void {
    $request = Request::create('http://localhost/');
    $name = $this->container->get('session_configuration')->getOptions($request)['name'];
    $this->cookies[$name] = (string) $uid;
  }

}
