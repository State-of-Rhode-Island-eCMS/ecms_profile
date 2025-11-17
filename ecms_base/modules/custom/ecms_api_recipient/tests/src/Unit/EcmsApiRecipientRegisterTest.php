<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_api_recipient\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Url;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\ecms_api\EcmsApiHelper;
use Drupal\ecms_api_recipient\EcmsApiRecipientRegister;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Message\ResponseInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Unit testing for the EcmsApiRecipientRegister class.
 *
 * @package Drupal\Tests\ecms_api_recipient\Unit
 */
#[Group("ecms_api_recipient")]
#[Group("ecms_api")]
#[Group("ecms")]
#[CoversClass(\Drupal\ecms_api_recipient\EcmsApiRecipientRegister::class)]
class EcmsApiRecipientRegisterTest extends UnitTestCase {

  /**
   * Mock of the access token.
   */
  const ACCESS_TOKEN = "TEST-ACCESS-TOKEN";

  /**
   * Mock of the client id.
   */
  const HUB_CLIENT_ID = "TEST_HUB_CLIENT_ID";

  /**
   * Mock of the client secret.
   */
  const HUB_CLIENT_SECRET = "TEST_HUB_CLIENT_SECRET";

  /**
   * Mock of the scope.
   */
  const HUB_SCOPE = "test_hub_scope";

  /**
   * Mock of the content types to enable by default.
   */
  const INSTALLED_CONTENT_TYPES = ['notification'];

  /**
   * Mock of the content types returned.
   */
  const CONTENT_TYPES = [
    'data' => [
      [
        'type' => 'node_type--node_type',
        'id' => 'random-uuid',
        'attributes' => [
          'drupal_internal__type' => 'notification',
        ],
      ],
    ],
  ];

  /**
   * Mock of the config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $configFactory;

  /**
   * Mock of the request_stack service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * Mock of the http_client service.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $httpClient;

  /**
   * Mock of the ecms_api_helper service.
   *
   * @var \Drupal\ecms_api\EcmsApiHelper|\PHPUnit\Framework\MockObject\MockObject
   */
  private $ecmsApiHelper;

  /**
   * Mock of the jsonapi_extras.entity.to_jsonapi service.
   *
   * @var \Drupal\jsonapi_extras\EntityToJsonApi|\PHPUnit\Framework\MockObject\MockObject
   */
  private $entityToJsonApi;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->requestStack = $this->createMock(RequestStack::class);
    $this->entityToJsonApi = $this->createMock(EntityToJsonApi::class);
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->ecmsApiHelper = $this->createMock(EcmsApiHelper::class);

    $container = new ContainerBuilder();
    $container->set('unrouted_url_assembler', $this->createMock(UnroutedUrlAssemblerInterface::class));
    \Drupal::setContainer($container);
  }

  /**
   * Test the registerSite method.
   *
   * @param string|null $siteUrl
   *   The site url to test with.
   * @param string|null $hubUrl
   *   The hub url to test with.
   * @param bool $expectContentTypes
   *   Whether content types should be returned.
   * @param bool $accessToken
   *   Whether an access token should be returned.
   * @param bool $guzzleException
   *   Whether a guzzle exception should be expected.
   * @param int $code
   *   The http status code to expect.
   *
   */
  #[DataProvider('dataProviderForTestRegisterSite')]
  public function testRegisterSite(string $siteUrl, string $hubUrl, bool $expectContentTypes, bool $accessToken, bool $guzzleException, int $code): void {
    $passedUrlGuards = FALSE;
    $recipientConfigCount = 0;

    $request = $this->createMock(Request::class);
    $request->expects($this->once())
      ->method('getSchemeAndHttpHost')
      ->willReturn($siteUrl);

    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->willReturn($request);

    // Mock the two URL objects.
    $siteUrlMock = $this->createMock(Url::class);
    $hubUrlMock = $this->createMock(Url::class);

    if (empty($siteUrl) || $siteUrl === 'invalid') {
      $siteUrlMock->expects($this->never())
        ->method('toString')
        ->willReturn($siteUrl);
      $siteUrlMock->expects($this->never())
        ->method('toUriString')
        ->willReturn($siteUrl);
    }
    else {
      $recipientConfigCount = 1;
      if (empty($hubUrl) || $hubUrl === 'invalid') {

        $hubUrlMock->expects($this->never())
          ->method('toString')
          ->willReturn($hubUrl);
        $hubUrlMock->expects($this->never())
          ->method('toUriString')
          ->willReturn($hubUrl);
      }
      else {
        $passedUrlGuards = TRUE;
      }
    }

    $apiRecipientRegister = $this->getMockBuilder(EcmsApiRecipientRegister::class)
      ->onlyMethods(['getContentTypes', 'getAccessToken'])
      ->setConstructorArgs([
        $this->httpClient,
        $this->entityToJsonApi,
        $this->ecmsApiHelper,
        $this->configFactory,
        $this->requestStack,
      ])
      ->getMock();

    if ($passedUrlGuards) {
      if (!$expectContentTypes) {
        $recipientConfigCount = 2;
        $apiRecipientRegister->expects($this->once())
          ->method('getContentTypes')
          ->willReturn(NULL);
      }
      else {
        $apiRecipientRegister->expects($this->once())
          ->method('getContentTypes')
          ->willReturn(self::CONTENT_TYPES);

        // Increase the config count.
        $recipientConfigCount = 5;

        if (!$accessToken) {
          $apiRecipientRegister->expects($this->once())
            ->method('getAccessToken')
            ->willReturn(NULL);
        }
        else {
          $recipientConfigCount = 5;
          $apiRecipientRegister->expects($this->once())
            ->method('getAccessToken')
            ->willReturn(self::ACCESS_TOKEN);

          if ($guzzleException) {
            $exception = $this->createMock(GuzzleException::class);
            $this->httpClient->expects($this->once())
              ->method('request')
              ->willThrowException($exception);
          }
          else {
            $response = $this->createMock(ResponseInterface::class);
            $response->expects($this->once())
              ->method('getStatusCode')
              ->willReturn($code);

            $this->httpClient->expects($this->once())
              ->method('request')
              ->willReturn($response);
          }
        }
      }
    }

    $immutableHubConfig = $this->createMock(ImmutableConfig::class);
    $immutableHubConfig->expects($this->exactly($recipientConfigCount))
      ->method('get')
      ->willReturnMap([
        ['api_main_hub', $hubUrl],
        ['api_main_hub_client_id', self::HUB_CLIENT_ID],
        ['api_main_hub_client_secret', self::HUB_CLIENT_SECRET],
        ['api_main_hub_scope', self::HUB_SCOPE],
        ['verify_ssl', TRUE],
      ]);

    $this->configFactory->expects($this->exactly($recipientConfigCount))
      ->method('get')
      ->with('ecms_api_recipient.settings')
      ->willReturn($immutableHubConfig);

    $apiRecipientRegister->registerSite();

  }

  /**
   * Data provider for the testRegisterSite method.
   *
   * @return array[]
   *   The array of parameters to pass to testRegisterSite().
   */
  public static function dataProviderForTestRegisterSite(): array {

    return [
      'test1' => [
        '',
        '',
        FALSE,
        FALSE,
        TRUE,
        500,
      ],
      'test2' => [
        'invalid',
        '',
        FALSE,
        FALSE,
        TRUE,
        500,
      ],
      'test3' => [
        'https://test.com',
        '',
        FALSE,
        FALSE,
        TRUE,
        500,
      ],
      'test4' => [
        'https://test.com',
        'invalid',
        FALSE,
        FALSE,
        TRUE,
        500,
      ],
      'test5' => [
        'https://test.com',
        'https://hub.test.com',
        FALSE,
        FALSE,
        TRUE,
        500,
      ],
      'test6' => [
        'https://test.com',
        'https://hub.test.com',
        TRUE,
        FALSE,
        TRUE,
        500,
      ],
      'test7' => [
        'https://test.com',
        'https://hub.test.com',
        TRUE,
        TRUE,
        TRUE,
        500,
      ],
      'test8' => [
        'https://test.com',
        'https://hub.test.com',
        TRUE,
        TRUE,
        FALSE,
        201,
      ],
      'test9' => [
        'https://test.com',
        'https://hub.test.com',
        TRUE,
        TRUE,
        FALSE,
        404,
      ],
    ];
  }

}
