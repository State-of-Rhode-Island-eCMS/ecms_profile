<?php

namespace Drupal\ecms_health_file_migration\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * A drush command file.
 *
 * @package Drupal\ecms_health_file_migration\Commands
 */
class CustomDrushCommands extends DrushCommands {

  /**
   * Drush command test media.
   *
   * @command ecms_health_file_migration:make-media-entity
   * @aliases make-media
   * @usage ecms_health_file_migration:make-media-entity
   */
  public function makeMediaEntity() {

    $entity = \Drupal::entityTypeManager()
      ->getStorage('media')
      ->create(['name' => 'foo', 'bundle' => 'resource']);
    $entity->save();

  }

  /**
   * Drush command test bad media type.
   *
   * @command ecms_health_file_migration:make-fake-media-entity
   * @aliases make-fake-media
   * @usage ecms_health_file_migration:make-fake-media-entity
   */
  public function makeFakeMediaEntity() {

    $entity = \Drupal::entityTypeManager()
      ->getStorage('media')
      ->create(['name' => 'foo', 'bundle' => 'not_real_media_bundle']);
    $entity->save();

  }

}
