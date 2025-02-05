<?php

declare(strict_types=1);

namespace Drupal\ecms_api_recipient\Drush\Commands;

use Drupal\ecms_api_recipient\EcmsApiRecipientRegister;
use Drupal\ecms_api_recipient\EcmsApiRecipientRetrieveNotifications;
use Drush\Attributes\Command;
use Drush\Attributes\Usage;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

final class RegisterWithHubCommand extends DrushCommands {

  use AutowireTrait;

  /**
   * Constructs an AptifyProductSync object.
   */
  public function __construct(
    private readonly EcmsApiRecipientRegister $apiRecipientRegister,
    private readonly EcmsApiRecipientRetrieveNotifications $apiRecipientRetrieveNotifications,
  ) {
    parent::__construct();
  }

  /**
   * Sync Aptify products to Drupal entities.
   */
  #[Command(name: 'ecms:register-hub', aliases: ['ecms:rh'])]
  #[Usage(name: 'ecms:register-hub', description: 'Register the site with the main hub.')]
  public function registerWithHub(): void {
    // Register with the hub site.
    $this->apiRecipientRegister->registerWithHub();
  }

  #[Command(name: 'ecms:get-hub-content', aliases: ['ecms:ghc'])]
  #[Usage(name: 'ecms:get-hub-content', description: 'Retrieve the content from the hub.')]
  public function retieveHubContent(): void {
    // Register with the hub site.
    $this->apiRecipientRetrieveNotifications->retrieveHubContent();
  }

}
