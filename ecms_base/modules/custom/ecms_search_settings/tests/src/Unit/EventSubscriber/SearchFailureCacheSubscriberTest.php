<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_search_settings\Unit\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\Site\Settings;
use Drupal\ecms_search_settings\EventSubscriber\SearchFailureCacheSubscriber;
use Drupal\search_api\Event\QueryPreExecuteEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Drupal\search_api\Query\Query;
use Drupal\search_api\Query\QueryInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Unit tests for SearchFailureCacheSubscriber.
 *
 * @package Drupal\Tests\ecms_search_settings\Unit\EventSubscriber
 */
#[Group('ecms_search_settings')]
#[Group('ecms')]
#[CoversClass(SearchFailureCacheSubscriber::class)]
class SearchFailureCacheSubscriberTest extends UnitTestCase {

  /**
   * The fixed request time used in tests.
   */
  const REQUEST_TIME = 1794268800;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * The page cache policy result for the current request.
   *
   * @var string|null
   */
  protected ?string $policyResult = RequestPolicyInterface::ALLOW;

  /**
   * The subscriber under test.
   *
   * @var \Drupal\ecms_search_settings\EventSubscriber\SearchFailureCacheSubscriber
   */
  protected SearchFailureCacheSubscriber $subscriber;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn(self::REQUEST_TIME);

    $policy = $this->createMock(RequestPolicyInterface::class);
    $policy->method('check')->willReturnCallback(fn () => $this->policyResult);

    $this->requestStack = new RequestStack();
    $this->pushRequest();

    $this->subscriber = new SearchFailureCacheSubscriber($time, $this->requestStack, $policy);
    $this->setSettings([]);
  }

  /**
   * Replace the site settings for a test.
   *
   * @param array $settings
   *   The settings to use.
   */
  private function setSettings(array $settings): void {
    new Settings($settings);
  }

  /**
   * Replace the main request.
   *
   * @param string $method
   *   The request method.
   */
  private function pushRequest(string $method = 'GET'): void {
    while ($this->requestStack->pop()) {
      // Empty the stack.
    }
    $this->requestStack->push(Request::create('/search?keys=test', $method));
  }

  /**
   * Notify the subscriber that a Search API query is about to run.
   *
   * @param bool $aborted
   *   Whether the query reports that it was aborted.
   * @param int $maxAge
   *   The query's max-age after it ran.
   *
   * @return \Drupal\search_api\Query\Query
   *   The query.
   */
  private function addQuery(bool $aborted, int $maxAge = 0): Query {
    $query = $this->getMockBuilder(Query::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['wasAborted'])
      ->getMock();
    $query->method('wasAborted')->willReturn($aborted);
    $query->mergeCacheMaxAge($maxAge);
    $this->subscriber->onQueryPreExecute(new QueryPreExecuteEvent($query));
    return $query;
  }

  /**
   * Build a ResponseEvent.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response.
   * @param bool $mainRequest
   *   Whether to simulate a main request (TRUE) or sub-request (FALSE).
   *
   * @return \Symfony\Component\HttpKernel\Event\ResponseEvent
   *   The configured event.
   */
  private function buildEvent(Response $response, bool $mainRequest = TRUE): ResponseEvent {
    $kernel = $this->createMock(HttpKernelInterface::class);
    $requestType = $mainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST;

    return new ResponseEvent($kernel, $this->requestStack->getMainRequest(), $requestType, $response);
  }

  /**
   * Build a cacheable response with the given status and max-age.
   *
   * @param int $status
   *   The HTTP status code.
   * @param int $maxAge
   *   The max-age of the response's cacheability metadata.
   *
   * @return \Drupal\Core\Cache\CacheableResponse
   *   The response.
   */
  private function buildResponse(int $status = 200, int $maxAge = 0): CacheableResponse {
    $response = new CacheableResponse('', $status);
    $response->getCacheableMetadata()->setCacheMaxAge($maxAge);
    return $response;
  }

  /**
   * Assert that the response headers were left as they were.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response.
   */
  private function assertHeadersUnchanged(Response $response): void {
    $this->assertNull($response->getExpires());
    $this->assertFalse($response->headers->has('X-Ecms-Search-Failed'));
  }

  /**
   * Test that getSubscribedEvents returns the expected event mapping.
   */
  public function testGetSubscribedEvents(): void {
    $events = SearchFailureCacheSubscriber::getSubscribedEvents();
    $this->assertEquals('onQueryPreExecute', $events[SearchApiEvents::QUERY_PRE_EXECUTE]);
    $this->assertEquals(['onRequest', 1000], $events[KernelEvents::REQUEST]);
    // Must run before FinishResponseSubscriber (priority 0).
    $this->assertEquals(['onResponse', 10], $events[KernelEvents::RESPONSE]);
  }

  /**
   * Test that only the failed query gets the failure TTL.
   *
   * @param array $settings
   *   The site settings.
   * @param int $expectedMaxAge
   *   The expected max-age.
   */
  #[DataProvider('dataProviderForFailedSearch')]
  public function testFailedQueryGetsTtl(array $settings, int $expectedMaxAge): void {
    $this->setSettings($settings);
    $succeeded = $this->addQuery(FALSE, 0);
    $failed = $this->addQuery(TRUE);

    $this->subscriber->applyFailureMaxAge();

    $this->assertSame($expectedMaxAge, $failed->getCacheMaxAge());
    // A query that did not fail keeps its own max-age.
    $this->assertSame(0, $succeeded->getCacheMaxAge());
  }

  /**
   * Data provider for testFailedQueryGetsTtl().
   *
   * @return array[]
   *   Keyed test cases.
   */
  public static function dataProviderForFailedSearch(): array {
    return [
      'default lifetime' => [
        [], SearchFailureCacheSubscriber::DEFAULT_MAX_AGE,
      ],
      'lifetime from settings' => [
        ['ecms_search_failure_max_age' => 300], 300,
      ],
    ];
  }

  /**
   * Test that a failed query keeps a lower non-zero max-age of its own.
   */
  public function testFailedQueryWithMaxAgeIsUnchanged(): void {
    $query = $this->addQuery(TRUE, 30);
    $this->subscriber->applyFailureMaxAge();
    $this->assertSame(30, $query->getCacheMaxAge());
  }

  /**
   * Test that other QueryInterface implementations are left alone.
   */
  public function testOtherQueryImplementationIsIgnored(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('wasAborted')->willReturn(TRUE);
    $this->subscriber->onQueryPreExecute(new QueryPreExecuteEvent($query));
    $this->subscriber->applyFailureMaxAge();

    $response = $this->buildResponse(200, 60);
    $this->subscriber->onResponse($this->buildEvent($response));
    $this->assertHeadersUnchanged($response);
  }

  /**
   * Test the cases where the failed query must not be changed.
   *
   * @param array $settings
   *   The site settings.
   * @param string $method
   *   The request method.
   * @param string|null $policyResult
   *   The page cache request policy result.
   */
  #[DataProvider('dataProviderForQueryUnchanged')]
  public function testQueryUnchanged(array $settings, string $method, ?string $policyResult): void {
    $this->setSettings($settings);
    $this->pushRequest($method);
    $this->policyResult = $policyResult;
    $query = $this->addQuery(TRUE);

    $this->subscriber->applyFailureMaxAge();
    $this->assertSame(0, $query->getCacheMaxAge());

    // Nothing was changed, so the response is not touched either.
    $response = $this->buildResponse(200, 60);
    $this->subscriber->onResponse($this->buildEvent($response));
    $this->assertHeadersUnchanged($response);
  }

  /**
   * Data provider for testQueryUnchanged().
   *
   * @return array[]
   *   Keyed test cases.
   */
  public static function dataProviderForQueryUnchanged(): array {
    return [
      'logged-in or session request' => [
        [], 'GET', NULL,
      ],
      'page cache denied' => [
        [], 'GET', RequestPolicyInterface::DENY,
      ],
      'POST is not cacheable' => [
        [], 'POST', RequestPolicyInterface::ALLOW,
      ],
      'turned off in settings' => [
        ['ecms_search_failure_max_age' => 0], 'GET', RequestPolicyInterface::ALLOW,
      ],
    ];
  }

  /**
   * Test that a page whose search failed gets a matching Expires header.
   */
  public function testFailedSearchResponseGetsExpires(): void {
    $this->addQuery(TRUE);
    $this->subscriber->applyFailureMaxAge();

    // Something else on the page lowered the max-age below the TTL.
    $response = $this->buildResponse(200, 45);
    $this->subscriber->onResponse($this->buildEvent($response));

    $this->assertSame(45, $response->getCacheableMetadata()->getCacheMaxAge());
    $this->assertSame(self::REQUEST_TIME + 45, $response->getExpires()->getTimestamp());
    $this->assertSame('1', $response->headers->get('X-Ecms-Search-Failed'));
  }

  /**
   * Test that a HEAD request is treated like GET.
   */
  public function testHeadRequest(): void {
    $this->pushRequest('HEAD');
    $query = $this->addQuery(TRUE);
    $this->subscriber->applyFailureMaxAge();

    $this->assertSame(SearchFailureCacheSubscriber::DEFAULT_MAX_AGE, $query->getCacheMaxAge());
  }

  /**
   * Test the cases where the response must not be changed.
   *
   * @param bool[] $queries
   *   The aborted state of each query run in the request.
   * @param bool $mainRequest
   *   Whether this is a main request.
   * @param int $status
   *   The response status code.
   * @param int $maxAge
   *   The page max-age.
   */
  #[DataProvider('dataProviderForResponseUnchanged')]
  public function testResponseUnchanged(array $queries, bool $mainRequest, int $status, int $maxAge): void {
    foreach ($queries as $aborted) {
      $this->addQuery($aborted);
    }
    $this->subscriber->applyFailureMaxAge();

    $response = $this->buildResponse($status, $maxAge);
    $this->subscriber->onResponse($this->buildEvent($response, $mainRequest));

    $this->assertSame($maxAge, $response->getCacheableMetadata()->getCacheMaxAge());
    $this->assertHeadersUnchanged($response);
  }

  /**
   * Data provider for testResponseUnchanged().
   *
   * @return array[]
   *   Keyed test cases.
   */
  public static function dataProviderForResponseUnchanged(): array {
    return [
      'no search on the page' => [
        [], TRUE, 200, 0,
      ],
      'search succeeded' => [
        [FALSE, FALSE], TRUE, 200, 0,
      ],
      'sub-request is ignored' => [
        [TRUE], FALSE, 200, 60,
      ],
      'server error is not made cacheable' => [
        [TRUE], TRUE, 500, 60,
      ],
      'redirect is not made cacheable' => [
        [TRUE], TRUE, 302, 60,
      ],
      'other content keeps the page uncacheable' => [
        [TRUE], TRUE, 200, 0,
      ],
      'permanent max-age is left alone' => [
        [TRUE], TRUE, 200, Cache::PERMANENT,
      ],
    ];
  }

  /**
   * Test that a new main request forgets the queries of the previous one.
   */
  public function testStateIsResetPerRequest(): void {
    $this->addQuery(TRUE);
    $this->subscriber->applyFailureMaxAge();

    $kernel = $this->createMock(HttpKernelInterface::class);
    $request = $this->requestStack->getMainRequest();
    // A sub-request keeps the state of its main request.
    $this->subscriber->onRequest(new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST));
    $response = $this->buildResponse(200, 60);
    $this->subscriber->onResponse($this->buildEvent($response));
    $this->assertSame('1', $response->headers->get('X-Ecms-Search-Failed'));

    $this->subscriber->onRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
    $response = $this->buildResponse(200, 60);
    $this->subscriber->onResponse($this->buildEvent($response));
    $this->assertHeadersUnchanged($response);
  }

  /**
   * Test that the request being handled is checked, not the stack's first.
   *
   * When a container handles several requests (e.g. kernel tests), the
   * request stack's main request can be an earlier, cacheable request.
   */
  public function testHandledRequestIsChecked(): void {
    // The stack's main request is a cacheable GET; the handled one is a POST.
    $kernel = $this->createMock(HttpKernelInterface::class);
    $handled = Request::create('/search?keys=test', 'POST');
    $this->subscriber->onRequest(new RequestEvent($kernel, $handled, HttpKernelInterface::MAIN_REQUEST));

    $query = $this->addQuery(TRUE);
    $this->subscriber->applyFailureMaxAge();
    $this->assertSame(0, $query->getCacheMaxAge());
  }

  /**
   * Test that a response without cacheability metadata is ignored.
   */
  public function testNonCacheableResponse(): void {
    $this->addQuery(TRUE);
    $this->subscriber->applyFailureMaxAge();

    $response = new Response();
    $this->subscriber->onResponse($this->buildEvent($response));

    $this->assertHeadersUnchanged($response);
  }

}
