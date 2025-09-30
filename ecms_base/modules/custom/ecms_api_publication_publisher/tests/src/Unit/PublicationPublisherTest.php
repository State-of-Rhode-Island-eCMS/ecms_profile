<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_api_publication_publisher\Unit;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\ecms_api\EcmsApiHelper;
use Drupal\ecms_api_publication_publisher\PublicationPublisher;
use Drupal\ecms_api_publisher\EcmsApiSyndicate;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\TestTools\Random;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for the PublicationPublisher class.
 *
 * @package Drupal\Tests\ecms_api_publication_publisher\Unit
 *
 */
#[Group("ecms_api_publication_publisher")]
#[Group("ecms_api")]
#[Group("ecms")]
#[CoversClass(\Drupal\ecms_api_publication_publisher\PublicationPublisher::class)]
class PublicationPublisherTest extends UnitTestCase {

  /**
   * The ecms_api_publisher.syndicate service mock.
   *
   * @var \Drupal\ecms_api_publisher\EcmsApiSyndicate|\PHPUnit\Framework\MockObject\MockObject
   */
  private $ecmsApiSyndicate;

  /**
   * The original node to test with.
   *
   * @var \Drupal\node\NodeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $originalNode;

  /**
   * Mock of the http_client service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpclient;

  /**
   * Mock of the entity_to_jsonapi service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityToJsonApi;

  /**
   * The actual node to test with.
   *
   * @var \Drupal\node\NodeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $node;

  /**
   * Mock of the ecms_api_helper service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $ecmsApiHelper;

  /**
   * {@inheritDoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->httpclient = $this->createMock(ClientInterface::class);
    $this->entityToJsonApi = $this->createMock(EntityToJsonApi::class);
    $this->ecmsApiSyndicate = $this->createMock(EcmsApiSyndicate::class);
    $this->ecmsApiHelper = $this->createMock(EcmsApiHelper::class);

    $this->originalNode = $this->createMock(NodeInterface::class);
    $this->node = $this->createMock(NodeInterface::class);
  }

  /**
   * Test the broadcastPublication method.
   *
   * @param string $nodeType
   *   The node type to test with.
   * @param int $global
   *   Whether the publication is global.
   *   -1: The field is missing.
   *   0 : The field is not selected.
   *   1 : The field is selected.
   *   2 : The field is empty.
   * @param string $moderation
   *   The moderation state of the node.
   *   none: assume the field doe not exist.
   *   empty: assume the field is empty.
   *
   */
  #[DataProvider('dataProviderForBroadcastPublication')]
  public function testBroadcastPublication(
    string $nodeType,
    int $global,
    string $moderation,
  ): void {

    $hasFieldCount = 1;
    $hasModerationField = TRUE;

    if ($moderation === 'none') {
      $hasModerationField = FALSE;
    }

    $this->node->expects($this->once())
      ->method('getType')
      ->willReturn($nodeType);

    if ($nodeType === 'publication') {

      $this->node->expects($this->exactly($hasFieldCount))
        ->method('hasField')
        ->with('moderation_state')
        ->willReturn($hasModerationField);

      if ($hasModerationField) {
        $moderationItemList = $this->createMock(FieldItemListInterface::class);
        $moderationArray = [0 => ['value' => $moderation]];

        if ($moderation === 'empty') {
          // Mimic an empty field.
          $moderationArray = [];
        }

        $moderationItemList->expects($this->once())
          ->method('getValue')
          ->willReturn($moderationArray);

        $this->node->expects($this->once())
          ->method('get')
          ->with('moderation_state')
          ->willReturn($moderationItemList);
      }

    }

    $publicationPublisher = new PublicationPublisher($this->ecmsApiSyndicate);
    $publicationPublisher->broadcastPublication($this->node);
  }

  /**
   * Data provider for the testBroadcastPublication method.
   *
   * @return array
   *   Array of parameters to pass to testBroadcastPublication.
   */
  public static function dataProviderForBroadcastPublication(): array {
    return [
      'test1' => [
        Random::machineName(8),
        -1,
        Random::machineName(8),
      ],
      'test2' => [
        'publication',
        -1,
        Random::machineName(8),
      ],
      'test3' => [
        'publication',
        0,
        'none',
      ],
      'test4' => [
        'publication',
        1,
        'none',
      ],
      'test5' => [
        'publication',
        2,
        'none',
      ],
      'test6' => [
        'publication',
        1,
        'review',
      ],
      'test7' => [
        'publication',
        1,
        Random::machineName(8),
      ],
      'test8' => [
        'publication',
        1,
        'published',
      ],
      'test9' => [
        'publication',
        1,
        'empty',
      ],
    ];
  }

}
