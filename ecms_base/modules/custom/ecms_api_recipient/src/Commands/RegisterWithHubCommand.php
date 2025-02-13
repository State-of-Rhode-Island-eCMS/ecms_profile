<?php

declare(strict_types=1);

namespace Drupal\ecms_api_recipient\Commands;

use Drupal\ecms_api_recipient\EcmsApiRecipientRegister;
use Drupal\ecms_api_recipient\EcmsApiRecipientRetrieveNotifications;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageCacheInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;

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
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly ModuleExtensionList $moduleExtensionList,
    private readonly StorageCacheInterface $storageCache,
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
   * Grant the recipient role access to syndicate emergency notifications.
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
      $this->installEcmsApiRecipientRole();
      // Reload the role
      $role = $this->entityTypeManager
        ->getStorage('user_role')
        ->load('ecms_api_recipient');

      if (!$role) {
        $this->logger()
          ->error('The ecms_api_recipient role could not be installed.');
        return;
      }

      $member = reset($this->entityTypeManager->getStorage('user')->loadByProperties([
        'name' => 'ecms_api_recipient',
      ]));

      $member->addRole('ecms_api_recipient');
      $member->save();
    }

    $role->grantPermission("create emergency_notification content");
    $role->grantPermission("edit own emergency_notification content");
    $role->grantPermission("translate emergency_notification node");
    $role->save();
  }

  /**
   * Install the ecms_api_recipient role from the install directory.
   */
  private function installEcmsApiRecipientRole(): void {
    $path = $this->moduleExtensionList->getPath('ecms_api_recipient');
    $install_source = new FileStorage($path . "/config/install/");
    $this->storageCache->write('user.role.ecms_api_recipient', $install_source->read('user.role.ecms_api_recipient'));
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
