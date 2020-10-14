<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_notification_publisher;

use Drupal\ecms_api\EcmsApiBase;
use Drupal\ecms_api_publisher\EcmsApiSyndicate;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\node\NodeInterface;
use GuzzleHttp\ClientInterface;

/**
 * Determine if a notification node should be syndicated.
 */
class NotificationPublisher extends EcmsApiBase {

  /**
   * The ecms_api_publisher.syndicate service.
   *
   * @var \Drupal\ecms_api_publisher\EcmsApiSyndicate
   */
  private $ecmsApiSyndicate;

  /**
   * EcmsApiPublisher constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The http_client service.
   * @param \Drupal\jsonapi_extras\EntityToJsonApi $entityToJsonApi
   *   The jsonapi_extras.entity.to_jsonapi service.
   * @param \Drupal\ecms_api_publisher\EcmsApiSyndicate $ecmsApiSyndicate
   *   The ecms_api_publisher.syndicate service.
   */
  public function __construct(ClientInterface $httpClient, EntityToJsonApi $entityToJsonApi, EcmsApiSyndicate $ecmsApiSyndicate) {
    parent::__construct($httpClient, $entityToJsonApi);

    $this->ecmsApiSyndicate = $ecmsApiSyndicate;
  }

  /**
   * Determine if a notification should be broadcast to the syndicate sites.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node that was just updated or inserted.
   */
  public function broadcastNotification(NodeInterface $node): void {
    // Guard against a non-notification.
    if ($node->getType() !== 'notification') {
      return;
    }

    // If the notification was not marked as global, ignore.
    if (!$this->isGlobalNotification($node)) {
      return;
    }

    // If the node has just transitioned to published or not-published.
    if ($this->hasTransitionedToPublished($node) || $this->hasTransitionedToNotPublished($node)) {
      // @todo: Need to determine insert, update or refactor away?
      $this->ecmsApiSyndicate->syndicateNode($node);
    }
  }

  /**
   * Check if the notification has transitioned to published.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check.
   *
   * @return bool
   *   True if the node just transitioned to published.
   */
  private function hasTransitionedToPublished(NodeInterface $node): bool {
    // If the node is not currently published, return early.
    if (!$node->isPublished()) {
      return FALSE;
    }

    // Get the original node.
    /** @var \Drupal\node\NodeInterface|null $original */
    $original = $node->original;

    if (empty($original)) {
      // If the original is empty, the node transitioned
      // immediately to published.
      return TRUE;
    }

    // If the original node was not published,
    // a transition to published happened.
    if (!$original->isPublished()) {
      return TRUE;
    }

    // Default to false.
    return FALSE;
  }

  /**
   * Check if the notification has transitioned to not published.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check.
   *
   * @return bool
   *   True if the node just transitioned to not-published.
   */
  private function hasTransitionedToNotPublished(NodeInterface $node): bool {
    // If the node is currently published, return early.
    if ($node->isPublished()) {
      return FALSE;
    }

    // Get the original node.
    /** @var \Drupal\node\NodeInterface|null $original */
    $original = $node->original;

    if (empty($original)) {
      // If the original is empty, the node transitioned
      // immediately to not published.
      return TRUE;
    }

    // If the original node was published,
    // a transition to not published happened.
    if ($original->isPublished()) {
      return TRUE;
    }

    // Default to false.
    return FALSE;
  }

  /**
   * Check if the global notification checkbox was selected.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check.
   *
   * @return bool
   *   True if the field was selected.
   */
  private function isGlobalNotification(NodeInterface $node): bool {
    // Ensure the global notification field exists.
    if (!$node->hasField('field_notification_global')) {
      return FALSE;
    }

    // Get the field value.
    $globalCheck = $node->get('field_notification_global')->getValue();

    // If it equals 1, the global checkbox was selected.
    if (!empty($globalCheck) && $globalCheck[0]['value'] === 1) {
      return TRUE;
    }

    return FALSE;
  }

}
