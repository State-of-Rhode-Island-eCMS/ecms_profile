<?php

declare(strict_types=1);

namespace Drupal\ecms_languages\Plugin\Block;

use Drupal\google_translator\Plugin\Block\GoogleTranslator;

/**
 * Provides a 'Google Translate Language Selector' block.
 *
 * @Block(
 *   id = "ecms_languages_google_translator",
 *   admin_label = @Translation("ECMS Translator (Google Translator)"),
 *   category = @Translation("ECMS Translator")
 * )
 */
class LanguageSelector extends GoogleTranslator {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // Return just the element and nothing else.
    return parent::getElement();
  }

}
