<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_recipient;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class EcmsApiRecipientRetrieveNotifications {

  /**
   * The queue id for notification retrieval.
   */
  const NOTIFICATION_CREATION_QUEUE = 'ecms_api_recipient_notification_creation_queue';

  /**
   * The queue id for the pager queue.
   */
  const NOTIFICATION_PAGER_QUEUE = 'ecms_api_recipient_notification_pager_queue';

  /**
   * The ecms_api_recipient.settings configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * The queue interface for the notification creation queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  private $queue;

  /**
   * The queue interface for the paging queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  private $pagerQueue;

  /**
   * The http_client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  private $httpClient;

  /**
   * EcmsApiRecipientRetrieveNotifications constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   * @param \GuzzleHttp\ClientInterface $httpClient
   */
  public function __construct(ConfigFactoryInterface $configFactory, QueueFactory $queueFactory, ClientInterface $httpClient) {
    $this->config = $configFactory->get('ecms_api_recipient.settings');
    $this->queue = $queueFactory->get(self::NOTIFICATION_CREATION_QUEUE);
    $this->pagerQueue = $queueFactory->get(self::NOTIFICATION_PAGER_QUEUE);
    $this->httpClient = $httpClient;
  }

  /**
   * Query the api for published notifications.
   *
   * @param \Drupal\Core\Url|null $url
   *   The url to query or null to query the main hub.
   */
  public function retrieveNotifications(?Url $url): void {
    // If the url is empty, get the hub.
    if (empty($url)) {
      $url = $this->getHubUrl();

      if (empty($url)) {
        return;
      }
    }

    $contents = $this->callApiEndpoint($url);

    if (empty($contents)) {
      return;
    }

    $this->processNotifications($contents);

  }

  /**
   * Call the endpoint to get notifications.
   *
   * @param \Drupal\Core\Url $endpoint
   *   The endpoint url to query.
   *
   * @return string|null
   *   The contents of the request or null if an error occurs.
   */
  private function callApiEndpoint(Url $endpoint): ?string {
    // Callback to the hub and get all notification UUIDS.
    // Callback as the anonymous user.
    try {
      $request = $this->httpClient->request('GET', $endpoint->toString());
    }
    catch (GuzzleException $exception) {
      return NULL;
    }

    // Guard against an invalid http code.
    if ($request->getStatusCode() !== 200) {
      return NULL;
    }

    // Return the contents of the request.
    return $request->getBody()->getContents();

  }

  /**
   * Process the json from the API call.
   *
   * @param string $contents
   *   The json string returned by callApiEndpoint.
   */
  private function processNotifications(string $contents): void {
    // Decode the json string.
    $json = json_decode($contents);

    // Guard against a json error.
    if (empty($json)) {
      return;
    }

    // Ensure we have an object.
    if (!is_object($json)) {
      return;
    }

    // Ensure we have the data property.
    if (!property_exists($json, 'data')) {
      return;
    }

    foreach ($json->data as $notification) {
      if (!property_exists($notification, 'id')) {
        continue;
      }

      // Append the uuid onto the queue for creation.
      $this->queueNotification($notification->id);
    }

    // Check if we have the next page link.
    if (
      property_exists($json, 'links') &&
      property_exists($json->links, 'next') &&
      property_exists($json->links->next, 'href')
    ) {
      // Queue the next page for creation.
      $this->queueNextPage($json->links->next->href);
    }
  }

  /**
   * Queue the notification for creation.
   *
   * @param array $uuids
   *   The UUIDS to queue from the hub.
   */
  private function queueNotification(string $uuid): void {
    // Push a new item onto the queue.
    $this->queue->createItem($uuid);
  }

  /**
   * Queue the next page of notifications for retrieval.
   *
   * @param string $nextPage
   *   The href of the next page as provided by Json API.
   */
  private function queueNextPage(string $nextPage): void {
    try {
      $url = Url::fromUri($nextPage);
    }
    catch(\InvalidArgumentException $e) {
      return;
    }

    // Queue the URL for the next page.
    $this->pagerQueue->createItem($url);
  }

  /**
   * Get the hub url from configuration.
   *
   * @return \Drupal\Core\Url|null
   *   A url object or null if an error occurred.
   */
  private function getHubUrl(): ?Url {
    $hubUri = $this->config->get('api_main_hub');

    // Guard against an empty string.
    if (empty($hubUri)) {
      return NULL;
    }

    // Make sure the hub in config is a valid uri.
    try {
      $hub = Url::fromUri($hubUri);
    }
    catch(\InvalidArgumentException $e) {
      return NULL;
    }

    // Add query parameters to the url.
    $filter = [
      "page[limit]" => 10,
    ];

    $queryParams = http_build_query($filter);

    try {
      $url = Url::fromUri("{$hub->toString()}/EcmsApi/node/notification?{$queryParams}");
    }
    catch(\InvalidArgumentException $e) {
      return NULL;
    }

    // Return the hub with the query parameters.
    return $url;
  }

}
