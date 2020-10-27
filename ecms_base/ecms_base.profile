<?php

/**
 * @file
 * Provides update hooks for previously installed sites.
 */

declare(strict_types = 1);

use Drupal\Core\Config\FileStorage;

/**
 * Update Basic HTML configuration.
 */
function ecms_base_update_9001(array &$sandbox): void {
  $path = \Drupal::service('extension.list.profile')->getPath('ecms_base');

  /** @var \Drupal\Core\Config\FileStorage $install_source */
  $install_source = new FileStorage($path . "/config/install/");

  /** @var \Drupal\Core\Config\StorageInterface $active_storage */
  $active_storage = \Drupal::service('config.storage');
  $active_storage->write('editor.editor.basic_html', $install_source->read('editor.editor.basic_html'));
  $active_storage->write('filter.format.basic_html', $install_source->read('filter.format.basic_html'));

}

/**
 * Updates to run for the 0.1.3 tag.
 */
function ecms_base_update_9013(array &$sandbox): void {
  $path = \Drupal::service('extension.list.profile')->getPath('ecms_base');

  /** @var \Drupal\Core\Config\FileStorage $install_source */
  $install_source = new FileStorage($path . "/config/install/");

  /** @var \Drupal\Core\Config\StorageInterface $active_storage */
  $active_storage = \Drupal::service('config.storage');

  // Install the external links module.
  \Drupal::service('module_installer')->install(['extlink']);

  // Change the extlink settings to use what is in ecms_base profile.
  $active_storage->write('extlink.settings', $install_source->read('extlink.settings'));

  // Make sure the scheduler module is installed.
  \Drupal::service('module_installer')->install(['scheduler']);

  // Call the workflow service to update configuration.
  \Drupal::service('ecms_workflow.bundle_create')
    ->assignWorkflowToActiveTypes();

}
