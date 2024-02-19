<?php

declare(strict_types=1);

namespace Drupal\ecms_api_publisher;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\ecms_api_publisher\Entity\EcmsApiSiteInterface;
use Drupal\file\FileInterface;
use Drupal\user\UserInterface;

/**
 * Handles queueing nodes for syndication.
 *
 * @package Drupal\ecms_api_publisher
 */
class EcmsApiSyndicate {

  use StringTranslationTrait;

  /**
   * The publisher queue name.
   */
  const SYNDICATE_QUEUE = 'ecms_api_publisher_queue';

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The queue interface for the syndicate queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  private $queue;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * EcmsApiSyndicate constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, QueueFactory $queueFactory, MessengerInterface $messenger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->queue = $queueFactory->get(self::SYNDICATE_QUEUE);
    $this->messenger = $messenger;
  }

  /**
   * Syndicate the entity to all registered sites.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The node that should be syndicated to other sites.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function syndicateEntity(EntityInterface $entity): void {
    $type = $entity->bundle();

    // Get a list of all ecms_api_site entities that are of the bundle.
    $sites = $this->getApiSites($type);

    // Ensure we have entities to work with.
    if (empty($sites)) {
      return;
    }

    // Loop through the site entities and add them to a queue.
    foreach ($sites as $site) {
      $this->processEntity($entity, $site);
    }

    // Notify the user that the entity will be posted on the next cron run.
    $this->messenger->addMessage($this->t('Successfully queued the @type "%title" to get posted to @number sites on the next cron run.', [
      '@type' => $type,
      '%title' => $entity->label(),
      '@number' => count($sites),
    ]));

    // Add a message on how to manually push the content to all sites.
    $this->messenger->addWarning($this->t('If you need this content posted immediately please <a href=":form">follow this link to manually clear the queue</a>.', [
      ':form' => Url::fromRoute('ecms_api_publisher.batch_send_form')->toString(),
    ]));
  }

  /**
   * Get the API site entities that want to receive this notification.
   *
   * @param string $bundle
   *   The bundle to filter the ecms_api_site entities with.
   *
   * @return array
   *   The entities that would like to receive the content of $bundle type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getApiSites(string $bundle): array {
    $storage = $this->entityTypeManager->getStorage('ecms_api_site');

    $sites = $storage->loadByProperties(['content_type' => $bundle]);

    // Return the ecms_api_sites.
    return $sites;
  }

  /**
   * Queue entities for creation on the hub site.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to process.
   * @param \Drupal\ecms_api_publisher\Entity\EcmsApiSiteInterface $site
   *   The ecms_api_site entity to submit the entity.
   */
  private function processEntity(EntityInterface $entity, EcmsApiSiteInterface $site): void {
    $references = $entity->referencedEntities();

    // Keep drilling into the references.
    if (!empty($references)) {
      $this->processReferencedEntities($references, $site);
    }

    $queueData = [
      'site_entity' => $site,
      'syndicated_content_entity' => $entity,
    ];

    // Queue this entity for syndication.
    $this->queue->createItem($queueData);
  }

  /**
   * Process referenced entities.
   *
   * @param array $references
   *   Array of entities referencing the original entity.
   * @param \Drupal\ecms_api_publisher\Entity\EcmsApiSiteInterface $site
   *   The ecms_api_site entity to submit the entity.
   */
  private function processReferencedEntities(array $references, EcmsApiSiteInterface $site): void {
    // Loop through the entities.
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    foreach ($references as $entity) {

      // Ignore all configuration entities.
      if ($entity instanceof ConfigEntityBase) {
        continue;
      }

      // Ignore all file entities entities.
      if ($entity instanceof FileInterface) {
        continue;
      }

      // Ignore all user entities.
      if ($entity instanceof UserInterface) {
        continue;
      }

      $this->processEntity($entity, $site);
    }
  }

}
