<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_recipient;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\ecms_api\EcmsApiBase;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\RequestStack;

class EcmsApiCreateNotifications extends EcmsApiBase {

  const API_SCOPE = 'ecms_api_recipient';

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * The request_stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The ecms_api_recipient.jsonapi_helper service.
   *
   * @var \Drupal\ecms_api_recipient\JsonapiHelper
   */
  private $jsonApiHelper;

  /**
   * EcmsApiCreateNotifications constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The http_client service.
   * @param \Drupal\jsonapi_extras\EntityToJsonApi $entityToJsonApi
   *   The jsonapi_extras.entity.to_jsonapi service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request_stack service.
   */
  public function __construct(
    ClientInterface $httpClient,
    EntityToJsonApi $entityToJsonApi,
    ConfigFactoryInterface $configFactory,
    RequestStack $requestStack,
    EntityTypeManagerInterface $entityTypeManager,
    JsonapiHelper $jsonApiHelper
  ) {
    parent::__construct($httpClient, $entityToJsonApi);

    $this->configFactory = $configFactory;
    $this->requestStack = $requestStack;
    $this->entityTypeManager = $entityTypeManager;
    $this->jsonApiHelper = $jsonApiHelper;
  }

  /**
   * Create the notification node.
   *
   * @param object $jsonNodeObject
   *   The json object returned from json api.
   *
   * @return bool
   *   True if successful or false if an error occurred.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function createNotificationFromJson(object $jsonNodeObject): bool {
    // Get the correct author id (ecms_api_recipient).
    $recipientUser = $this->getEcmsApiRecipientUser();

    // If the recipient user doesn't exist, stop processing.
    if (empty($recipientUser)) {
      return FALSE;
    }

    // Convert the json to an array.
    $convertedJson = $this->jsonApiHelper->convertJsonDataToArray($jsonNodeObject);

    $this->alterEntityAttributes($convertedJson['attributes'], NULL);

    // Add the uuid back into the attributes.
    $convertedJson['attributes']['uuid'] = $jsonNodeObject->id;
    $convertedJson['attributes']['status'] = TRUE;

    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->jsonApiHelper->extractEntity($convertedJson);

    // Guard against an empty node object.
    if (!$node instanceof NodeInterface) {
      return FALSE;
    }

    // Set the author of the node.
    $node->set('uid', $recipientUser->id());

    try {
      $node->save();
    }
    catch (EntityStorageException $e) {
      // @todo: Log this error message.
      return FALSE;
    }

    // Return TRUE if the node saved without error.
    return TRUE;

  }

  public function createNotificationTranslationFromJson(object $jsonNodeObject): bool {
    // Get the correct author id (ecms_api_recipient).
    $recipientUser = $this->getEcmsApiRecipientUser();

    // If the recipient user doesn't exist, stop processing.
    if (empty($recipientUser)) {
      return FALSE;
    }

    // Convert the json to an array.
    $convertedJson = $this->jsonApiHelper->convertJsonDataToArray($jsonNodeObject);

    $this->alterEntityAttributes($convertedJson['attributes'], NULL);
    // Add the uuid back into the attributes.
    $convertedJson['attributes']['uuid'] = $jsonNodeObject->id;
    $convertedJson['attributes']['status'] = TRUE;

    $originalNodes = $this->entityTypeManager->getStorage('node')->loadByProperties(['uuid' => $jsonNodeObject->id]);
    /** @var NodeInterface $originalNode */
    $originalNode = array_shift($originalNodes);

    $originalNode->addTranslation($convertedJson['attributes']['langcode'], $convertedJson['attributes']);
    try {
      $originalNode->save();
    }
    catch (EntityStorageException $e) {
      // @todo: Log this error message.
      return FALSE;
    }

    return TRUE;

  }

  /**
   * Check if a node's uuid already exists.
   *
   * @param string $uuid
   *   The uuid to query nodes with.
   *
   * @return bool
   *   True if the uuid already exists.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function checkEntityUuidExists(string $uuid): bool {
    $storage = $this->entityTypeManager->getStorage('node');

    $entities = $storage->loadByProperties(['uuid' => $uuid]);

    // If entities are found, return TRUE.
    if (!empty($entities)) {
      return TRUE;
    }

    // Default to false.
    return FALSE;
  }

  private function getEcmsApiRecipientUser(): ?UserInterface {
    $storage = $this->entityTypeManager->getStorage('user');

    $users = $storage->loadByProperties(['name' => self::API_SCOPE]);

    // Guard against no entity.
    if (empty($users)) {
      return NULL;
    }

    /** @var \Drupal\user\UserInterface $user */
    $user = array_shift($users);

    return $user;
  }

  /**
   * The the oauth client id from configuration.
   *
   * @return string
   *   The configuration value for oauth_client_id.
   */
  private function getApiClientId(): string {
    return $this->configFactory->get('ecms_api_recipient.settings')->get('oauth_client_id');
  }

  /**
   * Get the api client secret from configuration.
   *
   * @return string
   *   The configuration value for the api_main_hub_client_secret.
   */
  private function getApiClientSecret(): string {
    return $this->configFactory->get('ecms_api_recipient.settings')->get('oauth_client_secret');
  }

  /**
   * Get the current site host as a URL object.
   *
   * @return \Drupal\Core\Url|null
   *   The URL of the current site or null if any errors were thrown.
   */
  private function getSiteUrl(): ?Url {
    $httpHost = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();

    // Trap any arguments in case the provided URI is invalid.
    try {
      $url = Url::fromUri($httpHost);
    }
    catch (\InvalidArgumentException $e) {
      return NULL;
    }

    return $url;
  }


}
