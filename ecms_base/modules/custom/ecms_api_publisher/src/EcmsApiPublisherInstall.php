<?php

declare(strict_types=1);

namespace Drupal\ecms_api_publisher;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Install tasks for the ecms_api_publisher module.
 *
 * @package Drupal\ecms_api_publisher
 */
class EcmsApiPublisherInstall {

  use StringTranslationTrait;

  /**
   * The role id to assign to the oauth user account.
   */
  const PUBLISHER_ROLE = 'ecms_api_publisher';

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The configuration settings for the ecms_api_publisher module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $ecmsApiPublisherConfig;

  /**
   * EcmsApiPublisherInstall constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->ecmsApiPublisherConfig = $configFactory->get('ecms_api_publisher.settings');
  }

  /**
   * Install the Ecms API Publisher module.
   */
  public function installEcmsApiPublisher(): void {
    // Create a new API User.
    $account = $this->createApiPublisherUser();

    // Guard against a NULL user.
    if (empty($account)) {
      return;
    }

    // Create a new OAuth Consumer.
    $this->createSimpleOauthConsumer($account);

    // Set permissions for the ecms_api_publisher role.
    $this->setRolePermissions();
  }

  /**
   * Create a new user for use with the API.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Return the user account or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function createApiPublisherUser(): ?EntityInterface {
    $storage = $this->entityTypeManager->getStorage('user');

    $account = $storage->create([
      'name' => 'ecms_api_publisher',
      'mail' => $this->getMailAddress(),
      'roles' => [self::PUBLISHER_ROLE],
      'pass' => $this->generatePassword(),
      'status' => 1,
    ]);

    try {
      $account->save();
    }
    catch (EntityStorageException $e) {
      return NULL;
    }

    return $account;
  }

  /**
   * Create the oAuth consumer entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $user
   *   The user account to associate with the oAuth consumer.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The consumer entity or null.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function createSimpleOauthConsumer(EntityInterface $user): ?EntityInterface {
    $storage = $this->entityTypeManager->getStorage('consumer');

    $values = [
      'user_id' => $user->id(),
      'client_id' => $this->t('eCMS Publisher'),
      'roles' => [self::PUBLISHER_ROLE],
      'label' => $this->t('eCMS Publisher'),
      'description' => $this->t('An oAuth client to receive endpoints from an eCMS recipient site.'),
      'third_party' => FALSE,
      'uuid' => $this->getClientId(),
      'secret' => $this->getClientSecret(),
    ];

    $consumer = $storage->create($values);

    try {
      $consumer->save();
    }
    catch (EntityStorageException $e) {
      return NULL;
    }

    return $consumer;
  }

  /**
   * Get the client id from configuration.
   *
   * @return string
   *   The client id to use for the application.
   */
  private function getClientId(): string {
    return $this->ecmsApiPublisherConfig->get('oauth_client_id');
  }

  /**
   * Get the client secret from configuration.
   *
   * @return string
   *   The client secret to use for the application.
   */
  private function getClientSecret(): string {
    return $this->ecmsApiPublisherConfig->get('oauth_client_secret');
  }

  /**
   * Get the email address from configuration.
   *
   * @return string
   *   The email address stored in configuration.
   */
  private function getMailAddress(): string {
    return $this->ecmsApiPublisherConfig->get('api_publisher_mail');
  }

  /**
   * Generate a random password for the user.
   *
   * @return string
   *   A random string generated with the Crypt utility method.
   */
  protected function generatePassword(): string {
    return Crypt::randomBytesBase64();
  }

  /**
   * Grant permission to the publisher role to create endpoints entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function setRolePermissions(): void {
    $storage = $this->entityTypeManager->getStorage('user_role');

    $role = $storage->load(self::PUBLISHER_ROLE);

    // Guard against an empty role.
    if (empty($role)) {
      return;
    }

    // Allow the publisher role the ability to create api site entities.
    $role->grantPermission("add ecms api site entities");

    try {
      $role->save();
    }
    catch (EntityStorageException $e) {
      return;
    }
  }

}
