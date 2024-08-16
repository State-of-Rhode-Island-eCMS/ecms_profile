<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_api_publisher\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecms_api_publisher\EcmsApiPublisherInstall;
use Drupal\Tests\UnitTestCase;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;

/**
 * Unit tests for the EcmsApiPublisherInstall class.
 *
 * @package Drupal\Tests\ecms_api_publisher\Unit
 * @group ecms
 * @group ecms_api
 * @group ecms_api_publisher
 */
class EcmsApiPublisherInstallTest extends UnitTestCase {

  /**
   * The email account to test with.
   */
  const USER_MAIL = 'test@oomphinc.com';

  /**
   * The publisher role to test with.
   */
  const PUBLISHER_ROLE = 'ecms_api_publisher';

  /**
   * The password to test with.
   */
  const PASSWORD = 'TestPassword123';

  /**
   * The client id to test with.
   */
  const CLIENT_ID = 'test-client-id';

  /**
   * The client secret to test with.
   */
  const CLIENT_SECRET = 'test-client-secret';

  /**
   * The user profile information to test with.
   */
  const USER_PROFILE_INFORMATION = [
    'name' => 'ecms_api_publisher',
    'mail' => self::USER_MAIL,
    'roles' => [self::PUBLISHER_ROLE],
    'pass' => self::PASSWORD,
    'status' => 1,
  ];

  /**
   * Mock of the entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $entityTypeManager;

  /**
   * Mock of the config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $configFactory;

  /**
   * Mock of the configuration values for the module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|\PHPUnit\Framework\MockObject\MockObject
   */
  private $immutableConfig;

  /**
   * Mock the container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  private $container;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->immutableConfig = $this->createMock(ImmutableConfig::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('ecms_api_publisher.settings')
      ->willReturn($this->immutableConfig);

    $this->container = new ContainerBuilder();
    $this->container->set('entity_type.manager', $this->entityTypeManager);
    $this->container->set('config.factory', $this->configFactory);
    $this->container->set('string_translation', $this->getStringTranslationStub());

    \Drupal::setContainer($this->container);

  }

  /**
   * Test the uninstallEcmsApiPublisher() method.
   */
  public function testUninstallEcmsApiPublisher(): void {
    // User Account.
    $userAccount = $this->createMock(UserInterface::class);
    $userAccount->expects($this->once())
      ->method('delete');

    // User Storage.
    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['name' => 'ecms_api_publisher'])
      ->willReturn($userAccount);

    // Consumer.
    $consumerEntity = $this->createMock(EntityInterface::class);
    $consumerEntity->expects($this->once())
      ->method('delete');

    // Consumer Storage.
    $consumerStorage = $this->createMock(EntityStorageInterface::class);
    $consumerStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['uuid' => self::CLIENT_ID])
      ->willReturn($consumerEntity);

    // Publisher Role.
    $roleEntity = $this->createMock(RoleInterface::class);
    $roleEntity->expects($this->once())
      ->method('revokePermission')
      ->with('add ecms api site entities')
      ->willReturnSelf();

    // Role Storage.
    $roleStorage = $this->createMock(EntityStorageInterface::class);
    $roleStorage->expects($this->once())
      ->method('load')
      ->with(self::PUBLISHER_ROLE)
      ->willReturn($roleEntity);

    // Entity Type Manager.
    $this->entityTypeManager->expects($this->exactly(3))
      ->method('getStorage')
      ->withConsecutive(['user'], ['consumer'], ['user_role'])
      ->willReturnOnConsecutiveCalls(
        $userStorage,
        $consumerStorage,
        $roleStorage
      );

    // Run the uninstall.
    $ecmsApiPublisherInstall = new EcmsApiPublisherInstall($this->entityTypeManager, $this->configFactory);
    $ecmsApiPublisherInstall->uninstallEcmsApiPublisher();
  }

  /**
   * Test the installEcmsApiPublisher() method.
   */
  public function testInstallEcmsApiPublisher(): void {
    $this->immutableConfig->expects($this->exactly(3))
      ->method('get')
      ->withConsecutive(
        ['api_publisher_mail'], ['oauth_client_id'], ['oauth_client_secret'])
      ->willReturnOnConsecutiveCalls(
        self::USER_MAIL,
        self::CLIENT_ID,
        self::CLIENT_SECRET
      );

    $roleEntity = $this->createMock(RoleInterface::class);
    $roleEntity->expects($this->once())
      ->method('grantPermission')
      ->with('add ecms api site entities')
      ->willReturnSelf();
    $roleEntity->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $userAccount = $this->createMock(UserInterface::class);
    $userAccount->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $consumerEntity = $this->createMock(EntityInterface::class);
    $consumerEntity->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->expects($this->once())
      ->method('create')
      ->with(self::USER_PROFILE_INFORMATION)
      ->willReturn($userAccount);

    $consumerStorage = $this->createMock(EntityStorageInterface::class);
    $consumerStorage->expects($this->once())
      ->method('create')
      ->willReturn($consumerEntity);

    $roleStorage = $this->createMock(EntityStorageInterface::class);
    $roleStorage->expects($this->once())
      ->method('load')
      ->with(self::PUBLISHER_ROLE)
      ->willReturn($roleEntity);

    $this->entityTypeManager->expects($this->exactly(3))
      ->method('getStorage')
      ->withConsecutive(['user'], ['consumer'], ['user_role'])
      ->willReturnOnConsecutiveCalls($userStorage, $consumerStorage, $roleStorage);

    $ecmsApiPublisherInstall = $this->getMockBuilder(EcmsApiPublisherInstall::class)
      ->onlyMethods(['generatePassword'])
      ->setConstructorArgs([$this->entityTypeManager, $this->configFactory])
      ->getMock();
    $ecmsApiPublisherInstall->expects($this->once())
      ->method('generatePassword')
      ->willReturn(self::PASSWORD);

    $ecmsApiPublisherInstall->installEcmsApiPublisher();

  }

  /**
   * Test the installEcmsApiPublisher() method with an empty role.
   */
  public function testInstallEcmsApiPublisherEmptyRole(): void {
    $this->immutableConfig->expects($this->exactly(3))
      ->method('get')
      ->withConsecutive(
        ['api_publisher_mail'], ['oauth_client_id'], ['oauth_client_secret'])
      ->willReturnOnConsecutiveCalls(
        self::USER_MAIL,
        self::CLIENT_ID,
        self::CLIENT_SECRET
      );

    $roleEntity = $this->createMock(RoleInterface::class);
    $roleEntity->expects($this->never())
      ->method('grantPermission')
      ->with('add ecms api site entities')
      ->willReturnSelf();
    $roleEntity->expects($this->never())
      ->method('save')
      ->willReturnSelf();

    $userAccount = $this->createMock(UserInterface::class);
    $userAccount->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $consumerEntity = $this->createMock(EntityInterface::class);
    $consumerEntity->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->expects($this->once())
      ->method('create')
      ->with(self::USER_PROFILE_INFORMATION)
      ->willReturn($userAccount);

    $consumerStorage = $this->createMock(EntityStorageInterface::class);
    $consumerStorage->expects($this->once())
      ->method('create')
      ->willReturn($consumerEntity);

    $roleStorage = $this->createMock(EntityStorageInterface::class);
    $roleStorage->expects($this->once())
      ->method('load')
      ->with(self::PUBLISHER_ROLE)
      ->willReturn(NULL);

    $this->entityTypeManager->expects($this->exactly(3))
      ->method('getStorage')
      ->withConsecutive(['user'], ['consumer'], ['user_role'])
      ->willReturnOnConsecutiveCalls($userStorage, $consumerStorage, $roleStorage);

    $ecmsApiPublisherInstall = $this->getMockBuilder(EcmsApiPublisherInstall::class)
      ->onlyMethods(['generatePassword'])
      ->setConstructorArgs([$this->entityTypeManager, $this->configFactory])
      ->getMock();
    $ecmsApiPublisherInstall->expects($this->once())
      ->method('generatePassword')
      ->willReturn(self::PASSWORD);

    $ecmsApiPublisherInstall->installEcmsApiPublisher();

  }

  /**
   * Test the installEcmsApiPublisher() method with a role exception.
   */
  public function testInstallEcmsApiPublisherRoleException(): void {
    $this->immutableConfig->expects($this->exactly(3))
      ->method('get')
      ->withConsecutive(
        ['api_publisher_mail'], ['oauth_client_id'], ['oauth_client_secret'])
      ->willReturnOnConsecutiveCalls(
        self::USER_MAIL,
        self::CLIENT_ID,
        self::CLIENT_SECRET
      );

    $exception = $this->createMock(EntityStorageException::class);
    $roleEntity = $this->createMock(RoleInterface::class);
    $roleEntity->expects($this->once())
      ->method('grantPermission')
      ->with('add ecms api site entities')
      ->willReturnSelf();

    $roleEntity->expects($this->once())
      ->method('save')
      ->willThrowException($exception);

    $userAccount = $this->createMock(UserInterface::class);
    $userAccount->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $consumerEntity = $this->createMock(EntityInterface::class);
    $consumerEntity->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->expects($this->once())
      ->method('create')
      ->with(self::USER_PROFILE_INFORMATION)
      ->willReturn($userAccount);

    $consumerStorage = $this->createMock(EntityStorageInterface::class);
    $consumerStorage->expects($this->once())
      ->method('create')
      ->willReturn($consumerEntity);

    $roleStorage = $this->createMock(EntityStorageInterface::class);
    $roleStorage->expects($this->once())
      ->method('load')
      ->with(self::PUBLISHER_ROLE)
      ->willReturn($roleEntity);

    $this->entityTypeManager->expects($this->exactly(3))
      ->method('getStorage')
      ->withConsecutive(['user'], ['consumer'], ['user_role'])
      ->willReturnOnConsecutiveCalls($userStorage, $consumerStorage, $roleStorage);

    $ecmsApiPublisherInstall = $this->getMockBuilder(EcmsApiPublisherInstall::class)
      ->onlyMethods(['generatePassword'])
      ->setConstructorArgs([$this->entityTypeManager, $this->configFactory])
      ->getMock();
    $ecmsApiPublisherInstall->expects($this->once())
      ->method('generatePassword')
      ->willReturn(self::PASSWORD);

    $ecmsApiPublisherInstall->installEcmsApiPublisher();

  }

  /**
   * Test the installEcmsApiPublisher() method with a user exception.
   */
  public function testInstallEcmsApiPublisherUserException(): void {

    $this->immutableConfig->expects($this->once())
      ->method('get')
      ->with('api_publisher_mail')
      ->willReturn(self::USER_MAIL);

    $roleEntity = $this->createMock(RoleInterface::class);
    $roleEntity->expects($this->never())
      ->method('grantPermission')
      ->with('add ecms api site entities')
      ->willReturnSelf();
    $roleEntity->expects($this->never())
      ->method('save')
      ->willReturnSelf();

    $exception = $this->createMock(EntityStorageException::class);
    $userAccount = $this->createMock(UserInterface::class);
    $userAccount->expects($this->once())
      ->method('save')
      ->willThrowException($exception);

    $consumerEntity = $this->createMock(EntityInterface::class);
    $consumerEntity->expects($this->never())
      ->method('save')
      ->willReturnSelf();

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->expects($this->once())
      ->method('create')
      ->with(self::USER_PROFILE_INFORMATION)
      ->willReturn($userAccount);

    $consumerStorage = $this->createMock(EntityStorageInterface::class);
    $consumerStorage->expects($this->never())
      ->method('create')
      ->willReturn($consumerEntity);

    $roleStorage = $this->createMock(EntityStorageInterface::class);
    $roleStorage->expects($this->never())
      ->method('load')
      ->with(self::PUBLISHER_ROLE)
      ->willReturn($roleEntity);

    $this->entityTypeManager->expects($this->exactly(1))
      ->method('getStorage')
      ->with('user')
      ->willReturn($userStorage);

    $ecmsApiPublisherInstall = $this->getMockBuilder(EcmsApiPublisherInstall::class)
      ->onlyMethods(['generatePassword'])
      ->setConstructorArgs([$this->entityTypeManager, $this->configFactory])
      ->getMock();
    $ecmsApiPublisherInstall->expects($this->once())
      ->method('generatePassword')
      ->willReturn(self::PASSWORD);

    $ecmsApiPublisherInstall->installEcmsApiPublisher();

  }

  /**
   * Test the installEcmsApiPublisher() method with a consumer exception.
   */
  public function testInstallEcmsApiPublisherConsumerException(): void {

    $this->immutableConfig->expects($this->exactly(3))
      ->method('get')
      ->withConsecutive(
        ['api_publisher_mail'], ['oauth_client_id'], ['oauth_client_secret'])
      ->willReturnOnConsecutiveCalls(
        self::USER_MAIL,
        self::CLIENT_ID,
        self::CLIENT_SECRET
      );

    $roleEntity = $this->createMock(RoleInterface::class);
    $roleEntity->expects($this->once())
      ->method('grantPermission')
      ->with('add ecms api site entities')
      ->willReturnSelf();
    $roleEntity->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $exception = $this->createMock(EntityStorageException::class);
    $userAccount = $this->createMock(UserInterface::class);
    $userAccount->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $consumerEntity = $this->createMock(EntityInterface::class);
    $consumerEntity->expects($this->once())
      ->method('save')
      ->willThrowException($exception);

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->expects($this->once())
      ->method('create')
      ->with(self::USER_PROFILE_INFORMATION)
      ->willReturn($userAccount);

    $consumerStorage = $this->createMock(EntityStorageInterface::class);
    $consumerStorage->expects($this->once())
      ->method('create')
      ->willReturn($consumerEntity);

    $roleStorage = $this->createMock(EntityStorageInterface::class);
    $roleStorage->expects($this->once())
      ->method('load')
      ->with(self::PUBLISHER_ROLE)
      ->willReturn($roleEntity);

    $this->entityTypeManager->expects($this->exactly(3))
      ->method('getStorage')
      ->withConsecutive(['user'], ['consumer'], ['user_role'])
      ->willReturnOnConsecutiveCalls($userStorage, $consumerStorage, $roleStorage);

    $ecmsApiPublisherInstall = $this->getMockBuilder(EcmsApiPublisherInstall::class)
      ->onlyMethods(['generatePassword'])
      ->setConstructorArgs([$this->entityTypeManager, $this->configFactory])
      ->getMock();
    $ecmsApiPublisherInstall->expects($this->once())
      ->method('generatePassword')
      ->willReturn(self::PASSWORD);

    $ecmsApiPublisherInstall->installEcmsApiPublisher();

  }

  /**
   * Test the generatePassword() method.
   */
  public function testGeneratePassword(): void {
    $installReflection = new \ReflectionClass('\Drupal\ecms_api_publisher\EcmsApiPublisherInstall');

    $generatePasswordMethod = $installReflection->getMethod('generatePassword');
    $generatePasswordMethod->setAccessible(TRUE);

    $installClass = new EcmsApiPublisherInstall($this->entityTypeManager, $this->configFactory);
    $result = $generatePasswordMethod->invokeArgs($installClass, []);

    $this->assertIsString($result);

    $length = strlen($result);

    $this->assertEquals(43, $length);
  }

}
