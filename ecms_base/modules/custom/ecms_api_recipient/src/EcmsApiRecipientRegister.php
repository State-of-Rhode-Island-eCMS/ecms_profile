<?php

declare(strict_types=1);

namespace Drupal\ecms_api_recipient;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\ecms_api\EcmsApiBase;
use Drupal\ecms_api\EcmsApiHelper;
use Drupal\jsonapi_extras\EntityToJsonApi;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handle registering with the hub site.
 *
 * @package Drupal\ecms_api_recipient
 */
class EcmsApiRecipientRegister extends EcmsApiBase {

  /**
   * The content types to register with the hub by default.
   */
  const INSTALLED_CONTENT_TYPES = ['notification', 'emergency_notification'];

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * The request_stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * EcmsApiRecipientRegister constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The http_client service.
   * @param \Drupal\jsonapi_extras\EntityToJsonApi $entityToJsonApi
   *   The jsonapi_extras.entity.to_jsonapi service.
   * @param \Drupal\ecms_api\EcmsApiHelper $ecmsApiHelper
   *   The ecms_api_helper service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request_stack service.
   */
  public function __construct(ClientInterface $httpClient, EntityToJsonApi $entityToJsonApi, EcmsApiHelper $ecmsApiHelper, ConfigFactoryInterface $configFactory, RequestStack $requestStack) {
    parent::__construct($httpClient, $entityToJsonApi, $ecmsApiHelper);

    $this->configFactory = $configFactory;
    $this->requestStack = $requestStack;
  }

  /**
   * Register this site with the main hub.
   */
  public function registerSite(): void {
    // Get the domain of the current site.
    $siteUrl = $this->getSiteUrl();

    // Guard against an empty site url.
    if (empty($siteUrl)) {
      return;
    }

    // Get the hub url.
    $hubUrl = $this->getApiHub();

    // Guard against a null hub url.
    if (empty($hubUrl)) {
      return;
    }

    $verifySsl = $this->configFactory
      ->get('ecms_api_recipient.settings')
      ->get('verify_ssl') ?? TRUE;

    // Get the content types from the hub.
    $allowedContentTypes = $this->getContentTypes(
      $hubUrl,
      self::INSTALLED_CONTENT_TYPES,
      $verifySsl
    );

    if (empty($allowedContentTypes)) {
      return;
    }

    // Build the EcmsApiSite entity to pass to json api.
    $apiSiteEntity = $this->getSiteEntity($siteUrl, $allowedContentTypes);

    // Get the id/secret/scope from configuration.
    $clientId = $this->getApiClientId();
    $clientSecret = $this->getApiClientSecret();
    $clientScope = $this->getApiScope();

    // Get the access token.
    $accessToken = $this->getAccessToken($hubUrl, $clientId, $clientSecret, $clientScope, $verifySsl);

    // Guard against an empty access token.
    if (empty($accessToken)) {
      return;
    }


    // POST the entity to the API.
    $this->postEntity($accessToken, $hubUrl, $apiSiteEntity, $verifySsl);
  }

  /**
   * Save the ecms_api_site entity to the hub.
   *
   * @param string $accessToken
   *   The access token. @see EcmsApiBase::getAccessToken().
   * @param \Drupal\Core\Url $url
   *   The URL of the hub site.
   * @param array $entityArray
   *   The entity to save. @see self::getSiteEntity().
   *
   * @return bool
   *   Returns true on successful creation.
   */
  protected function postEntity(string $accessToken, Url $url, array $entityArray, bool $verify = TRUE): bool {
    // Get the endpoint for the entity.
    $apiEndpoint = self::API_ENDPOINT;

    // Create the endpoint url.
    $endPoint = "{$url->toString()}/{$apiEndpoint}/ecms_api_site/ecms_api_site";

    $payload = [
      'json' => [
        'data' => $entityArray,
      ],
      'headers' => [
        'Content-Type' => 'application/vnd.api+json',
        'Authorization' => "Bearer {$accessToken}",
      ],
      'verify' => $verify,
    ];

    try {
      $request = $this->httpClient->request('POST', $endPoint, $payload);
    }
    catch (GuzzleException $exception) {
      return FALSE;
    }

    // 201 means successfully created the entity.
    if ($request->getStatusCode() === 201) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get the hub host from configuration as a URL object.
   *
   * @return \Drupal\Core\Url|null
   *   The URL of the hub site or null if any errors were thrown.
   */
  private function getApiHub(): ?Url {
    $hubHost = $this->configFactory->get('ecms_api_recipient.settings')->get('api_main_hub');

    try {
      $url = Url::fromUri($hubHost);
    }
    catch (\InvalidArgumentException $e) {
      return NULL;
    }

    return $url;
  }

  /**
   * The the api client id from configuration.
   *
   * @return string
   *   The configuration value for api_main_hub_client_id.
   */
  private function getApiClientId(): string {
    return $this->configFactory->get('ecms_api_recipient.settings')->get('api_main_hub_client_id');
  }

  /**
   * Get the api client secret from configuration.
   *
   * @return string
   *   The configuration value for the api_main_hub_client_secret.
   */
  private function getApiClientSecret(): string {
    return $this->configFactory->get('ecms_api_recipient.settings')->get('api_main_hub_client_secret');
  }

  /**
   * Get the api scope from configuration.
   *
   * @return string
   *   The configuration value for the api_main_hub_scope.
   */
  private function getApiScope(): string {
    return $this->configFactory->get('ecms_api_recipient.settings')->get('api_main_hub_scope');
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

  /**
   * Create an ecms_api_site entity.
   *
   * @param \Drupal\Core\Url $siteUrl
   *   The URL of the site.
   * @param array $allowedContentTypes
   *   The content types to retrieve. @see EcmsApiBase::getContentTypes().
   *
   * @return array
   *   The ecms_api_site entity to submit to json api.
   */
  private function getSiteEntity(Url $siteUrl, array $allowedContentTypes): array {
    $data = [
      'type' => 'ecms_api_site--ecms_api_site',
      'attributes' => [
        'name' => $siteUrl->toUriString(),
        'api_host' => $siteUrl->toString(),
      ],
      'relationships' => [
        'content_type' => [
          'data' => $allowedContentTypes,
        ],
      ],
    ];

    return $data;
  }

}
