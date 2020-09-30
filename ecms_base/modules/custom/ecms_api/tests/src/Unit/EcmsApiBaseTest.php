<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_api\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\ecms_api\EcmsApiBase;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class EcmsApiBaseTest.
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
          'uuid' => self::ENTITY_UUID,
        ],
      ],
    ],
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
      ],
    ],
  ];

  /**
   * The endpoint for the node_type api.
   */
  const NODE_TYPE_ENDPOINT = '/EcmsApi/node_type/node_type';

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
   * Mock response to return from Guzzle.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Psr\Http\Message\ResponseInterface
   */
  private $response;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

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
      ->setConstructorArgs([$this->httpclient, $this->entityToJsonApi])
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
   * @param bool $expected
   *   The expected response.
   *
   * @dataProvider dataProviderForTestSubmitEntity
   */
  public function testSubmitEntity(string $method, int $code, bool $expected): void {
    $endpoint = self::ENTITY_ENDPOINT;
    $uuidCount = 2;

    // Test for all allowed requests.
    if ($code !== 0) {
      $this->url->expects($this->once())
        ->method('toString')
        ->willReturn(self::ENDPOINT_URL);

      $this->entity->expects($this->once())
        ->method('getEntityTypeId')
        ->willReturn(self::ENTITY_TYPE);

      $this->entity->expects($this->exactly(2))
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

      $this->entityToJsonApi->expects($this->once())
        ->method('normalize')
        ->with($this->entity)
        ->willReturn(self::NORMALIZED_ENTITY);
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

    $ecmsApi = $this->getMockBuilder(EcmsApiBase::class)
      ->setConstructorArgs([$this->httpclient, $this->entityToJsonApi])
      ->getMock();

    $submitEntity = new \ReflectionMethod(EcmsApiBase::class, 'submitEntity');
    $submitEntity->setAccessible(TRUE);

    $result = $submitEntity->invokeArgs(
      $ecmsApi, [
        $method,
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
        FALSE,
      ],
      'test2' => [
        'POST',
        201,
        TRUE,
      ],
      'test3' => [
        'PATCH',
        200,
        TRUE,
      ],
      'test4' => [
        'POST',
        401,
        FALSE,
      ],
      'test5' => [
        'POST',
        -1,
        FALSE,
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
      ->setConstructorArgs([$this->httpclient, $this->entityToJsonApi])
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

}
