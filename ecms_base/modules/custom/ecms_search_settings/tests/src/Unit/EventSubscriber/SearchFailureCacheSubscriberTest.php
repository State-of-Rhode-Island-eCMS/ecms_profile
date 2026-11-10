<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_search_settings\Unit\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Site\Settings;
use Drupal\ecms_search_settings\EventSubscriber\SearchFailureCacheSubscriber;
use Drupal\search_api\Event\QueryPreExecuteEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Drupal\search_api\Query\QueryInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    $this->subscriber = new SearchFailureCacheSubscriber($time);
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
   * Notify the subscriber that a Search API query is about to run.
   *
   * @param bool $aborted
   *   Whether the query reports that it was aborted.
   */
  private function addQuery(bool $aborted): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('wasAborted')->willReturn($aborted);
    $this->subscriber->onQueryPreExecute(new QueryPreExecuteEvent($query));
  }

  /**
   * Build a ResponseEvent.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response.
   * @param string $method
   *   The request method.
   * @param bool $mainRequest
   *   Whether to simulate a main request (TRUE) or sub-request (FALSE).
   *
   * @return \Symfony\Component\HttpKernel\Event\ResponseEvent
   *   The configured event.
   */
  private function buildEvent(Response $response, string $method = 'GET', bool $mainRequest = TRUE): ResponseEvent {
    $kernel = $this->createMock(HttpKernelInterface::class);
    $requestType = $mainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST;

    return new ResponseEvent($kernel, Request::create('/search?keys=test', $method), $requestType, $response);
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
   * Assert that the response was left as it was.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response.
   * @param int $maxAge
   *   The max-age the response started with.
   */
  private function assertUnchanged(Response $response, int $maxAge = 0): void {
    if ($response instanceof CacheableResponse) {
      $this->assertSame($maxAge, $response->getCacheableMetadata()->getCacheMaxAge());
    }
    $this->assertNull($response->getExpires());
    $this->assertFalse($response->headers->has('X-Ecms-Search-Failed'));
  }

  /**
   * Test that getSubscribedEvents returns the expected event mapping.
   */
  public function testGetSubscribedEvents(): void {
    $events = SearchFailureCacheSubscriber::getSubscribedEvents();
    $this->assertEquals('onQueryPreExecute', $events[SearchApiEvents::QUERY_PRE_EXECUTE]);
    // Must run before FinishResponseSubscriber (priority 0).
    $this->assertEquals(['onResponse', 10], $events[KernelEvents::RESPONSE]);
  }

  /**
   * Test that a failed search gets a short max-age and Expires header.
   *
   * @param array $settings
   *   The site settings.
   * @param int $expectedMaxAge
   *   The expected max-age.
   */
  #[DataProvider('dataProviderForFailedSearch')]
  public function testFailedSearchIsCacheable(array $settings, int $expectedMaxAge): void {
    $this->setSettings($settings);
    $this->addQuery(FALSE);
    $this->addQuery(TRUE);

    $response = $this->buildResponse();
    $this->subscriber->onResponse($this->buildEvent($response));

    $this->assertSame($expectedMaxAge, $response->getCacheableMetadata()->getCacheMaxAge());
    $this->assertSame(self::REQUEST_TIME + $expectedMaxAge, $response->getExpires()->getTimestamp());
    $this->assertSame('1', $response->headers->get('X-Ecms-Search-Failed'));
  }

  /**
   * Data provider for testFailedSearchIsCacheable().
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
   * Test that a HEAD request is treated like GET.
   */
  public function testHeadRequest(): void {
    $this->addQuery(TRUE);

    $response = $this->buildResponse();
    $this->subscriber->onResponse($this->buildEvent($response, 'HEAD'));

    $this->assertSame(SearchFailureCacheSubscriber::DEFAULT_MAX_AGE, $response->getCacheableMetadata()->getCacheMaxAge());
  }

  /**
   * Test the cases where the response must not be changed.
   *
   * @param array $settings
   *   The site settings.
   * @param bool[] $queries
   *   The aborted state of each query run in the request.
   * @param string $method
   *   The request method.
   * @param bool $mainRequest
   *   Whether this is a main request.
   * @param int $status
   *   The response status code.
   * @param int $maxAge
   *   The response max-age.
   */
  #[DataProvider('dataProviderForUnchanged')]
  public function testResponseUnchanged(
    array $settings,
    array $queries,
    string $method,
    bool $mainRequest,
    int $status,
    int $maxAge,
  ): void {
    $this->setSettings($settings);
    foreach ($queries as $aborted) {
      $this->addQuery($aborted);
    }

    $response = $this->buildResponse($status, $maxAge);
    $this->subscriber->onResponse($this->buildEvent($response, $method, $mainRequest));

    $this->assertUnchanged($response, $maxAge);
  }

  /**
   * Data provider for testResponseUnchanged().
   *
   * @return array[]
   *   Keyed test cases.
   */
  public static function dataProviderForUnchanged(): array {
    return [
      'no search on the page' => [
        [], [], 'GET', TRUE, 200, 0,
      ],
      'search succeeded' => [
        [], [FALSE, FALSE], 'GET', TRUE, 200, 0,
      ],
      'sub-request is ignored' => [
        [], [TRUE], 'GET', FALSE, 200, 0,
      ],
      'POST is not cacheable' => [
        [], [TRUE], 'POST', TRUE, 200, 0,
      ],
      'server error is not made cacheable' => [
        [], [TRUE], 'GET', TRUE, 500, 0,
      ],
      'redirect is not made cacheable' => [
        [], [TRUE], 'GET', TRUE, 302, 0,
      ],
      'page already has a max-age' => [
        [], [TRUE], 'GET', TRUE, 200, 3600,
      ],
      'page is permanently cacheable' => [
        [], [TRUE], 'GET', TRUE, 200, -1,
      ],
      'turned off in settings' => [
        ['ecms_search_failure_max_age' => 0], [TRUE], 'GET', TRUE, 200, 0,
      ],
    ];
  }

  /**
   * Test that a response without cacheability metadata is ignored.
   */
  public function testNonCacheableResponse(): void {
    $this->addQuery(TRUE);

    $response = new Response();
    $this->subscriber->onResponse($this->buildEvent($response));

    $this->assertUnchanged($response);
  }

}
