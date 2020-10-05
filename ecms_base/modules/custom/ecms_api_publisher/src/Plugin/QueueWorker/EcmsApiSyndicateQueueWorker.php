<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_publisher\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ecms_api_publisher\EcmsApiPublisher;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Post syndicated content on cron.
 *
 * @QueueWorker(
 *   id = "ecms_api_publisher_queue",
 *   title = @Translation("Ecms API Cron Publisher"),
 *   cron = {"time" = 5}
 * )
 */
class EcmsApiSyndicateQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The ecms_api_publisher.publisher service.
   *
   * @var \Drupal\ecms_api_publisher\EcmsApiPublisher
   */
  private $ecmsApiPublisher;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ecms_api_publisher.publisher')
    );
  }

  /**
   * EcmsApiSyndicateQueueWorker constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ecms_api_publisher\EcmsApiPublisher $ecmsApiPublisher
   *   The ecms_api_publisher.publisher service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, EcmsApiPublisher $ecmsApiPublisher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->ecmsApiPublisher = $ecmsApiPublisher;
  }

  /**
   * Process an item in the queue.
   *
   * @param mixed $data
   *   Data should be an array with the following keys:
   *   - site_entity: \Drupal\ecms_api_publisher\Entity\EcmsApiSiteInterface
   *   - syndicated_content_entity: \Drupal\node\NodeInterface
   *   - method: string.
   */
  public function processItem($data): void {
    /** @var \Drupal\ecms_api_publisher\Entity\EcmsApiSiteInterface $ecmsApiSiteEntity */
    $ecmsApiSiteEntity = $data['site_entity'];
    $apiUrl = $ecmsApiSiteEntity->getApiEndpoint()->getUrl();

    /** @var \Drupal\node\NodeInterface $node */
    $node = $data['syndicated_content_entity'];
    $method = $data['method'];

    $result = $this->ecmsApiPublisher->syndicateNode($method, $apiUrl, $node);

    // If the submission was not successful, requeue the task.
    if (!$result) {
      $message = $this->t('An error occurred accessing the API endpoint here: @endpoint', [
        '@endpoint' => $apiUrl->toUriString(),
      ]);

      throw new RequeueException($message->render());
    }
  }

}
