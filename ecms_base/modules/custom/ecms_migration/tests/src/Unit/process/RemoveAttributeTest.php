<?php

declare(strict_types=1);

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
   * Test for a removal of all style attributes.
   */
  public function testRemoveMultipleStyles(): void {
    $value = '<div style="width:500px;">Content to leave</div><p style="color:red;">Content to leave</p><span style="color:blue; font-size: 10px;">Content to leave</span>';
    $configuration['attribute'] = 'style';
    $plugin = new RemoveAttribute($configuration, 'remove_attribute', []);
    $actual = $plugin->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertStringNotContainsString('style="width:500px;"', $actual);
    $this->assertStringNotContainsString('style="color:red;"', $actual);
    $this->assertStringNotContainsString('style="color:blue; font-size: 10px;"', $actual);

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
