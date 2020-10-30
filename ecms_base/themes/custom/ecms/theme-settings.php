<?php

/**
 * @file
 * Creates a theme settings form for the eCMS theme.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
function ecms_form_system_theme_settings_alter(&$form, FormStateInterface $form_state, $form_id = NULL) {
  // Work-around for a core bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }

  // Build out the form.
  // Theme settings.
  $form['ecms_theme_options'] = [
    '#type' => 'details',
    '#title' => t("Theme Options"),
  ];

  $theme = \Drupal::theme()->getActiveTheme();
  $color_config_json_string = file_get_contents("{$theme->getPath()}/ecms_patternlab/source/_data/color-config.json");
  if ($color_config_json_string) {

    $json_decoded = json_decode($color_config_json_string, TRUE);
    if ($json_decoded === NULL) {
      return;
    }

    foreach ($json_decoded['palettes'] as $key => $palette) {
      // Do not add dark mode themes to list.
      $darkModeFound = strpos($key, '--dark');

      if ($darkModeFound !== FALSE) {
        continue;
      }

      $paletteOptions[$key] = $palette['humanName'];
    }

    $form['ecms_theme_options']['color_palette'] = [
      "#type" => "select",
      "#title" => t("Color Palette"),
      "#default_value" => theme_get_setting('color_palette'),
      "#options" => $paletteOptions,
      "#description" => t("Select which color palette the site will use."),
    ];
  }

  // Header settings.
  $form['ecms_header'] = [
    '#type' => 'details',
    '#title' => "Header",
  ];

  $form['ecms_header']['header_top_line'] = [
    '#type' => 'textfield',
    '#title' => t('Top Line'),
    '#default_value' => theme_get_setting('header_top_line'),
    '#description' => t("(Optional) The top line of the header."),
    '#maxlength' => 255,
  ];

  $form['ecms_header']['header_main_line'] = [
    '#type' => 'textfield',
    '#title' => t('Main Line'),
    '#default_value' => theme_get_setting('header_main_line'),
    '#description' => t("The main line of the header."),
    '#required' => TRUE,
    '#maxlength' => 255,
  ];

  $form['ecms_header']['header_bottom_line'] = [
    '#type' => 'textfield',
    '#title' => t('Bottom Line'),
    '#default_value' => theme_get_setting('header_bottom_line'),
    '#description' => t("(Optional) The bottom line of the header."),
    '#maxlength' => 255,
  ];

  // Footer settings.
  $form['ecms_footer'] = [
    '#type' => 'details',
    '#title' => "Footer",
  ];

  $form['ecms_footer']['footer_left'] = [
    '#type' => 'text_format',
    '#title' => t('Footer: Left Column'),
    '#format' => 'basic_html',
    '#description' => t('The left column of the footer.'),
    '#default_value' => theme_get_setting('footer_left')['value'],
  ];

  $form['ecms_footer']['footer_center'] = [
    '#type' => 'text_format',
    '#title' => t('Footer: Center Column'),
    '#format' => 'basic_html',
    '#description' => t('The center column of the footer.'),
    '#default_value' => theme_get_setting('footer_center')['value'],
  ];

  $form['ecms_footer']['footer_right'] = [
    '#type' => 'text_format',
    '#title' => t('Footer: Right Column'),
    '#format' => 'basic_html',
    '#description' => t('The right column of the footer.'),
    '#default_value' => theme_get_setting('footer_right')['value'],
  ];

  $form['ecms_footer']['footer_state_info'] = [
    '#type' => 'text_format',
    '#title' => t('Footer: State Info'),
    '#format' => 'basic_html',
    '#description' => t('Universal state links.'),
    '#default_value' => theme_get_setting('footer_state_info')['value'],
  ];
}
