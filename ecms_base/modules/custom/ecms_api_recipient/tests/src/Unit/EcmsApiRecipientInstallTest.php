<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_api_recipient\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecms_api_recipient\EcmsApiRecipientInstall;
use Drupal\Tests\UnitTestCase;

/**
 * Class EcmsApiRecipientInstallTest
 *
 * @package Drupal\Tests\ecms_api_recipient\Unit
 * @group ecms
 * @group ecms_api
 * @group ecms_api_recipient
 */
class EcmsApiRecipientInstallTest extends UnitTestCase {

  const USER_ID = 123;

  const CLIENT_ID = 'client-uuid-test';

  const CLIENT_SECRET = 'client-secret-test';

  const ACCOUNT = [
    'name' => 'ecms_api_recipient',
    'mail' => 'ecms_api_recipient@ecms.com',
    'roles' => ['ecms_api_recipient'],
  ];

  const CONSUMER = [
    'user_id' => 123,
    'roles' => ['ecms_api_recipient'],
    'label' => 'eCMS Recipient',
    'description' => 'An oAuth client to receive conten from an eCMS publishing site.',
    'third_party' => FALSE,
    'uuid' => self::CLIENT_ID,
    'secret' => self::CLIENT_SECRET,
  ];

  private $entityTypeManager;

  private $configFactory;

  private $apiConfig;

  private $userEntity;
  private $consumerEntity;

  protected function setUp(): void {
    // Mock the immutable config for the recipient module.
    $this->apiConfig = $this->createMock(ImmutableConfig::class);

    // Mock the config.factory service.
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->configFactory->expects($this->once())
      ->with('get')
      ->willReturn($this->apiConfig);

    // Mock the entity_type.manager service.
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $this->userEntity = $this->createMock(EntityInterface::class);
    $this->userEntity->expects($this->once())
      ->method('id')
      ->willReturn(self::USER_ID);

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  public function testInstallEcmsApiRecipient(): void {
    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->expects($this->once())
      ->method('create')
      ->with(self::ACCOUNT)
      ->willReturn($this->userEntity);

    $this->userEntity->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $consumerEntity = $this->createMock(EntityInterface::class);
    $consumerStorage = $this->createMock(EntityStorageInterface::class);
    $consumerStorage->expects($this->once())
      ->method('create')
      ->with(self::CONSUMER)
      ->willReturn($consumerEntity);

    $consumerEntity->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $this->apiConfig->expects($this->exactly(2))
      ->method('get')
      ->willReturnOnConsecutiveCalls(
        [self::CLIENT_ID],
        [self::CLIENT_SECRET]
      );

    $ecmsApiRecipientInstall = new EcmsApiRecipientInstall($this->entityTypeManager, $this->configFactory);

    $ecmsApiRecipientInstall->installEcmsApiRecipient();

  }

}