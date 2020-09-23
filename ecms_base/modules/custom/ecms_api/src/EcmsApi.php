<?php

declare(strict_types = 1);

namespace Drupal\ecms_api;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class EcmsApi {

  const API_ENDPOINT = 'EcmsApi';

  const NO_API_FIELD_NAMES = [
    'nid',
    'uid',
    'vid',
  ];

  const NO_API_FIELD_TYPES = [
    'entity_reference',
  ];

  /**
   * The http_client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  private $httpClient;

  /**
   * The entity_type.manager service.
   *
   * @var\Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  private $entityFieldManager;

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * EcmsApi constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The http_client service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory interface.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, ClientInterface $httpClient, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
  }

  /**
   * Get an access token to authorize the request.
   *
   * @return string|null
   *   The access token or NULL.
   */
  protected function getAccessToken(Url $url, string $client_id, string $client_secret, string $scope): ?string {
    $payload = [
      'form_params' => [
        'grant_type' => 'client_credentials',
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'scope' => $scope,
      ],
    ];

    // Get the endpoint of the entity.
    $endpoint = "{$url->toString()}/oauth/token";

    try {
      $response = $this->httpClient->request('POST', $endpoint, $payload);
    }
    catch (GuzzleException $exception) {
      return NULL;
    }

    if ($response->getStatusCode() === 200) {
      $contents = $response->getBody()->getContents();
      $json = json_decode($contents);

      // Guard against a json error.
      if (empty($json)) {
        return NULL;
      }

      // Ensure we have an object.
      if (!is_object($json)) {
        return NULL;
      }

      // Ensure we have the access token property.
      if (!property_exists($json, 'access_token')) {
        return NULL;
      }

      // Return the access token.
      return $json->access_token;
    }

    return NULL;
  }

  /**
   * @param string $accessToken
   *   The access token authorizing access. @see getAccessToken().
   * @param \Drupal\Core\Url $url
   *   The base url of the API to access.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to post.
   *
   * @return bool
   */
  protected function postEntity(string $accessToken, Url $url, EntityInterface $entity): bool {
    // Get the endpoint for the entity.
    $endpoint = $this->getEndpointUrl($url, $entity);

    $payload = [
      'json' => [
        'type' => $entity->bundle(),
        'attributes' => [],
      ],
      'headers' => [
        'Content-Type' => 'application/vnd.api+json',
        'Authorization' => "Bearer {$accessToken}"
      ],
    ];

    // Alter the payload with the field values.
    $this->buildEntityAttributes($payload, $entity);

    try {
      $request = $this->httpClient->request('POST', $endpoint, $payload);
    }
    catch (GuzzleException $exception) {
      return FALSE;
    }



    return FALSE;


    // @todo: Setup the curl request to post the entity payload.
  }

  private function getEndpointUrl(Url $url, EntityInterface $entity): string {
    // Get the endpoint for the entity.
    $entityPath = "{$entity->getEntityTypeId()}/{$entity->bundle()}";
    $endPoint = self::API_ENDPOINT;

    return "{$url->toString()}/{$endPoint}/{$entityPath}";
  }

  private function buildEntityAttributes(&$payload, $entity): void {
    // Get the fields available for the entity.
    $fields = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());

    /**
     * @var \Drupal\Core\Field\FieldDefinitionInterface $field
     */
    foreach ($fields as $field) {
      if (in_array($field->getName(), self::NO_API_FIELD_NAMES)) {
        continue;
      }

      if (in_array($field->getType(), self::NO_API_FIELD_TYPES)) {
        continue;
      }

      $payload['json']['attributes']["{$field->getName()}"] = $entity->get($field->getName())->value;
    }

  }

//  /**
//   * Post a new entity
//   * @param \Drupal\Core\Entity\EntityInterface $entity
//   *
//   * @return bool
//   */
public function insertEntity(EntityInterface $entity): bool {
    $url = Url::fromUri('https://appserver');

    $accessToken = $this->getAccessToken($url, 'REDACTED', 'REDACTED', 'ecms_api_publisher');


    if (!empty($accessToken)) {
      return $this->postEntity($accessToken, $url, $entity);
    }

    return FALSE;
}
//
//  public function updateEntity(EntityInterface $entity): bool {}

}