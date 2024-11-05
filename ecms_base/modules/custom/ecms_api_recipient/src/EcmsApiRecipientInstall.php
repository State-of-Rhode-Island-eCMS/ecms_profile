<?php

declare(strict_types=1);

namespace Drupal\ecms_api_recipient;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Installation tasks for the ecms_api_recipient module.
 *
 * @package Drupal\ecms_api_recipient
 */
class EcmsApiRecipientInstall {

  use StringTranslationTrait;

  /**
   * The role id to assign to the oauth user account.
   */
  const RECIPIENT_ROLE = 'ecms_api_recipient';

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The configuration settings for the ecms_api_recipient module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $ecmsRecipientApiConfig;

  /**
   * EcmsApiRecipientInstall constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->ecmsRecipientApiConfig = $configFactory->get('ecms_api_recipient.settings');
  }

  /**
   * Install the Ecms API Recipient module.
   */
  public function installEcmsApiRecipient(): void {
    // Create a new API User.
    $account = $this->createApiRecipientUser();

    // Guard against a NULL user.
    if (empty($account)) {
      return;
    }

    // Create a new OAuth Consumer.
    $this->createSimpleOauthConsumer($account);
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
  private function createApiRecipientUser(): ?EntityInterface {
    $storage = $this->entityTypeManager->getStorage('user');

    $account = $storage->create([
      'name' => 'ecms_api_recipient',
      'mail' => $this->getMailAddress(),
      'roles' => [self::RECIPIENT_ROLE],
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
      'client_id' => $this->getClientId(),
      'roles' => [self::RECIPIENT_ROLE],
      'label' => $this->t('eCMS Recipient'),
      'description' => $this->t('An oAuth client to receive content from an eCMS publishing site.'),
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
    return $this->ecmsRecipientApiConfig->get('oauth_client_id');
  }

  /**
   * Get the client secret from configuration.
   *
   * @return string
   *   The client secret to use for the application.
   */
  private function getClientSecret(): string {
    return $this->ecmsRecipientApiConfig->get('oauth_client_secret');
  }

  /**
   * Get the email address from configuration.
   *
   * @return string
   *   The email address stored in configuration.
   */
  private function getMailAddress(): string {
    return $this->ecmsRecipientApiConfig->get('api_recipient_mail');
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

}
