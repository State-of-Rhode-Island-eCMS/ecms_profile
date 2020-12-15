<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_api_publisher\Unit;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\ecms_api_publisher\EcmsApiSyndicate;
use Drupal\ecms_api_publisher\Entity\EcmsApiSiteInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;

/**
 * Unit testing for the EcmsApiSyndicate class.
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
    $container->set('url_generator', $this->createMock(UrlGeneratorInterface::class));

    \Drupal::setContainer($container);
  }

  /**
   * Test the syndicateEntity method.
   */
  public function testsyndicateEntitySuccess(): void {
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
    $ecmsApiSyndicate->syndicateEntity($entity);
  }

  /**
   * Test the syndicateEntity method with no api sites available.
   */
  public function testsyndicateEntityNone(): void {
    $entity = $this->createMock(NodeInterface::class);

    $this->queue->expects($this->never())
      ->method('createItem');

    $entity->expects($this->once())
      ->method('bundle')
      ->willReturn(self::NODE_TYPE);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadByProperties')
      ->with(['content_type' => self::NODE_TYPE])
      ->willReturn([]);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('ecms_api_site')
      ->willReturn($storage);

    $this->messenger->expects($this->never())
      ->method('addMessage');

    $this->messenger->expects($this->never())
      ->method('addWarning');

    $ecmsApiSyndicate = new EcmsApiSyndicate($this->entityTypeManager, $this->queueFactory, $this->messenger);
    $ecmsApiSyndicate->syndicateEntity($entity);

  }

  /**
   * Test the syndicateEntity method with references.
   */
  public function testsyndicateEntityReferences(): void {
    $entity = $this->createMock(EntityInterface::class);

    $configEntity = $this->createMock(ConfigEntityBase::class);
    $fileEntity = $this->createMock(FileInterface::class);
    $userEntity = $this->createMock(UserInterface::class);
    $mediaEntity = $this->createMock(MediaInterface::class);

    $references = [
      $configEntity,
      $fileEntity,
      $userEntity,
      $mediaEntity,
    ];

    $entity->expects($this->atLeastOnce())
      ->method('referencedEntities')
      ->willReturn($references);

    $entity->expects($this->once())
      ->method('bundle')
      ->willReturn(self::NODE_TYPE);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadByProperties')
      ->with(['content_type' => self::NODE_TYPE])
      ->willReturn($this->ecmsApiSites);

    $this->queue->expects($this->exactly(count($this->ecmsApiSites) * 2))
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
    $ecmsApiSyndicate->syndicateEntity($entity);
  }

}
