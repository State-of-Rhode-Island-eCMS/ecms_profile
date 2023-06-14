<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_publisher\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ecms_api_publisher\Entity\EcmsApiSiteInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the batch form for manually processing queued syndicated nodes.
 *
 * @package Drupal\ecms_api_publisher\Form
 */
class EcmsApiBatchSendUpdatesForm extends ConfirmFormBase {

  /**
   * The publisher queue name.
   */
  const SYNDICATE_QUEUE = 'ecms_api_publisher_queue';

  /**
   * The queue interface for the syndicate queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * EcmsApiBatchSendUpdatesForm constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue service.
   */
  public function __construct(QueueFactory $queueFactory) {
    $this->queue = $queueFactory->get(self::SYNDICATE_QUEUE);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('queue')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getDescription(): TranslatableMarkup {
    // Get the actual count of items in the queue.
    $count = $this->queue->numberOfItems();
    return $this->formatPlural(
      $count,
      'Are you sure you would like to manually push 1 syndicated content item to all recipient sites?  This action cannot be undone!',
      'Are you sure you would like to manually push @count syndicated content items to all recipient sites?  This action cannot be undone!'
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Do you want to manually push all syndicated content?');
  }

  /**
   * {@inheritDoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('<front>');
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'ecms_api_publisher_batch_send_updates';
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $operations = [];

    // Loop through the queue and add them to a batch.
    while ($item = $this->queue->claimItem()) {
      // Add to the batch operations.
      $operation = [
        '\Drupal\ecms_api_publisher\Form\EcmsApiBatchSendUpdatesForm::postSyndicateContent',
        [
          $item->data['site_entity'],
          $item->data['syndicated_content_entity'],
        ],
      ];

      $operations[] = $operation;
      // Remove the item from the queue.
      $this->queue->deleteItem($item);
    }

    // Only run a batch if there are operations available.
    if (empty($operations)) {
      $this->messenger()->addStatus('No queue items were found or they have been claimed by another process. Please wait a few minutes and try again.');
      $form_state->setRedirect('<front>');
    }
    else {
      $batch = [
        'title' => $this->t('Manually syndicating content.'),
        'operations' => $operations,
        'finished' => '\Drupal\ecms_api_publisher\Form\EcmsApiBatchSendUpdatesForm::postSyndicateContentFinished',
      ];

      batch_set($batch);
    }
  }

  /**
   * Post syndicated content from the queue.
   *
   * @param \Drupal\ecms_api_publisher\Entity\EcmsApiSiteInterface $ecmsApiSite
   *   The site to post the content to.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to post to the site.
   * @param array $context
   *   Additional context from the batch process.
   */
  public static function postSyndicateContent(EcmsApiSiteInterface $ecmsApiSite, EntityInterface $entity, array &$context): void {
    // Get the ecms_api_publisher.publisher service.
    /** @var \Drupal\ecms_api_publisher\EcmsApiPublisher $ecmsApiPublisher */
    $ecmsApiPublisher = \Drupal::service('ecms_api_publisher.publisher');

    $url = $ecmsApiSite->getApiEndpoint()->getUrl();

    // Tell the user what we're doing.
    $context['message'] = t('Posting the @type "@title" to @endpoint',
      [
        '@type' => $entity->bundle(),
        '@title' => $entity->label(),
        '@endpoint' => $url->toString(),
      ]
    );

    // Post the entity.
    $result = $ecmsApiPublisher->syndicateEntity($url, $entity);

    // If an error occurs, re-queue the item.
    if (!$result) {
      $data = [
        'site_entity' => $ecmsApiSite,
        'syndicated_content_entity' => $entity,
      ];

      // Requeue the item for later processing.
      EcmsApiBatchSendUpdatesForm::requeueItems($data);

      // Let the user know about the error.
      $context['results']['error'][] = t('An error occurred posting the @type "@title" to @endpoint. This item has been re-queued.',
        [
          '@type' => $entity->bundle(),
          '@title' => $entity->label(),
          '@endpoint' => $url->toString(),
        ]
      );
    }
  }

  /**
   * Handle the finishing operations of the batch.
   *
   * @param bool $success
   *   True if the batch finished successfully.
   * @param array $results
   *   The results of the batch.
   * @param array $operations
   *   The operations that did not complete.
   */
  public static function postSyndicateContentFinished(bool $success, array $results, array $operations): void {
    $messenger = \Drupal::messenger();
    if ($success) {
      // Look for any results unfinished.
      if (!empty($results['error'])) {
        // Let the user know what didn't complete.
        foreach ($results['error'] as $message) {
          $messenger->addError($message);
        }
      }
    }
    else {
      // An error occurred and the user continued to the error page.
      // Loop through the remaining operations and re-queue them.
      foreach ($operations as $error) {
        if (!empty($error[1])) {
          /** @var \Drupal\ecms_api_publisher\Entity\EcmsApiSiteInterface $site */
          $ecmsApiSite = array_shift($error[1]);
          /** @var \Drupal\Core\Entity\EntityInterface $entity */
          $entity = array_shift($error[1]);

          // Rebuild the queue item for requeue.
          $data = [
            'site_entity' => $ecmsApiSite,
            'syndicated_content_entity' => $entity,
          ];

          // Requeue the item for later processing.
          EcmsApiBatchSendUpdatesForm::requeueItems($data);

          $url = $ecmsApiSite->getApiEndpoint()->getUrl();

          $message = t('An error occurred before posting the @type "@title" to @endpoint. This item has been re-queued.',
            [
              '@type' => $entity->bundle(),
              '@title' => $entity->label(),
              '@endpoint' => $url->toString(),
            ]
          );

          $messenger->addError($message);
        }
      }
    }
  }

  /**
   * Requeue an item to the ecms_api_publisher_queue.
   *
   * @param array $data
   *   The data to requeue.
   */
  public static function requeueItems(array $data): void {
    /** @var \Drupal\Core\Queue\QueueFactory $queueFactory */
    $queueFactory = \Drupal::service('queue');

    // Get the queue.
    $queue = $queueFactory->get(self::SYNDICATE_QUEUE);

    // Re-queue the item.
    $queue->createItem($data);
  }

}
