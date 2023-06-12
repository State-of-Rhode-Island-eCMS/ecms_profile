<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_publisher;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the eCMS API Site entity.
 *
 * @see \Drupal\ecms_api_publisher\Entity\EcmsApiSite.
 */
class EcmsApiSiteAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {

    // Default the permissions to no opinion.
    $accessResult = AccessResult::neutral();

    switch ($operation) {
      case 'view':
        // Check if the entity belongs to the user.
        if ($account->id() === $entity->getOwnerId()) {
          $accessResult = AccessResult::allowedIfHasPermission($account, 'view own published ecms api site entities');
        }
        else {
          $accessResult = AccessResult::allowedIfHasPermission($account, 'view any published ecms api site entities');
        }
        break;

      case 'update':
        $accessResult = AccessResult::allowedIfHasPermission($account, 'edit ecms api site entities');
        break;

      case 'delete':
        $accessResult = AccessResult::allowedIfHasPermission($account, 'delete ecms api site entities');
        break;
    }

    return $accessResult;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'add ecms api site entities');
  }

}
