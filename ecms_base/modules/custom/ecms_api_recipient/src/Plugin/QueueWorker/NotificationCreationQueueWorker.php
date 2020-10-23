<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_recipient\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\PostponeItemException;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Url;
use Drupal\ecms_api_recipient\EcmsApiCreateNotifications;
use Drupal\ecms_api_recipient\EcmsApiRecipientRetrieveNotifications;
use Drupal\ecms_api_recipient\JsonapiHelper;
use Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Create notifications queued from the hub..
 *
 * @QueueWorker(
 *   id = "ecms_api_recipient_notification_creation_queue",
 *   title = @Translation("Ecms Api Notification Creation Queue"),
 *   cron = {"time" = 5}
 * )
 */
class NotificationCreationQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  const HUB_DEFAULT_LANGCODE = 'en';

  /**
   * The ecms_api_recipient.retrieve_notifications service.
   *
   * @var \Drupal\ecms_api_recipient\EcmsApiRecipientRetrieveNotifications
   */
  private $ecmsNotificationRetriever;

  /**
   * The http_client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  private $httpClient;

  /**
   * The ecms_api_recipient.settings configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * The ecms_api_recipient.create_notifications service.
   *
   * @var \Drupal\ecms_api_recipient\EcmsApiCreateNotifications
   */
  private $ecmsApiCreateNotification;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ecms_api_recipient.retrieve_notifications'),
      $container->get('http_client'),
      $container->get('config.factory'),
      $container->get('ecms_api_recipient.create_notifications')
    );
  }

  /**
   * NotificationCreationQueueWorker constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ecms_api_recipient\EcmsApiCreateNotifications $ecmsApiCreateNotification
   *   The ecms_api_recipient.retrieve_notifications service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    EcmsApiRecipientRetrieveNotifications $ecmsApiRetriever,
    ClientInterface $httpClient,
    ConfigFactoryInterface $configFactory,
    EcmsApiCreateNotifications $ecmsApiCreateNotification
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->ecmsNotificationRetriever = $ecmsApiRetriever;
    $this->httpClient = $httpClient;
    $this->config = $configFactory->get('ecms_api_recipient.settings');
    $this->ecmsApiCreateNotification = $ecmsApiCreateNotification;
  }

  /**
   * Process the queue item.
   *
   * @param array $notification
   *   An array with uuid and langcode.
   *   This will be the notification uuid from the hub site. Cannot typehint
   *   the parameter due to the QueueWorkerInterface.
   */
  public function processItem($notification) {
    if (!is_array($notification)) {
      return;
    }

    // Ensure we have uuid and langcode values.
    if (empty($notification['uuid']) || empty($notification['langcode'])) {
      return;
    }

    // Get the endpoint.
    $endpoint = $this->getNotificationEndpoint($notification['uuid'], $notification['langcode']);

    if (empty($endpoint)) {
      throw new RequeueException();
    }

    $content = $this->callApiEndpoint($endpoint);

    if (empty($content)) {
      return;
    }

    $json = $this->decodeJson($content);

    if (empty($json)) {
      return;
    }

    $uuidExists = $this->ecmsApiCreateNotification->checkEntityUuidExists($json->data->id);

    // Check if this object is in the default language.
    if (!$json->data->attributes->default_langcode) {
      // This entity is not the default language. Check if the existing entity
      // already exists before proceeding.

      if (!$uuidExists) {
        // Postpone processing until the the uuid exists.
        throw new PostponeItemException('The base translation does not exist yet.');
      }

      // Create the translation for the existing node.
      if (!$this->createTranslation($json)) {
        throw new RequeueException();
      }

      // Continue since the translation was saved correctly.
      return;

    }
    else if ($uuidExists) {
      // This is in the default language and the uuid already exists, continue.
      return;
    }

    // Create the notification. Requeue the node if it does not save correctly.
    if (!$this->createNotification($json)) {
      throw new RequeueException();
    }
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
    $body = $request->getBody()->getContents();

    return $body;

  }

  /**
   * Get the hub url from configuration.
   *
   * @return \Drupal\Core\Url|null
   *   A url object or null if an error occurred.
   */
  private function getNotificationEndpoint(string $uuid, string $langcode): ?Url {
    $hubUri = $this->config->get('api_main_hub');

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

    $apiParts = [
      'EcmsApi',
      'node',
      'notification',
      "{$uuid}",
    ];

    if ($langcode !== self::HUB_DEFAULT_LANGCODE) {
      array_unshift($apiParts, $langcode);
    }

    // Append the hub string to the parts array;
    array_unshift($apiParts, $hub->toString());

    $apiPath = implode('/', $apiParts);

    try {
      $url = Url::fromUri($apiPath);
    }
    catch (\InvalidArgumentException $e) {
      return NULL;
    }

    // Return the hub with the query parameters.
    return $url;
  }

  /**
   * Create the entity.
   *
   * @param string $content
   *   The json string returned from the hub.
   *
   * @return bool
   *   True if the node was successfully created.
   */
  private function createNotification(object $json): bool {
    // Post this entity to the current site.
    $status = $this->ecmsApiCreateNotification->createNotificationFromJson($json->data);

    return $status;
  }

  private function createTranslation(object $json): bool {
    $status = $this->ecmsApiCreateNotification->createNotificationTranslationFromJson($json->data);

    return $status;
  }

  private function decodeJson(string $content): ?object {
    // Decode the json string.
    $json = json_decode($content);

    // Guard against a json error.
    if (empty($json)) {
      return NULL;
    }

    // Ensure we have an object.
    if (!is_object($json)) {
      return NULL;
    }

    // Ensure we have the data property.
    if (!property_exists($json, 'data')) {
      return NULL;
    }

    return $json;
  }

}
