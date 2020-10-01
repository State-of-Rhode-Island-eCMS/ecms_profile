<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_publisher;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\ecms_api\EcmsApiBase;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\node\NodeInterface;
use GuzzleHttp\ClientInterface;

/**
 * Class EcmsApiPublisher.
 *
 * @package Drupal\ecms_api_publisher
 */
class EcmsApiPublisher extends EcmsApiBase {

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * EcmsApiPublisher constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The http_client service.
   * @param \Drupal\jsonapi_extras\EntityToJsonApi $entityToJsonApi
   *   The jsonapi_extras.entity.to_jsonapi service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   */
  public function __construct(ClientInterface $httpClient, EntityToJsonApi $entityToJsonApi, ConfigFactoryInterface $configFactory) {
    parent::__construct($httpClient, $entityToJsonApi);

    $this->configFactory = $configFactory;
  }

  /**
   * Queue a node for syndication.
   *
   * @param string $method
   *   The HTTP method to use for the syndication. POST or PATCH.
   * @param \Drupal\Core\Url $recipientUrl
   *   The URL of the API receiving the node.
   * @param \Drupal\node\NodeInterface $node
   *   The node to submit.
   *
   * @return bool
   *   True if successfully saved.
   */
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
    return $this->configFactory->get('ecms_api_publisher.settings')->get('recipient_client_id');
  }

  /**
   * Get the client secret from configuration.
   *
   * @return string
   *   The client secret for the recipient site.
   */
  private function getClientSecret(): string {
    return $this->configFactory->get('ecms_api_publisher.settings')->get('recipient_client_secret');
  }

  /**
   * Get the client scope from configuration.
   *
   * @return string
   *   The client scope for the recipient site.
   */
  private function getClientScope(): string {
    return $this->configFactory->get('ecms_api_publisher.settings')->get('recipient_client_scope');
  }

}
