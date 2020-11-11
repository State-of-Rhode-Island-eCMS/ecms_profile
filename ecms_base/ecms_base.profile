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

  // Make sure the pathauto and redirect modules are installed.
  \Drupal::service('module_installer')->install(['pathauto']);
  \Drupal::service('module_installer')->install(['redirect']);

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

  // Install the SVG Image module.
  \Drupal::service('module_installer')->install(['svg_image']);

}

/**
 * Updates to run for the 0.1.4 tag.
 */
function ecms_base_update_9014(array &$sandbox): void {
  $path = \Drupal::service('extension.list.profile')->getPath('ecms_base');

  /** @var \Drupal\Core\Config\FileStorage $install_source */
  $install_source = new FileStorage($path . "/config/install/");

  /** @var \Drupal\Core\Config\StorageInterface $active_storage */
  $active_storage = \Drupal::service('config.storage');

  $modules_to_install = [
    'pathauto',
    'redirect',
    'key',
    'encrypt',
    'real_aes',
    'webform_encrypt',
  ];

  // Make sure necessary modules are installed.
  \Drupal::service('module_installer')->install($modules_to_install);

  $active_storage->write('pathauto.settings', $install_source->read('pathauto.settings'));
  $active_storage->write('redirect.settings', $install_source->read('redirect.settings'));

  // Ensure encryption config is updated.
  $active_storage->write('encrypt.settings', $install_source->read('encrypt.settings'));
  $active_storage->write('key.key.encryption_key', $install_source->read('key.key.encryption_key'));
  $active_storage->write('encrypt.profile.webform_encryption', $install_source->read('encrypt.profile.webform_encryption'));
}

/**
 * Updates to run for the 0.1.7 tag.
 */
function ecms_base_update_9017(array &$sandbox): void {
  $path = \Drupal::service('extension.list.profile')->getPath('ecms_base');

  /** @var \Drupal\Core\Config\FileStorage $install_source */
  $install_source = new FileStorage($path . "/config/install/");

  /** @var \Drupal\Core\Config\StorageInterface $active_storage */
  $active_storage = \Drupal::service('config.storage');

  // Ensure scheduler permissions are updated.
  $active_storage->write('user.role.content_publisher', $install_source->read('user.role.content_publisher'));
  $active_storage->write('user.role.site_admin', $install_source->read('user.role.site_admin'));

  $modules_to_install = [
    'twig_tweak',
    'ecms_distribution',
  ];

  // Enable eCMS distribution module.
  \Drupal::service('module_installer')->install($modules_to_install);
}

/**
 * Updates to run for the 0.1.9 tag.
 */
function ecms_base_update_9019(array &$sandbox): void {
  $path = \Drupal::service('extension.list.profile')->getPath('ecms_base');

  /** @var \Drupal\Core\Config\FileStorage $install_source */
  $install_source = new FileStorage($path . "/config/install/");

  /** @var \Drupal\Core\Config\StorageInterface $active_storage */
  $active_storage = \Drupal::service('config.storage');

  // Add the social navigation menu.
  $active_storage->write('system.menu.social-navigation', $install_source->read('system.menu.social-navigation'));

  // Add scheduler settings.
  $active_storage->write('scheduled_transitions.settings', $install_source->read('scheduled_transitions.settings'));

  // Install new modules.
  $modules_to_install = [
    'paragraphs_type_permissions',
  ];

  // Make sure necessary modules are installed.
  \Drupal::service('module_installer')->install($modules_to_install);
}
