<?php

declare(strict_types=1);

namespace Drupal\ecms_api_publisher\Hook;

use Drupal\consumers\Entity\ConsumerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\user\UserInterface;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\Core\Password\PasswordInterface;
/**
 * Hook implementations for ecms_api_publisher.
 */
class EcmsApiPublisherHooks {

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
    if ($account->uuid() === '7c3e2b1a-8d5f-4e6c-9a7b-1f2e3d4c5b6a') {
      // UUID matches the ecms_api_publisher recipe.
      // Set the mail to the overridden configuration value.
      $account->set('mail', $this->configFactory
        ->get('ecms_api_publisher.settings')
        ->get('api_publisher_mail')
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
    if ($consumer->uuid() === 'dffc6f7c-8d6b-4a78-b361-c40ab0caf520') {
      // UUID matches the ecms_api_publisher recipe.
      // Set the client id and secret. to the overridden configuration values.
      $consumer->set('client_id', $this->configFactory
        ->get('ecms_api_publisher.settings')
        ->get('oauth_client_id')
      );

      $consumer->set('secret', $this->configFactory
        ->get('ecms_api_publisher.settings')
        ->get('oauth_client_secret')
      );
    }
  }

}
