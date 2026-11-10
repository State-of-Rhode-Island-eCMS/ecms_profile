<?php

declare(strict_types=1);

namespace Drupal\ecms_search_settings\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Site\Settings;
use Drupal\search_api\Event\QueryPreExecuteEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Drupal\search_api\Query\QueryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Makes a page whose search failed cacheable for a short time.
 *
 * When Solr (SearchStax) errors or times out, search_api_solr sets max-age 0
 * on the query. That bubbles up to the page, so the response is sent as
 * "no-cache, private". Drupal, Varnish and Cloudflare then skip caching it,
 * and every request runs PHP and waits for the Solr timeout again. Bot traffic
 * on faceted search pages makes a short SearchStax problem much worse.
 *
 * This subscriber notes every Search API query in the request. If one of them
 * was aborted (the Views integration aborts on a backend exception), the page
 * response gets a short max-age and Expires header instead of 0. The internal
 * page cache uses the Expires header, so it drops the error page when the TTL
 * ends instead of keeping it until a cache tag is invalidated.
 *
 * TTL: $settings['ecms_search_failure_max_age'] (seconds, default 60).
 * Set it to 0 to turn this off.
 */
class SearchFailureCacheSubscriber implements EventSubscriberInterface {

  /**
   * Default lifetime of a failed-search response, in seconds.
   */
  const DEFAULT_MAX_AGE = 60;

  /**
   * Search API queries executed during this request.
   *
   * @var \Drupal\search_api\Query\QueryInterface[]
   */
  protected array $queries = [];

  /**
   * Constructs a SearchFailureCacheSubscriber.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    protected TimeInterface $time,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SearchApiEvents::QUERY_PRE_EXECUTE => 'onQueryPreExecute',
      // Run before FinishResponseSubscriber (priority 0), which turns a
      // max-age 0 response into "no-cache, private".
      KernelEvents::RESPONSE => ['onResponse', 10],
    ];
  }

  /**
   * Notes a Search API query so its outcome can be checked later.
   *
   * @param \Drupal\search_api\Event\QueryPreExecuteEvent $event
   *   The query pre-execute event.
   */
  public function onQueryPreExecute(QueryPreExecuteEvent $event): void {
    $this->queries[] = $event->getQuery();
  }

  /**
   * Gives a failed-search page a short max-age instead of 0.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event.
   */
  public function onResponse(ResponseEvent $event): void {
    if (!$event->isMainRequest() || !$this->queries) {
      return;
    }

    $max_age = (int) Settings::get('ecms_search_failure_max_age', self::DEFAULT_MAX_AGE);
    $response = $event->getResponse();
    if ($max_age <= 0
      || !$event->getRequest()->isMethodCacheable()
      || !$response instanceof CacheableResponseInterface
      || !$response->isSuccessful()
      || $response->getCacheableMetadata()->getCacheMaxAge() !== 0
      || !$this->searchFailed()) {
      return;
    }

    // Logged-in and other uncacheable requests are still made private by
    // FinishResponseSubscriber, which also replaces this Expires header.
    $response->getCacheableMetadata()->setCacheMaxAge($max_age);
    $response->setExpires(new \DateTime('@' . ($this->time->getRequestTime() + $max_age)));
    $response->headers->set('X-Ecms-Search-Failed', '1');
  }

  /**
   * Checks whether any Search API query in this request was aborted.
   *
   * @return bool
   *   TRUE if a search failed.
   */
  protected function searchFailed(): bool {
    foreach ($this->queries as $query) {
      if ($query instanceof QueryInterface && $query->wasAborted()) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
