<?php

declare(strict_types = 1);

namespace Drupal\ecms_blocks\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a listing of promos referenced by the node.
 *
 * @Block(
 *   id = "ecms_promotions_node_specific",
 *   admin_label = @Translation("Promotions - Node Specific"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
class PromotionsNodeSpecificBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // Load node from context.
    $node = $this->getContextValue('node');

    // Assume what the promotions field machine name is.
    $field_string_guess = 'field_' . $node->bundle() . '_promotions';

    // Using a switch in case an entity doesn't follow the above pattern.
    // Set $promos to an array of promotion entities.
    switch (TRUE) {
      case ($node->hasField($field_string_guess)):
        $promos = $node->get($field_string_guess)->referencedEntities();
        break;

      default:
        $promos = NULL;
    }

    // If there are promos, render their teaser view.
    if (count($promos) > 0) {
      // Get language code.
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

      // Return a list of rendered teaser nodes.
      $builder = \Drupal::entityTypeManager()->getViewBuilder('node');
      return $builder->viewMultiple($promos, 'teaser', $language);
    }

    // If there is not a map file do not render block.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): object {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $config = $this->getConfiguration();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {}

}
