<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_api_emergency_notification_publisher\Unit;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\ecms_api\EcmsApiHelper;
use Drupal\ecms_api_emergency_notification_publisher\EmergencyNotificationPublisher;
use Drupal\ecms_api_publisher\EcmsApiSyndicate;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\TestTools\Random;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for the EmergencyNotificationPublisher class.
 *
 * @package Drupal\Tests\ecms_api_emergency_notification_publisher\Unit
 *
 */
#[Group("ecms_api_emergency_notification_publisher")]
#[Group("ecms_api")]
#[Group("ecms")]
#[CoversClass(\Drupal\ecms_api_emergency_notification_publisher\EmergencyNotificationPublisher::class)]
class EmergencyNotificationPublisherTest extends UnitTestCase {

  /**
   * The http_client service mock.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $httpclient;

  /**
   * The jsonapi_extras.entity.to_jsonapi service mock.
   *
   * @var \Drupal\jsonapi_extras\EntityToJsonApi|\PHPUnit\Framework\MockObject\MockObject
   */
  private $entityToJsonApi;

  /**
   * Mock of the ecms_api_helper service.
   *
   * @var \Drupal\ecms_api\EcmsApiHelper|\PHPUnit\Framework\MockObject\MockObject
   */
  private $ecmsApiHelper;

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
   * The actual node to test with.
   *
   * @var \Drupal\node\Entity\Node|\PHPUnit\Framework\MockObject\MockObject
   */
  private $node;

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
    $this->node = $this->createMock(Node::class);
  }

  /**
   * Test the broadCastNotification method.
   *
   * @param string $nodeType
   *   The node type to test with.
   * @param int $global
   *   Whether the notification is global.
   *   -1: The field is missing.
   *   0 : The field is not selected.
   *   1 : The field is selected.
   *   2 : The field is empty.
   * @param string $moderation
   *   The moderation state of the node.
   *   none: assume the field does not exist.
   *   empty: assume the field is empty.
   *
   */
  #[DataProvider('dataProviderForBroadcastNotification')]
  public function testBroadcastNotification(
    string $nodeType,
    string $moderation,
  ): void {

    $hasModerationField = TRUE;

    if ($moderation === 'none') {
      $hasModerationField = FALSE;
    }

    $this->node->expects($this->once())
      ->method('getType')
      ->willReturn($nodeType);

    if ($nodeType === 'emergency_notification') {

      $moderationItemList = $this->createMock(FieldItemListInterface::class);
      $originalModerationItemList = $this->createMock(FieldItemListInterface::class);

      if ($hasModerationField) {

        $this->node->expects($this->once())
          ->method('hasField')
          ->with('moderation_state')
          ->willReturn(TRUE);

        $moderationArray = [0 => ['value' => $moderation]];

        $originalModerationItemList->expects($this->once())
          ->method('getValue')
          ->willReturn([0 => ['value' => 'archived']]);

        $this->originalNode->expects($this->once())
          ->method('get')
          ->with('moderation_state')
          ->willReturn($originalModerationItemList
          );

       // $this->node->original = $originalModerationItemList;
        $this->node->expects($this->exactly(2))
          ->method('__get')
          ->with('original')
          ->willReturn($this->originalNode);

        $this->node->expects($this->once())
          ->method('get')
          ->will($this->returnValueMap([
            ['moderation_state', $moderationItemList],
          ]));

        if ($moderation === 'empty') {
          // Mimic an empty field.
          $moderationArray = [];
        }

        $moderationItemList->expects($this->once())
          ->method('getValue')
          ->willReturn($moderationArray);

        $this->node->expects($this->once())
          ->method('get')
          ->will($this->returnValueMap([
            ['moderation_state', $moderationItemList],
          ]));
      }
    }

    $emergencyNotificationPublisher = new EmergencyNotificationPublisher($this->httpclient, $this->entityToJsonApi, $this->ecmsApiHelper, $this->ecmsApiSyndicate);
    $emergencyNotificationPublisher->broadcastNotification($this->node);

  }

  /**
   * Data provider for the testBroadcastNotification method.
   *
   * @return array
   *   Array of parameters to pass to testBroadcastNotification.
   */
  public static function dataProviderForBroadcastNotification(): array {
    return [
      'test1' => [
        Random::machineName(8),
        Random::machineName(8),
      ],
      'test2' => [
        'notification',
        Random::machineName(8),
      ],
      'test3' => [
        'emergency_notification',
        'none',
      ],
      'test4' => [
        'emergency_notification',
        'none',
      ],
      'test5' => [
        'emergency_notification',
        'none',
      ],
      'test6' => [
        'emergency_notification',
        'review',
      ],
      'test7' => [
        'emergency_notification',
        Random::machineName(8),
      ],
      'test8' => [
        'emergency_notification',
        'published',
      ],
      'test9' => [
        'emergency_notification',
        'empty',
      ],
    ];
  }

}
