<?php

declare(strict_types = 1);

namespace Drupal\ecms_blocks\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of hotels.
 *
 * @Block(
 *   id = "ecms_hotel_listing",
 *   admin_label = @Translation("Hotels list"),
 * )
 */
class HotelListingBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * The entity_type.bundle.info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundle;

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
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * PromotionsGlobalBlock constructor.
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
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entityTypeBundle
   *   The entity_type.manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $languageManager, EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfo $entityTypeBundle) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->languageManager = $languageManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundle = $entityTypeBundle;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {

    $bundles = $this->entityTypeBundle->getBundleInfo('node');
    // Guard against a missing hotel bundle.
    if (!array_key_exists('hotel', $bundles)) {
      return [];
    }

    $node_storage = $this->entityTypeManager->getStorage('node');

    // Query all hotels.
    $query = $node_storage->getQuery();
    $query->condition('type', 'hotel')
      ->condition('status', 1)
      ->sort('title', "ASC");

    $nids = $query->execute();

    // Guard against no nodes.
    if (empty($nids)) {
      return [];
    }

    // Load multiple nodes.
    $nodes = $node_storage->loadMultiple($nids);

    // Get language code.
    $language = $this->languageManager->getCurrentLanguage()->getId();

    // Return a list of rendered teaser nodes.
    $builder = $this->entityTypeManager->getViewBuilder('node');
    return $builder->viewMultiple($nodes, 'teaser', $language);
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

}
