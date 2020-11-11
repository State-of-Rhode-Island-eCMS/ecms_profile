<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_api\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\ecms_api\EcmsApiBase;
use Drupal\ecms_api\EcmsApiHelper;
use Drupal\file\FileInterface;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\media\MediaInterface;
use Drupal\media\Plugin\media\Source\File;
use Drupal\media\Plugin\media\Source\OEmbed;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use phpmock\MockBuilder;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Unit tests for the EcmsApiBase class.
 *
 * @package Drupal\Tests\ecms_api\Unit
 * @covers \Drupal\ecms_api\EcmsApiBase
 * @group ecms
 * @group ecms_api
 */
class EcmsApiBaseTest extends UnitTestCase {

  /**
   * The endpoint url.
   */
  const ENDPOINT_URL = 'https://oomphinc.com';

  /**
   * The oauth path.
   */
  const OAUTH_ENDPOINT = 'https://oomphinc.com/oauth/token';

  /**
   * The client id to test with.
   */
  const CLIENT_ID = 'TEST-CLIENT-ID';

  /**
   * The client secret to test with.
   */
  const CLIENT_SECRET = 'TEST-CLIENT-SECRET';

  /**
   * The client scope to test with.
   */
  const CLIENT_SCOPE = 'TEST-CLIENT-SCOPE';

  /**
   * The access token to test with.
   */
  const ACCESS_TOKEN = 'test-access-token-123';

  /**
   * The oauth success return values.
   */
  const OAUTH_SUCCESS = [
    'token_type' => 'Bearer',
    'expires_in' => 100,
    'access_token' => self::ACCESS_TOKEN,
  ];

  /**
   * The entity endpoint to test with.
   */
  const ENTITY_ENDPOINT = 'https://oomphinc.com/EcmsApi/entity_type_test/entity_bundle';

  /**
   * The entity type to test with.
   */
  const ENTITY_TYPE = 'entity_type_test';

  /**
   * The entity bundle to test with.
   */
  const ENTITY_BUNDLE = 'entity_bundle';

  /**
   * The entity uuid to test with.
   */
  const ENTITY_UUID = '1234-5678-abcd';

  /**
   * The expected payload to be sent to the API.
   */
  const PAYLOAD = [
    'json' => [
      'data' => [
        'type' => self::ENTITY_BUNDLE,
        'id' => self::ENTITY_UUID,
        'attributes' => [
          'field_text_field' => 'Text Field',
          'field_date_field' => '1980-11-09T23:43:43+00:00:00',
          'field_text_field_processed' => [
            'value' => 'Text Field Processed',
          ],
          'uuid' => self::ENTITY_UUID,
        ],
        'relationships' => [],
      ],
    ],
    'headers' => [
      'Content-Type' => 'application/vnd.api+json',
      'Authorization' => "Bearer " . self::ACCESS_TOKEN,
    ],
  ];

  /**
   * The expected payload to be sent to the API.
   */
  const CHECK_ENTITY_EXISTS_PAYLOAD = [
    'headers' => [
      'Content-Type' => 'application/vnd.api+json',
      'Authorization' => "Bearer " . self::ACCESS_TOKEN,
    ],
  ];

  /**
   * The expected return value of the entity after normalization.
   */
  const NORMALIZED_ENTITY = [
    'data' => [
      'type' => self::ENTITY_BUNDLE,
      'id' => self::ENTITY_UUID,
      'attributes' => [
        'drupal_internal__nid' => 12345,
        'drupal_internal__vid' => 54321,
        'created' => 232654568,
        'field_text_field' => 'Text Field',
        'field_date_field' => '1980-11-09T23:43:43+00:00:00',
        'field_text_field_processed' => [
          'value' => 'Text Field Processed',
          'processed' => TRUE,
        ],
      ],
      'relationships' => [
        'uid' => '',
        'revision_uid' => '',
        'revision_user' => '',
        'node_type' => '',
        'thumbnail' => '',
        'paragraph_type' => '',
        'bundle' => '',
        'vid' => '',
        'parent' => '',
        'content_translation_uid' => '',
      ],
    ],
  ];

  /**
   * The expected return value of the entity after normalization.
   */
  const NORMALIZED_MEDIA_ENTITY = [
    'data' => [
      'type' => self::ENTITY_BUNDLE,
      'id' => self::ENTITY_UUID,
      'attributes' => [
        'drupal_internal__nid' => 12345,
        'drupal_internal__vid' => 54321,
        'created' => 232654568,
        'field_text_field' => 'Text Field',
        'field_date_field' => '1980-11-09T23:43:43+00:00:00',
        'field_text_field_processed' => [
          'value' => 'Text Field Processed',
          'processed' => TRUE,
        ],
      ],
      'relationships' => [
        self::MEDIA_SOURCE_FIELD_NAME => [
          'data' => [
            'id' => 'uuid-1234-test-5678',
          ],
        ],
        'uid' => '',
        'revision_uid' => '',
        'revision_user' => '',
        'node_type' => '',
        'thumbnail' => '',
        'paragraph_type' => '',
        'bundle' => '',
        'vid' => '',
        'parent' => '',
        'content_translation_uid' => '',
      ],
    ],
  ];

  /**
   * The expected payload to be sent to the API.
   */
  const MEDIA_PAYLOAD = [
    'json' => [
      'data' => [
        'type' => self::ENTITY_BUNDLE,
        'id' => self::ENTITY_UUID,
        'attributes' => [
          'field_text_field' => 'Text Field',
          'field_date_field' => '1980-11-09T23:43:43+00:00:00',
          'field_text_field_processed' => [
            'value' => 'Text Field Processed',
          ],
          'uuid' => self::ENTITY_UUID,
        ],
        'relationships' => [
          self::MEDIA_SOURCE_FIELD_NAME => [
            'data' => [
              'id' => self::MEDIA_SOURCE_FILE_UUID,
            ],
          ],
        ],
      ],
    ],
    'headers' => [
      'Content-Type' => 'application/vnd.api+json',
      'Authorization' => "Bearer " . self::ACCESS_TOKEN,
    ],
  ];

  const PARAGRAPH_ENTITY_UUID = 'bcf3f58d-8c27-4cab-bcac-18d3702c5dbb';
  const PARAGRAPH_ENTITY_JSON = '{"jsonapi":{"version":"1.0","meta":{"links":{"self":{"href":"http:\/\/jsonapi.org\/format\/1.0\/"}}}},"data":{"type":"paragraph--icon_card","id":"bcf3f58d-8c27-4cab-bcac-18d3702c5dbb","links":{"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/paragraph\/icon_card\/bcf3f58d-8c27-4cab-bcac-18d3702c5dbb"}},"attributes":{"drupal_internal__id":37,"drupal_internal__revision_id":58,"langcode":"en","status":true,"created":"2020-11-05T12:48:54+00:00","parent_id":null,"parent_type":"node","parent_field_name":"field_press_release_paragraphs","behavior_settings":null,"default_langcode":true,"revision_translation_affected":true,"content_translation_source":"und","content_translation_outdated":false,"content_translation_changed":"2020-11-05T12:48:54+00:00","field_list_title":"Icon Card #1","field_text":{"value":"\u003Cp\u003EThis is a test\u003C\/p\u003E\r\n","format":"basic_html","processed":"\u003Cp\u003EThis is a test\u003C\/p\u003E"}},"relationships":{"paragraph_type":{"data":{"type":"paragraphs_type--paragraphs_type","id":"4d71e2ef-b3a6-4926-96c7-0541db99e5cb"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/paragraph\/icon_card\/bcf3f58d-8c27-4cab-bcac-18d3702c5dbb\/paragraph_type"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/paragraph\/icon_card\/bcf3f58d-8c27-4cab-bcac-18d3702c5dbb\/relationships\/paragraph_type"}}},"field_icon":{"data":{"type":"file--file","id":"e32e7a2a-ef10-4e95-a960-b739258866de","meta":{"alt":"Icon","title":"","width":64,"height":64}},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/paragraph\/icon_card\/bcf3f58d-8c27-4cab-bcac-18d3702c5dbb\/field_icon"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/paragraph\/icon_card\/bcf3f58d-8c27-4cab-bcac-18d3702c5dbb\/relationships\/field_icon"}}}}},"links":{"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/paragraph\/icon_card\/bcf3f58d-8c27-4cab-bcac-18d3702c5dbb"}}}';
  /**
   * The endpoint for the node_type api.
   */
  const NODE_TYPE_ENDPOINT = '/EcmsApi/node_type/node_type';

  /**
   * The language ID to test with.
   */
  const LANGUAGE_ID = 'de';

  /**
   * The endpoint url if the entity is not in the default language.
   */
  const ENTITY_ENDPOINT_LANGUAGE = 'https://oomphinc.com/de/EcmsApi/entity_type_test/entity_bundle';

  const JSON_DATA_OBJECT_STRING = '{"jsonapi":{"version":"1.0","meta":{"links":{"self":{"href":"http:\/\/jsonapi.org\/format\/1.0\/"}}}},"data":{"type":"node--notification","id":"2e434fe8-0fcd-48ae-941e-ea78c4f348f7","links":{"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7?resourceVersion=id%3A593"}},"attributes":{"drupal_internal__nid":218,"drupal_internal__vid":593,"langcode":"en","revision_timestamp":"2020-10-23T15:23:06+00:00","revision_log":"Bulk operation publish revision ","status":true,"title":"Notification - Cui Inhibeo (en)","created":"2020-10-20T03:49:23+00:00","changed":"2020-10-23T15:23:06+00:00","promote":false,"sticky":false,"default_langcode":false,"revision_translation_affected":true,"moderation_state":"published","path":{"alias":null,"pid":null,"langcode":"en"},"rh_action":null,"rh_redirect":null,"rh_redirect_response":null,"content_translation_source":"und","content_translation_outdated":false,"field_notification_expire_date":"2020-10-10T04:21:00+00:00","field_notification_global":true,"field_notification_text":"Global Notification Text"},"relationships":{"node_type":{"data":{"type":"node_type--node_type","id":"8dafd8ea-debc-4f84-91e8-78a781304d11"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/node_type?resourceVersion=id%3A593"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/relationships\/node_type?resourceVersion=id%3A593"}}},"revision_uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/revision_uid?resourceVersion=id%3A593"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/relationships\/revision_uid?resourceVersion=id%3A593"}}},"uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/uid?resourceVersion=id%3A593"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/relationships\/uid?resourceVersion=id%3A593"}}}}},"links":{"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7"}}}';

  const JSON_UUID = '2e434fe8-0fcd-48ae-941e-ea78c4f348f7';

  const FILE_BYTES = 'beepboopbeepbeep';

  const MEDIA_SOURCE_FIELD_NAME = 'test_field_name';
  const MEDIA_SOURCE_FILE_UUID = 'file-test-uuid-1234-5566';
  const MEDIA_SOURCE_FILE_ID = 747;

  /**
   * Mock of the http_client service.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $httpclient;

  /**
   * Mock of the jsonapi_extras.entity.to_jsonapi service.
   *
   * @var \Drupal\jsonapi_extras\EntityToJsonApi|\PHPUnit\Framework\MockObject\MockObject
   */
  private $entityToJsonApi;

  /**
   * Mock entity to test with.
   *
   * @var \Drupal\Core\Entity\EntityInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $entity;

  /**
   * Mock URL to test with.
   *
   * @var \Drupal\Core\Url|\PHPUnit\Framework\MockObject\MockObject
   */
  private $url;

  /**
   * Mock of the ecms_api_helper service.
   *
   * @var \Drupal\ecms_api\EcmsApiHelper|\PHPUnit\Framework\MockObject\MockObject
   */
  private $ecmsApiHelper;

  /**
   * Mock response to return from Guzzle.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Psr\Http\Message\ResponseInterface
   */
  private $response;

  /**
   * Mock the php fopen function.
   *
   * @var \phpmock\Mock
   */
  private $mockPhpFopenFunction;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $mockPhpFopenFunction = new MockBuilder();
    $mockPhpFopenFunction->setNamespace('\Drupal\ecms_api')
      ->setName('fopen')
      ->setFunction(
        function (string $path, string $mode) {
          // @codingStandardsIgnoreLine
          return self::FILE_BYTES;
        }
      );

    $this->mockPhpFopenFunction = $mockPhpFopenFunction->build();
    $this->mockPhpFopenFunction->enable();

    $this->ecmsApiHelper = $this->createMock(EcmsApiHelper::class);
    $this->httpclient = $this->createMock(ClientInterface::class);
    $this->response = $this->createMock(ResponseInterface::class);
    $this->entityToJsonApi = $this->getMockBuilder(EntityToJsonApi::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['normalize'])
      ->getMock();

    $this->entity = $this->createMock(EntityInterface::class);

    // Prophesize the URL toString method.
    $this->url = $this->getMockBuilder(Url::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['toString'])
      ->getMock();

    $container = new ContainerBuilder();
    $container->set('unrouted_url_assembler', $this->createMock(UnroutedUrlAssemblerInterface::class));
    \Drupal::setContainer($container);
  }

  /**
   * {@inheritDoc}
   */
  protected function tearDown(): void {
    parent::tearDown();

    // Disable the global t().
    $this->mockPhpFopenFunction->disable();
  }

  /**
   * Test the getAccessToken method.
   *
   * @param int $code
   *   The http status code to test.
   * @param string $oauthResponse
   *   The oauth response to test.
   * @param string|null $expectation
   *   The expected result to be returned.
   *
   * @dataProvider dataProviderForGetAccessToken
   */
  public function testGetAccessToken(int $code, string $oauthResponse, ?string $expectation): void {
    $payload = [
      'form_params' => [
        'grant_type' => 'client_credentials',
        'client_id' => self::CLIENT_ID,
        'client_secret' => self::CLIENT_SECRET,
        'scope' => self::CLIENT_SCOPE,
      ],
    ];

    $this->url->expects($this->once())
      ->method('toString')
      ->willReturn(self::ENDPOINT_URL);

    // Any http code greater than 0.
    if ($code > 0) {
      $this->response->expects($this->once())
        ->method('getStatusCode')
        ->willReturn($code);

      // getBody is only called with a 200 status code.
      if ($code === 200) {
        $streamInterface = $this->createMock(StreamInterface::class);
        $streamInterface->expects($this->once())
          ->method('getContents')
          ->willReturn($oauthResponse);

        $this->response->expects($this->once())
          ->method('getBody')
          ->willReturn($streamInterface);
      }

      $this->httpclient->expects($this->once())
        ->method('request')
        ->with('POST', self::OAUTH_ENDPOINT, $payload)
        ->willReturn($this->response);
    }
    else {
      // If a negative code is passed, throw an exception.
      $guzzleException = $this->createMock(GuzzleException::class);
      $this->httpclient->expects($this->once())
        ->method('request')
        ->with('POST', self::OAUTH_ENDPOINT, $payload)
        ->willThrowException($guzzleException);
    }

    $ecmsApi = $this->getMockBuilder(EcmsApiBase::class)
      ->setConstructorArgs([
        $this->httpclient,
        $this->entityToJsonApi,
        $this->ecmsApiHelper,
      ])
      ->getMock();

    $getAccessToken = new \ReflectionMethod(EcmsApiBase::class, 'getAccessToken');
    $getAccessToken->setAccessible(TRUE);

    $result = $getAccessToken->invokeArgs(
      $ecmsApi, [
        $this->url,
        self::CLIENT_ID,
        self::CLIENT_SECRET,
        self::CLIENT_SCOPE,
      ]
    );

    $this->assertEquals($expectation, $result);
  }

  /**
   * Data provider for testGetAccessToken().
   *
   * @return array[]
   *   Array of parameters to pass to the testGetAccessToken method.
   */
  public function dataProviderForGetAccessToken(): array {
    return [
      'test1' => [
        200,
        json_encode((object) self::OAUTH_SUCCESS),
        self::OAUTH_SUCCESS['access_token'],
      ],
      'test2' => [
        200,
        json_encode(serialize(self::OAUTH_SUCCESS)),
        NULL,
      ],
      'test3' => [
        200,
        json_encode([
          'token_type' => 'Bearer',
          'expires_in' => 100,
        ]),
        NULL,
      ],
      'test4' => [
        200,
        json_encode(''),
        NULL,
      ],
      'test5' => [
        404,
        '',
        NULL,
      ],
      'test6' => [
        -1,
        '',
        NULL,
      ],
    ];
  }

  /**
   * Test the submitEntity method.
   *
   * @param string $method
   *   The HTTP method to submit.
   * @param int $code
   *   The http status code to mock.
   * @param bool $defaultLanguage
   *   Whether the entity has the default language.
   * @param int $entityExistsCode
   *   The expected code that checkEntityExists will return.
   * @param bool $expected
   *   The expected response.
   *
   * @dataProvider dataProviderForTestSubmitEntity
   */
  public function testSubmitEntity(string $method, int $code, bool $defaultLanguage, int $entityExistsCode, bool $expected): void {

    switch ($entityExistsCode) {
      case 200:
        $existsMethod = 'PATCH';
        break;

      case 404:
        $existsMethod = 'POST';
        break;

      default:
        $existsMethod = NULL;
        break;
    }

    if (!empty($existsMethod)) {
      if ($defaultLanguage) {
        $endpoint = self::ENTITY_ENDPOINT;
      }
      else {
        $endpoint = self::ENTITY_ENDPOINT_LANGUAGE;
      }

      $uuidCount = 2;
      $bundleCount = 2;
      // Test for all allowed requests.
      if ($code !== 0) {

        $language = $this->createMock(LanguageInterface::class);
        $language->expects($this->once())
          ->method('getId')
          ->willReturn(self::LANGUAGE_ID);

        $language->expects($this->once())
          ->method('isDefault')
          ->willReturn($defaultLanguage);

        $this->entity->expects($this->once())
          ->method('language')
          ->willReturn($language);

        // If test 6, change the bundle count.
        if ($code === 6) {
          $bundleCount = 1;
          $uuidCount = 0;
        }

        $this->url->expects($this->once())
          ->method('toString')
          ->willReturn(self::ENDPOINT_URL);

        $this->entity->expects($this->once())
          ->method('getEntityTypeId')
          ->willReturn(self::ENTITY_TYPE);

        $this->entity->expects($this->exactly($bundleCount))
          ->method('bundle')
          ->willReturn(self::ENTITY_BUNDLE);

        if ($method === 'PATCH') {
          $uuidCount = 3;
          $uuid = self::ENTITY_UUID;
          $endpoint = "{$endpoint}/{$uuid}";
        }

        $this->entity->expects($this->exactly($uuidCount))
          ->method('uuid')
          ->willReturn(self::ENTITY_UUID);

        // If test 6, alter the normlaize method return.
        if ($code === 6) {
          $this->entityToJsonApi->expects($this->once())
            ->method('normalize')
            ->with($this->entity)
            ->willReturn([]);
        }
        else {
          $this->entityToJsonApi->expects($this->once())
            ->method('normalize')
            ->with($this->entity)
            ->willReturn(self::NORMALIZED_ENTITY);
        }
      }

      // Test a guzzle exception.
      if ($code === -1) {
        $this->response->expects($this->never())
          ->method('getStatusCode')
          ->willReturn($code);

        $guzzleException = $this->createMock(GuzzleException::class);
        $this->httpclient->expects($this->once())
          ->method('request')
          ->with($method, $endpoint, self::PAYLOAD)
          ->willThrowException($guzzleException);
      }

      if ($code > 100) {
        $this->response->expects($this->once())
          ->method('getStatusCode')
          ->willReturn($code);

        $this->httpclient->expects($this->once())
          ->method('request')
          ->with($method, $endpoint, self::PAYLOAD)
          ->willReturn($this->response);
      }
    }

    $ecmsApi = $this->getMockBuilder(EcmsApiBase::class)
      ->setConstructorArgs([
        $this->httpclient,
        $this->entityToJsonApi,
        $this->ecmsApiHelper,
      ])
      ->onlyMethods(['checkEntityExists'])
      ->getMock();

    $ecmsApi->expects($this->once())
      ->method('checkEntityExists')
      ->with(self::ACCESS_TOKEN, $this->url, $this->entity)
      ->willReturn($existsMethod);

    $submitEntity = new \ReflectionMethod(EcmsApiBase::class, 'submitEntity');
    $submitEntity->setAccessible(TRUE);

    $result = $submitEntity->invokeArgs(
      $ecmsApi, [
        self::ACCESS_TOKEN,
        $this->url,
        $this->entity,
      ]
    );

    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for the testSubmitEntity method.
   *
   * @return array[]
   *   Array of parameters for the testSubmitEntity method.
   */
  public function dataProviderForTestSubmitEntity(): array {
    return [
      'test1' => [
        'DELETE',
        0,
        TRUE,
        0,
        FALSE,
      ],
      'test2' => [
        'POST',
        201,
        TRUE,
        404,
        TRUE,
      ],
      'test3' => [
        'PATCH',
        200,
        TRUE,
        200,
        TRUE,
      ],
      'test4' => [
        'POST',
        401,
        TRUE,
        404,
        FALSE,
      ],
      'test5' => [
        'POST',
        -1,
        TRUE,
        404,
        FALSE,
      ],
      'test6' => [
        'POST',
        6,
        TRUE,
        404,
        FALSE,
      ],
      'test7' => [
        'PATCH',
        200,
        FALSE,
        200,
        TRUE,
      ],
    ];
  }

  /**
   * Test the getContentTypes() method.
   *
   * @param array $types
   *   An array of node machine names to query.
   * @param int $code
   *   The http status code to expect.
   * @param bool $expectation
   *   The expected result of the method.
   *
   * @dataProvider dataProviderForTestGetContentTypes
   */
  public function testGetContentTypes(array $types, int $code, bool $expectation): void {
    $queryParams = $this->getQueryParams($types);
    $returnTypes = $this->buildNodeTypeReturn($types);
    $url = self::ENDPOINT_URL;
    $typePath = self::NODE_TYPE_ENDPOINT;

    $fullEndpointPath = "{$url}{$typePath}?{$queryParams}";

    $this->url->expects($this->once())
      ->method('toString')
      ->willReturn(self::ENDPOINT_URL);

    if ($code === -1) {
      $exception = $this->createMock(GuzzleException::class);
      $this->httpclient->expects($this->once())
        ->method('request')
        ->with('GET', $fullEndpointPath)
        ->willThrowException($exception);
    }
    else {
      if ($code === 200) {
        $streamInterface = $this->createMock(StreamInterface::class);
        $streamInterface->expects($this->once())
          ->method('getContents')
          ->willReturn($returnTypes);

        $this->response->expects($this->once())
          ->method('getBody')
          ->willReturn($streamInterface);
      }

      $this->response->expects($this->once())
        ->method('getStatusCode')
        ->willReturn($code);

      $this->httpclient->expects($this->once())
        ->method('request')
        ->with('GET', $fullEndpointPath)
        ->willReturn($this->response);
    }

    $ecmsApi = $this->getMockBuilder(EcmsApiBase::class)
      ->setConstructorArgs([
        $this->httpclient,
        $this->entityToJsonApi,
        $this->ecmsApiHelper,
      ])
      ->getMock();

    $getContentTypes = new \ReflectionMethod(EcmsApiBase::class, 'getContentTypes');
    $getContentTypes->setAccessible(TRUE);

    $result = $getContentTypes->invokeArgs(
      $ecmsApi, [
        $this->url,
        $types,
      ]
    );

    if ($expectation) {
      $this->assertIsArray($result);

      $this->assertEquals(count($types), count($result));
    }
    else {
      $this->assertNull($result);
    }
  }

  /**
   * Data provider for testGetContentTypes().
   *
   * @return array[]
   *   Array of method parameters for testGetContentTypes().
   */
  public function dataProviderForTestGetContentTypes(): array {
    return [
      'test1' => [
        ['notification'],
        200,
        TRUE,
      ],
      'test2' => [
        ['notification', 'basic_page', 'testing'],
        200,
        TRUE,
      ],
      'test3' => [
        ['notification'],
        -1,
        FALSE,
      ],
      'test4' => [
        [],
        200,
        FALSE,
      ],
      'test5' => [
        ['notification'],
        500,
        FALSE,
      ],
      'test6' => [
        ['null_object'],
        200,
        FALSE,
      ],
      'test7' => [
        ['null_data'],
        200,
        FALSE,
      ],
    ];
  }

  /**
   * Build the query params for the endpoint url.
   *
   * @param array $types
   *   An array of node type machine names.
   *
   * @return string
   *   A url encoded query string.
   */
  private function getQueryParams(array $types): string {
    $filter = [];

    // Loop through the content types and build filters.
    foreach ($types as $key => $value) {
      $filter["filter[type-{$key}][condition][path]"] = "drupal_internal__type";
      $filter["filter[type-{$key}][condition][operator]"] = "=";
      $filter["filter[type-{$key}][condition][value]"] = "{$value}";
    }

    return http_build_query($filter);
  }

  /**
   * Mock the return of the json api node type endpoint.
   *
   * @param array $types
   *   An array of node type machine names.
   *
   * @return string
   *   A json encoded string of the return type.
   */
  private function buildNodeTypeReturn(array $types): string {

    if (empty($types)) {
      return json_encode('');
    }

    if (array_search("null_object", $types, TRUE) !== FALSE) {
      return json_encode('this is not an object');
    }

    if (array_search('null_data', $types, TRUE) !== FALSE) {
      return json_encode((object) ['data_does_not_exist' => []]);
    }

    $return = ['data' => []];

    foreach ($types as $key => $value) {
      $return['data'][$key] = (object) [
        'type' => 'node_type--node_type',
        'id' => $this->randomMachineName(),
        'attributes' => [
          'drupal_internal__type' => $value,
        ],
      ];
    }

    return json_encode((object) $return);
  }

  /**
   * Test the checkEntityExists method.
   *
   * @param int $code
   *   The http code returned from the http request.
   * @param bool $defaultLanguage
   *   Whether the entity is in the default language.
   * @param string|null $expected
   *   The expected result from the method call.
   *
   * @dataProvider dataProviderForTestCheckEntityExists
   */
  public function testCheckEntityExists(int $code, bool $defaultLanguage, ?string $expected): void {
    if ($defaultLanguage) {
      $endpoint = self::ENTITY_ENDPOINT;
    }
    else {
      $endpoint = self::ENTITY_ENDPOINT_LANGUAGE;
    }

    $language = $this->createMock(LanguageInterface::class);
    $language->expects($this->once())
      ->method('getId')
      ->willReturn(self::LANGUAGE_ID);

    $language->expects($this->once())
      ->method('isDefault')
      ->willReturn($defaultLanguage);

    $this->entity->expects($this->once())
      ->method('language')
      ->willReturn($language);

    $this->url->expects($this->once())
      ->method('toString')
      ->willReturn(self::ENDPOINT_URL);

    $this->entity->expects($this->once())
      ->method('getEntityTypeId')
      ->willReturn(self::ENTITY_TYPE);

    $this->entity->expects($this->once())
      ->method('bundle')
      ->willReturn(self::ENTITY_BUNDLE);

    $uuid = self::ENTITY_UUID;
    $endpoint = "{$endpoint}/{$uuid}";

    $this->entity->expects($this->once())
      ->method('uuid')
      ->willReturn(self::ENTITY_UUID);

    if ($code < 200 || $code > 399) {
      $request = $this->createMock(RequestInterface::class);
      $response = $this->createMock(ResponseInterface::class);
      $response->expects($this->once())
        ->method('getStatusCode')
        ->willReturn($code);
      $exception = new ClientException('', $request, $response);

      $this->httpclient->expects($this->once())
        ->method('request')
        ->with('GET', $endpoint, self::CHECK_ENTITY_EXISTS_PAYLOAD)
        ->willThrowException($exception);
    }
    else {

      $this->response->expects($this->once())
        ->method('getStatusCode')
        ->willReturn($code);

      $this->httpclient->expects($this->once())
        ->method('request')
        ->with('GET', $endpoint, self::CHECK_ENTITY_EXISTS_PAYLOAD)
        ->willReturn($this->response);
    }

    $ecmsApi = $this->getMockBuilder(EcmsApiBase::class)
      ->setConstructorArgs([
        $this->httpclient,
        $this->entityToJsonApi,
        $this->ecmsApiHelper,
      ])
      ->getMock();

    $checkEntityExists = new \ReflectionMethod(EcmsApiBase::class, 'checkEntityExists');
    $checkEntityExists->setAccessible(TRUE);

    $result = $checkEntityExists->invokeArgs(
      $ecmsApi, [
        self::ACCESS_TOKEN,
        $this->url,
        $this->entity,
      ]
    );

    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for the testCheckEntityExists method.
   *
   * @return array[]
   *   Array of parameters to pass to the testCheckEntityExists method.
   */
  public function dataProviderForTestCheckEntityExists(): array {
    return [
      'test1' => [
        -1,
        TRUE,
        NULL,
      ],
      'test2' => [
        200,
        TRUE,
        'PATCH',
      ],
      'test3' => [
        404,
        TRUE,
        'POST',
      ],
      'test4' => [
        500,
        TRUE,
        NULL,
      ],
      'test5' => [
        -1,
        FALSE,
        NULL,
      ],
      'test6' => [
        200,
        FALSE,
        'PATCH',
      ],
      'test7' => [
        404,
        FALSE,
        'POST',
      ],
      'test8' => [
        500,
        FALSE,
        NULL,
      ],
      'test9' => [
        301,
        FALSE,
        NULL,
      ],
    ];
  }

  /**
   * Test the getFileEndpointUrl method.
   */
  public function testGetFileEndpointUrl(): void {
    $this->entity->expects($this->once())
      ->method('getEntityTypeId')
      ->willReturn('test_entity_type');

    $this->entity->expects($this->once())
      ->method('bundle')
      ->willReturn('entity_bundle');

    $this->url->expects($this->once())
      ->method('toString')
      ->willReturn(self::ENDPOINT_URL);
    $fieldname = 'file_upload_test_field_name';

    $ecmsApi = $this->getMockBuilder(EcmsApiBase::class)
      ->setConstructorArgs([
        $this->httpclient,
        $this->entityToJsonApi,
        $this->ecmsApiHelper,
      ])
      ->getMock();

    $getFileEndpointUrl = new \ReflectionMethod(EcmsApiBase::class, 'getFileEndpointUrl');
    $getFileEndpointUrl->setAccessible(TRUE);

    $result = $getFileEndpointUrl->invokeArgs(
      $ecmsApi, [
        $this->entity,
        $fieldname,
        $this->url,
      ]
    );

    $expected = 'https://oomphinc.com/EcmsApi/test_entity_type/entity_bundle/file_upload_test_field_name';

    $this->assertEquals($expected, $result);
  }

  /**
   * Test the checkMediaSourceIsFile method.
   *
   * @param bool $isFile
   *   Is the source a file or not.
   *
   * @dataProvider dataProviderForTestCheckMediaSourceIsFile
   */
  public function testCheckMediaSourceIsFile(bool $isFile): void {
    if ($isFile) {
      $source = $this->createMock(File::class);
    }
    else {
      $source = $this->createMock(OEmbed::class);
    }

    $media = $this->createMock(MediaInterface::class);

    $media->expects($this->once())
      ->method('getSource')
      ->willReturn($source);

    $ecmsApi = $this->getMockBuilder(EcmsApiBase::class)
      ->setConstructorArgs([
        $this->httpclient,
        $this->entityToJsonApi,
        $this->ecmsApiHelper,
      ])
      ->getMock();

    $checkMediaSourceIsFile = new \ReflectionMethod(EcmsApiBase::class, 'checkMediaSourceIsFile');
    $checkMediaSourceIsFile->setAccessible(TRUE);

    $actual = $checkMediaSourceIsFile->invokeArgs(
      $ecmsApi, [
        $media,
      ]
    );

    $this->assertEquals($isFile, $actual);
  }

  /**
   * Data provider for the testCheckMediaSourceIsFile method.
   *
   * @return array
   *   Array of parameters for testCheckMediaSourceIsFile.
   */
  public function dataProviderForTestCheckMediaSourceIsFile(): array {
    return [
      'test1' => [TRUE],
      'test2' => [FALSE],
    ];
  }

  /**
   * Test the submitSourceFileEntity method.
   */
  public function testSubmitSourceFileEntity(): void {
    $fileId = 45865;
    $filepath = '/path/to/public/files/directory/test-image.png';
    $fieldname = 'file_upload_test_field_name';
    $endpoint = 'https://oomphinc.com/EcmsApi/test_entity_type/entity_bundle/file_upload_test_field_name';

    $payload = [
      'headers' => [
        'Content-Type' => "application/octet-stream",
        'Authorization' => "Bearer " . self::ACCESS_TOKEN,
        'Accept' => "application/vnd.api+json",
        'Content-Disposition' => 'file; filename="test-image.png"',
      ],
      'body' => self::FILE_BYTES,
    ];

    $this->entity->expects($this->once())
      ->method('getEntityTypeId')
      ->willReturn('test_entity_type');

    $this->entity->expects($this->once())
      ->method('bundle')
      ->willReturn('entity_bundle');

    $this->url->expects($this->once())
      ->method('toString')
      ->willReturn(self::ENDPOINT_URL);

    $this->ecmsApiHelper->expects($this->once())
      ->method('getFilePath')
      ->with($fileId)
      ->willReturn($filepath);

    $request = $this->createMock(ResponseInterface::class);
    $request->expects($this->once())
      ->method('getStatusCode')
      ->willReturn(201);

    $stream = $this->createMock(StreamInterface::class);
    $stream->expects($this->once())
      ->method('getContents')
      ->willReturn(self::JSON_DATA_OBJECT_STRING);

    $request->expects($this->once())
      ->method('getBody')
      ->willReturn($stream);

    $this->httpclient->expects($this->once())
      ->method('request')
      ->with('POST', $endpoint, $payload)
      ->willReturn($request);

    $ecmsApi = $this->getMockBuilder(EcmsApiBase::class)
      ->setConstructorArgs([
        $this->httpclient,
        $this->entityToJsonApi,
        $this->ecmsApiHelper,
      ])
      ->getMock();

    $submitSourceFileEntity = new \ReflectionMethod(EcmsApiBase::class, 'submitSourceFileEntity');
    $submitSourceFileEntity->setAccessible(TRUE);

    $actual = $submitSourceFileEntity->invokeArgs(
      $ecmsApi, [
        $this->entity,
        self::ACCESS_TOKEN,
        $this->url,
        $fileId,
        $fieldname,
      ]
    );

    $this->assertEquals(self::JSON_UUID, $actual);
  }

  /**
   * Test the fetchEntityFromApi method.
   *
   * @param int $code
   *   The status code to return.
   * @param bool $defaultLanguage
   *   The language to use for the entity.
   * @param string|null $expected
   *   The expected return result.
   *
   * @dataProvider dataProviderForTestFetchEntityFromApi
   */
  public function testFetchEntityFromApi(int $code, bool $defaultLanguage, ?string $expected): void {
    if ($defaultLanguage) {
      $endpoint = self::ENTITY_ENDPOINT;
    }
    else {
      $endpoint = self::ENTITY_ENDPOINT_LANGUAGE;
    }

    $language = $this->createMock(LanguageInterface::class);
    $language->expects($this->once())
      ->method('getId')
      ->willReturn(self::LANGUAGE_ID);

    $language->expects($this->once())
      ->method('isDefault')
      ->willReturn($defaultLanguage);

    $this->entity->expects($this->once())
      ->method('language')
      ->willReturn($language);

    $this->url->expects($this->once())
      ->method('toString')
      ->willReturn(self::ENDPOINT_URL);

    $this->entity->expects($this->once())
      ->method('getEntityTypeId')
      ->willReturn(self::ENTITY_TYPE);

    $this->entity->expects($this->once())
      ->method('bundle')
      ->willReturn(self::ENTITY_BUNDLE);

    $uuid = self::ENTITY_UUID;
    $endpoint = "{$endpoint}/{$uuid}";

    $this->entity->expects($this->once())
      ->method('uuid')
      ->willReturn(self::ENTITY_UUID);

    if ($code < 200 || $code > 399) {
      $request = $this->createMock(RequestInterface::class);
      $response = $this->createMock(ResponseInterface::class);
      $response->expects($this->once())
        ->method('getStatusCode')
        ->willReturn($code);
      $exception = new ClientException('', $request, $response);

      $this->httpclient->expects($this->once())
        ->method('request')
        ->with('GET', $endpoint, self::CHECK_ENTITY_EXISTS_PAYLOAD)
        ->willThrowException($exception);
    }
    else {
      $this->response->expects($this->once())
        ->method('getStatusCode')
        ->willReturn($code);

      $stream = $this->createMock(StreamInterface::class);
      $stream->expects($this->once())
        ->method('getContents')
        ->willReturn(self::JSON_DATA_OBJECT_STRING);

      $this->response->expects($this->once())
        ->method('getBody')
        ->willReturn($stream);

      $this->httpclient->expects($this->once())
        ->method('request')
        ->with('GET', $endpoint, self::CHECK_ENTITY_EXISTS_PAYLOAD)
        ->willReturn($this->response);

      $expectedJson = json_decode(self::JSON_DATA_OBJECT_STRING);
      $expected = $expectedJson->data;
    }

    $ecmsApi = $this->getMockBuilder(EcmsApiBase::class)
      ->setConstructorArgs([
        $this->httpclient,
        $this->entityToJsonApi,
        $this->ecmsApiHelper,
      ])
      ->getMock();

    $fetchEntityFromApi = new \ReflectionMethod(EcmsApiBase::class, 'fetchEntityFromApi');
    $fetchEntityFromApi->setAccessible(TRUE);

    $result = $fetchEntityFromApi->invokeArgs(
      $ecmsApi, [
        self::ACCESS_TOKEN,
        $this->url,
        $this->entity,
      ]
    );

    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for the testFetchEntityFromApi method.
   *
   * @return array[]
   *   Parameters to pass to the testFetchEntityFromApi method.
   */
  public function dataProviderForTestFetchEntityFromApi(): array {
    return [
      'test1' => [
        -1,
        TRUE,
        NULL,
      ],
      'test2' => [
        200,
        TRUE,
        self::JSON_DATA_OBJECT_STRING,
      ],
      'test3' => [
        404,
        TRUE,
        NULL,
      ],
    ];
  }

  /**
   * Test the submit entity method with media & file fields.
   *
   * @param string $type
   *   The type of entity to expect.
   * @param bool $expected
   *   The expected result.
   *
   * @dataProvider dataProviderForTestSubmitEntityWithFileFields
   */
  public function testSubmitEntityWithFileFields(string $type, bool $expected): void {
    switch ($type) {
      case 'media':
        $localEntity = $this->createMock(MediaInterface::class);

        $source = $this->createMock(File::class);

        $source->expects($this->once())
          ->method('getConfiguration')
          ->willReturn(['source_field' => self::MEDIA_SOURCE_FIELD_NAME]);

        $source->expects($this->once())
          ->method('getSourceFieldValue')
          ->with($localEntity)
          ->willReturn(self::MEDIA_SOURCE_FILE_ID);

        $localEntity->expects($this->exactly(2))
          ->method('getSource')
          ->willReturn($source);

        break;

      case 'fieldable':
        $fileEntity = $this->createMock(FileInterface::class);
        $fileEntity->expects($this->once())
          ->method('id')
          ->willReturn(self::MEDIA_SOURCE_FILE_ID);

        $fieldItem = $this->createMock(FieldItemInterface::class);
        $fieldItem->expects($this->once())
          ->method('__get')
          ->with('entity')
          ->willReturn($fileEntity);

        $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
        $fieldDefinition->expects($this->once())
          ->method('getType')
          ->willReturn('image');

        $fieldItemList = $this->createMock(FieldItemListInterface::class);
        $fieldItemList->expects($this->once())
          ->method('getFieldDefinition')
          ->willReturn($fieldDefinition);

        $fieldItemList->expects($this->once())
          ->method('isEmpty')
          ->willReturn(FALSE);
        $fieldItemList->expects($this->once())
          ->method('first')
          ->willReturn($fieldItem);
        $fieldItemList->expects($this->once())
          ->method('getName')
          ->willReturn(self::MEDIA_SOURCE_FIELD_NAME);

        $localEntity = $this->createMock(FieldableEntityInterface::class);
        $localEntity->expects($this->once())
          ->method('getFields')
          ->willReturn([$fieldItemList]);

        break;
    }

    $language = $this->createMock(LanguageInterface::class);
    $language->expects($this->once())
      ->method('getId')
      ->willReturn(self::LANGUAGE_ID);

    $language->expects($this->once())
      ->method('isDefault')
      ->willReturn(TRUE);

    $localEntity->expects($this->once())
      ->method('language')
      ->willReturn($language);

    $this->url->expects($this->once())
      ->method('toString')
      ->willReturn(self::ENDPOINT_URL);

    $localEntity->expects($this->once())
      ->method('getEntityTypeId')
      ->willReturn(self::ENTITY_TYPE);

    $localEntity->expects($this->exactly(2))
      ->method('bundle')
      ->willReturn(self::ENTITY_BUNDLE);

    $localEntity->expects($this->exactly(2))
      ->method('uuid')
      ->willReturn(self::ENTITY_UUID);

    $ecmsApi = $this->getMockBuilder(EcmsApiBase::class)
      ->setConstructorArgs([
        $this->httpclient,
        $this->entityToJsonApi,
        $this->ecmsApiHelper,
      ])
      ->onlyMethods(['checkEntityExists', 'submitSourceFileEntity'])
      ->getMock();

    $ecmsApi->expects($this->once())
      ->method('checkEntityExists')
      ->with(self::ACCESS_TOKEN, $this->url, $localEntity)
      ->willReturn('POST');

    $ecmsApi->expects($this->once())
      ->method('submitSourceFileEntity')
      ->with($localEntity, self::ACCESS_TOKEN, $this->url, self::MEDIA_SOURCE_FILE_ID, self::MEDIA_SOURCE_FIELD_NAME)
      ->willReturn(self::MEDIA_SOURCE_FILE_UUID);

    $this->entityToJsonApi->expects($this->once())
      ->method('normalize')
      ->with($localEntity)
      ->willReturn(self::NORMALIZED_MEDIA_ENTITY);

    $request = $this->createMock(ResponseInterface::class);
    $request->expects($this->once())
      ->method('getStatusCode')
      ->willReturn(201);
    $this->httpclient->expects($this->once())
      ->method('request')
      ->with('POST', self::ENTITY_ENDPOINT, self::MEDIA_PAYLOAD)
      ->willReturn($request);

    $submitEntity = new \ReflectionMethod(EcmsApiBase::class, 'submitEntity');
    $submitEntity->setAccessible(TRUE);

    $result = $submitEntity->invokeArgs(
      $ecmsApi, [
        self::ACCESS_TOKEN,
        $this->url,
        $localEntity,
      ]
    );

    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for the testSubmitEntityWithFileFields method.
   *
   * @return array[]
   *   Array of parameters to pass to testSubmitEntityWithFileFields.
   */
  public function dataProviderForTestSubmitEntityWithFileFields(): array {
    return [
      'test1' => ['media', TRUE],
      'test2' => ['fieldable', TRUE],
    ];
  }

  /**
   * Test the setParagraphEntityRevisionIds method.
   */
  public function testSetParagraphEntityRevisionIds(): void {
    $references = [];

    $nonParagraphEntity = $this->createMock(EntityInterface::class);

    $references[] = $nonParagraphEntity;

    $uuidField = $this->createMock(FieldItemListInterface::class);
    $uuidField->expects($this->once())
      ->method('__get')
      ->with('value')
      ->willReturn(self::PARAGRAPH_ENTITY_UUID);

    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->expects($this->once())
      ->method('get')
      ->with('uuid')
      ->willReturn($uuidField);

    $references[] = $paragraph;
    $this->entity->expects($this->once())
      ->method('referencedEntities')
      ->willReturn($references);

    $ecmsApi = $this->getMockBuilder(EcmsApiBase::class)
      ->setConstructorArgs([
        $this->httpclient,
        $this->entityToJsonApi,
        $this->ecmsApiHelper,
      ])
      ->onlyMethods(['fetchEntityFromApi'])
      ->getMock();

    $jsonData = json_decode(self::PARAGRAPH_ENTITY_JSON);
    $ecmsApi->expects($this->once())
      ->method('fetchEntityFromApi')
      ->with(self::ACCESS_TOKEN, $this->url, $paragraph)
      ->willReturn($jsonData->data);

    $setParagraphEntityRevisionIds = new \ReflectionMethod(EcmsApiBase::class, 'setParagraphEntityRevisionIds');
    $setParagraphEntityRevisionIds->setAccessible(TRUE);

    $relationships = [
      'field_test' => [
        'id' => self::PARAGRAPH_ENTITY_UUID,
        'meta' => [
          'target_revision_id' => 99999,
        ],
      ],
    ];

    $setParagraphEntityRevisionIds->invokeArgs(
      $ecmsApi, [
        &$relationships,
        $this->entity,
        self::ACCESS_TOKEN,
        $this->url,
      ]
    );

    $this->assertEquals(58, $relationships['field_test']['meta']['target_revision_id']);

  }

}
