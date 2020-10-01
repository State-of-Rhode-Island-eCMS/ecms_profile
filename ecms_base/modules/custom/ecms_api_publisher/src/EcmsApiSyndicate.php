<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_publisher;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

/**
 * Class EcmsApiPublisher.
 *
 * @package Drupal\ecms_api_publisher
 */
class EcmsApiSyndicate {

  use StringTranslationTrait;

  /**
   * The publisher queue name.
   */
  const SYNDICATE_QUEUE = 'ecms_api_publisher_queue';

  const ALLOWED_METHODS = [
    'INSERT' => 'POST',
    'UPDATE' => 'PATCH',
  ];

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
   * @param \Drupal\node\NodeInterface $entity
   *   The node that should be syndicated to other sites.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function syndicateNode(NodeInterface $entity, string $method): void {
    if (!array_key_exists($method, self::ALLOWED_METHODS)) {
      return;
    }
    // @todo: Ensure the node is published.
    // @see: https://www.sitepoint.com/drupal-8-queue-api-powerful-manual-and-cron-queueing/
    $type = $entity->bundle();

    // Get a list of all ecms_api_site entities that are of the bundle.
    $sites = $this->getApiSites($type);

    // Ensure we have entities to work with.
    if (empty($sites)) {
      return;
    }

    // Loop through the site entities and add them to a queue.
    foreach ($sites as $site) {
      $queueData = [
        'site_entity' => $site,
        'syndicated_content_entity' => $entity,
        'method' => self::ALLOWED_METHODS[$method],
      ];

      // Push a new item onto the queue.
      $this->queue->createItem($queueData);
    }

    // Notify the user that the node will be posted on the next cron run.
    $this->messenger->addMessage($this->t('Successfully queued the @type "%title" to get posted to @number sites on the next cron run.', [
      '@type' => $type,
      '%title' => $entity->getTitle(),
      '@number' => count($sites),
    ]));

    // Add a message on how to manually push the content to all sites.
    // @todo: Get the correct form link for @href.
    $this->messenger->addWarning($this->t('If you need this content posted immediately please <a href="@href">follow this link to manually clear the queue</a>.', [
      '@href' => '/',
    ]));
    // @todo: Write the cron queue processor.
    // @todo: Write the form to batch the queued items if any exist.
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

}
