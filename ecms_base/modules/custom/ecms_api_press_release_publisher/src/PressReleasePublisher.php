<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_press_release_publisher;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\PostponeItemException;
use Drupal\Core\Url;
use Drupal\ecms_api\EcmsApiBase;
use Drupal\ecms_api\EcmsApiHelper;
use Drupal\file\FileInterface;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\media\MediaInterface;
use GuzzleHttp\ClientInterface;

class PressReleasePublisher extends EcmsApiBase {

  /**
   * The ecms_api_recipient.settings configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * PressReleasePublisher constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The http_client service.
   * @param \Drupal\jsonapi_extras\EntityToJsonApi $entityToJsonApi
   *    The jsonapi_extras.entity.to_jsonapi service.
   */
  public function __construct(ClientInterface $httpClient, EntityToJsonApi $entityToJsonApi, EcmsApiHelper $ecmsApiHelper, ConfigFactoryInterface $configFactory) {
    parent::__construct($httpClient, $entityToJsonApi, $ecmsApiHelper);

    $this->config = $configFactory->get('ecms_api_recipient.settings');
  }

  /**
   * Post the entity to the hub.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to publish to the hub.
   *
   * @return bool
   *   True if the entity was saved to the hub.
   */
  public function postEntity(EntityInterface $entity): bool {

    $url = $this->getHubUri();

    // Guard against a non-url.
    if (empty($url)) {
      return FALSE;
    }

    $client_id = $this->getClientId();
    $client_secret = $this->getClientSecret();
    $scope = $this->getClientScope();

    $accessToken = $this->getAccessToken($url, $client_id, $client_secret, $scope);

    // Ensure an access token was granted.
    if (empty($accessToken)) {
      return FALSE;
    }

    if ($this->submitEntity($accessToken, $url, $entity)) {
      // If the entity was created, return true.
      return TRUE;
    }

    // Default to false.
    return FALSE;
  }

//  /**
//   * Alter the attributes of the JSON Api entity.
//   *
//   * @param array $attributes
//   *   Associative array of entity attributes to send with JSON API.
//   * @param \Drupal\Core\Entity\EntityInterface|null $entity
//   *   The entity being submitted or null.
//   */
//  protected function alterEntityAttributes(array &$attributes, ?EntityInterface $entity = NULL): void {
//    $keys = array_keys($attributes);
//
//    foreach ($keys as $key) {
//      // If the attribute is disallowed, remove it.
//      if (in_array($key, self::NO_API_FIELD_NAMES)) {
//        unset($attributes[$key]);
//      }
//    }
//
//    if ($entity instanceof MediaInterface) {
//      if (in_array('langcode', $keys)) {
//        unset($attributes['langcode']);
//      }
//    }
//
//    // Add the uuid to the attributes.
//    if ($entity) {
//      $attributes['uuid'] = $entity->uuid();
//    }
//  }

  /**
   * Get the hub uri from configuration.
   *
   * @return Url|null
   *   The client id for the recipient site.
   */
  private function getHubUri(): ?Url {
    $path = $this->config->get('api_main_hub');

    if (empty($path)) {
      return NULL;
    }

    try {
      return Url::fromUri($path);
    }
    catch (\InvalidArgumentException $e) {
      return NULL;
    }
  }

  /**
   * Get the client id from configuration.
   *
   * @return string
   *   The client id for the recipient site.
   */
  private function getClientId(): string {
    return $this->config->get('api_main_hub_client_id');
  }

  /**
   * Get the client secret from configuration.
   *
   * @return string
   *   The client secret for the recipient site.
   */
  private function getClientSecret(): string {
    return $this->config->get('api_main_hub_client_secret');
  }

  /**
   * Get the client scope from configuration.
   *
   * @return string
   *   The client scope for the recipient site.
   */
  private function getClientScope(): string {
    return $this->config->get('api_main_hub_scope');
  }

}
