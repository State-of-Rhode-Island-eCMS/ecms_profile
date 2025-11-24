<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_api_publisher\Unit\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ecms_api_publisher\EcmsApiPublisher;
use Drupal\ecms_api_publisher\Entity\EcmsApiSiteInterface;
use Drupal\ecms_api_publisher\Form\EcmsApiBatchSendUpdatesForm;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use phpmock\MockBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit testing for the EcmsApiBatchSendUpdateForm class.
 *
 * @package Drupal\Tests\ecms_api_publisher\Unit\Form
 *
 */
#[Group("ecms_api")]
#[Group("ecms_api_publisher")]
#[Group("ecms")]
#[CoversClass(\Drupal\ecms_api_publisher\Form\EcmsApiBatchSendUpdatesForm::class)]
class EcmsApiBatchSendUpdateFormTest extends UnitTestCase {

  /**
   * Name of the queue.
   */
  const SYNDICATE_QUEUE = 'ecms_api_publisher_queue';

  /**
   * Mock the ecms_api_publisher_queue queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $queue;

  /**
   * The form to test.
   *
   * @var \Drupal\ecms_api_publisher\Form\EcmsApiBatchSendUpdatesForm
   */
  private $batchForm;

  /**
   * Mock of the messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $messenger;

  /**
   * Mock of the ecms_api_publisher.publisher service.
   *
   * @var \Drupal\ecms_api_publisher\EcmsApiPublisher|\PHPUnit\Framework\MockObject\MockObject
   */
  private $ecmsApiPublisher;

  /**
   * Mock the global batch_set() function.
   *
   * @var \phpmock\Mock
   */
  private $globalBatch;

  /**
   * Mock the global t() function.
   *
   * @var \phpmock\Mock
   */
  private $mockGlobalTFunction;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $mockGlobalBatchFunction = new MockBuilder();
    $mockGlobalBatchFunction->setNamespace('Drupal\ecms_api_publisher\Form')
      ->setName('batch_set')
      ->setFunction(
        function (array $batch) {
        }
      );

    $mockGlobalTFunction = new MockBuilder();
    $mockGlobalTFunction->setNamespace('Drupal\ecms_api_publisher\Form')
      ->setName('t')
      ->setFunction(
        function ($string, array $args = [], array $options = []) {
          // @codingStandardsIgnoreLine
          return new TranslatableMarkup($string, $args, $options);
        }
      );

    $this->ecmsApiPublisher = $this->getMockBuilder(EcmsApiPublisher::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['syndicateEntity'])
      ->getMock();

    $this->mockGlobalTFunction = $mockGlobalTFunction->build();
    $this->mockGlobalTFunction->enable();

    $this->globalBatch = $mockGlobalBatchFunction->build();
    $this->globalBatch->enable();

    $this->queue = $this->createMock(QueueInterface::class);
    $this->messenger = $this->createMock(MessengerInterface::class);
  }

  /**
   * {@inheritDoc}
   */
  protected function tearDown(): void {
    parent::tearDown();

    $this->globalBatch->disable();
    $this->mockGlobalTFunction->disable();
  }

  /**
   * Set the Drupal container for the form class.
   */
  protected function setFormContainer(): void {
    $queueFactory = $this->createMock(QueueFactory::class);
    $queueFactory->expects($this->once())
      ->method('get')
      ->with(self::SYNDICATE_QUEUE)
      ->willReturn($this->queue);

    $container = new ContainerBuilder();

    $container->set('queue', $queueFactory);
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('messenger', $this->messenger);

    \Drupal::setContainer($container);

    $this->batchForm = EcmsApiBatchSendUpdatesForm::create($container);
  }

  /**
   * Test the getDescription method.
   *
   * @param int $count
   *   The count of items in the queue.
   *
   */
  #[DataProvider('dataProviderForTestGetDescription')]
  public function testGetDescription(int $count): void {
    $this->setFormContainer();
    $this->queue->expects($this->once())
      ->method('numberOfItems')
      ->willReturn($count);

    $expected = "Are you sure you would like to manually push 1 syndicated content item to all recipient sites?  This action cannot be undone!";

    if ($count > 1) {
      $expected = "Are you sure you would like to manually push {$count} syndicated content items to all recipient sites?  This action cannot be undone!";
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
  public static function dataProviderForTestGetDescription(): array {
    return [
      'test1' => [1],
      'test2' => [5],
      'test3' => [10],
    ];
  }

  /**
   * Test the getQuestion method.
   */
  public function testGetQuestion(): void {
    $this->setFormContainer();
    $expected = "Do you want to manually push all syndicated content?";

    $actual = $this->batchForm->getQuestion();

    $this->assertEquals($expected, $actual->render());
  }

  /**
   * Test the getFormId method.
   */
  public function testGetFormId(): void {
    $this->setFormContainer();
    $expected = "ecms_api_publisher_batch_send_updates";

    $actual = $this->batchForm->getFormId();

    $this->assertEquals($expected, $actual);
  }

  /**
   * Test the getCancelUrl method.
   */
  public function testGetCancelUrl(): void {
    $this->setFormContainer();
    $expected = Url::fromRoute('<front>');

    $actual = $this->batchForm->getCancelUrl();

    $this->assertEquals($expected, $actual);
  }

  /**
   * Test the submitForm method.
   *
   */
  #[DataProvider('dataProviderForTestSubmitForm')]
  public function testSubmitForm(int $queueCount = 3, bool $batchExpected = TRUE): void {
    $this->setFormContainer();
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    $apiSite = $this->createMock(EcmsApiSiteInterface::class);
    $node = $this->createMock(NodeInterface::class);
    // Claims to pass to the queue.
    $claims = [];

    // Build up the claims array.
    if ($queueCount > 0) {
      for ($i = 0; $i < $queueCount; $i++) {
        $item = new \stdClass();
        $item->data = [
          'site_entity' => $apiSite,
          'syndicated_content_entity' => $node,
        ];

        $claims[] = $item;
      }

      $claims[] = FALSE;

      $this->queue->expects($this->exactly($queueCount + 1))
        ->method('claimItem')
        ->willReturnOnConsecutiveCalls(...$claims);

      $this->queue->expects($this->exactly($queueCount))
        ->method('deleteItem');
    }
    else {
      $this->queue->expects($this->exactly(1))
        ->method('claimItem')
        ->willReturn(FALSE);

      $this->queue->expects($this->never())
        ->method('deleteItem');
    }

    if (!$batchExpected) {
      $this->messenger->expects($this->once())
        ->method('addStatus')
        ->with('No queue items were found or they have been claimed by another process. Please wait a few minutes and try again.');

      $form_state->expects($this->once())
        ->method('setRedirect')
        ->with('<front>');
    }

    $this->batchForm->submitForm($form, $form_state);
  }

  /**
   * Parameters to pass to the testSubmitForm method.
   *
   * @return array[]
   *   Parameters to pass to the testSubmitForm method.
   */
  public static function dataProviderForTestSubmitForm(): array {
    return [
      'test1' => [3, TRUE],
      'test2' => [1, TRUE],
      'test3' => [0, FALSE],
    ];
  }

  /**
   * Set the container for the static methods.
   */
  protected function setStaticMethodContainer(): void {
    $queueFactory = $this->createMock(QueueFactory::class);
    $queueFactory->expects($this->any())
      ->method('get')
      ->with(self::SYNDICATE_QUEUE)
      ->willReturn($this->queue);

    $container = new ContainerBuilder();

    $container->set('queue', $queueFactory);
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('messenger', $this->messenger);
    $container->set('ecms_api_publisher.publisher', $this->ecmsApiPublisher);

    \Drupal::setContainer($container);
  }

  /**
   * Test the requeueItem static method.
   */
  public function testRequeueItems(): void {
    $this->setStaticMethodContainer();

    $data = ['test' => 'array'];

    $this->queue->expects($this->once())
      ->method('createItem')
      ->with($data);

    EcmsApiBatchSendUpdatesForm::requeueItems($data);
  }

  /**
   * Test the postSyndicatedContentFinished method.
   *
   * @param bool $success
   *   Status of the batch to test.
   * @param array $results
   *   The expected results array.
   * @param array $operations
   *   The expected operations array.
   *
   */
  #[DataProvider('dataProviderForFinishedMethod')]
  public function testPostSyndicateContentFinished(bool $success, array $results, array $operations): void {
    $this->setStaticMethodContainer();

    if ($success) {
      if (!empty($results['error'])) {
        $this->messenger->expects($this->exactly(count($results['error'])))
          ->method('addError');
      }
      else {
        $this->messenger->expects($this->never())
          ->method('addError');
      }
    }

    if (!$success) {
      foreach ($operations as &$error) {
        $urlMock = $this->createMock(Url::class);
        $urlMock->expects($this->once())
          ->method('toString');

        $linkMock = $this->createMock(LinkItem::class);
        $linkMock->expects($this->once())
          ->method('getUrl')
          ->willReturn($urlMock);
        $apiSite = $this->createMock(EcmsApiSiteInterface::class);
        $apiSite->expects($this->once())
          ->method('getApiEndpoint')
          ->willReturn($linkMock);

        // Mock the API Site entity.
        $error[1][0] = $apiSite;

        $node = $this->createMock(NodeInterface::class);
        $node->expects($this->once())
          ->method('bundle');
        $node->expects($this->once())
          ->method('label');

        $error[1][1] = $node;
      }

      $this->messenger->expects($this->exactly(count($operations)))
        ->method('addError');

      $this->queue->expects($this->exactly(count($operations)))
        ->method('createItem');
    }

    EcmsApiBatchSendUpdatesForm::postSyndicateContentFinished($success, $results, $operations);

  }

  /**
   * Parameters for the testPostSyndicateContentFinished method.
   *
   * @return array[]
   *   Parameters for the testPostSyndicateContentFinished method.
   */
  public static function dataProviderForFinishedMethod(): array {
    return [
      'test1' => [
        TRUE,
        ['error' => []],
        [],
      ],
      'test2' => [
        TRUE,
        [
          'error' => [
            1 => 'Message 1',
            2 => 'Message 2',
            3 => 'Message 3',
          ],
        ],
        [],
      ],
      'test3' => [
        FALSE,
        ['error' => []],
        [
          0 => [
            '\class\name\space',
            [
              'apiSite',
              'node',
            ],
          ],
          2 => [
            '\class\name\space',
            [
              'apiSite',
              'node',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Test the postSyndicateContent method.
   *
   * @param bool $result
   *   Mock the result of the EcmsApiPublisher::syndicateEntity method.
   *
   */
  #[DataProvider('dataProviderForTestPostSyndicateContent')]
  public function testPostSyndicateContent(bool $result): void {
    $this->setStaticMethodContainer();

    $urlMock = $this->createMock(Url::class);

    $linkMock = $this->createMock(LinkItem::class);
    $linkMock->expects($this->once())
      ->method('getUrl')
      ->willReturn($urlMock);

    $ecmsApiSite = $this->createMock(EcmsApiSiteInterface::class);
    $ecmsApiSite->expects($this->once())
      ->method('getApiEndpoint')
      ->willReturn($linkMock);

    $node = $this->createMock(NodeInterface::class);

    $methodCount = 1;

    // If an error occurred.
    if (!$result) {
      $methodCount = 2;

      $data = [
        'site_entity' => $ecmsApiSite,
        'syndicated_content_entity' => $node,
      ];

      $this->queue->expects($this->once())
        ->method('createItem')
        ->with($data);
    }

    $urlMock->expects($this->exactly($methodCount))
      ->method('toString');

    $node->expects($this->exactly($methodCount))
      ->method('bundle');

    $node->expects($this->exactly($methodCount))
      ->method('label');

    $this->ecmsApiPublisher->expects($this->once())
      ->method('syndicateEntity')
      ->willReturn($result);

    $context = [];

    EcmsApiBatchSendUpdatesForm::postSyndicateContent($ecmsApiSite, $node, $context);
  }

  /**
   * Parameters for the testPostSyndicateContent method.
   *
   * @return array
   *   Parameters for the testPostSyndicateContent method.
   */
  public static function dataProviderForTestPostSyndicateContent(): array {
    return [
      'test1' => [TRUE],
      'test2' => [FALSE],
    ];
  }

}
