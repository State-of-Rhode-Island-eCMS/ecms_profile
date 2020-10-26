<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_api_recipient\Unit;

use Drupal\ecms_api_recipient\JsonApiHelper;
use Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class JsonApiHelperTest
 *
 * @package Drupal\Tests\ecms_api_recipient\Unit
 *
 * @group ecms_api
 * @group ecms_api_recipient
 */
class JsonApiHelperTest extends UnitTestCase {

  /**
   * Expected array from the convert method.
   */
  const TEST_ARRAY = [
    1 =>  [
      2 => [
        'three',
        4,
      ],
      5 => [
        'six' => [
          7,
          8,
          9,
        ],
      ]
    ],
    10 => [
      'eleven' => [
        12,
        '13',
        14,
      ]
    ],
  ];

  /**
   * The json object to test with.
   */
  const JSON_DATA_ARRAY = [
    'type' => 'node--notification',
    'attributes' => [
      'field_test' => 'test',
    ],
  ];

  /**
   * Mock of the seriaalizer.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * Mock of the JsonApiDocumentTopLevelNormalizer.
   *
   * @var \Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $jsonapiDocumentTopLevelNormalizer;

  /**
   * Mock of the ResourceTypeRepositoryInterface.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $resourceTypeRepository;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->serializer = $this->createMock(SerializerInterface::class);
    $this->jsonapiDocumentTopLevelNormalizer = $this->createMock(JsonApiDocumentTopLevelNormalizer::class );
    $this->resourceTypeRepository = $this->createMock(ResourceTypeRepositoryInterface::class);

  }

  /**
   * Test the convertJsonDataToArray method.
   */
  public function testConvertJsonDataToArray(): void {

    $data = [
      1 =>  (object) [
        2 => [
          'three',
          4,
        ],
        5 => [
          'six' => (object)[
            7,
            8,
            9,
          ],
        ]
      ],
      10 => (object) [
        'eleven' => (object)[
          12,
          13,
          14,
        ]
      ],
    ];
    $helperClass = new JsonApiHelper($this->serializer, $this->jsonapiDocumentTopLevelNormalizer, $this->resourceTypeRepository);

    $result = $helperClass->convertJsonDataToArray($data);

    $this->assertEquals(self::TEST_ARRAY, $result);
  }

  /**
   * Test the extractEntity method.
   */
  public function testExtractEntity(): void {
    $expected = [
      'data' => [
        'type' => self::JSON_DATA_ARRAY['type'],
        'attributes' => self::JSON_DATA_ARRAY['attributes'],
      ],
    ];

    $node = $this->createMock(NodeInterface::class);

    $resourceType = $this->createMock(ResourceType::class);
    $this->resourceTypeRepository->expects($this->once())
      ->method('getByTypeName')
      ->with(self::JSON_DATA_ARRAY['type'])
      ->wilLReturn($resourceType);

    $this->jsonapiDocumentTopLevelNormalizer->expects($this->once())
      ->method('denormalize')
      ->with($expected, NULL, 'api_json', [
        'resource_type' => $resourceType,
      ])
    ->willReturn($node);

    $helperClass = new JsonApiHelper($this->serializer, $this->jsonapiDocumentTopLevelNormalizer, $this->resourceTypeRepository);

    $result = $helperClass->extractEntity(self::JSON_DATA_ARRAY);

    $this->assertInstanceOf('\Drupal\node\NodeInterface', $result);
  }

}
