<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_notification_publisher;

use Drupal\ecms_api\EcmsApiBase;
use Drupal\ecms_api\EcmsApiHelper;
use Drupal\ecms_api_publisher\EcmsApiSyndicate;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\node\NodeInterface;
use GuzzleHttp\ClientInterface;

/**
 * Determine if a notification node should be syndicated.
 */
class NotificationPublisher extends EcmsApiBase {

  const MODERATION_PUBLISHED = 'published';

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
  public function __construct(ClientInterface $httpClient, EntityToJsonApi $entityToJsonApi, EcmsApiHelper $ecmsApiHelper, EcmsApiSyndicate $ecmsApiSyndicate) {
    parent::__construct($httpClient, $entityToJsonApi, $ecmsApiHelper);

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

    // If the original notification was not marked as global, ignore.
    if (!$this->isGlobalNotification($node)) {
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
      $this->ecmsApiSyndicate->syndicateNode($node);
    }
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

    if (empty($globalCheck)) {
      return FALSE;
    }

    // Cast the value as a boolean as this is changing between the default node
    // and the translated version of the node.
    $value = (bool) $globalCheck[0]['value'];
    // If it equals TRUE, the global checkbox was selected.
    if ($value) {
      return TRUE;
    }

    return FALSE;
  }

}
