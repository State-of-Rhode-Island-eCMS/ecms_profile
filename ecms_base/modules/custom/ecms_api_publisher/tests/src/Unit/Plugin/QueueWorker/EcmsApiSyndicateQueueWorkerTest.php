<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit testing for the EcmsApiSyndicateQueueWorker class.
 *
 *
 * @package Drupal\Tests\ecms_api_publisher\Unit\Plugin\QueueWorker
 */
#[Group("ecms_api_publisher")]
#[Group("ecms_api")]
#[Group("ecms")]
#[CoversClass(\Drupal\ecms_api_publisher\Plugin\QueueWorker\EcmsApiSyndicateQueueWorker::class)]
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
      ->onlyMethods(['syndicateEntity'])
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
   */
  #[DataProvider('dataProviderForProcessItem')]
  public function testProcessItem(bool $expected): void {
    $url = $this->createMock(Url::class);
    $node = $this->createMock(NodeInterface::class);

    $url->expects($this->exactly($expected ? 0 : 1))
      ->method('toUriString')
      ->willReturn('https://www.example.com');
    $linkItem = $this->createMock(LinkItem::class);
    $linkItem->expects($this->once())
      ->method('getUrl')
      ->willReturn($url);
    $siteEntity = $this->createMock(EcmsApiSiteInterface::class);
    $siteEntity->expects($this->once())
      ->method('getApiEndpoint')
      ->willReturn($linkItem);

    $this->ecmsApiPublisher->expects($this->once())
      ->method('syndicateEntity')
      ->with($url, $node)
      ->willReturn($expected);

    if (!$expected) {
      $this->expectException(RequeueException::class);
    }

    $data = [
      'site_entity' => $siteEntity,
      'syndicated_content_entity' => $node,
    ];

    $this->queueWorker->processItem($data);
  }

  /**
   * Data provider for the testProcessItem method.
   *
   * @return array
   *   Parameters to pass to testProcessItem().
   */
  public static function dataProviderForProcessItem(): array {
    return [
      'test1' => [FALSE],
      'test2' => [TRUE],
    ];
  }

}
