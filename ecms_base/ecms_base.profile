<?php

/**
 * @file
 * Provides update hooks for previously installed sites.
 */

declare(strict_types = 1);

use Drupal\Core\Config\FileStorage;

/**
 * Update Basic HTML configuration to match the install config.
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
