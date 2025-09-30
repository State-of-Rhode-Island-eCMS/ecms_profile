<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_api_notification_publisher\Unit;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\ecms_api\EcmsApiHelper;
use Drupal\ecms_api_notification_publisher\NotificationPublisher;
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
 * Unit tests for the NotificationPublisher class.
 *
 * @package Drupal\Tests\ecms_api_notification_publisher\Unit
 *
 */
#[Group("ecms_api_notification_publisher")]
#[Group("ecms_api")]
#[Group("ecms")]
#[CoversClass(\Drupal\ecms_api_notification_publisher\NotificationPublisher::class)]
class NotificationPublisherTest extends UnitTestCase {

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
   * @var \Drupal\node\NodeInterface|\PHPUnit\Framework\MockObject\MockObject
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
    $this->node = $this->createMock(NodeInterface::class);
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
   *   none: assume the field doe not exist.
   *   empty: assume the field is empty.
   *
   */
  #[DataProvider('dataProviderForBroadcastNotification')]
  public function testBroadcastNotification(
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

    if ($nodeType === 'notification') {
      $hasGlobalField = TRUE;

      if ($global === -1) {
        $hasGlobalField = FALSE;
      }

      if ($global === 1) {
        $hasFieldCount = 2;
      }

      $this->node->expects($this->exactly($hasFieldCount))
        ->method('hasField')
        ->will($this->returnValueMap([
          ['field_notification_global',$hasGlobalField],
          ['moderation_state', $hasModerationField],
        ]));

      if ($hasGlobalField) {
        $getFieldCount = 1;

        $moderationItemList = $this->createMock(FieldItemListInterface::class);

        if ($hasModerationField) {
          $getFieldCount = 2;

          $moderationArray = [0 => ['value' => $moderation]];

          if ($moderation === 'empty') {
            // Mimic an empty field.
            $moderationArray = [];
          }

          $moderationItemList->expects($this->once())
            ->method('getValue')
            ->willReturn($moderationArray);
        }

        $globalNotification = [0 => ['value' => $global]];

        if ($global === 2) {
          // Mimic an empty field.
          $globalNotification = [];
        }

        $fieldItemList = $this->createMock(FieldItemListInterface::class);
        $fieldItemList->expects($this->once())
          ->method('getValue')
          ->willReturn($globalNotification);

        $this->node->expects($this->exactly($getFieldCount))
          ->method('get')
          ->will($this->returnValueMap([
            ['field_notification_global', $fieldItemList],
            ['moderation_state', $moderationItemList],
          ]));
      }
    }

    $notificationPublisher = new NotificationPublisher($this->httpclient, $this->entityToJsonApi, $this->ecmsApiHelper, $this->ecmsApiSyndicate);
    $notificationPublisher->broadcastNotification($this->node);

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
        -1,
        Random::machineName(8),
      ],
      'test2' => [
        'notification',
        -1,
        Random::machineName(8),
      ],
      'test3' => [
        'notification',
        0,
        'none',
      ],
      'test4' => [
        'notification',
        1,
        'none',
      ],
      'test5' => [
        'notification',
        2,
        'none',
      ],
      'test6' => [
        'notification',
        1,
        'review',
      ],
      'test7' => [
        'notification',
        1,
        Random::machineName(8),
      ],
      'test8' => [
        'notification',
        1,
        'published',
      ],
      'test9' => [
        'notification',
        1,
        'empty',
      ],
    ];
  }

}
