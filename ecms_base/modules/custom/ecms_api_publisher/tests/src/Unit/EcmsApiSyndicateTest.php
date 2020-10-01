<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_api_publisher\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\ecms_api_publisher\EcmsApiSyndicate;
use Drupal\ecms_api_publisher\Entity\EcmsApiSiteInterface;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Class EcmsApiSyndicateTest.
 *
 * @package Drupal\Tests\ecms_api_publisher\Unit
 * @covers \Drupal\ecms_api_publisher\EcmsApiSyndicate
 * @group ecms
 * @group ecms_api
 * @group ecms_api_publisher
 */
class EcmsApiSyndicateTest extends UnitTestCase {

  /**
   * The queue name to test with.
   */
  const SYNDICATE_QUEUE = 'ecms_api_publisher_queue';

  /**
   * The bundle to test with.
   */
  const NODE_TYPE = 'test_node_type';

  /**
   * Mock of the entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $entityTypeManager;

  /**
   * Mock of the queue interface.
   *
   * @var \Drupal\Core\Queue\QueueInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $queue;

  /**
   * Mock of the messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $messenger;

  /**
   * Array of EcmsApiInterface items.
   *
   * @var array
   */
  private $ecmsApiSites;

  /**
   * Mock of the queue service.
   *
   * @var \Drupal\Core\Queue\QueueFactory|\PHPUnit\Framework\MockObject\MockObject
   */
  private $queueFactory;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->queue = $this->createMock(QueueInterface::class);
    $this->queueFactory = $this->createMock(QueueFactory::class);
    $this->queueFactory->expects($this->once())
      ->method('get')
      ->with(self::SYNDICATE_QUEUE)
      ->willReturn($this->queue);

    $this->ecmsApiSites = [
      $this->createMock(EcmsApiSiteInterface::class),
      $this->createMock(EcmsApiSiteInterface::class),
    ];
    $this->messenger = $this->createMock(MessengerInterface::class);

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());

    \Drupal::setContainer($container);
  }

  /**
   * Test the syndicateNode method.
   */
  public function testSyndicateNodeSuccess(): void {
    $entity = $this->createMock(NodeInterface::class);

    $entity->expects($this->once())
      ->method('bundle')
      ->willReturn(self::NODE_TYPE);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadByProperties')
      ->with(['content_type' => self::NODE_TYPE])
      ->willReturn($this->ecmsApiSites);

    $this->queue->expects($this->exactly(count($this->ecmsApiSites)))
      ->method('createItem');

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('ecms_api_site')
      ->willReturn($storage);

    $this->messenger->expects($this->once())
      ->method('addMessage');

    $this->messenger->expects($this->once())
      ->method('addWarning');

    $ecmsApiSyndicate = new EcmsApiSyndicate($this->entityTypeManager, $this->queueFactory, $this->messenger);
    $ecmsApiSyndicate->syndicateNode($entity);

  }

  /**
   * Test the syndicateNode method with no api sites available.
   */
  public function testSyndicateNodeNone(): void {
    $entity = $this->createMock(NodeInterface::class);

    $entity->expects($this->once())
      ->method('bundle')
      ->willReturn(self::NODE_TYPE);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadByProperties')
      ->with(['content_type' => self::NODE_TYPE])
      ->willReturn([]);

    $this->queue->expects($this->never())
      ->method('createItem');

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('ecms_api_site')
      ->willReturn($storage);

    $this->messenger->expects($this->never())
      ->method('addMessage');

    $this->messenger->expects($this->never())
      ->method('addWarning');

    $ecmsApiSyndicate = new EcmsApiSyndicate($this->entityTypeManager, $this->queueFactory, $this->messenger);
    $ecmsApiSyndicate->syndicateNode($entity);

  }

}
