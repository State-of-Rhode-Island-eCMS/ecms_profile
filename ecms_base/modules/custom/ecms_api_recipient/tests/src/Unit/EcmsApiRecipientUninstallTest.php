<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_api_recipient\Unit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecms_api_recipient\EcmsApiRecipientUninstall;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit testing for the EcmsApiRecipientUninstall class.
 *
 *
 * @package Drupal\Tests\ecms_api_recipient\Unit
 */
#[Group("ecms_api_recipient")]
#[Group("ecms_api")]
#[Group("ecms")]
#[CoversClass(\Drupal\ecms_api_recipient\EcmsApiRecipientUninstall::class)]
class EcmsApiRecipientUninstallTest extends UnitTestCase {

  /**
   * The role id that is installed with this module.
   */
  const ROLE = 'ecms_api_recipient';

  /**
   * The user name that is installed with this module.
   */
  const USER_NAME = 'ecms_api_recipient';

  /**
   * Mock of the entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $entityTypeManager;

  /**
   * Mock the entity storage for user roles.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $roleStorage;

  /**
   * Mock the entity storage for user entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $userStorage;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->roleStorage = $this->createMock(EntityStorageInterface::class);
    $this->userStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->expects($this->exactly(2))
      ->method('getStorage')
      ->willReturnMap([
        ['user_role', $this->roleStorage], ['user', $this->userStorage]
      ]);
  }

  /**
   * Test the uninstall method with successful mocks.
   */
  public function testSuccessfulUninstall(): void {
    $role = $this->createMock(EntityInterface::class);
    $role->expects($this->once())
      ->method('delete');

    $this->roleStorage->expects($this->once())
      ->method('load')
      ->with(self::ROLE)
      ->willReturn($role);

    $user = $this->createMock(EntityInterface::class);
    $user->expects($this->once())
      ->method('delete');

    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['name' => self::USER_NAME])
      ->willReturn([$user]);

    $uninstallClass = new EcmsApiRecipientUninstall($this->entityTypeManager);

    $uninstallClass->uninstall();
  }

  /**
   * Test the uninstall method with null entities.
   */
  public function testNullEntitiesUninstall(): void {
    $this->roleStorage->expects($this->once())
      ->method('load')
      ->with(self::ROLE)
      ->willReturn(NULL);

    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['name' => self::USER_NAME])
      ->willReturn([]);

    $uninstallClass = new EcmsApiRecipientUninstall($this->entityTypeManager);

    $uninstallClass->uninstall();
  }

  /**
   * Test the uninstall method with entity storage exceptions.
   */
  public function testStorageExceptionsUninstall(): void {
    $storageException = $this->createMock(EntityStorageException::class);

    $role = $this->createMock(EntityInterface::class);
    $role->expects($this->once())
      ->method('delete')
      ->willThrowException($storageException);

    $this->roleStorage->expects($this->once())
      ->method('load')
      ->with(self::ROLE)
      ->willReturn($role);

    $user = $this->createMock(EntityInterface::class);
    $user->expects($this->once())
      ->method('delete')
      ->willThrowException($storageException);

    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['name' => self::USER_NAME])
      ->willReturn([$user]);

    $uninstallClass = new EcmsApiRecipientUninstall($this->entityTypeManager);

    $uninstallClass->uninstall();
  }

}
