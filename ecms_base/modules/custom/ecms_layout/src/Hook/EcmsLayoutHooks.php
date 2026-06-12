<?php

declare(strict_types=1);

namespace Drupal\ecms_layout\Hook;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\ecms_layout\LandingPageTitleDetector;

/**
 * Hook implementations for the ecms_layout module.
 */
class EcmsLayoutHooks {

  public function __construct(
    private readonly LandingPageTitleDetector $titleDetector,
  ) {}

  /**
   * Implements hook_preprocess_node().
   */
  #[Hook('preprocess_node')]
  public function preprocessNode(array &$variables): void {
    $node = $variables['node'] ?? NULL;
    if (!$node || $node->bundle() !== 'landing_page') {
      return;
    }
    $variables['render_fallback_title'] = !$this->titleDetector->hasH1Block($node);
  }

  /**
   * Implements hook_entity_bundle_field_info_alter().
   *
   * Makes the node title display-configurable on landing_page so it can be
   * placed as a field_block in Layout Builder. See drupal.org/i/3036862.
   */
  #[Hook('entity_bundle_field_info_alter')]
  public function entityBundleFieldInfoAlter(array &$fields, EntityTypeInterface $entity_type, string $bundle): void {
    if ($entity_type->id() === 'node' && $bundle === 'landing_page' && isset($fields['title'])) {
      $fields['title']->setDisplayConfigurable('view', TRUE);
    }
  }

}
