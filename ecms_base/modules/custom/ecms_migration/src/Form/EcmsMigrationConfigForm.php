<?php

declare(strict_types=1);

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
   * {@inheritDoc}
   */
  protected function getEditableConfigNames(): array {
    $return = [
      'ecms_migration.settings',
    ];

    $migrations = $this->getRawMigrationConfiguration();

    return array_merge($return, $migrations);
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'ecms_migration_settings_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    $form['#tree'] = TRUE;

    $values = $this->getRawSettingsConfiguration();

    foreach (array_keys($values) as $fieldsetName) {
      $form["{$fieldsetName}"] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Settings for the %name migration', ['%name' => $fieldsetName]),
      ];
    }

    foreach ($values as $fieldset => $settings) {
      foreach ($settings as $setting => $value) {
        $form["{$fieldset}"]["{$setting}"] = [
          '#type' => 'textfield',
          '#title' => $this->t('Set the %name value', ['%name' => $setting]),
          '#default_value' => $value,
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $values = $this->getRawSettingsConfiguration();

    foreach (array_keys($values) as $fieldset) {
      $newConfig = $form_state->getValue("{$fieldset}");

      $this->config('ecms_migration.settings')
        ->set("{$fieldset}", $newConfig)
        ->save();

      $this->updateMigrationConfiguration($fieldset, $newConfig);
    }

    // Flush all caches to detect the new migration configuration settings.
    drupal_flush_all_caches();
  }

  /**
   * Get the array of data from configuration.
   *
   * @return array
   *   The raw data for the configuration.
   */
  private function getRawSettingsConfiguration(): array {
    $config = $this->config('ecms_migration.settings');
    $values = $config->getRawData();

    if (isset($values['_core'])) {
      unset($values['_core']);
    }

    return $values;
  }

  /**
   * Get the migration configuration object names.
   *
   * @return array
   *   A flattened array of migration keys to update.
   */
  private function getRawMigrationConfiguration(): array {
    $config = $this->configFactory->get('ecms_migration.migrations');
    $values = $config->getRawData();

    if (isset($values['_core'])) {
      unset($values['_core']);
    }

    $flattenedArray = array_values($values);
    return array_merge(...$flattenedArray);
  }

  /**
   * Update the migration configuration with the user supplied values.
   *
   * @param string $name
   *   The name of the migration fieldset.
   * @param array $settings
   *   The settings for the migration.
   */
  private function updateMigrationConfiguration(string $name, array $settings): void {
    // Get the migration configuration for this migration name.
    $migrations = $this->config('ecms_migration.migrations')->get($name);

    foreach ($settings as $key => $value) {
      if ($key === 'json_source_url') {
        $this->setJsonUrl($settings['json_source_url'], $migrations);
      }
      else {
        $this->setCssSelector($key, $value, $migrations);
      }
    }
  }

  /**
   * Set the css selectors for the migration tools.
   *
   * @param string $key
   *   The migration tools key to update.
   * @param string $selector
   *   The new css selector to replace the key.
   * @param array $migrations
   *   The migrations associated with this form setting.
   */
  protected function setCssSelector(string $key, string $selector, array $migrations): void {
    // Set a null selector if the selector key is empty.
    if (empty($selector)) {
      $selector = "#ECMS-MIGRATION-NULL-SELECTOR";
    }

    /** @var \Drupal\Core\Config\Config $migration */
    foreach ($migrations as $migration) {
      $migrationConfig = $this->config($migration);
      $migration_tools = $migrationConfig->get('source.migration_tools');

      // Guard against no migration tools.
      if (empty($migration_tools)) {
        continue;
      }

      // Change the selector value to that of the form.
      if (isset($migration_tools[0]['fields']["{$key}"])) {
        $migration_tools[0]['fields']["{$key}"]['jobs'][0]['arguments'][0] = $selector;
      }

      // Save the migration configuration.
      $migrationConfig->set('source.migration_tools', $migration_tools)->save();
    }
  }

  /**
   * Set the JSON url for the supplied migrations.
   *
   * @param string $jsonUrl
   *   The URL of the JSON file.
   * @param array $migrations
   *   The migrations to apply this URL.
   */
  protected function setJsonUrl(string $jsonUrl, array $migrations): void {

    // All migrations have a URL in the source.
    /** @var \Drupal\Core\Config\Config $migration */
    foreach ($migrations as $migration) {
      $migrationConfig = $this->config($migration);
      $migrationConfig->set('source.urls', [$jsonUrl])->save();
    }
  }

}
