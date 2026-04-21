<?php

declare(strict_types=1);

namespace Drupal\ecms\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the Header section of the ecms theme settings form.
 */
class HeaderSettings extends EcmsSettingsBase {

  /**
   * Attaches the ecms_header details element and its fields to $form.
   *
   * @param array $form
   *   The form array, passed by reference.
   */
  public function alterForm(array &$form): void {
    $form['ecms_header'] = [
      '#type' => 'details',
      '#title' => "Header",
    ];

    $form['ecms_header']['header_top_line'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Top Line'),
      '#default_value' => $this->themeSettingsProvider->getSetting('header_top_line'),
      '#description' => $this->t("(Optional) The top line of the header. For translation, search for 'State of Rhode Island'"),
      '#maxlength' => 255,
    ];

    $form['ecms_header']['header_main_line'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Main Line'),
      '#default_value' => $this->themeSettingsProvider->getSetting('header_main_line'),
      '#description' => $this->t("The main line of the header. For translation, search for 'Agency Name'"),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['ecms_header']['header_bottom_line'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bottom Line'),
      '#default_value' => $this->themeSettingsProvider->getSetting('header_bottom_line'),
      '#description' => $this->t("(Optional) The bottom line of the header. For translation, search for 'Agency Slogan'"),
      '#maxlength' => 255,
    ];

    $form['ecms_header']['logo_only'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->themeSettingsProvider->getSetting('logo_only'),
      '#title' => $this->t('Display logo only. Do not show any text.'),
    ];

    $form['ecms_header']['mega_menu'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->themeSettingsProvider->getSetting('mega_menu') ?? FALSE,
      '#title' => $this->t('Should the menu display as a mega menu..'),
    ];
  }

}
