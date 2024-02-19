<?php

declare(strict_types=1);

namespace Drupal\ecms_api_recipient;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecms_api\EcmsApiBase;
use Drupal\ecms_api\EcmsApiHelper;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use GuzzleHttp\ClientInterface;

/**
 * Service to create notification nodes from json api objects.
 *
 * @package Drupal\ecms_api_recipient
 */
class EcmsApiCreateNotifications extends EcmsApiBase {

  /**
   * The scope used to connect to the api.
   */
  const API_SCOPE = 'ecms_api_recipient';

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The ecms_api_recipient.jsonapi_helper service.
   *
   * @var \Drupal\ecms_api_recipient\JsonApiHelper
   */
  private $jsonApiHelper;

  /**
   * EcmsApiCreateNotifications constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The http_client service.
   * @param \Drupal\jsonapi_extras\EntityToJsonApi $entityToJsonApi
   *   The jsonapi_extras.entity.to_jsonapi service.
   * @param \Drupal\ecms_api\EcmsApiHelper $ecmsApiHelper
   *   The ecms_api_helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\ecms_api_recipient\JsonApiHelper $jsonApiHelper
   *   The ecms_api_recipient.jsonapi_helper service.
   */
  public function __construct(
    ClientInterface $httpClient,
    EntityToJsonApi $entityToJsonApi,
    EcmsApiHelper $ecmsApiHelper,
    EntityTypeManagerInterface $entityTypeManager,
    JsonApiHelper $jsonApiHelper
  ) {
    parent::__construct($httpClient, $entityToJsonApi, $ecmsApiHelper);

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
      return FALSE;
    }

    // Return TRUE if the node saved without error.
    return TRUE;

  }

  /**
   * Create a translation for an existing node.
   *
   * @param object $jsonNodeObject
   *   The json data object.
   *
   * @return bool
   *   True if the translation saved successfully.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
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

    /** @var \Drupal\node\NodeInterface|null $originalNode */
    $originalNode = $this->loadExistingNode($jsonNodeObject->id);

    // Guard against an empty node.
    if (empty($originalNode)) {
      return FALSE;
    }

    // Guard against this translation already existing.
    if ($originalNode->hasTranslation($convertedJson['attributes']['langcode'])) {
      // We already have a translation, continue.
      return TRUE;
    }

    // Add the translation to the node.
    $originalNode->addTranslation($convertedJson['attributes']['langcode'], $convertedJson['attributes']);

    try {
      $originalNode->save();
    }
    catch (EntityStorageException $e) {
      // Trap any errors and requeue.
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

  /**
   * Load an existing node by uuid.
   *
   * @param string $uuid
   *   The uuid of the node to query.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node if found or null.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function loadExistingNode(string $uuid): ?NodeInterface {
    $storage = $this->entityTypeManager->getStorage('node');

    $entities = $storage->loadByProperties(['uuid' => $uuid]);

    // If entities are not found, return NULL.
    if (empty($entities)) {
      return NULL;
    }

    /** @var \Drupal\node\NodeInterface $node */
    $node = array_shift($entities);

    return $node;
  }

  /**
   * Load the ecms_api_recipient user.
   *
   * @return \Drupal\user\UserInterface|null
   *   The ecms_api_recipient user account or null if not found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
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

}
