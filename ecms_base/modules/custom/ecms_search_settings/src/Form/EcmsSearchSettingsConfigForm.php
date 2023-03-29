<?php

declare(strict_types = 1);

namespace Drupal\ecms_search_settings\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provide config form to set custom search settings, e.g. character count.
 *
 * @package Drupal\ecms_search_settings\Form
 */
class EcmsSearchSettingsConfigForm extends ConfigFormBase {

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'ecms_search_settings.settings',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'ecms_search_settings_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    $form['character_count'] = [
      '#title' => $this->t('Character Count in Search Results'),
      '#description' => $this->t(
        'Set the limit of characters in search results with long text. Defaults to 190.'
      ),
      '#type' => 'textfield',
      '#default_value' => '190',
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $character_count = $form_state->getValue('character_count');

    $this->config('ecms_search_settings.settings')
      ->set('character_count', $character_count)
      ->save();
  }

}
