<?php

declare(strict_types = 1);

namespace Drupal\ecms_api;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\jsonapi_extras\EntityToJsonApi;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Ecms API base class.
 *
 * The EcmsApiBase class is an abstract that can be extended by other
 * services. By default, this class provides the functionality to authenticate
 * to another site with Simple OAuth using the
 * `client_credentials` connection method.
 * This class also provides the method to submit an entity to another site
 * using the Json API.
 *
 * @package Drupal\ecms_api
 */
abstract class EcmsApiBase {

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
    'drupal_internal__mid',
    'drupal_internal__fid',
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
  protected $httpClient;

  /**
   * The jsonapi_extras.entity.to_jsonapi service.
   *
   * @var \Drupal\jsonapi_extras\EntityToJsonApi
   */
  protected $entityToJsonApi;

  /**
   * EcmsApiBase constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The http_client service.
   * @param \Drupal\jsonapi_extras\EntityToJsonApi $entityToJsonApi
   *   The jsonapi_extras.entity.to_jsonapi service.
   */
  public function __construct(ClientInterface $httpClient, EntityToJsonApi $entityToJsonApi) {
    $this->httpClient = $httpClient;
    $this->entityToJsonApi = $entityToJsonApi;
  }

  /**
   * Get an access token to authorize the request.
   *
   * @param \Drupal\Core\Url $url
   *   The base URL for the oauth endpoint.
   * @param string $client_id
   *   The client id for the oauth connection.
   * @param string $client_secret
   *   The client secret for the oauth connection.
   * @param string $scope
   *   The scope for the oauth connection.
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
  protected function submitEntity(string $accessToken, Url $url, EntityInterface $entity): bool {
    // Query the endpoint to get the correct HTTP method.
    $method = $this->checkEntityExists($accessToken, $url, $entity);

    // Only allow certain methods to be submitted.
    if (empty($method) || !in_array($method, self::ALLOWED_HTTP_METHODS, TRUE)) {
      return FALSE;
    }

    // Get the endpoint for the entity.
    $endpoint = $this->getEndpointUrl($url, $entity, $method);

    // Convert the entity to a json resource array.
    $normalizedEntity = $this->entityToJsonApi->normalize($entity);

    if (empty($normalizedEntity['data']['attributes'])) {
      return FALSE;
    }

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
   * Check if an entity exists on the endpoint.
   *
   * @param string $accessToken
   *   The access token from getAccessToken.
   * @param \Drupal\Core\Url $url
   *   The URL of the api site.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being submitted.
   *
   * @return string|null
   *   The http method to use for submission or null on error.
   */
  protected function checkEntityExists(string $accessToken, Url $url, EntityInterface $entity): ?string {
    // Get the endpoint and assume a patch to append the UUID to the url.
    $endpoint = $this->getEndpointUrl($url, $entity, 'PATCH');

    // Pass the access token so we can get unpublished entities.
    $payload = [
      'headers' => [
        'Content-Type' => 'application/vnd.api+json',
        'Authorization' => "Bearer {$accessToken}",
      ],
    ];

    try {
      $request = $this->httpClient->request('GET', $endpoint, $payload);
    }
    catch (GuzzleException $exception) {
      if ($exception->getCode() === 404) {
        return 'POST';
      }
      return NULL;
    }

    // If we receive a 200, the entity already exists.
    if ($request->getStatusCode() === 200) {
      return 'PATCH';
    }

    // Default to NULL.
    return NULL;
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
    $language = $entity->language();
    $languageEndpoint = $language->getId();

    // Get the endpoint for the entity.
    $entityPath = "{$entity->getEntityTypeId()}/{$entity->bundle()}";

    if ($method === 'PATCH') {
      $entityPath = "{$entityPath}/{$entity->uuid()}";
    }
    $endPoint = self::API_ENDPOINT;

    // Append the language id if it is not the default language.
    if (!$language->isDefault()) {
      return "{$url->toString()}/{$languageEndpoint}/{$endPoint}/{$entityPath}";
    }

    return "{$url->toString()}/{$endPoint}/{$entityPath}";
  }

  /**
   * Alter the attributes of the JSON Api entity.
   *
   * @param array $attributes
   *   Associative array of entity attributes to send with JSON API.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity being submitted or null.
   */
  protected function alterEntityAttributes(array &$attributes, ?EntityInterface $entity = NULL): void {
    $keys = array_keys($attributes);

    foreach ($keys as $key) {
      // If the attribute is disallowed, remove it.
      if (in_array($key, self::NO_API_FIELD_NAMES)) {
        unset($attributes[$key]);
      }
    }

    // Add the uuid to the attributes.
    if ($entity) {
      $attributes['uuid'] = $entity->uuid();
    }
  }

  /**
   * Get the content types from the hub by machine name.
   *
   * @param \Drupal\Core\Url $url
   *   The url of the hub to get the content type uuid.
   * @param array $types
   *   The machine name of the content types to retrieve.
   *
   * @return array|null
   *   Return the data array from the json api or null if an error occurs.
   */
  protected function getContentTypes(Url $url, array $types): ?array {
    $filter = [];

    // Loop through the content types and build filters.
    foreach ($types as $key => $value) {
      $filter["filter[type-{$key}][condition][path]"] = "drupal_internal__type";
      $filter["filter[type-{$key}][condition][operator]"] = "=";
      $filter["filter[type-{$key}][condition][value]"] = "{$value}";
    }

    $queryParams = http_build_query($filter);
    $apiEndpoint = self::API_ENDPOINT;
    $endpoint = "{$url->toString()}/{$apiEndpoint}/node_type/node_type?{$queryParams}";

    try {
      $request = $this->httpClient->request("GET", $endpoint);
    }
    catch (GuzzleException $exception) {
      return NULL;
    }

    if ($request->getStatusCode() === 200) {
      $contents = $request->getBody()->getContents();

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

      // Ensure we have the data property.
      if (!property_exists($json, 'data')) {
        return NULL;
      }

      // Return the content type id's that were requested.
      return $json->data;
    }

    return NULL;

  }

}
