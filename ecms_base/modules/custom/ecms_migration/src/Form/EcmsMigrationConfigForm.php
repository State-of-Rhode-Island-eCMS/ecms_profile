<?php

declare(strict_types = 1);

namespace Drupal\ecms_migration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provide a configuration form for setting up the eCMS Migration URLs.
 *
 * @package Drupal\ecms_migration\Form
 */
class EcmsMigrationConfigForm extends ConfigFormBase {

  /**
   * @inheritDoc
   */
  protected function getEditableConfigNames(): array {
    return [
      'ecms_migration.settings',
    ];
  }

  /**
   * @inheritDoc
   */
  public function getFormId(): string {
    return 'ecms_migration_settings_form';
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

//    $values = $form_state->getValue('excluded_languages');
//
//    $this->config('ecms_migration.settings')
//      ->set('excluded_languages', $values)
//      ->save();
  }

}
