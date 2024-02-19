<?php

declare(strict_types=1);

namespace Drupal\ecms_layout\Plugin\Layout;

/**
 * Provides a plugin class for one column layouts.
 */
final class ThreeColumnLayout extends LayoutBase {

  /**
   * {@inheritdoc}
   */
  protected function getColumnWidths(): array {
    return [
      '33-repeat' => $this->t('33% Columns'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultColumnWidth(): string {
    return '33-repeat';
  }

}
