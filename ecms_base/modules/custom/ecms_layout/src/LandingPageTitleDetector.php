<?php

declare(strict_types=1);

namespace Drupal\ecms_layout;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\SectionComponent;
use Drupal\node\NodeInterface;

/**
 * Decides whether a landing_page node's layout already renders an <h1>.
 */
final class LandingPageTitleDetector {

  private const ALWAYS_H1 = [
    'inline_block:page_title_with_photo',
    'field_block:node:landing_page:title',
  ];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Returns TRUE if any component in the node's layout renders an <h1>.
   */
  public function hasH1Block(NodeInterface $node): bool {
    if (!$node->hasField(OverridesSectionStorage::FIELD_NAME)) {
      return FALSE;
    }
    $sections = $node->get(OverridesSectionStorage::FIELD_NAME);
    foreach ($sections->getSections() as $section) {
      foreach ($section->getComponents() as $component) {
        if ($this->componentRendersH1($component)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Decides whether a single section component will render an <h1>.
   */
  private function componentRendersH1(SectionComponent $component): bool {
    $plugin_id = $component->getPluginId();

    if (in_array($plugin_id, self::ALWAYS_H1, TRUE)) {
      return TRUE;
    }

    if ($plugin_id === 'inline_block:header_image_action') {
      $config = $component->toArray()['configuration'] ?? [];
      return $this->hiaTitleEnabled($config);
    }

    return FALSE;
  }

  /**
   * Reads the header_image_action inline block's "use as H1" toggle.
   */
  private function hiaTitleEnabled(array $config): bool {
    $revision_id = $config['block_revision_id'] ?? NULL;
    if (!$revision_id) {
      return FALSE;
    }
    $block = $this->entityTypeManager->getStorage('block_content')->loadRevision($revision_id);
    if (!$block || !$block->hasField('field_hia_page_title_enabled')) {
      return FALSE;
    }
    return (bool) $block->get('field_hia_page_title_enabled')->value;
  }

}
