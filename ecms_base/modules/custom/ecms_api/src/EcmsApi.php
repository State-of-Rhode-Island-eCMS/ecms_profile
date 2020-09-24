<?php

declare(strict_types = 1);

namespace Drupal\ecms_api;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\jsonapi_extras\EntityToJsonApi;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class EcmsApi.
 *
 * @package Drupal\ecms_api
 */
abstract class EcmsApi {

  /**
   * The API endpoint prefix.
   */
  const API_ENDPOINT = 'EcmsApi';

  /**
   * Allowed HTTP methods to accept for submission to the API endpoints.
   */
  const ALLOWED_HTTP_METHODS = [
    'POST',
    'PATCH',
  ];

  /**
   * Fields that should not be submitted.
   */
  const NO_API_FIELD_NAMES = [
    'drupal_internal__nid',
    'drupal_internal__vid',
    'revision_timestamp',
    'status',
    'created',
    'changed',
    'promote',
    'sticky',
    'path',
  ];

  /**
   * The http_client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  private $httpClient;

  /**
   * The jsonapi_extras.entity.to_jsonapi service.
   *
   * @var \Drupal\jsonapi_extras\EntityToJsonApi
   */
  private $entityToJsonApi;

  /**
   * EcmsApi constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The http_client service.
   * @param \Drupal\jsonapi_extras\EntityToJsonApi $entityToJsonApi
   *   The jsonapi_extras.entity.to_jsonapi service.
   */
  public function __construct(
    ClientInterface $httpClient,
    EntityToJsonApi $entityToJsonApi
  ) {
    $this->httpClient = $httpClient;
    $this->entityToJsonApi = $entityToJsonApi;
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

      // Decode the json string.
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
   * Submit a new entity to the API endpoint.
   *
   * @param string $method
   *   The HTTP method to use to submit to the api. Currently allowed methods
   *   are POST for creating entities and PATCH for updating entities.
   * @param string $accessToken
   *   The access token to the API. @see getAccessToken().
   * @param \Drupal\Core\Url $url
   *   The url of the endpoint.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity submitted.
   *
   * @return bool
   *   True if the entity was successfully submitted.
   */
  protected function submitEntity(string $method, string $accessToken, Url $url, EntityInterface $entity): bool {
    // Only allow certain methods to be submitted.
    if (!in_array($method, self::ALLOWED_HTTP_METHODS, TRUE)) {
      return FALSE;
    }

    // Get the endpoint for the entity.
    $endpoint = $this->getEndpointUrl($url, $entity, $method);

    // Convert the entity to a json resource array.
    $normalizedEntity = $this->entityToJsonApi->normalize($entity);

    $payload = [
      'json' => [
        'data' => [
          'type' => $entity->bundle(),
          'id' => $entity->uuid(),
          'attributes' => $normalizedEntity['data']['attributes'],
        ],
      ],
      'headers' => [
        'Content-Type' => 'application/vnd.api+json',
        'Authorization' => "Bearer {$accessToken}",
      ],
    ];

    // Alter the entity attributes before submission.
    $this->alterEntityAttributes($payload['json']['data']['attributes'], $entity);

    try {
      $request = $this->httpClient->request($method, $endpoint, $payload);
    }
    catch (GuzzleException $exception) {
      return FALSE;
    }

    // 201 means successfully created the entity.
    if ($method === 'POST' && $request->getStatusCode() === 201) {
      return TRUE;
    }

    // 200 means successfully updated the entity.
    if ($method === 'PATCH' && $request->getStatusCode() === 200) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get the endpoint URL.
   *
   * @param \Drupal\Core\Url $url
   *   The url of the API.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being submitted to the API.
   * @param string $method
   *   The method being used to call the json API endpoint.
   *
   * @return string
   *   The full url to the endpoint API.
   */
  protected function getEndpointUrl(Url $url, EntityInterface $entity, string $method): string {
    // Get the endpoint for the entity.
    $entityPath = "{$entity->getEntityTypeId()}/{$entity->bundle()}";

    if ($method === 'PATCH') {
      $entityPath = "{$entityPath}/{$entity->uuid()}";
    }
    $endPoint = self::API_ENDPOINT;

    return "{$url->toString()}/{$endPoint}/{$entityPath}";
  }

  /**
   * Alter the attributes of the JSON Api entity.
   *
   * @param array $attributes
   *   Associative array of entity attributes to send with JSON API.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being submitted.
   */
  protected function alterEntityAttributes(array &$attributes, EntityInterface $entity): void {
    $keys = array_keys($attributes);

    foreach ($keys as $key) {
      // If the attribute is disallowed, remove it.
      if (in_array($key, self::NO_API_FIELD_NAMES)) {
        unset($attributes[$key]);
      }
    }

    // Add the uuid to the attributes.
    $attributes['uuid'] = $entity->uuid();

  }

}
