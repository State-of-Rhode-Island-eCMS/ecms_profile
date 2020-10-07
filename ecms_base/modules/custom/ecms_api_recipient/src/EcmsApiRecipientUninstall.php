<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_recipient;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Uninstall tasks for the ecms_api_recipient module.
 *
 * @package Drupal\ecms_api_recipient
 */
class EcmsApiRecipientUninstall {

  /**
   * The role id that is installed with this module.
   */
  const ROLE = 'ecms_api_recipient';

  /**
   * The user name that is installed with this module.
   */
  const USER_NAME = 'ecms_api_recipient';

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * EcmsApiRecipientUninstall constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Removes module created configurations.
   */
  public function uninstall(): void {
    $this->removeRole();
    $this->removeUser();
  }

  /**
   * Remove the module created role.
   *
   * @return bool
   *   True if the role was deleted successfully.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function removeRole(): bool {
    $storage = $this->entityTypeManager->getStorage('user_role');

    $entity = $storage->load(self::ROLE);

    if (empty($entity)) {
      return FALSE;
    }

    try {
      $entity->delete();
      return TRUE;
    }
    catch (EntityStorageException $e) {
      return FALSE;
    }
  }

  /**
   * Remove the module created user.
   *
   * @return bool
   *   True if the user was deleted successfully.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function removeUser(): bool {
    $storage = $this->entityTypeManager->getStorage('user');

    $entities = $storage->loadByProperties(['name' => self::USER_NAME]);

    if (empty($entities)) {
      return FALSE;
    }

    $entity = array_shift($entities);

    try {
      $entity->delete();
      return TRUE;
    }
    catch (EntityStorageException $e) {
      return FALSE;
    }
  }

}
