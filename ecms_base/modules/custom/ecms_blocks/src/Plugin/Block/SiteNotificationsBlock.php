<?php

declare(strict_types = 1);

namespace Drupal\ecms_blocks\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of promos referenced by the node.
 *
 * @Block(
 *   id = "ecms_site_notifications",
 *   admin_label = @Translation("Site Notifications"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
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

    // Get the current date object.
    $now = new DrupalDateTime('now');
    $now->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));

    // Query all notifications.
    $query = $node_storage->getQuery();
    $query->condition('type', 'notification')
      ->condition('status', 1)
      ->condition('field_notification_expire_date', $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '>')
      ->sort('field_notification_global', "DESC")
      ->sort('field_notification_weight', "DESC");

    $nids = $query->execute();

    // Guard against no nodes.
    if (empty($nids)) {
      return [];
    }

    // Load multiple nodes.
    $nodes = $node_storage->loadMultiple($nids);

    // Get language code from the current node.
    $active_node = $this->getContextValue('node');

    if ($active_node) {
      $language = $active_node->get('langcode')->value;
    }
    else {
      // Get site active language.
      $language = $this->languageManager->getCurrentLanguage()->getId();
    }

    // Return a list of rendered teaser nodes.
    $builder = $this->entityTypeManager->getViewBuilder('node');
    $build = $builder->viewMultiple($nodes, 'teaser', $language);

    // Set a cache of an hour on the render array.
    $build['#cache']['max-age'] = 3600;

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    // We want to rebuild when notifications change.
    return Cache::mergeTags(parent::getCacheTags(), ['node_list:notification']);
  }

}
