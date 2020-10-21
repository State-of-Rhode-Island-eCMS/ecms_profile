<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_recipient;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\ecms_api\EcmsApiBase;
use Drupal\jsonapi_extras\EntityToJsonApi;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\RequestStack;

class EcmsApiCreateNotifications extends EcmsApiBase {

  const API_SCOPE = 'ecms_api_recipient';

  private $configFactory;

  private $requestStack;

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * EcmsApiCreateNotifications constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The http_client service.
   * @param \Drupal\jsonapi_extras\EntityToJsonApi $entityToJsonApi
   *   The jsonapi_extras.entity.to_jsonapi service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request_stack service.
   */
  public function __construct(
    ClientInterface $httpClient,
    EntityToJsonApi $entityToJsonApi,
    ConfigFactoryInterface $configFactory,
    RequestStack $requestStack,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($httpClient, $entityToJsonApi);

    $this->configFactory = $configFactory;
    $this->requestStack = $requestStack;
    $this->entityTypeManager = $entityTypeManager;
  }

  public function createNotificationFromJson(object $jsonNodeObject): bool {
    // Get the domain of the current site.
    $siteUrl = $this->getSiteUrl();

    // Guard against an empty site url.
    if (empty($siteUrl)) {
      return FALSE;
    }

    // Get the id/secret/scope from configuration.
    $clientId = $this->getApiClientId();
    $clientSecret = $this->getApiClientSecret();

    // Get the access token.
    $accessToken = $this->getAccessToken($siteUrl, $clientId, $clientSecret, self::API_SCOPE);

    // Guard against an empty access token.
    if (empty($accessToken)) {
      return FALSE;
    }

    // POST the entity to the API.
    $status = $this->submitEntityAsJson($accessToken, $siteUrl, $jsonNodeObject);
    //$this->submitEntity()
    //$this->postEntity($accessToken, $hubUrl, $apiSiteEntity);
    return $status;
  }

  private function submitEntityAsJson(string $accessToken, Url $url, object $data): bool {
    // Query the endpoint to get the correct HTTP method.
    // @todo: Use the entity type manager to query for the uuid of the object.
    $method = 'POST';
    $method = $this->checkEntityUuidExists($data->id);
//
    // Only allow certain methods to be submitted.
    if (empty($method) || !in_array($method, self::ALLOWED_HTTP_METHODS, TRUE)) {
      return FALSE;
    }

    // Get the endpoint for the entity.
    $endpoint = $this->getEndpointUrlFromJson($url, $data, $method);

    // Convert the entity to a json resource array.
//    $normalizedEntity = $this->entityToJsonApi->normalize($entity);
//
//    if (empty($normalizedEntity['data']['attributes'])) {
//      return FALSE;
//    }

    $payload = [
      'json' => [
        'data' => $data,
      ],
      'headers' => [
        'Content-Type' => 'application/vnd.api+json',
        'Authorization' => "Bearer {$accessToken}",
      ],
    ];

//    // Alter the entity attributes before submission.
//    $this->alterEntityAttributes($payload['json']['data']['attributes'], $entity);

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

  private function getEndpointUrlFromJson(Url $url, object $data, string $method): string {
    // Break the node type on the '--'.
    $parts = explode('--', $data->type);

    if ($method === 'PATCH') {
      $parts[] = $data->id;
    }

    array_unshift($parts, self::API_ENDPOINT);

    $path = implode('/', $parts);

    return "{$url->toString()}/{$path}";

  }

  private function checkEntityUuidExists(string $uuid): string {
    $storage = $this->entityTypeManager->getStorage('node');

    $entities = $storage->loadByProperties(['uuid' => $uuid]);

    // If no entities were found, return POST.
    if (empty($entities)) {
      return 'POST';
    }

    // The entity exists at this point, so we want to PATCH it.
    return 'PATCH';

  }

  /**
   * The the oauth client id from configuration.
   *
   * @return string
   *   The configuration value for oauth_client_id.
   */
  private function getApiClientId(): string {
    return $this->configFactory->get('ecms_api_recipient.settings')->get('oauth_client_id');
  }

  /**
   * Get the api client secret from configuration.
   *
   * @return string
   *   The configuration value for the api_main_hub_client_secret.
   */
  private function getApiClientSecret(): string {
    return $this->configFactory->get('ecms_api_recipient.settings')->get('oauth_client_secret');
  }

  /**
   * Get the current site host as a URL object.
   *
   * @return \Drupal\Core\Url|null
   *   The URL of the current site or null if any errors were thrown.
   */
  private function getSiteUrl(): ?Url {
    $httpHost = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();

    // Trap any arguments in case the provided URI is invalid.
    try {
      $url = Url::fromUri($httpHost);
    }
    catch (\InvalidArgumentException $e) {
      return NULL;
    }

    return $url;
  }


}
