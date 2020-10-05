<?php

declare(strict_types = 1);

namespace Drupal\ecms_layout\Plugin\Layout;

/**
 * Provides a plugin class for one column layouts.
 */
final class FourColumnLayout extends LayoutBase {

  /**
   * {@inheritdoc}
   */
  protected function getColumnWidths(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultColumnWidth(): string {
    return '';
  }

}
