<?php

declare(strict_types=1);

namespace Drupal\ecms_layout\Plugin\Layout;

/**
 * Provides a plugin class for one column layouts.
 */
final class OneColumnLayout extends LayoutBase {

  /**
   * {@inheritdoc}
   */
  protected function getColumnWidths(): array {
    return [
      '25' => $this->t('25%'),
      '50' => $this->t('50%'),
      '75' => $this->t('75%'),
      '100' => $this->t('100%'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultColumnWidth(): string {
    return '100';
  }

}
