<?php

declare(strict_types=1);

namespace Drupal\ecms_layout\Plugin\Layout;

/**
 * Provides a plugin class for four column layouts.
 */
final class FourColumnLayout extends LayoutBase {

  /**
   * {@inheritdoc}
   */
  protected function getColumnWidths(): array {
    return [
      '25-repeat' => $this->t('25% Columns'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultColumnWidth(): string {
    return '25-repeat';
  }

}
