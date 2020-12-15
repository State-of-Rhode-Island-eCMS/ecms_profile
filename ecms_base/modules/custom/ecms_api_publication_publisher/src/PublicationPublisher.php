<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_publication_publisher;

use Drupal\ecms_api\EcmsApiBase;
use Drupal\ecms_api\EcmsApiHelper;
use Drupal\ecms_api_publisher\EcmsApiSyndicate;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\node\NodeInterface;
use GuzzleHttp\ClientInterface;

/**
 * Determine if a publication node should be syndicated.
 */
class PublicationPublisher extends EcmsApiBase {

  /**
   * The published moderation state.
   */
  const MODERATION_PUBLISHED = 'published';

  /**
   * The ecms_api_publisher.syndicate service.
   *
   * @var \Drupal\ecms_api_publisher\EcmsApiSyndicate
   */
  private $ecmsApiSyndicate;

  /**
   * PublicationsPublisher constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The http_client service.
   * @param \Drupal\jsonapi_extras\EntityToJsonApi $entityToJsonApi
   *   The jsonapi_extras.entity.to_jsonapi service.
   * @param \Drupal\ecms_api\EcmsApiHelper $ecmsApiHelper
   *   The ecms_api_helper service.
   * @param \Drupal\ecms_api_publisher\EcmsApiSyndicate $ecmsApiSyndicate
   *   The ecms_api_publisher.syndicate service.
   */
  public function __construct(ClientInterface $httpClient, EntityToJsonApi $entityToJsonApi, EcmsApiHelper $ecmsApiHelper, EcmsApiSyndicate $ecmsApiSyndicate) {
    parent::__construct($httpClient, $entityToJsonApi, $ecmsApiHelper);

    $this->ecmsApiSyndicate = $ecmsApiSyndicate;
  }

  /**
   * Determine if a publication should be broadcast to the syndicate sites.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node that was just updated or inserted.
   */
  public function broadcastPublication(NodeInterface $node): void {
    // Guard against a non-publication.
    if ($node->getType() !== 'publication') {
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
      $this->ecmsApiSyndicate->syndicateEntity($node);
    }
  }

}
