<?php

declare(strict_types=1);

namespace Drupal\ecms_api;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Url;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\media\MediaInterface;
use Drupal\media\Plugin\media\Source\File;
use Drupal\paragraphs\ParagraphInterface;
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

  const NO_API_PARAGRAPH_ONLY = [
    'langcode',
  ];

  /**
   * Fields that should not be submitted.
   */
  const NO_API_FIELD_NAMES = [
    'drupal_internal__nid',
    'drupal_internal__vid',
    'drupal_internal__mid',
    'drupal_internal__fid',
    'drupal_internal__id',
    'drupal_internal__tid',
    'drupal_internal__revision_id',
    'revision_timestamp',
    'status',
    'created',
    'changed',
    'promote',
    'sticky',
    'path',
    'content_translation_changed',
    'parent_id',
    'rh_action',
    'rh_redirect',
    'rh_redirect_response',
    'rh_redirect_fallback_action',
  ];

  /**
   * Related fields that should not be submitted through JsonAPI.
   */
  const NO_RELATIONSHIPS_API = [
    'uid',
    'revision_uid',
    'revision_user',
    'node_type',
    'thumbnail',
    'paragraph_type',
    'bundle',
    'vid',
    'parent',
    'content_translation_uid',
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
   * The ecms_api_helper service.
   *
   * @var \Drupal\ecms_api\EcmsApiHelper
   */
  protected $ecmsApiHelper;

  /**
   * EcmsApiBase constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The http_client service.
   * @param \Drupal\jsonapi_extras\EntityToJsonApi $entityToJsonApi
   *   The jsonapi_extras.entity.to_jsonapi service.
   * @param \Drupal\ecms_api\EcmsApiHelper $ecmsApiHelper
   *   The ecms_api_helper service.
   */
  public function __construct(ClientInterface $httpClient, EntityToJsonApi $entityToJsonApi, EcmsApiHelper $ecmsApiHelper) {
    $this->httpClient = $httpClient;
    $this->entityToJsonApi = $entityToJsonApi;
    $this->ecmsApiHelper = $ecmsApiHelper;
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
   * @param bool $verify
   *   Verify the SSL connection.
   *
   * @return string|null
   *   The access token or NULL.
   */
  protected function getAccessToken(
    Url $url,
    string $client_id,
    string $client_secret,
    string $scope,
    bool $verify = TRUE,
  ): ?string {
    $payload = [
      'form_params' => [
        'grant_type' => 'client_credentials',
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'scope' => $scope,
      ],
      'verify' => $verify,
    ];

    // Get the endpoint of the entity.
    $trimmedUrl = rtrim($url->toString(), '/');
    $endpoint = "{$trimmedUrl}/oauth/token";

    try {
      $response = $this->httpClient->request('POST', $endpoint, $payload);
    }
    catch (GuzzleException $exception) {
      \Drupal::logger('ecms_api')->error($exception->getMessage());
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
   * @param bool $verify
   *   Verify the SSL connection.
   *
   * @return bool
   *   True if the entity was successfully submitted.
   */
  protected function submitEntity(
    string $accessToken,
    Url $url,
    EntityInterface $entity,
    bool $verify = TRUE,
  ): bool {
    $sourceFieldName = '';
    if ($entity instanceof MediaInterface && $this->checkMediaSourceIsFile($entity)) {
      // Get the source.
      $source = $entity->getSource();
      // Get the source field name.
      $sourceFieldName = $source->getConfiguration()['source_field'];
      // Get the source file id.
      $sourceFileId = (int) $source->getSourceFieldValue($entity);
      // Submit the source file field before processing the media entity.
      $fileUuid = $this->submitSourceFileEntity($entity, $accessToken, $url, $sourceFileId, $sourceFieldName, $verify);

      if (empty($fileUuid)) {
        // Return false if the file entity for the media element was not saved.
        return FALSE;
      }
    }
    elseif ($entity instanceof FieldableEntityInterface) {
      $fields = $entity->getFields();
      /** @var \Drupal\Core\Field\FieldItemListInterface $field */
      foreach ($fields as $field) {
        if ($field->getFieldDefinition()->getType() === 'image' && !$field->isEmpty()) {
          $fileEntity = $field->first()->entity;
          $sourceFieldName = $field->getName();

          $fileUuid = $this->submitSourceFileEntity($entity, $accessToken, $url, (int) $fileEntity->id(), $sourceFieldName);

          if (empty($fileUuid)) {
            // Return false if the file entity for the
            // media element was not saved.
            return FALSE;
          }
        }
      }
    }

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
          'relationships' => $normalizedEntity['data']['relationships'],
        ],
      ],
      'headers' => [
        'Content-Type' => 'application/vnd.api+json',
        'Authorization' => "Bearer {$accessToken}",
      ],
    ];

    // Alter the entity attributes before submission.
    $this->alterEntityAttributes($payload['json']['data']['attributes'], $entity);

    if (!empty($fileUuid)) {
      // Alter the media entity file uuid.
      $normalizedEntity['data']['relationships']["{$sourceFieldName}"]['data']['id'] = $fileUuid;
      $payload['json']['data']['relationships']["{$sourceFieldName}"] = $normalizedEntity['data']['relationships']["{$sourceFieldName}"];
      unset($payload['json']['data']['attributes']['langcode']);
    }

    // Unset the langcode regardless, getting permission errors.
    // https://www.drupal.org/project/drupal/issues/2794431
    unset($payload['json']['data']['attributes']['langcode']);

    // Alter the entity relationships before submission.
    $this->alterEntityRelationships($payload['json']['data']['relationships']);

    $this->setParagraphEntityRevisionIds($payload['json']['data']['relationships'], $entity, $accessToken, $url);

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
   * Set the paragraph entity revision ids on the remote entity.
   *
   * @param array $relationships
   *   The relationships array returned from the normalized entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being syndicated.
   * @param string $accessToken
   *   The access token to connect to json api.
   * @param \Drupal\Core\Url $url
   *   The url of the endpoint.
   */
  protected function setParagraphEntityRevisionIds(array &$relationships, EntityInterface $entity, string $accessToken, Url $url): void {
    // Get referenced entities.
    $references = $entity->referencedEntities();
    if (empty($references)) {
      return;
    }

    // Loop through the referenced entities and fetch paragraphs from the api.
    /** @var \Drupal\Core\Entity\EntityInterface $referencedEntity */
    foreach ($references as $referencedEntity) {
      // We only care about paragraph entities.
      if (!$referencedEntity instanceof ParagraphInterface) {
        continue;
      }

      $uuid = $referencedEntity->get('uuid')->value;

      $remoteEntity = $this->fetchEntityFromApi($accessToken, $url, $referencedEntity);

      if (!property_exists($remoteEntity, 'attributes') || !property_exists($remoteEntity->attributes, 'drupal_internal__revision_id')) {
        return;
      }

      $remoteRevisionId = (int) $remoteEntity->attributes->drupal_internal__revision_id;

      $this->setRemoteParagraphRevisionId($relationships, $uuid, $remoteRevisionId);

    }
  }

  /**
   * Ensure that the media entity source field is a file.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity to check.
   *
   * @return bool
   *   Return true if the source entity is a File.
   */
  protected function checkMediaSourceIsFile(MediaInterface $media): bool {
    $source = $media->getSource();

    if ($source instanceof File) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Submit the source file for a media entity or legacy file field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to post with json api.
   * @param string $accessToken
   *   The access token to connect to the hub site.
   * @param \Drupal\Core\Url $url
   *   The url of the hub site.
   * @param int $fileId
   *   The file id of the file to submit to json api.
   * @param string $fieldName
   *   The name of the field to upload the file to.
   * @param bool $verify
   *   Verify the SSL connection.
   *
   * @return string|null
   *   The uuid of the new file or null.
   */
  protected function submitSourceFileEntity(
    EntityInterface $entity,
    string $accessToken,
    Url $url,
    int $fileId,
    string $fieldName,
    bool $verify = TRUE,
  ): ?string {
    $filePath = $this->ecmsApiHelper->getFilePath($fileId);

    // Guard against an empty filepath.
    if (empty($filePath)) {
      return NULL;
    }

    $pathParts = explode('/', $filePath);
    $filename = array_pop($pathParts);

    $payload = [
      'headers' => [
        'Content-Type' => "application/octet-stream",
        'Authorization' => "Bearer {$accessToken}",
        'Accept' => "application/vnd.api+json",
        'Content-Disposition' => 'file; filename="' . $filename . '"',
      ],
      'body' => fopen($filePath, 'r'),
      'verify' => $verify,
    ];

    $endpoint = $this->getFileEndpointUrl($entity, $fieldName, $url);

    try {
      $request = $this->httpClient->request('POST', $endpoint, $payload);
    }
    catch (GuzzleException $exception) {
      return NULL;
    }

    if ($request->getStatusCode() === 201) {
      $body = $request->getBody();
      $contents = $body->getContents();
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
      if (!property_exists($json, 'data') || !property_exists($json->data, 'id')) {
        return NULL;
      }

      // Return the uuid of the newly created file.
      return $json->data->id;
    }

    // Default to NULL.
    return NULL;
  }

  /**
   * Get the endpoint of a file upload through json api.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity with a file upload required.
   * @param string $fieldName
   *   The field name of the file upload.
   * @param \Drupal\Core\Url $url
   *   The URL of the endpoint.
   *
   * @return string
   *   The string to the file entpoint.
   */
  private function getFileEndpointUrl(EntityInterface $entity, string $fieldName, Url $url): string {
    $apiEndpoint = self::API_ENDPOINT;
    $trimmedUrl = rtrim($url->toString(), '/');
    return "{$trimmedUrl}/{$apiEndpoint}/{$entity->getEntityTypeId()}/{$entity->bundle()}/{$fieldName}";
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
   * @param bool $verify
   *   Verify the SSL connection.
   *
   * @return string|null
   *   The http method to use for submission or null on error.
   */
  protected function checkEntityExists(
    string $accessToken,
    Url $url,
    EntityInterface $entity,
    bool $verify = TRUE,
  ): ?string {
    // Get the endpoint and assume a patch to append the UUID to the url.
    $endpoint = $this->getEndpointUrl($url, $entity, 'PATCH');

    // Pass the access token so we can get unpublished entities.
    $payload = [
      'headers' => [
        'Content-Type' => 'application/vnd.api+json',
        'Authorization' => "Bearer {$accessToken}",
      ],
      'verify' => $verify,
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
   * Fetch an entity from the endpoint if it exists.
   *
   * @param string $accessToken
   *   The access token to request the entity.
   * @param \Drupal\Core\Url $url
   *   The URL of the endpoint.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to try and fetch.
   * @param bool $verify
   *   Verify the SSL connection.
   *
   * @return object|null
   *   Return the object decoded from json or null if not exists or error.
   */
  protected function fetchEntityFromApi(
    string $accessToken,
    Url $url,
    EntityInterface $entity,
    bool $verify = TRUE,
  ): ?object {
    // Get the endpoint and assume a patch to append the UUID to the url.
    $endpoint = $this->getEndpointUrl($url, $entity, 'PATCH');

    // Pass the access token so we can get unpublished entities.
    $payload = [
      'headers' => [
        'Content-Type' => 'application/vnd.api+json',
        'Authorization' => "Bearer {$accessToken}",
      ],
      'verify' => $verify,
    ];

    try {
      $request = $this->httpClient->request('GET', $endpoint, $payload);
    }
    catch (GuzzleException $exception) {
      return NULL;
    }

    // If we receive a 200, the entity already exists.
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
    $trimmedUrl = rtrim($url->toString(), '/');

    // Append the language id if it is not the default language.
    if (!$language->isDefault()) {
      return "{$trimmedUrl}/{$languageEndpoint}/{$endPoint}/{$entityPath}";
    }

    return "{$trimmedUrl}/{$endPoint}/{$entityPath}";
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

      if ($entity instanceof ParagraphInterface) {
        foreach ($keys as $key) {
          // If the attribute is disallowed, remove it.
          if (in_array($key, self::NO_API_PARAGRAPH_ONLY)) {
            unset($attributes[$key]);
          }
        }
      }
    }

    foreach ($attributes as $key => $value) {
      // Remove `processed` keys from text fields.
      // @see: https://www.drupal.org/project/drupal/issues/2972988
      // @see: https://www.drupal.org/project/drupal/issues/2984466
      if (is_array($value) && array_key_exists('processed', $value)) {
        unset($attributes[$key]['processed']);
      }
    }
  }

  /**
   * Alter the relationships of the JSON Api entity.
   *
   * @param array $relationships
   *   Associative array of entity relationships to send with JSON API.
   */
  private function alterEntityRelationships(array &$relationships): void {
    $keys = array_keys($relationships);

    foreach ($keys as $key) {
      // If the attribute is disallowed, remove it.
      if (in_array($key, self::NO_RELATIONSHIPS_API)) {
        unset($relationships[$key]);
      }
    }
  }

  /**
   * Get the content types from the hub by machine name.
   *
   * @param \Drupal\Core\Url $url
   *   The url of the hub to get the content type uuid.
   * @param array $types
   *   The machine name of the content types to retrieve.
   * @param bool $verify
   *   Verify the SSL connection.
   *
   * @return array|null
   *   Return the data array from the json api or null if an error occurs.
   */
  protected function getContentTypes(
    Url $url,
    array $types,
    bool $verify = TRUE,
  ): ?array {
    $filter = [];

    // Loop through the content types and build filters.
    foreach ($types as $key => $value) {
      $filter["filter[type-{$key}][condition][path]"] = "drupal_internal__type";
      $filter["filter[type-{$key}][condition][operator]"] = "=";
      $filter["filter[type-{$key}][condition][value]"] = "{$value}";
    }

    $queryParams = http_build_query($filter);
    $apiEndpoint = self::API_ENDPOINT;
    $trimmedUrl = rtrim($url->toString(), '/');
    $endpoint = "{$trimmedUrl}/{$apiEndpoint}/node_type/node_type?{$queryParams}";

    try {
      $request = $this->httpClient->request("GET", $endpoint, ['verify' => $verify]);
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

  /**
   * Set the remote paragraph revision id.
   *
   * @param array $data
   *   The data to search for the revision id.
   * @param string $uuid
   *   The UUID of the entity to search.
   * @param int $revisionId
   *   The new revision id of the remote entitiy.
   */
  private function setRemoteParagraphRevisionId(array &$data, string $uuid, int $revisionId): void {
    foreach ($data as $key => &$value) {
      if ($key === 'id' && $data[$key] === $uuid) {
        // Set the revision ID from the remote entity.
        $data['meta']['target_revision_id'] = $revisionId;
      }

      if (is_array($value)) {
        $this->setRemoteParagraphRevisionId($value, $uuid, $revisionId);
      }
    }
  }

}
