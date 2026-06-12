<?php

declare(strict_types=1);

namespace Drupal\ecms\Form;

/**
 * Builds the Theme Options section of the ecms theme settings form.
 */
class ThemeOptions extends EcmsSettingsBase {

  /**
   * Attaches the ecms_theme_options details element and its fields to $form.
   *
   * @param array $form
   *   The form array, passed by reference.
   */
  public function alterForm(array &$form): void {
    $form['ecms_theme_options'] = [
      '#type' => 'details',
      '#title' => $this->t("Theme Options"),
    ];

    $this->buildColorPaletteField($form, $this->themeManager->getActiveTheme()->getPath());
    $this->buildPaletteOverrideField($form, $this->themeManager->getActiveTheme()->getPath());
    $this->buildIllustrationField($form, $this->themeManager->getActiveTheme()->getPath());
  }

  /**
   * Builds the color_palette select field.
   */
  private function buildColorPaletteField(array &$form, string $theme_path): void {
    $color_config_json_string = file_get_contents("{$theme_path}/assets/data/color-config.json");
    if (!$color_config_json_string) {
      return;
    }

    $json_decoded = json_decode($color_config_json_string, TRUE);
    if ($json_decoded === NULL) {
      return;
    }

    $paletteOptions = [];
    foreach ($json_decoded['palettes'] as $key => $palette) {
      // Do not add dark mode themes to list.
      if (str_contains($key, '--dark')) {
        continue;
      }
      $paletteOptions[$key] = $palette['humanName'];
    }

    $form['ecms_theme_options']['color_palette'] = [
      '#type' => 'select',
      '#title' => $this->t("Color Palette"),
      '#default_value' => $this->themeSettingsProvider->getSetting('color_palette'),
      '#options' => $paletteOptions,
      '#description' => $this->t("Select which color palette the site will use."),
    ];
  }

  /**
   * Builds the palette_override fieldset (palette select + pages textarea).
   *
   * Mirrors the UX of core's request_path block visibility condition: paths
   * one per line, "*" wildcard, "<front>" for the front page. When the
   * override palette is set and the current URL alias matches one of the
   * listed paths, ecms_preprocess_html() swaps the palette class on <html>.
   */
  private function buildPaletteOverrideField(array &$form, string $theme_path): void {
    $color_config_json_string = file_get_contents("{$theme_path}/assets/data/color-config.json");
    if (!$color_config_json_string) {
      return;
    }

    $json_decoded = json_decode($color_config_json_string, TRUE);
    if ($json_decoded === NULL) {
      return;
    }

    $paletteOptions = ['' => $this->t('- None -')];
    foreach ($json_decoded['palettes'] as $key => $palette) {
      if (str_contains($key, '--dark')) {
        continue;
      }
      $paletteOptions[$key] = $palette['humanName'];
    }

    $form['ecms_theme_options']['palette_override_group'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Path-based palette override'),
      '#description' => $this->t('Override the site palette on specific paths. Leave the palette unset to disable.'),
    ];

    $form['ecms_theme_options']['palette_override_group']['palette_override_palette'] = [
      '#type' => 'select',
      '#title' => $this->t('Override palette'),
      '#default_value' => $this->themeSettingsProvider->getSetting('palette_override_palette') ?? '',
      '#options' => $paletteOptions,
    ];

    $form['ecms_theme_options']['palette_override_group']['palette_override_pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pages'),
      '#default_value' => $this->themeSettingsProvider->getSetting('palette_override_pages') ?? '',
      '#description' => $this->t('Specify pages by using their paths. Enter one path per line. The "*" character is a wildcard. An example path is %user-wildcard for every user page. %front is the front page.', [
        '%user-wildcard' => '/user/*',
        '%front' => '<front>',
      ]),
    ];
  }

  /**
   * Builds the illustration_option select field.
   */
  private function buildIllustrationField(array &$form, string $theme_path): void {
    $illustration_json_string = file_get_contents("{$theme_path}/assets/data/illustrations.json");
    if (!$illustration_json_string) {
      return;
    }

    $json_decoded = json_decode($illustration_json_string, TRUE);
    if ($json_decoded === NULL) {
      return;
    }

    $illustrations = [];
    $allFilenames = [];

    // Loop over illustrations and build out arrays.
    foreach ($json_decoded['illustrations'] as $key => $object) {
      $illustrations[$object['filename']] = $key;
      $allFilenames[] = $object['filename'];
    }

    // Comma separated filenames to pass as the key for the random selection.
    // Twig will parse this string and select a filename at random.
    $allFilenames = implode(', ', $allFilenames);
    $randomKeyString = 'random:' . $allFilenames;

    // Set up hardcoded options.
    $illustrationOptions = [
      'none' => $this->t("No illustration"),
      $randomKeyString => t("Random illustration"),
    ];

    // Add compiled list to hardcoded options.
    $illustrationOptions = $illustrationOptions + $illustrations;

    $form['ecms_theme_options']['illustration_option'] = [
      '#type' => 'select',
      '#title' => $this->t("Illustration"),
      '#default_value' => $this->themeSettingsProvider->getSetting('illustration_option'),
      '#options' => $illustrationOptions,
      '#description' => $this->t("Select your configuration for page illustrations."),
    ];
  }

}
