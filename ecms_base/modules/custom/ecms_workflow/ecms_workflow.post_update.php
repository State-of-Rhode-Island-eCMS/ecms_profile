<?php

/**
 * @file
 * ecms_workflow.post_update.php
 */

declare(strict_types=1);

use Drupal\ecms_workflow\EcmsWorkflowBundleCreate;

/**
 * Revoke permissions from non-drupal_admin roles.
 */
function ecms_workflow_post_update_revoke_emergency_notification_permissions(): void {
  // @see RIGA-588.
  // Revoke permissions from non-drupal_admin roles.
  \Drupal::service('ecms_workflow.bundle_create')
    ->revokeRolePermissions(
      EcmsWorkflowBundleCreate::CONTENT_PUBLISHER_ROLE,
      'emergency_notification'
    );

  \Drupal::service('ecms_workflow.bundle_create')
    ->revokeRolePermissions(
      EcmsWorkflowBundleCreate::CONTENT_AUTHOR_ROLE,
      'emergency_notification'
    );
}

/**
 * Revoke permissions from non-drupal_admin roles.
 */
function ecms_workflow_post_update_unpublish_all_emergency_notifications(array &$sandbox): void {
  // @see RIGA-588.
  $storage = \Drupal::entityTypeManager()
    ->getStorage('node');

  if (!isset($sandbox['nodes'])) {
    $sandbox['nodes'] = $storage
      ->getQuery()
      ->condition('type', 'emergency_notification')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    $sandbox['progress'] = 0;
    $sandbox['max'] = count($sandbox['nodes']);
  }

  while ($nid = array_shift($sandbox['nodes'])) {
    $node = $storage->load($nid);
    $node->setPublished(FALSE);
    $node->moderation_state = 'archived';
    try {
      $node->save();
    }
    catch (\Exception $e) {
      // Trap any errors.
    }

    $sandbox['progress']++;
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : $sandbox['progress'] / $sandbox['max'];
}
