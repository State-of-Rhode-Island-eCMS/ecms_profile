<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_recipient;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Retrieve currently published notifications from the hub site.
 *
 * This service will be called on installation and will manage pulling
 * notifications from the hub site and adding them to a queue for generation
 * on the current site.
 *
 * @package Drupal\ecms_api_recipient
 */
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
  private $notificationQueue;

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
   * The language_manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private $languageManager;

  /**
   * EcmsApiRecipientRetrieveNotifications constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue service.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The http_client service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, QueueFactory $queueFactory, ClientInterface $httpClient, LanguageManagerInterface $languageManager) {
    $this->config = $configFactory->get('ecms_api_recipient.settings');
    $this->notificationQueue = $queueFactory->get(self::NOTIFICATION_CREATION_QUEUE);
    $this->pagerQueue = $queueFactory->get(self::NOTIFICATION_PAGER_QUEUE);
    $this->httpClient = $httpClient;
    $this->languageManager = $languageManager;
  }

  /**
   * Retrieve notifications from the hub querying for all languages.
   */
  public function retrieveNotificationsFromHub(): void {
    // Get all installed languages.
    $languages = $this->languageManager->getLanguages();

    foreach ($languages as $code => $language) {
      $url = $this->getHubUrl($language);
      $this->retrieveNotifications($url);
    }
  }

  /**
   * Query the url for published notifications.
   *
   * @param \Drupal\Core\Url $url
   *   The url to query.
   */
  public function retrieveNotifications(Url $url): void {
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

      if (!property_exists($notification, 'attributes') && !property_exists($notification->attributes, 'langcode')) {
        continue;
      }

      // Pass the uuid and language to the queue for creation.
      $queueNotification = [
        'uuid' => $notification->id,
        'langcode' => $notification->attributes->langcode,
      ];

      $this->queueNotification($queueNotification);
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
   * @param array $notification
   *   An array with uuid and langcode.
   */
  private function queueNotification(array $notification): void {
    // Push a new item onto the queue.
    $this->notificationQueue->createItem($notification);
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
    catch (\InvalidArgumentException $e) {
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
  private function getHubUrl(LanguageInterface $language): ?Url {
    $hubUri = $this->config->get('api_main_hub');
    $languageId = $language->getId();

    $endpointPath = [
      'EcmsApi',
      'node',
      'notification',
    ];

    // If the language is not the default, append it to the endpoint array.
    if (!$language->isDefault()) {
      array_unshift($endpointPath, $languageId);
    }

    // Guard against an empty string.
    if (empty($hubUri)) {
      return NULL;
    }

    // Make sure the hub in config is a valid uri.
    try {
      $hub = Url::fromUri($hubUri);
    }
    catch (\InvalidArgumentException $e) {
      return NULL;
    }

    // Append the hub url string to the endpoint array.
    array_unshift($endpointPath, $hub->toString());

    // Add query parameters to the url.
    $filter = [
      "page[limit]" => 10,
      "filter[global][condition][path]" => "field_notification_global",
      "filter[global][condition][operator]" => "=",
      "filter[global][condition][value]" => TRUE,
    ];

    $queryParams = http_build_query($filter);

    // Create an endpoint path.
    $path = implode('/', $endpointPath);

    try {
      $url = Url::fromUri("{$path}?{$queryParams}");
    }
    catch (\InvalidArgumentException $e) {
      return NULL;
    }

    // Return the hub with the query parameters.
    return $url;
  }

}
