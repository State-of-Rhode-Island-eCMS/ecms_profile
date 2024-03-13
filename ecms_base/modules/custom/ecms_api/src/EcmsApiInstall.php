<?php

declare(strict_types=1);

namespace Drupal\ecms_api;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Handles installation tasks for the ecms_api module.
 *
 * @package Drupal\ecms_api
 */
class EcmsApiInstall {

  /**
   * The API path prefix.
   */
  const API_PREFIX = 'EcmsApi';

  /**
   * The oauth public key path.
   */
  const OAUTH_PUBLIC_KEY = '../ecms_api_public.key';

  /**
   * The oauth private key path.
   */
  const OAUTH_PRIVATE_KEY = '../ecms_api_private.key';

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * EcmsApiInstall constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * Set the required configuration for the eCMS API.
   */
  public function installEcmsApi(): void {
    // Update the json api configuration.
    $this->updateJsonApiConfiguration();

    // Update the json api extras configuration.
    $this->updateJsonApiExtraConfiguration();

    // Update the simple oauth configuration.
    $this->updateSimpleOauthConfiguration();
  }

  /**
   * Update the configuration for the Json API.
   */
  private function updateJsonApiConfiguration(): void {
    $config = $this->configFactory->getEditable('jsonapi.settings');

    $config->set('read_only', FALSE);
    $config->save();
  }

  /**
   * Update the JSON API Extras configuration.
   */
  private function updateJsonApiExtraConfiguration(): void {
    $config = $this->configFactory->getEditable('jsonapi_extras.settings');

    $config->set('path_prefix', self::API_PREFIX);
    $config->save();
  }

  /**
   * Update the Simple OAuth keys.
   */
  private function updateSimpleOauthConfiguration(): void {
    $config = $this->configFactory->getEditable('simple_oauth.settings');

    $config->set('public_key', self::OAUTH_PUBLIC_KEY);
    $config->set('private_key', self::OAUTH_PRIVATE_KEY);
    $config->save();
  }

}
