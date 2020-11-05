<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_press_release_publisher;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

class PressReleaseSyndicate {

  /**
   * The published moderation state.
   */
  const MODERATION_PUBLISHED = 'published';

  /**
   * The press release publishing queue worker id.
   */
  const PRESS_RELEASE_QUEUE = 'ecms_api_press_release_publisher';

  /**
   * The ecms_api_press_release_publisher queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  private $queue;

  /**
   * PressReleaseSyndicate constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue service.
   */
  public function __construct(QueueFactory $queueFactory) {
    $this->queue = $queueFactory->get(self::PRESS_RELEASE_QUEUE);
  }

  /**
   * Broadcast a press release to the hub.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to broadcast.
   */
  public function syndicatePressRelease(NodeInterface $node): void {
    // Guard against a non-press_release.
    if ($node->getType() !== 'press_release') {
      return;
    }

    // Guard against a node that is not using content_moderation.
    if (!$node->hasField('moderation_state')) {
      // We can only act upon nodes in moderation.
      return;
    }

    // Get the moderated state of this revision.
    $moderatedState = array_column($node->get('moderation_state')->getValue(), 'value');

    // Guard against an empty array.
    if (empty($moderatedState)) {
      return;
    }

    if (in_array(self::MODERATION_PUBLISHED, $moderatedState, TRUE)) {
      $this->processEntity($node);
      // @todo: Push the media to the hub first.
      // @todo: How to handle the paragraphs?
      // @todo: How to handle media items within paragraphs?
      // @todo: How to handle the taxonomy terms?
      // @todo: How to handle terms with the same name on different sites?
      // @todo: Push the node to the hub.
    }
  }

  /**
   * Queue entities for creation on the hub site.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to process.
   */
  private function processEntity(EntityInterface $entity): void {
    $references = $entity->referencedEntities();

    // Keep drilling into the references.
    if (!empty($references)) {
      $this->processReferencedEntities($references);
    }

    // Queue this entity for syndication.
    $this->queue->createItem($entity);
  }

  /**
   * Process referenced entities.
   *
   * @param array $references
   *   Array of entities referencing the original entity.
   */
  private function processReferencedEntities(array $references): void {
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

      $this->processEntity($entity);
    }
  }

}
