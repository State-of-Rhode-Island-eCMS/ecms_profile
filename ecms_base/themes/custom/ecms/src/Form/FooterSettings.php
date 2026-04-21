<?php

declare(strict_types=1);

namespace Drupal\ecms\Form;

/**
 * Builds the Footer section of the ecms theme settings form.
 */
class FooterSettings extends EcmsSettingsBase {

  /**
   * Attaches the ecms_footer details element and its fields to $form.
   *
   * @param array $form
   *   The form array, passed by reference.
   */
  public function alterForm(array &$form): void {
    $form['ecms_footer'] = [
      '#type' => 'details',
      '#title' => $this->t("Footer"),
    ];

    $form['ecms_footer']['footer_left'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Footer: First column'),
      '#format' => $this->themeSettingsProvider->getSetting('footer_left')['format'] ?? 'basic_html',
      '#description' => $this->t('The left column of the footer.'),
      '#default_value' => $this->themeSettingsProvider->getSetting('footer_left')['value'],
    ];

    $form['ecms_footer']['footer_center'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Footer: Second column'),
      '#format' => $this->themeSettingsProvider->getSetting('footer_center')['format'] ?? 'basic_html',
      '#description' => $this->t('The center column of the footer.'),
      '#default_value' => $this->themeSettingsProvider->getSetting('footer_center')['value'],
    ];

    $form['ecms_footer']['footer_right'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Footer: Third column'),
      '#format' => $this->themeSettingsProvider->getSetting('footer_right')['format'] ?? 'basic_html',
      '#description' => $this->t('The right column of the footer.'),
      '#default_value' => $this->themeSettingsProvider->getSetting('footer_right')['value'],
    ];

    $form['ecms_footer']['footer_state_info'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Footer: Fourth column'),
      '#format' => $this->themeSettingsProvider->getSetting('footer_state_info')['format'] ?? 'basic_html',
      '#description' => $this->t('Universal state links.'),
      '#default_value' => $this->themeSettingsProvider->getSetting('footer_state_info')['value'],
    ];

    $form['ecms_footer']['footer_divider'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Footer divider'),
      '#description' => $this->t('Enable the divider between the third and fourth column.'),
      '#default_value' => $this->themeSettingsProvider->getSetting('footer_divider', 'default') ?? TRUE,
    ];

    $form['ecms_footer']['footer_wave'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Footer wave'),
      '#description' => $this->t('Enable a wave effect on the footer.'),
      '#default_value' => $this->themeSettingsProvider->getSetting('footer_wave', 'default') ?? FALSE,
    ];

    $form['ecms_footer']['footer_above'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Footer: Above columns'),
      '#format' => $this->themeSettingsProvider->getSetting('footer_above')['format'] ?? 'basic_html',
      '#description' => $this->t('The left column of the footer.'),
      '#default_value' => $this->themeSettingsProvider->getSetting('footer_above')['value'] ?: '',
    ];
  }

}
