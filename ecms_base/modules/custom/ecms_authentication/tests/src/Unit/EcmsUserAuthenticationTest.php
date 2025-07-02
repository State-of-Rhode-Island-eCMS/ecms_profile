<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_authentication\Unit;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\ecms_authentication\EcmsUserAuthentication;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Unit tests for the EcmsUserAuthentication class.
 *
 * @package Drupal\Tests\ecms_authentication\Unit
 *
 * @covers \Drupal\ecms_authentication\EcmsUserAuthentication
 * @group ecms_authentication
 */
class EcmsUserAuthenticationTest extends UnitTestCase {

  /**
   * The host of this test.
   */
  const HOST = 'test.subdomain.com';

  /**
   * The admin group constant.
   */
  const ADMIN_GROUP = 'Drupal_Admin';

  /**
   * Mock of the request_stack service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->requestStack = $this->createMock(RequestStack::class);
  }

  /**
   * Test the checkGroupAccess method.
   *
   * @param array $groups
   *   The groups to check.
   * @param bool $expected
   *   The expected result.
   *
   * @dataProvider dataProviderForCheckGroupAccess
   */
  public function testCheckGroupAccess(array $groups, bool $expected): void {

    if (in_array(self::ADMIN_GROUP, $groups, TRUE)) {
      $this->requestStack->expects($this->never())
        ->method('getCurrentRequest');
    }
    else {
      $request = $this->createMock(Request::class);
      $request->expects($this->once())
        ->method('getHttpHost')
        ->willReturn(self::HOST);

      $this->requestStack->expects($this->once())
        ->method('getCurrentRequest')
        ->willReturn($request);
    }

    // Setup our new class to test.
    $ecmsUserAuthentication = new EcmsUserAuthentication($this->requestStack);

    $actual = $ecmsUserAuthentication->checkGroupAccess($groups);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for the testAdministratorAccess method.
   *
   * @return array[]
   *   Array of params to pass to testAdministratorAccess.
   */
  public function dataProviderForCheckGroupAccess(): array {
    return [
      'test1' => [
        [
          'test group one',
          self::HOST,
          'Drupal Admin',
        ],
        TRUE,
      ],
      'test2' => [
        [
          'Drupal Admin',
          'test.invaliddomain.com',
          'test.invaliddomainnumbertwo.com',
          'oomphinc.com',
        ],
        FALSE,
      ],
      'test3' => [
        [
          'test.invaliddomain.com',
          'test.invaliddomainnumbertwo.com',
          'oomphinc.com',
        ],
        FALSE,
      ],
      'test4' => [
        [
          self::HOST,
          'test.invaliddomainnumbertwo.com',
          'oomphinc.com',
        ],
        TRUE,
      ],
      'test5' => [
        [],
        FALSE,
      ],
      'test6' => [
        [
          'test group one',
          self::HOST,
          'Drupal_Admin',
        ],
        TRUE,
      ],
      'test7' => [
        [
          'Drupal_Admin',
          'test.invaliddomain.com',
          'test.invaliddomainnumbertwo.com',
          'oomphinc.com',
        ],
        TRUE,
      ],
      'test8' => [
        [
          'test group one',
          self::HOST,
          'drupal_admin',
        ],
        TRUE,
      ],
      'test9' => [
        [
          'DrUpAl_AdMiN',
          'test.invaliddomain.com',
          'test.invaliddomainnumbertwo.com',
          'oomphinc.com',
        ],
        FALSE,
      ],
      'test10' => [
        [
          'test group one',
          $this->randomMachineName(),
          'drupal_admin',
        ],
        FALSE,
      ],
    ];
  }

  /**
   * Test the removeAdminGroup method.
   *
   * @param bool $hasAadAdminGroup
   *   Whether the AAD has the admin group.
   * @param bool $hasDrupalAdminRole
   *   Whether the Drupal user has the admin group.
   * @param bool $exception
   *   Whether a storage exception should be expected.
   *
   * @dataProvider dataProviderForTestRemoveAdminGroup
   */
  public function testRemoveAdminGroup(bool $hasAadAdminGroup, bool $hasDrupalAdminRole, bool $exception): void {
    $aadGroups = [
      $this->randomMachineName(),
      $this->randomMachineName(),
    ];

    if ($hasAadAdminGroup) {
      $aadGroups[] = self::ADMIN_GROUP;
    }

    $userRoles = [
      $this->randomMachineName(),
      $this->randomMachineName(),
    ];

    if ($hasDrupalAdminRole) {
      $userRoles[] = strtolower(self::ADMIN_GROUP);
    }

    $account = $this->createMock(UserInterface::class);

    // If the admin group is not available in AAD.
    if (!$hasAadAdminGroup) {
      $account->expects($this->once())
        ->method('getRoles')
        ->with(TRUE)
        ->willReturn($userRoles);

      if ($hasDrupalAdminRole) {
        $account->expects($this->once())
          ->method('removeRole')
          ->with(strtolower(self::ADMIN_GROUP))
          ->willReturnSelf();

        if ($exception) {
          $exception = $this->createMock(EntityStorageException::class);
          $account->expects($this->once())
            ->method('save')
            ->willThrowException($exception);
        }
        else {
          $account->expects($this->once())
            ->method('save')
            ->willReturnSelf();
        }

      }
      else {
        $account->expects($this->never())
          ->method('removeRole')
          ->with(strtolower(self::ADMIN_GROUP))
          ->willReturnSelf();

        $account->expects($this->never())
          ->method('save')
          ->willReturnSelf();
      }
    }
    else {
      $account->expects($this->never())
        ->method('getRoles')
        ->with(TRUE)
        ->willReturn($userRoles);
    }

    // Setup our new class to test.
    $ecmsUserAuthentication = new EcmsUserAuthentication($this->requestStack);

    $ecmsUserAuthentication->removeAdminGroup($aadGroups, $account);
  }

  /**
   * Data provider for the testRemoveAdminGroup method.
   *
   * @return array
   *   Array or parameters to pass to testRemoveAdminGroup.
   */
  public static function dataProviderForTestRemoveAdminGroup(): array {
    return [
      'test1' => [FALSE, FALSE, FALSE],
      'test2' => [FALSE, TRUE, FALSE],
      'test3' => [TRUE, TRUE, FALSE],
      'test4' => [TRUE, FALSE, FALSE],
      'test5' => [FALSE, TRUE, TRUE],
    ];
  }

}
