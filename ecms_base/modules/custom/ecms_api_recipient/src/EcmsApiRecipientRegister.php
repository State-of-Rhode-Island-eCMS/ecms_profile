<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_recipient;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\ecms_api\EcmsApiBase;
use Drupal\jsonapi_extras\EntityToJsonApi;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class EcmsApiRecipientRegister.
 *
 * @package Drupal\ecms_api_recipient
 */
class EcmsApiRecipientRegister extends EcmsApiBase {

  const INSTALLED_CONTENT_TYPES = ['notification'];

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  private $requestStack;

  /**
   * EcmsApiRecipientRegister constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   * @param \Drupal\jsonapi_extras\EntityToJsonApi $entityToJsonApi
   */
  public function __construct(ClientInterface $httpClient, EntityToJsonApi $entityToJsonApi, ConfigFactoryInterface $configFactory, RequestStack $requestStack) {
    parent::__construct($httpClient, $entityToJsonApi);

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


    // Get the content types from the hub.
    $allowedContentTypes = $this->getContentTypes($hubUrl, self::INSTALLED_CONTENT_TYPES);
    if (empty($allowedContentTypes)) {
      return;
    }

    // @todo: build the EcmsApiSite entity to pass to json api.
    $apiSiteEntity = $this->getSiteEntity($siteUrl, $allowedContentTypes);
    // Get the access token.
    // @todo: Get the id/secret/scope from configuration.
    $accessToken = $this->getAccessToken($hubUrl, 'REDACTED', 'REDACTED', 'ecms_api_publisher');

    // Guard against an empty access token.
    if (empty($accessToken)) {
      return;
    }

    // @todo: POST the entity to the API.
    $this->postEntity($accessToken, $hubUrl, $apiSiteEntity);
    // @todo: Save the uuid of the entity for updating/deleting on uninstall.
  }

  /**
   * Save the ecms_api_site entity.
   *
   * @param string $accessToken
   * @param \Drupal\Core\Url $url
   * @param array $entityArray
   *
   * @return bool
   */
  protected function postEntity(string $accessToken, Url $url, array $entityArray): bool {
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

//  private function normalizeEcmsApiSiteEntity(EntityInterface $entity): array {
////  $test = new JsonApiDocumentTopLevel($entity, )
////    $this->serializer->serialize(
////      new JsonApiDocumentTopLevelNormalizer($entity),
////      'api_json',
////      $this->calculateContext($entity)
////    );
//    // @todo: Build the attributes for the entity.
//    return [];
//  }

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
    catch (InvalidArgumentException $e) {
      return NULL;
    }

    return $url;
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
    catch (InvalidArgumentException $e) {
      return NULL;
    }

    return $url;
  }

  /**
   * Create an ecms_api_site entity.
   *
   * @param \Drupal\Core\Url $siteUrl
   *   The URL of the site.
   *
   * @return array
   *   The ecms_api_site entity to submit to json api.
   */
  private function getSiteEntity(Url $siteUrl, array $allowedContentTypes): array {
    // @todo: Add the content types to post.
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