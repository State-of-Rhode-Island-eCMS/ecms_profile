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

    $search_settings_config = $this->config('ecms_search_settings.settings');
    if (isset($search_settings_config)) {
      $current_character_count = $search_settings_config->get('character_count');
    }

    $form['character_count'] = [
      '#title' => $this->t('Character Count in Search Results'),
      '#description' => $this->t(
        'Set the limit of characters in search results with long text. Defaults to 190.'
      ),
      '#type' => 'textfield',
      '#default_value' => $current_character_count ?? '190',
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $character_count = $form_state->getValue('character_count');

    // Store value in this module's config settings.
    $this->config('ecms_search_settings.settings')
      ->set('character_count', $character_count)
      ->save();

    // Set excerpt length in config for search index's 'highlighter' processor.
//    $config = \Drupal::service('config.factory')->getEditable('search_api.index.acquia_search_index');
    $this->config('search_api.index.acquia_search_index')
      ->set('processor_settings.highlight.excerpt_length', $character_count)
      ->save();
  }

}
