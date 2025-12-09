<?php

declare(strict_types=1);

namespace Drupal\ecms_asset_injector\Entity;

use Drupal\asset_injector\Entity\AssetInjectorCss;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Extended CSS Injector entity that processes color rules for dark mode.
 *
 * This class extends AssetInjectorCss to automatically process CSS and wrap
 * color-related rules in html:not(.dark) selectors. This ensures client-entered
 * styles only affect light mode, leaving dark mode unaffected.
 *
 * Implementation Strategy:
 * - $this->code: Original CSS entered by user (shown in admin form)
 * - $this->processedCode: Processed CSS with dark mode handling applied
 * - Both fields are stored in configuration (added via hook_entity_type_alter)
 * - Processing happens once in preSave() when entity is saved
 * - getCode() returns the processed version for file creation
 * - No reprocessing on page loads - processed code is persisted in config
 *
 * Performance Benefits:
 * - CSS parsing happens once per save, not on every page load
 * - Processed code is stored in YAML config and loaded from database
 * - No in-memory caching needed - data persists between requests
 * - Physical CSS files use processed code automatically
 *
 * User Experience:
 * - Users always see and edit original code in admin form
 * - Processing is transparent - happens automatically on save
 * - No risk of double-processing when re-editing
 *
 * @see \Drupal\ecms_asset_injector\CssColorProcessor
 * @see \Drupal\asset_injector\AssetFileStorage::createFile()
 * @see ecms_asset_injector_entity_type_alter()
 */
class EcmsAssetInjectorCss extends AssetInjectorCss {

  /**
   * Processed CSS with dark mode handling applied.
   *
   * This is automatically generated from $this->code during preSave() and
   * stored in configuration. The admin form never shows this - users only
   * see and edit the original code.
   *
   * Added to config_export via hook_entity_type_alter().
   *
   * @var string|null
   */
  public $processedCode;

  /**
   * {@inheritdoc}
   *
   * Process CSS before saving to generate and store the processedCode.
   * This happens once per save, not on every page load.
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    // Process the original code and store the result for persistence.
    /** @var \Drupal\ecms_asset_injector\CssColorProcessor $processor */
    $processor = \Drupal::service('ecms_asset_injector.css_color_processor');
    $this->processedCode = $processor->process($this->code);
  }

  /**
   * {@inheritdoc}
   *
   * Returns the processed CSS instead of the original.
   * This is called by AssetFileStorage::createFile() when generating
   * physical CSS files that are served to the browser.
   */
  public function getCode(): string {
    // Return processed code if available, otherwise fall back to original.
    // Fallback handles edge cases like entity creation before first save.
    return $this->processedCode ?? $this->code;
  }

}
