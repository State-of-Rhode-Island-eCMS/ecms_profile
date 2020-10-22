<?php

/**
 * @file
 * ecms_base.post_update.php
 */

declare(strict_types = 1);

use Drupal\Core\Entity\EntityStorageException;

/**
 * Change the admin role title to "Drupal_Admin".
 */
function ecms_base_post_update_change_admin_role_title(&$sandbox) {

  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager */
  $entityManager = \Drupal::service('entity_type.manager');

  $storage = $entityManager->getStorage('user_role');

  /** @var \Drupal\user\RoleInterface $adminRole */
  $adminRole = $storage->load('drupal_admin');

  if (empty($adminRole)) {
    return;
  }

  $adminRole->set('label', 'Drupal_Admin');

  try {
    $adminRole->save();
  }
  catch (EntityStorageException $e) {
    // Trap any errors.
  }
}
