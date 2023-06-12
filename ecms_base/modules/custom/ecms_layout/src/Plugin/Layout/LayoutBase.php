<?php

declare(strict_types = 1);

namespace Drupal\ecms_layout\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\ecms_layout\EcmsLayout;

/**
 * Provides a layout base for custom layouts.
 */
abstract class LayoutBase extends LayoutDefault {

  /**
   * {@inheritdoc}
   */
  public function build(array $regions): array {
    $build = parent::build($regions);

    $columnWidth = $this->configuration['column_width'];
    if ($columnWidth) {
      $build['#attributes']['class'][] = 'qh-layout-section--col-size-' . $columnWidth;
    }

    $class = $this->configuration['class'];
    if ($class) {
      $build['#attributes']['class'] = array_merge(
        explode(' ', $this->configuration['class']),
        $build['#attributes']['class']
      );
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'label' => '',
      'background_color' => EcmsLayout::ROW_BACKGROUND_COLOR_NONE,
      'class' => NULL,
      'column_width' => $this->getDefaultColumnWidth(),
      'full_width' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {

    $backgroundColorOptions = $this->getBackgroundColorOptions();
    $columnWidths = $this->getColumnWidths();

    $form['background'] = [
      '#type' => 'details',
      '#title' => $this->t('Background'),
      '#open' => $this->hasBackgroundSettings(),
      '#weight' => 20,
    ];

    $form['background']['background_color'] = [
      '#type' => 'radios',
      '#title' => $this->t('Background Color'),
      '#options' => $backgroundColorOptions,
      '#default_value' => $this->configuration['background_color'],
    ];

    $form['layout'] = [
      '#type' => 'details',
      '#title' => $this->t('Layout'),
      '#open' => TRUE,
      '#weight' => 30,
    ];

    $form['layout']['full_width'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Section Width'),
      'checkbox' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Full-width section'),
        '#description' => $this->t('Enabling this option will remove the content boundary. Thus, allowing this section to stretch edge to edge of the browser window.'),
        '#default_value' => $this->configuration['full_width'],
      ],
    ];

    $form['layout']['column_width'] = [
      '#type' => 'radios',
      '#title' => $this->t('Column Width'),
      '#options' => $columnWidths,
      '#default_value' => $this->configuration['column_width'],
      '#required' => TRUE,
    ];

    $form['extra'] = [
      '#type' => 'details',
      '#title' => $this->t('Extra'),
      '#open' => $this->hasExtraSettings(),
      '#weight' => 40,
    ];

    $form['extra']['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Class'),
      '#description' => $this->t('Enter custom css classes for this row. Separate multiple classes by a space and do not include a period.'),
      '#default_value' => $this->configuration['class'],
      '#attributes' => [
        'placeholder' => 'class-one class-two',
      ],
    ];

    $form['#attached']['library'][] = 'ecms_layout/layout_builder';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->configuration['background_color'] = $values['background']['background_color'];
    $this->configuration['class'] = $values['extra']['class'];
    $this->configuration['column_width'] = $values['layout']['column_width'];
    $this->configuration['full_width'] = $values['layout']['full_width']['checkbox'];
  }

  /**
   * Get the background color options.
   *
   * @return array
   *   The background color options.
   */
  protected function getBackgroundColorOptions(): array {
    return [
      EcmsLayout::ROW_BACKGROUND_COLOR_NONE => $this->t('None'),
      EcmsLayout::ROW_BACKGROUND_COLOR_PRIMARY => $this->t('Primary'),
      EcmsLayout::ROW_BACKGROUND_COLOR_PRIMARY_LIGHT => $this->t('Primary Light'),
      EcmsLayout::ROW_BACKGROUND_COLOR_COFFEEMILK => $this->t('Coffee Milk'),
    ];
  }

  /**
   * Get the column widths.
   *
   * @return array
   *   The column widths.
   */
  abstract protected function getColumnWidths(): array;

  /**
   * Get the default column width.
   *
   * @return string
   *   The default column width.
   */
  abstract protected function getDefaultColumnWidth(): string;

  /**
   * Determine if this layout has background settings.
   *
   * @return bool
   *   If this layout has background settings.
   */
  protected function hasBackgroundSettings(): bool {
    if (!empty($this->configuration['background_color'])) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Determine if this layout has extra settings.
   *
   * @return bool
   *   If this layout has extra settings.
   */
  protected function hasExtraSettings(): bool {
    if (!empty($this->configuration['extra']['class'])) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
