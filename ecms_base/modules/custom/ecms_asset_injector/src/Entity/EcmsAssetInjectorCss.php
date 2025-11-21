<?php

declare(strict_types=1);

namespace Drupal\ecms_asset_injector\Entity;

use Drupal\asset_injector\Entity\AssetInjectorCss;

/**
 * Extended CSS Injector entity that processes color rules for dark mode.
 *
 * This class extends the contrib AssetInjectorCss entity to intercept
 * the getCode() method and wrap color-related CSS rules in a light-mode
 * media query. This ensures client-entered styles only affect light mode,
 * leaving dark mode unaffected.
 */
class EcmsAssetInjectorCss extends AssetInjectorCss {

  /**
   * {@inheritdoc}
   */
  public function getCode(): string {
    $code = parent::getCode();

    // Process the CSS to wrap color rules in light-mode media query.
    /** @var \Drupal\ecms_asset_injector\CssColorProcessor $processor */
    $processor = \Drupal::service('ecms_asset_injector.css_color_processor');

    return $processor->process($code);
  }

}
