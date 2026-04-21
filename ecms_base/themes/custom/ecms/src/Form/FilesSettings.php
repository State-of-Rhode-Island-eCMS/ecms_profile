<?php

declare(strict_types=1);

namespace Drupal\ecms\Form;

/**
 * Builds the Files section of the ecms theme settings form.
 */
class FilesSettings extends EcmsSettingsBase {

  /**
   * Attaches the ecms_files details element and its fields to $form.
   *
   * @param array $form
   *   The form array, passed by reference.
   */
  public function alterForm(array &$form): void {
    $form['ecms_files'] = [
      '#type' => 'details',
      '#title' => "Files",
    ];

    $form['ecms_files']['use_file_path'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->themeSettingsProvider->getSetting('use_file_path'),
      '#title' => $this->t('Use specific file path for download links'),
      '#description' => $this->t('Use specific file path for download links, instead of Drupal media path.'),
    ];
  }

}
