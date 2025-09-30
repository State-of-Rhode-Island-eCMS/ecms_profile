<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_api\Unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ecms_api\EcmsApiInstall;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for the EcmsApiInstall class.
 *
 * @package Drupal\Tests\ecms_api\Unit
 *
 */
#[Group("ecms_api")]
#[Group("ecms")]
#[CoversClass(\Drupal\ecms_api\EcmsApiInstall::class)]
class EcmsApiInstallTest extends UnitTestCase {

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
   * The config.factory service mock.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $configFactory;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the json api config settings.
    $jsonConfig = $this->createMock(Config::class);
    $jsonConfig->expects($this->once())
      ->method('set')
      ->with('read_only', FALSE)
      ->willReturnSelf();
    $jsonConfig->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    // Mock the json api extra config settings.
    $jsonExtraConfig = $this->createMock(Config::class);
    $jsonExtraConfig->expects($this->once())
      ->method('set')
      ->with('path_prefix', self::API_PREFIX)
      ->willReturnSelf();
    $jsonExtraConfig->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    // Mock the oauth config settings.
    $oauthConfig = $this->createMock(Config::class);
    $oauthConfig->expects($this->exactly(2))
      ->method('set')
      ->will($this->returnValueMap([
        ['public_key', self::OAUTH_PUBLIC_KEY, $oauthConfig],
        ['private_key', self::OAUTH_PRIVATE_KEY, $oauthConfig],
      ]));
    $oauthConfig->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    // Setup the configFactory mock.
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);

    $this->configFactory->expects($this->exactly(3))
      ->method('getEditable')
      ->will($this->returnValueMap([
        ['jsonapi.settings', $jsonConfig],
        ['jsonapi_extras.settings', $jsonExtraConfig],
        ['simple_oauth.settings', $oauthConfig],
      ]));
  }

  /**
   * Test the installEcmsApi method.
   */
  public function testEcmsApiInstall(): void {
    $ecmsApiInstall = new EcmsApiInstall($this->configFactory);

    $ecmsApiInstall->installEcmsApi();
  }

}
