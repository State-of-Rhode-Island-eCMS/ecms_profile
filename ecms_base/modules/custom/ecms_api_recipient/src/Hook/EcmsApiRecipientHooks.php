<?php

declare(strict_types=1);

namespace Drupal\ecms_api_recipient\Hook;

use Drupal\consumers\Entity\ConsumerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\user\UserInterface;

/**
 * Hook implementations for ecms_api_recipient.
 */
class EcmsApiRecipientHooks {

  /**
   * Constructs a new EcmsApiRecipientHooks object.
   */
  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly PasswordGeneratorInterface $passwordGenerator,
    protected readonly PasswordInterface $password,
  ) {
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for user entities.
   */
  #[Hook('user_presave')]
  public function userPresave(UserInterface $account): void {
    // Add your user presave logic here.
    if (
      $account->uuid() === '8a4e7c2d-9f1b-4a3e-b5c6-1d2e3f4a5b6c' &&
      $account->isNew()
    ) {
      // UUID matches the ecms_api_recipient recipe.
      // Set the mail to the overridden configuration value.
      $account->set('mail', $this->configFactory
        ->get('ecms_api_recipient.settings')
        ->get('api_recipient_mail')
      );

      // Set a random password.
      $account->setPassword($this->password->hash($this->passwordGenerator->generate(32)));
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for consumer entities.
   */
  #[Hook('consumer_presave')]
  public function consumerPresave(ConsumerInterface $consumer): void {
    if (
      $consumer->uuid() === '9b5e8d3e-0f2c-5b4f-c6d7-2e3f4a5b6c7d' &&
      $consumer->isNew()
    ) {
      // UUID matches the ecms_api_recipient recipe.
      // Set the client id and secret. to the overridden configuration values.
      $consumer->set('client_id', $this->configFactory
        ->get('ecms_api_recipient.settings')
        ->get('oauth_client_id')
      );

      $consumer->set('secret', $this->configFactory
        ->get('ecms_api_recipient.settings')
        ->get('oauth_client_secret')
      );
    }
  }

}
