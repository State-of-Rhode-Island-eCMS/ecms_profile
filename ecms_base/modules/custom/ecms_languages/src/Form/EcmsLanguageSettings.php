<?php

declare(strict_types=1);

namespace Drupal\ecms_languages\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration form to exclude languages from the switcher.
 */
class EcmsLanguageSettings extends ConfigFormBase {

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.factory service.
   * @param Drupal\Core\Language\LanguageManager $language_manager
   *   The language_manager service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LanguageManager $language_manager
  ) {
    parent::__construct($config_factory);
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() : array {
    return [
      'ecms_languages.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return 'ecms_languages_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) : array {
    $config = $this->config('ecms_languages.settings');
    $excluded_languages = $config->get('excluded_languages') ?? [];
    $active_languages = $this->languageManager->getLanguages();
    $language_options = [];

    foreach ($active_languages as $key => $language) {
      $language_options[$key] = $language->getName();
    }

    $form['excluded_languages'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Excluded Languages'),
      '#description' => $this->t('Select languages to remove from the language switcher drop down.'),
      '#options' => $language_options,
      '#default_value' => $excluded_languages,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) : void {
    parent::submitForm($form, $form_state);

    $values = $form_state->getValue('excluded_languages');

    $this->config('ecms_languages.settings')
      ->set('excluded_languages', $values)
      ->save();
  }

}
