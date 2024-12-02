<?php

declare(strict_types=1);

namespace Drupal\ecms_api_press_release_publisher;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Installer for the press release publisher.
 */
class PressReleaseInstaller {

  const PROD_API_ENDPOINT = 'https://rigov.ecms.ri.gov/EcmsApi';

  const TEST_API_ENDPOINT = 'https://rigov.test-riecms.acsitefactory.com/EcmsApi';

  const DEV_API_ENDPOINT = 'https://rigov.dev-riecms.acsitefactory.com/EcmsApi';

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
    * PressReleaseInstaller constructor.
    *
    * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
    *   The entity_type.manager service.
    */
    public function __construct(EntityTypeManagerInterface $entityTypeManager) {
      $this->entityTypeManager = $entityTypeManager;
    }

  /**
   * The method to handle installation.
   */
    public function install(): void {
      $endpoint = $this->getApiEndpoint();
      $storage = $this->entityTypeManager->getStorage('ecms_api_site');
      // @todo Load the ecms_api_site entities and check for the main hub.
      $registeredSite = $storage->loadByProperties([
        'api_host' => $endpoint,
      ]);

      // Return early if it already exists.
      if (!empty($registeredSite)) {
        return;
      }

      // If the main hub is not found, create it.
      try {
        $host = $storage->create([
          'name' => 'Main Hub',
          'api_host' => $endpoint,
          'content_type' => [
            'press_release' => TRUE,
          ],
        ]);

        $host->save();
      }
      catch (EntityStorageException $e) {
        var_dump($e);
        // Trap any errors.
        return;
      }
    }

  /**
   * The API endpoint based on the environment variable.
   *
   * @return string
   *   The API endpoint.
   */
    private function getApiEndpoint(): string {
      $env = getenv('AH_SITE_ENVIRONMENT');
      switch ($env) {
        case 'test':
          return self::TEST_API_ENDPOINT;
        case 'dev':
          return self::DEV_API_ENDPOINT;
        default:
          return self::PROD_API_ENDPOINT;
      }
    }

}
