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

/**
 * Update permissions for the current taxonomy and node entities.
 */
function ecms_base_post_update_013_update_site_admin_role_permissions(&$sandbox): void {
  /** @var \Drupal\ecms_workflow\EcmsWorkflowBundleCreate $workflowBundleCreate */
  $workflowBundleCreate = \Drupal::service('ecms_workflow.bundle_create');

  /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo */
  $bundleInfo = \Drupal::service('entity_type.bundle.info');

  $nodes = $bundleInfo->getBundleInfo('node');

  // Guard again an empty array.
  if (!empty($nodes)) {
    // Add the correct workflow to the node types.
    foreach (array_keys($nodes) as $type) {
      $workflowBundleCreate->addContentTypeToWorkflow($type);
    }
  }

  $taxonomies = $bundleInfo->getBundleInfo('taxonomy_term');

  // Guard against empty taxonomies.
  if (!empty($taxonomies)) {
    // Add the correct permissions for the taxonomy types.
    foreach (array_keys($taxonomies) as $taxonomy) {
      $workflowBundleCreate->addTaxonomyTypePermissions($taxonomy);
    }
  }
}

/**
 * Add new permissions to the site admin role.
 */
function ecms_base_post_update_013_add_new_permissions_to_site_admin(&$sandbox): void {
  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
  $entityTypeManager = \Drupal::service('entity_type.manager');
  $storage = $entityTypeManager->getStorage('user_role');

  /** @var \Drupal\user\RoleInterface $siteAdminRole */
  $siteAdminRole = $storage->load('site_admin');

  // If we have an entity, update the permissions.
  if (!empty($siteAdminRole)) {
    // Add new permissions to the site_admin role.
    $new_permissions = [
      'access administration pages',
      'access taxonomy overview',
      'access user profiles',
      'administer themes',
      'administer users',
      'configure any layout',
      'create content translations',
      'create webform',
      'delete any webform',
      'delete any webform content',
      'delete content translations',
      'delete own webform',
      'delete own webform content',
      'edit any webform submission',
      'edit own webform',
      'moderated content bulk archive',
      'moderated content bulk publish',
      'moderated content bulk unpublish',
      'translate any entity',
      'update content translations',
      'use text format embed',
      'use text format full_html',
      'use text format paragraph_text',
      'view any webform submission',
      'view own webform submission',
      'view unpublished paragraphs',
    ];

    foreach ($new_permissions as $permission) {
      $siteAdminRole->grantPermission($permission);
    }

    try {
      $siteAdminRole->save();
    }
    catch (EntityStorageException $e) {
      return;
    }
  }
}
