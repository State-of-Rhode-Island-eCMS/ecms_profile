<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_publisher;

use Drupal\Core\Url;
use Drupal\ecms_api\EcmsApiBase;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\node\NodeInterface;
use GuzzleHttp\ClientInterface;

class EcmsApiPublisher extends EcmsApiBase {

  public function __construct(ClientInterface $httpClient, EntityToJsonApi $entityToJsonApi) {
    parent::__construct($httpClient, $entityToJsonApi);
  }

  public function syndicateNode(string $method, Url $recipientUrl, NodeInterface $node): bool {

    $clientId = $this->getClientId();
    $clientSecret = $this->getClientSecret();
    $clientScope = $this->getClientScope();

    // Get the access token to create this node.
    $accessToken = $this->getAccessToken($recipientUrl, $clientId, $clientSecret, $clientScope);

    // Guard against a null access token.
    if (empty($accessToken)) {
      return FALSE;
    }

    // Submit the entity to the API.
    $result = $this->submitEntity($method, $accessToken, $recipientUrl, $node);

    return $result;
  }

  /**
   * Get the client id from configuration.
   *
   * @return string
   *   The client id for the recipient site.
   */
  private function getClientId(): string {
    // @todo: Load this from configuration.
    return 'REDACTED';
  }

  /**
   * Get the client secret from configuration.
   *
   * @return string
   *   The client secret for the recipient site.
   */
  private function getClientSecret(): string {
    // @todo: Load this from configuration.
    return 'REDACTED';
  }

  /**
   * @return string
   *   The client scope for the recipient site.
   */
  private function getClientScope(): string {
    // @todo: Load this from configuration.
    return 'REDACTED';
  }

  // @todo: submit the node to the site.

}