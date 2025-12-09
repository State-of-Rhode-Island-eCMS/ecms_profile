<?php

declare(strict_types=1);

namespace Drupal\ecms_asset_injector\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\ecms_asset_injector\Entity\EcmsAssetInjectorCss;

/**
 * Hook implementations for ecms_asset_injector.
 */
class EcmsAssetInjectorHooks {

  /**
   * Implements hook_entity_type_alter().
   *
   * Replaces the AssetInjectorCss entity class with our extended version
   * that wraps color-related CSS rules in light-mode selectors, and adds
   * the processedCode property to config export.
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types): void {
    if (isset($entity_types['asset_injector_css'])) {
      // Use our extended entity class.
      $entity_types['asset_injector_css']->setClass(EcmsAssetInjectorCss::class);

      // Add processedCode to the config export so it's persisted.
      $config_export = $entity_types['asset_injector_css']->get('config_export');
      $config_export[] = 'processedCode';
      $entity_types['asset_injector_css']->set('config_export', $config_export);
    }
  }

}
