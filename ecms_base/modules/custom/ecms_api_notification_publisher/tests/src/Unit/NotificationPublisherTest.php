<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_api_notification_publisher\Unit;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\ecms_api_notification_publisher\NotificationPublisher;
use Drupal\ecms_api_publisher\EcmsApiSyndicate;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;

/**
 * Unit tests for the NotificationPublisher class.
 *
 * @package Drupal\Tests\ecms_api_notification_publisher\Unit
 * @covers \Drupal\ecms_api_notification_publisher\NotificationPublisher
 *
 * @group ecms
 * @group ecms_api
 * @group ecms_api_notification_publisher
 */
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
    $this->httpclient = $this->createMock(ClientInterface::class);
    $this->entityToJsonApi = $this->createMock(EntityToJsonApi::class);
    $this->ecmsApiSyndicate = $this->createMock(EcmsApiSyndicate::class);

    $this->originalNode = $this->createMock(NodeInterface::class);
    $this->node = $this->createMock(NodeInterface::class);
  }

  /**
   * Test the broadCastNotification method.
   *
   * @param bool $isNotification
   *   Whether the node is a notification type.
   * @param bool $isGlobal
   *   Whether the notification is a global notification.
   * @param bool $isNew
   *   Whether the node is new or not.
   * @param bool $originalPublished
   *   Is the original node published.
   * @param bool $nodePublished
   *   Is the actual node published.
   * @param bool $globalEmpty
   *   Is the global notification field unselected.
   *
   * @dataProvider dataProviderForBroadcastNotification
   */
  public function testBroadcastNotification(
    bool $isNotification,
    bool $isGlobal,
    bool $isNew,
    bool $originalPublished,
    bool $nodePublished,
    bool $globalEmpty
  ): void {
    $isPublishedCount = 2;
    if ($nodePublished && !$originalPublished) {
      $isPublishedCount = 1;
    }
    if (!$isNotification) {
      $this->node->expects($this->once())
        ->method('getType')
        ->willReturn($this->randomMachineName());
    }
    else {
      $this->node->expects($this->once())
        ->method('getType')
        ->willReturn('notification');

      $this->node->expects($this->once())
        ->method('hasField')
        ->with('field_notification_global')
        ->willReturn($isGlobal);

      if ($isGlobal) {
        if ($globalEmpty) {
          $globalNotificationArray = [];
        }
        else {
          $globalNotificationArray = [0 => ['value' => (int) $isGlobal]];

          $this->node->expects($this->exactly($isPublishedCount))
            ->method('isPublished')
            ->willReturn($nodePublished);

          if (!$isNew) {
            $this->originalNode->expects($this->once())
              ->method('isPublished')
              ->willReturn($originalPublished);
          }
        }

        $fieldItemList = $this->createMock(FieldItemListInterface::class);
        $fieldItemList->expects($this->once())
          ->method('getValue')
          ->willReturn($globalNotificationArray);

        $this->node->expects($this->once())
          ->method('get')
          ->with('field_notification_global')
          ->willReturn($fieldItemList);

        if (!$globalEmpty && (
          ($nodePublished && $nodePublished !== $originalPublished) ||
          ($originalPublished && $originalPublished !== $nodePublished)
          )) {
          $this->ecmsApiSyndicate->expects($this->once())
            ->method('syndicateNode')
            ->with($this->node, 'INSERT');
        }
      }
    }

    $notificationPublisher = new NotificationPublisher($this->httpclient, $this->entityToJsonApi, $this->ecmsApiSyndicate);

    $this->node->original = NULL;
    if (!$isNew) {
      $this->node->original = $this->originalNode;
    }

    $notificationPublisher->broadcastNotification($this->node);

  }

  /**
   * Data provider for the testBroadcastNotification method.
   *
   * @return array
   *   Array of parameters to pass to testBroadcastNotification.
   */
  public function dataProviderForBroadcastNotification(): array {
    return [
      'Not a notification node.' => [
        FALSE,
        FALSE,
        FALSE,
        FALSE,
        FALSE,
        FALSE,
      ],
      'Notification node that is not global' => [
        TRUE,
        FALSE,
        FALSE,
        FALSE,
        FALSE,
        FALSE,
      ],
      'Notification that is global but not published and not new' => [
        TRUE,
        TRUE,
        FALSE,
        FALSE,
        FALSE,
        FALSE,
      ],
      'Global notification that transitioned to not published' => [
        TRUE,
        TRUE,
        FALSE,
        TRUE,
        FALSE,
        FALSE,
      ],
      'Global notification that transitioned to published' => [
        TRUE,
        TRUE,
        FALSE,
        FALSE,
        TRUE,
        FALSE,
      ],
      'New global notification node that is published' => [
        TRUE,
        TRUE,
        TRUE,
        FALSE,
        TRUE,
        FALSE,
      ],
      'New global notification node that is not published' => [
        TRUE,
        TRUE,
        TRUE,
        FALSE,
        FALSE,
        FALSE,
      ],
      'Existing global notification node that is not published' => [
        TRUE,
        TRUE,
        FALSE,
        TRUE,
        TRUE,
        FALSE,
      ],
      'Existing global notification node that has transitioned to not published and has the global notification de-selected' => [
        TRUE,
        TRUE,
        FALSE,
        FALSE,
        TRUE,
        TRUE,
      ],
    ];
  }

}
