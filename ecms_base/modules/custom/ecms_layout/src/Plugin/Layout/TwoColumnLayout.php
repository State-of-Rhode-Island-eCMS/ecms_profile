<?php

declare(strict_types = 1);

namespace Drupal\ecms_layout\Plugin\Layout;

/**
 * Provides a plugin class for two column layouts.
 */
final class TwoColumnLayout extends LayoutBase {

  /**
   * {@inheritdoc}
   */
  protected function getColumnWidths(): array {
    return [
      '25-75' => $this->t('25% / 75%'),
      '33-67' => $this->t('33% / 67%'),
      '50-50' => $this->t('50% / 50%'),
      '67-33' => $this->t('67% / 33%'),
      '75-25' => $this->t('75% / 25%'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultColumnWidth(): string {
    return '50-50';
  }

}
