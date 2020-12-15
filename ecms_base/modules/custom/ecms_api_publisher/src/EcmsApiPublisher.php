<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_publisher;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Url;
use Drupal\ecms_api\EcmsApiBase;
use Drupal\ecms_api\EcmsApiHelper;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\user\UserInterface;
use GuzzleHttp\ClientInterface;

/**
 * Handles sending nodes to syndicated sites.
 *
 * @package Drupal\ecms_api_publisher
 */
class EcmsApiPublisher extends EcmsApiBase {

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * The entity storage for the user entity.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $userStorage;

  /**
   * The account_switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  private $accountSwitcher;

  /**
   * EcmsApiPublisher constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The http_client service.
   * @param \Drupal\jsonapi_extras\EntityToJsonApi $entityToJsonApi
   *   The jsonapi_extras.entity.to_jsonapi service.
   * @param \Drupal\ecms_api\EcmsApiHelper $ecmsApiHelper
   *   The ecms_api_helper service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\Session\AccountSwitcherInterface $accountSwitcher
   *   The account_switcher service.
   */
  public function __construct(
    ClientInterface $httpClient,
    EntityToJsonApi $entityToJsonApi,
    EcmsApiHelper $ecmsApiHelper,
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entityTypeManager,
    AccountSwitcherInterface $accountSwitcher
  ) {
    parent::__construct($httpClient, $entityToJsonApi, $ecmsApiHelper);

    $this->configFactory = $configFactory;
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->accountSwitcher = $accountSwitcher;
  }

  /**
   * Queue a node for syndication.
   *
   * @param \Drupal\Core\Url $recipientUrl
   *   The URL of the API receiving the node.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The node to submit.
   *
   * @return bool
   *   True if successfully saved.
   */
  public function syndicateEntity(Url $recipientUrl, EntityInterface $entity): bool {

    $clientId = $this->getClientId();
    $clientSecret = $this->getClientSecret();
    $clientScope = $this->getClientScope();

    // Get the access token to create this node.
    $accessToken = $this->getAccessToken($recipientUrl, $clientId, $clientSecret, $clientScope);

    // Guard against a null access token.
    if (empty($accessToken)) {
      return FALSE;
    }

    // Get the ecms_api_publisher user.
    $publisherAccount = $this->getEcmsApiPublisherUser();

    // Guard against a missing publisher account.
    if (empty($publisherAccount)) {
      return FALSE;
    }

    $this->accountSwitcher->switchTo($publisherAccount);

    // Submit the entity to the API.
    $result = $this->submitEntity($accessToken, $recipientUrl, $entity);

    $this->accountSwitcher->switchBack();

    return $result;
  }

  /**
   * Get the ecms_api_publisher user account.
   *
   * @return \Drupal\user\UserInterface|null
   *   The ecms_api_publisher user or null if it does not exist.
   */
  private function getEcmsApiPublisherUser(): ?UserInterface {

    $publishers = $this->userStorage->loadByProperties(['name' => 'ecms_api_publisher']);

    // Guard against an empty array.
    if (empty($publishers)) {
      return NULL;
    }

    /** @var \Drupal\user\UserInterface $publisher */
    $publisher = array_shift($publishers);

    return $publisher;
  }

  /**
   * Get the client id from configuration.
   *
   * @return string
   *   The client id for the recipient site.
   */
  private function getClientId(): string {
    return $this->configFactory->get('ecms_api_publisher.settings')->get('recipient_client_id');
  }

  /**
   * Get the client secret from configuration.
   *
   * @return string
   *   The client secret for the recipient site.
   */
  private function getClientSecret(): string {
    return $this->configFactory->get('ecms_api_publisher.settings')->get('recipient_client_secret');
  }

  /**
   * Get the client scope from configuration.
   *
   * @return string
   *   The client scope for the recipient site.
   */
  private function getClientScope(): string {
    return $this->configFactory->get('ecms_api_publisher.settings')->get('recipient_client_scope');
  }

}
