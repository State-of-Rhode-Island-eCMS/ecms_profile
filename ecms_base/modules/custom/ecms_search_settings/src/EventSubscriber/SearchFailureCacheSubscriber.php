<?php

declare(strict_types=1);

namespace Drupal\ecms_search_settings\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\Site\Settings;
use Drupal\search_api\Event\QueryPreExecuteEvent;
use Drupal\search_api\Query\Query;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Makes a failed search cacheable for a short time.
 *
 * When Solr (SearchStax) errors or times out, search_api_solr sets max-age 0
 * on the query. That bubbles up to the page, so the response is sent as
 * "no-cache, private". Drupal, Varnish and Cloudflare then skip caching it,
 * and every request runs PHP and waits for the Solr timeout again. Bot traffic
 * on faceted search pages makes a short SearchStax problem much worse.
 *
 * This subscriber notes every Search API query in the request. After a view
 * has executed (see ecms_search_settings_views_post_execute()), each aborted
 * query whose max-age is 0 gets the short failure TTL instead. That happens
 * before the view's cacheability bubbles into the render array, so only the
 * failed search is affected: anything else on the page with max-age 0 still
 * makes the page uncacheable. This is only done for requests the page cache
 * may serve (anonymous GET/HEAD); logged-in requests are left unchanged.
 *
 * On the response, if the page ended up with a positive max-age, an Expires
 * header is added. The internal page cache uses it, so the error page expires
 * on its own instead of being kept until a cache tag is invalidated.
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
   * Whether a failed query was given the failure TTL in this request.
   */
  protected bool $searchFailed = FALSE;

  /**
   * The main request being handled.
   *
   * The request stack's main request is not always this one: when a container
   * handles several requests (e.g. kernel tests), the first request pushed
   * stays at the bottom of the stack.
   */
  protected ?Request $request = NULL;

  /**
   * Constructs a SearchFailureCacheSubscriber.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\PageCache\RequestPolicyInterface $pageCacheRequestPolicy
   *   The page cache request policy.
   */
  public function __construct(
    protected TimeInterface $time,
    protected RequestStack $requestStack,
    protected RequestPolicyInterface $pageCacheRequestPolicy,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // Same value as SearchApiEvents::QUERY_PRE_EXECUTE. Reading that
      // constant autoloads the class, which fails when the container is built
      // before search_api is enabled (e.g. during the profile install).
      'search_api.query_pre_execute' => 'onQueryPreExecute',
      // Start each main request with a clean state, in case the container
      // (and so this service) is reused across requests.
      KernelEvents::REQUEST => ['onRequest', 1000],
      // Run before FinishResponseSubscriber (priority 0), which replaces the
      // Expires header of responses that are not cacheable.
      KernelEvents::RESPONSE => ['onResponse', 10],
    ];
  }

  /**
   * Notes the main request and forgets the queries of a previous one.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function onRequest(RequestEvent $event): void {
    if ($event->isMainRequest()) {
      $this->request = $event->getRequest();
      $this->queries = [];
      $this->searchFailed = FALSE;
    }
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
   * Gives each aborted query with max-age 0 the failure TTL instead.
   *
   * Must run before the query's cacheability is read into a render array;
   * for Views that is hook_views_post_execute().
   */
  public function applyFailureMaxAge(): void {
    $max_age = $this->getMaxAge();
    if ($max_age <= 0 || !$this->queries || !$this->isRequestCacheable()) {
      return;
    }

    foreach ($this->queries as $query) {
      // Only the core Query class is cacheable; other implementations are
      // left alone.
      if ($query instanceof Query
        && $query->wasAborted()
        && $query->getCacheMaxAge() === 0) {
        $this->setQueryMaxAge($query, $max_age);
        $this->searchFailed = TRUE;
      }
    }
  }

  /**
   * Adds an Expires header to a cacheable page whose search failed.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event.
   */
  public function onResponse(ResponseEvent $event): void {
    if (!$event->isMainRequest() || !$this->searchFailed) {
      return;
    }

    $response = $event->getResponse();
    if (!$response instanceof CacheableResponseInterface || !$response->isSuccessful()) {
      return;
    }

    // Something else on the page may still be uncacheable (max-age 0); then
    // the page stays uncacheable. A permanent max-age cannot happen here,
    // since the failed query limits it to the TTL.
    $page_max_age = $response->getCacheableMetadata()->getCacheMaxAge();
    if ($page_max_age <= 0) {
      return;
    }

    $response->setExpires(new \DateTime('@' . ($this->time->getRequestTime() + $page_max_age)));
    $response->headers->set('X-Ecms-Search-Failed', '1');
  }

  /**
   * Gets the configured failure TTL.
   *
   * @return int
   *   The TTL in seconds; 0 or less means turned off.
   */
  protected function getMaxAge(): int {
    return (int) Settings::get('ecms_search_failure_max_age', self::DEFAULT_MAX_AGE);
  }

  /**
   * Checks whether the current request may be served from the page cache.
   *
   * @return bool
   *   TRUE for GET/HEAD requests allowed by the page cache request policy
   *   (anonymous, no session).
   */
  protected function isRequestCacheable(): bool {
    $request = $this->request ?? $this->requestStack->getMainRequest();
    return $request
      && $request->isMethodCacheable()
      && $this->pageCacheRequestPolicy->check($request) === RequestPolicyInterface::ALLOW;
  }

  /**
   * Replaces the max-age of a Search API query.
   *
   * Query only allows lowering its max-age (mergeCacheMaxAge()), so the
   * property is set directly.
   *
   * @param \Drupal\search_api\Query\Query $query
   *   The query.
   * @param int $max_age
   *   The new max-age.
   */
  protected function setQueryMaxAge(Query $query, int $max_age): void {
    \Closure::bind(function () use ($max_age): void {
      $this->cacheMaxAge = $max_age;
    }, $query, Query::class)();
  }

}
