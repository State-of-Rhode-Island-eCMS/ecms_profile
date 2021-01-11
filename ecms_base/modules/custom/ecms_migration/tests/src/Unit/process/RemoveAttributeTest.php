<?php

namespace Drupal\Tests\ecms_migration\Unit\process;

use Drupal\ecms_migration\Plugin\migrate\process\RemoveAttribute;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the remove_attribute process plugin.
 *
 * @package Drupal\Tests\ecms_migration\Unit\process
 *
 * @group ecms_migration
 * @coversDefaultClass \Drupal\ecms_migration\Plugin\migrate\process\RemoveAttribute
 */
class RemoveAttributeTest extends MigrateProcessTestCase {

  /**
   * Test for a removal of the style attribute.
   */
  public function testRemoveStyle(): void {
    $value = '<div style="width:500px;height:500px;border:1px solid red;">Content to leave</div>';
    $configuration['attribute'] = 'style';
    $plugin = new RemoveAttribute($configuration, 'remove_attribute', []);
    $actual = $plugin->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertStringNotContainsString('style="width:500px;height:500px;border:1px solid red;"', $actual);

  }

  /**
   * Test for not removing a class attribute.
   */
  public function testIgnoreAttribute(): void {
    $value = '<div class="leaveAlone">Content to leave</div>';
    $configuration['attribute'] = 'id';
    $plugin = new RemoveAttribute($configuration, 'remove_attribute', []);
    $actual = $plugin->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertStringContainsString('class="leaveAlone"', $actual);

  }

}
