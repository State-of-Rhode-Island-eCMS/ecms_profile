<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_api_publisher\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Url;
use Drupal\ecms_api_publisher\EcmsApiPublisher;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use GuzzleHttp\ClientInterface;

/**
 * Unit testing for the EcmsApiPublisher class.
 *
 * @covers \Drupal\ecms_api_publisher\EcmsApiPublisher
 * @group ecms
 * @group ecms_api
 * @group ecms_api_publisher
 * @package Drupal\Tests\ecms_api_publisher\Unit
 */
class EcmsApiPublisherTest extends UnitTestCase {

  /**
   * The test client id.
   */
  const CLIENT_ID = 'TEST-CLIENT-ID';

  /**
   * The test client secret.
   */
  const CLIENT_SECRET = 'TEST-CLIENT-SECRET';

  /**
   * The test client scope.
   */
  const CLIENT_SCOPE = 'test_client_scope';

  /**
   * Mock of the config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $configFactory;

  /**
   * Mock of the EcmsApiPublisher class to test.
   *
   * @var \Drupal\ecms_api_publisher\EcmsApiPublisher|\PHPUnit\Framework\MockObject\MockObject
   */
  private $ecmsApiPublisher;

  /**
   * Mock of the account_switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $accountSwitcher;

  /**
   * Mock of the EntityStorageInterface for user entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $userStorage;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $immutableConfig = $this->createMock(ImmutableConfig::class);
    $immutableConfig->expects($this->exactly(3))
      ->method('get')
      ->withConsecutive(
        ['recipient_client_id'],
        ['recipient_client_secret'],
        ['recipient_client_scope']
      )
      ->willReturnOnConsecutiveCalls(self::CLIENT_ID, self::CLIENT_SECRET, self::CLIENT_SCOPE);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);

    $this->configFactory->expects($this->exactly(3))
      ->method('get')
      ->with('ecms_api_publisher.settings')
      ->willReturn($immutableConfig);

    $httpClient = $this->createMock(ClientInterface::class);
    $entityToJsonApi = $this->createMock(EntityToJsonApi::class);

    $this->accountSwitcher = $this->createMock(AccountSwitcherInterface::class);
    $this->userStorage = $this->createMock(EntityStorageInterface::class);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->wilLReturn($this->userStorage);

    $this->ecmsApiPublisher = $this->getMockBuilder(EcmsApiPublisher::class)
      ->onlyMethods(['getAccessToken', 'submitEntity'])
      ->setConstructorArgs([
        $httpClient,
        $entityToJsonApi,
        $this->configFactory,
        $entityTypeManager,
        $this->accountSwitcher,
      ])
      ->getMock();
  }

  /**
   * Test the syndicateNode method.
   *
   * @param string|null $accessToken
   *   The accessToken to return.
   * @param bool $publisherAccount
   *   Whether a publisher account exists.
   * @param bool $expected
   *   The expected result.
   *
   * @dataProvider dataProviderForTestSyndicateNode
   */
  public function testSyndicateNode(?string $accessToken, bool $publisherAccount, bool $expected): void {
    $url = $this->createMock(Url::class);
    $node = $this->createMock(NodeInterface::class);

    $this->ecmsApiPublisher->expects($this->once())
      ->method('getAccessToken')
      ->with($url, self::CLIENT_ID, self::CLIENT_SECRET, self::CLIENT_SCOPE)
      ->willReturn($accessToken);

    if (!empty($accessToken)) {
      $userAccountArray = [];

      if ($publisherAccount) {
        $account = $this->createMock(UserInterface::class);
        $userAccountArray[] = $account;

        $this->accountSwitcher->expects($this->once())
          ->method('switchTo')
          ->with($account);

        $this->accountSwitcher->expects($this->once())
          ->method('switchBack');

        $this->ecmsApiPublisher->expects($this->once())
          ->method('submitEntity')
          ->with($accessToken, $url, $node)
          ->willReturn($expected);
      }

      $this->userStorage->expects($this->once())
        ->method('loadByProperties')
        ->with()
        ->willReturn($userAccountArray);
    }

    $result = $this->ecmsApiPublisher->syndicateNode($url, $node);

    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for the testSyndicateNode method.
   *
   * @return array[]
   *   Parameters to pass to testSyndicateNode.
   */
  public function dataProviderForTestSyndicateNode(): array {
    return [
      'test1' => [NULL, FALSE, FALSE],
      'test2' => ['123456', FALSE, FALSE],
      'test3' => ['987654', TRUE, TRUE],
    ];
  }

}
