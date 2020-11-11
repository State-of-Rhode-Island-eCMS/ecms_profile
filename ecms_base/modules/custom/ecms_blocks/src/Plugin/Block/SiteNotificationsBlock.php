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

/**
 * Provides a listing of promos referenced by the node.
 *
 * @Block(
 *   id = "ecms_site_notifications",
 *   admin_label = @Translation("Site Notifications"),
 * )
 */
class SiteNotificationsBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
      $container->get('entity_type.manager')
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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $languageManager, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->languageManager = $languageManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {

    $node_storage = $this->entityTypeManager->getStorage('node');

    $query = $node_storage->getQuery();
    $query->condition('type', 'notification')
          ->condition('status', 1)
          ->sort('field_notification_global', "DESC");

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
  protected function blockAccess(AccountInterface $account): object {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

}
