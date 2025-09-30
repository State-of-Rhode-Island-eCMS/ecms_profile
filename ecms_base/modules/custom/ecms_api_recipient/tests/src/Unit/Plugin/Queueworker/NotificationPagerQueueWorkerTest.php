<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_api_recipient\Unit\Plugin\QueueWorker;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Url;
use Drupal\ecms_api_recipient\EcmsApiRecipientRetrieveNotifications;
use Drupal\ecms_api_recipient\Plugin\QueueWorker\NotificationPagerQueueWorker;
use Drupal\Tests\UnitTestCase;
use Drupal\TestTools\Random;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for the NotificationPagerQueueWorker class.
 *
 * @package Drupal\Tests\ecms_api_recipient\Unit\Plugin\QueueWorker
 */
#[Group("ecms_api_recipient")]
#[Group("ecms_api")]
class NotificationPagerQueueWorkerTest extends UnitTestCase {

  /**
   * Mock of the ecms_api_recipient.retrieve_notifications service.
   *
   * @var \Drupal\ecms_api_recipient\EcmsApiRecipientRetrieveNotifications|\PHPUnit\Framework\MockObject\MockObject
   */
  private $ecmsNotificationRetriever;

  /**
   * The NotificationPagerQueueWorker plugin to test.
   *
   * @var \Drupal\Core\Plugin\ContainerFactoryPluginInterface|\Drupal\ecms_api_recipient\Plugin\QueueWorker\NotificationPagerQueueWorker
   */
  private $plugin;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->ecmsNotificationRetriever = $this->createMock(EcmsApiRecipientRetrieveNotifications::class);

    $container = new ContainerBuilder();
    $container->set('ecms_api_recipient.retrieve_notifications', $this->ecmsNotificationRetriever);

    \Drupal::setContainer($container);

    $this->plugin = NotificationPagerQueueWorker::create($container, [], $this->randomMachineName(), '');
  }

  /**
   * Test the processItem method.
   *
   * @param \Drupal\Core\Url|null $url
   *   The url expected or null.
   *
   */
  #[DataProvider('dataProviderForProcessItem')]
  public function testProcessItem(?Url $url): void {
    $methodCount = 1;

    if (empty($url)) {
      $methodCount = 0;
    }

    $this->ecmsNotificationRetriever->expects($this->exactly($methodCount))
      ->method('retrieveNotifications')
      ->with($url);

    $this->plugin->processItem($url);
  }

  /**
   * Data provider for the testProcessItem method.
   *
   * @return array
   *   Parameters to pass to the testProcessItem method.
   */
  public static function dataProviderForProcessItem(): array {
    $machineName = Random::machineName(8);
    $path = "http://{$machineName}.com/";
    $url = Url::fromUri($path);
    return [
      'test1' => [NULL],
      'test2' => [$url],
    ];
  }

}
