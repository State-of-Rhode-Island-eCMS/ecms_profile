<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_authentication\Unit;

use Drupal\ecms_authentication\EcmsUserAuthentication;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class EcmsUserAuthenticationTest.
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
  const ADMIN_GROUP = 'Drupal Administrator';

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

    if (in_array(self::ADMIN_GROUP, $groups)) {
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
          'test.subdomain.com',
          'Drupal Administrator',
        ],
        TRUE,
      ],
      'test2' => [
        [
          'Drupal Administrator',
          'test.invaliddomain.com',
          'test.invaliddomainnumbertwo.com',
          'oomphinc.com',
        ],
        TRUE,
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
          'test.subdomain.com',
          'test.invaliddomainnumbertwo.com',
          'oomphinc.com',
        ],
        TRUE,
      ],
      'test5' => [
        [],
        FALSE,
      ],
    ];
  }

}
