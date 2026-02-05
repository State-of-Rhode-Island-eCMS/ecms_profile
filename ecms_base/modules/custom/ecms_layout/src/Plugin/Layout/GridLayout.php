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
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValues();
    $this->configuration['title'] = $values['content']['title'];
    $this->configuration['heading_level'] = $values['content']['heading_level'];
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
