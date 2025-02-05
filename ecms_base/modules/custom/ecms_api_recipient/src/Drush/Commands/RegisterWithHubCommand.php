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
   * Register the site with the main hub.
   *
   * @command ecms:register-hub
   * @aliases ecms:rh
   *
   * @usage ons:register-hub
   *   Register the site with the main hub.
   */
  public function registerWithHub(): void {
    // Register with the hub site.
    $this->apiRecipientRegister->registerWithHub();
  }

  /**
   * Retrieve content from the main hub.
   *
   * @command ecms:get-hub-content
   * @aliases ecms:ghc
   *
   * @usage ons:get-hub-content
   *   Get all content that should be on the site from the hub.
   */
  public function retieveHubContent(): void {
    // Register with the hub site.
    $this->apiRecipientRetrieveNotifications->retrieveHubContent();
  }

}
