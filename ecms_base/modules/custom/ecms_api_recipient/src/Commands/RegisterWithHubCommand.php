<?php

declare(strict_types=1);

namespace Drupal\ecms_api_recipient\Commands;

use Drupal\ecms_api_recipient\EcmsApiRecipientRegister;
use Drupal\ecms_api_recipient\EcmsApiRecipientRetrieveNotifications;
use Drush\Commands\DrushCommands;

/**
 * Drush command for interacting with the ecms hub.
 */
final class RegisterWithHubCommand extends DrushCommands {

  /**
   * Constructs an RegisterWithHubCommand object.
   */
  public function __construct(
    private readonly EcmsApiRecipientRegister $apiRecipientRegister,
    private readonly EcmsApiRecipientRetrieveNotifications $apiRecipientRetrieveNotifications,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct();
  }

  /**
   * Register the site with the main hub.
   *
   * @command ecms:register-hub
   * @aliases ecms:rh
   *
   * @usage ecms:register-hub
   *   Register the site with the main hub.
   */
  public function registerWithHub(): void {
    // Register with the hub site.
    $this->apiRecipientRegister->registerSite();
  }

  /**
   * Grant the ecms_api_recipient role access to syndicate
   * emergency notifications.
   *
   * @command ecms:grant-emergency-notification-access
   * @aliases ecms:gena
   *
   * @usage ecms:grant-emergency-notification-access
   *   Grant the ecms_api_recipient role access to syndicate
   *   emergency notifications.
   */
  public function grantEmergencyNotificationAccess(): void {
    $role = $this->entityTypeManager->getStorage('user_role')->load('ecms_api_recipient');
    if (!$role) {
      $this->logger()->error('The ecms_api_recipient role does not exist.');
      return;
    }

    $role->grantPermission("create emergency_notificaiton content");
    $role->grantPermission("edit own emergency_notificaiton content");
    $role->grantPermission("translate emergency_notificaiton node");
    $role->save();
  }

  /**
   * Retrieve content from the main hub.
   *
   * @command ecms:get-hub-content
   * @aliases ecms:ghc
   *
   * @usage ecms:get-hub-content
   *   Get all content that should be on the site from the hub.
   */
  public function retieveHubContent(): void {
    // Retrieve content from the hub.
    $this->apiRecipientRetrieveNotifications->retrieveNotificationsFromHub();
  }

}
