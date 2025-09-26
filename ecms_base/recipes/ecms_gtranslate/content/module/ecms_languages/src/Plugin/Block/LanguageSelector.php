<?php

declare(strict_types=1);

namespace Drupal\ecms_languages\Plugin\Block;

use Drupal\gtranslate\Plugin\Block\GTranslateBlock;

/**
 * Provides a 'GTranslate Language Selector' block.
 *
 * @Block(
 *   id = "ecms_languages_gtranslate",
 *   admin_label = @Translation("ECMS Translator (GTranslate)"),
 *
 *   category = @Translation("ECMS Translator")
 * )
 */
class LanguageSelector extends GTranslateBlock {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // Get the parent build array.
    $build = parent::build();

    // Add custom library for styling.
    $build['#attached']['library'][] = 'ecms_languages/element';

    return $build;
  }

}
