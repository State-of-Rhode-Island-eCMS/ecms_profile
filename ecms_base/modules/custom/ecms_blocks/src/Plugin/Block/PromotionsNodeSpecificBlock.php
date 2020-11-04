<?php

declare(strict_types = 1);

namespace Drupal\ecms_blocks\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

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
class PromotionsNodeSpecificBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The account service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The language_manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * PromotionsNodeSpecificBlock constructor.
   *
   * @param array $configuration
   *   Configuration array for the block.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language_manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The entity_type.manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $languageManager, EntityTypeManagerInterface $entityTypeManager, AccountInterface $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->languageManager = $languageManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // Load node from context.
    $node = $this->getContextValue('node');
    if (!$node instanceof NodeInterface) {
      return [];
    }

    // Assume what the promotions field machine name is.
    $field_string_guess = 'field_' . $node->bundle() . '_promotions';

    // Guard against a missing field.
    if (!$node->hasField($field_string_guess)) {
      return [];
    }

    $promos = $node->get($field_string_guess)->referencedEntities();

    // Guard against no promos.
    if (empty($promos)) {
      return [];
    }

    // Get language code.
    $language = $this->languageManager->getCurrentLanguage()->getId();

    // Return a list of rendered teaser nodes.
    $builder = $this->entityTypeManager->getViewBuilder('node');
    return $builder->viewMultiple($promos, 'teaser', $language);
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(): AccessResult {
    return AccessResult::allowedIfHasPermission($this->account, 'access content');
  }

}
