<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_api_publisher\Unit\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ecms_api_publisher\Form\EcmsApiBatchSendUpdatesForm;
use Drupal\Tests\UnitTestCase;

class EcmsApiBatchSendUpdateFormTest extends UnitTestCase {

  const SYNDICATE_QUEUE = 'ecms_api_publisher_queue';

  private $queue;
  private $batchForm;

  protected function setUp(): void {
    parent::setUp();

    $this->queue = $this->createMock(QueueInterface::class);

    $queueFactory = $this->createMock(QueueFactory::class);
    $queueFactory->expects($this->once())
      ->method('get')
      ->with(self::SYNDICATE_QUEUE)
      ->willReturn($this->queue);

    $container = new ContainerBuilder();

    $container->set('queue', $queueFactory);
    $container->set('string_translation', $this->getStringTranslationStub());

    \Drupal::setContainer($container);

    $this->batchForm = EcmsApiBatchSendUpdatesForm::create($container);
  }

  /**
   * Test the getDescription method.
   *
   * @param int $count
   *   The count of items in the queue.
   *
   * @dataProvider dataProviderForTestGetDescription
   */
  public function testGetDescription(int $count): void {
    $this->queue->expects($this->once())
      ->method('numberOfItems')
      ->willReturn($count);

    $expected = "Are you sure you would like to manually push syndicated content to 1 site?  This action cannot be undone!";

    if ($count > 1) {
      $expected = "Are you sure you would like to manually push syndicated content to {$count} sites?  This action cannot be undone!";
    }

    // Get the actual description.
    $result = $this->batchForm->getDescription();

    $this->assertEquals($expected, $result->render());
  }

  /**
   * Data provider for the testGetDescription method.
   *
   * @return array
   *   Array of parameters to pass to the testGetDescription method.
   */
  public function dataProviderForTestGetDescription(): array {
    return [
      'test1' => [1],
      'test2' => [5],
      'test3' => [10],
    ];
  }

  public function testGetQuestion(): void {
    $expected = "Do you want to manually push all syndicated content?";

    $actual = $this->batchForm->getQuestion();

    $this->assertEquals($expected, $actual->render());
  }

  public function testGetFormId(): void {
    $expected = "ecms_api_publisher_batch_send_updates";

    $actual = $this->batchForm->getFormId();

    $this->assertEquals($expected, $actual);
  }

  public function testGetCancelUrl(): void {
    $expected = Url::fromRoute('<front>');

    $actual = $this->batchForm->getCancelUrl();

    $this->assertEquals($expected, $actual);
  }

}