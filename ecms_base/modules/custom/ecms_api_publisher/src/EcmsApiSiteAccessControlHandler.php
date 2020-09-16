<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_publisher;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

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
    switch ($operation) {
      case 'view':
        // Check if the entity belongs to the user.
        if ($account->id() === $entity->getOwnerId()) {
          return AccessResult::allowedIfHasPermission($account, 'view own published ecms api site entities');
        }

        return AccessResult::allowedIfHasPermission($account, 'view published ecms api site entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit ecms api site entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete ecms api site entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'add ecms api site entities');
  }

}
