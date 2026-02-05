<?php

declare(strict_types=1);

namespace Drupal\ecms_layout\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a plugin class for the grid layout.
 */
final class GridLayout extends LayoutBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $configuration = parent::defaultConfiguration();
    $configuration['title'] = '';
    $configuration['heading_level'] = 'h2';
    $configuration['max_columns'] = '4';
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Remove layout options for grid layout (always auto-fit, never full-width).
    unset($form['layout']);

    $form['content'] = [
      '#type' => 'details',
      '#title' => $this->t('Content'),
      '#open' => TRUE,
      '#weight' => 10,
    ];

    $form['content']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('Optional title displayed above the grid.'),
      '#default_value' => $this->configuration['title'],
    ];

    $form['content']['heading_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Heading Level'),
      '#options' => [
        'h2' => $this->t('H2'),
        'h3' => $this->t('H3'),
        'h4' => $this->t('H4'),
        'h5' => $this->t('H5'),
        'h6' => $this->t('H6'),
      ],
      '#default_value' => $this->configuration['heading_level'],
    ];

    $form['content']['max_columns'] = [
      '#type' => 'radios',
      '#title' => $this->t('Max Columns'),
      '#description' => $this->t('Maximum number of columns in the grid.'),
      '#options' => [
        '3' => $this->t('3 (larger cards)'),
        '4' => $this->t('4 (smaller cards)'),
      ],
      '#default_value' => $this->configuration['max_columns'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Don't call parent - it expects layout fields we removed.
    $values = $form_state->getValues();

    $this->configuration['background_color'] = $values['background']['background_color'];
    $this->configuration['class'] = $values['extra']['class'];
    $this->configuration['title'] = $values['content']['title'];
    $this->configuration['heading_level'] = $values['content']['heading_level'];
    $this->configuration['max_columns'] = $values['content']['max_columns'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getColumnWidths(): array {
    return [
      'auto' => $this->t('Auto-fit grid'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultColumnWidth(): string {
    return 'auto';
  }

}
