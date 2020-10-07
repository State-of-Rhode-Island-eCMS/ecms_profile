<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_api_publisher\Unit\Plugin\QueueWorker;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Url;
use Drupal\ecms_api_publisher\EcmsApiPublisher;
use Drupal\ecms_api_publisher\Entity\EcmsApiSiteInterface;
use Drupal\ecms_api_publisher\Plugin\QueueWorker\EcmsApiSyndicateQueueWorker;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Unit testing for the EcmsApiSyndicateQueueWorker class.
 *
 * @covers \Drupal\ecms_api_publisher\Plugin\QueueWorker\EcmsApiSyndicateQueueWorker
 * @group ecms
 * @group ecms_api
 * @group ecms_api_publisher
 *
 * @package Drupal\Tests\ecms_api_publisher\Unit\Plugin\QueueWorker
 */
class EcmsApiSyndicateQueueWorkerTest extends UnitTestCase {

  /**
   * Mock the EcmsApiPublisher service.
   *
   * @var \Drupal\ecms_api_publisher\EcmsApiPublisher|\PHPUnit\Framework\MockObject\MockObject
   */
  private $ecmsApiPublisher;

  /**
   * The QueueWorker class to test.
   *
   * @var \Drupal\Core\Plugin\ContainerFactoryPluginInterface|\Drupal\ecms_api_publisher\Plugin\QueueWorker\EcmsApiSyndicateQueueWorker
   */
  private $queueWorker;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->ecmsApiPublisher = $this->getMockBuilder(EcmsApiPublisher::class)
      ->onlyMethods(['syndicateNode'])
      ->disableOriginalConstructor()
      ->getMock();

    $container = new ContainerBuilder();

    $container->set('ecms_api_publisher.publisher', $this->ecmsApiPublisher);
    $container->set('string_translation', $this->getStringTranslationStub());

    \Drupal::setContainer($container);

    $this->queueWorker = EcmsApiSyndicateQueueWorker::create($container, [], '', []);
  }

  /**
   * Test the processItem method.
   *
   * @param bool $expected
   *   The expected result of the http call.
   *
   * @dataProvider dataProviderForProcessItem
   */
  public function testProcessItem(bool $expected): void {
    $method = 'POST';
    $url = $this->createMock(Url::class);
    $node = $this->createMock(NodeInterface::class);

    $linkItem = $this->createMock(LinkItem::class);
    $linkItem->expects($this->once())
      ->method('getUrl')
      ->willReturn($url);
    $siteEntity = $this->createMock(EcmsApiSiteInterface::class);
    $siteEntity->expects($this->once())
      ->method('getApiEndpoint')
      ->willReturn($linkItem);

    $this->ecmsApiPublisher->expects($this->once())
      ->method('syndicateNode')
      ->with($method, $url, $node)
      ->willReturn($expected);

    if (!$expected) {
      $this->expectException(RequeueException::class);
    }

    $data = [
      'site_entity' => $siteEntity,
      'syndicated_content_entity' => $node,
      'method' => $method,
    ];

    $this->queueWorker->processItem($data);
  }

  /**
   * Data provider for the testProcessItem method.
   *
   * @return array
   *   Parameters to pass to testProcessItem().
   */
  public function dataProviderForProcessItem(): array {
    return [
      'test1' => [FALSE],
      'test2' => [TRUE],
    ];
  }

}
