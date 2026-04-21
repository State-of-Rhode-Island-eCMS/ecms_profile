<?php

declare(strict_types=1);

namespace Drupal\ecms\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the ecms theme.
 */
class EcmsThemeHooks {

  /**
   * Implements hook_preprocess_menu__main__megamenu().
   *
   * Adds a 'description' key to each menu item, sourced from the title
   * attribute stored in the link's URL options (where Drupal stores the
   * menu link description field).
   */
  #[Hook('preprocess_menu__main__megamenu')]
  public function preprocessMenuMainMegamenu(array &$variables): void {
    $this->addDescriptions($variables['items']);
  }

  /**
   * Recursively adds 'description' to each item from its URL title attribute.
   */
  private function addDescriptions(array &$items): void {
    foreach ($items as &$item) {
      $options = $item['url']->getOptions();
      $item['description'] = $options['attributes']['title'] ?? '';
      if (!empty($item['below'])) {
        $this->addDescriptions($item['below']);
      }
    }
  }

}
