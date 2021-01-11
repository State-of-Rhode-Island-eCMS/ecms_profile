<?php

declare(strict_types = 1);

namespace Drupal\ecms_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Uses a regex to remove the given attribute.
 *
 * @MigrateProcessPlugin(
 *   id = "remove_attribute"
 * )
 *
 * Remove 'style' attributes from HTML tags:
 * @code
 * field_text:
 *   plugin: remove_attribute
 *   source: text
 *   attribute: 'style'
 * @endcode
 */
class RemoveAttribute extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!isset($this->configuration['attribute'])) {
      throw new MigrateException('"search" must be configured.');
    }

    $attributeToRemove = $this->configuration['attribute'];
    return preg_replace('/(<[^>]+) ' . $attributeToRemove . '=".*?"/i', '$1', $value);
  }

}
