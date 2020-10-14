<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_publisher\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Session\AccountSwitcher;
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
   * The account_switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcher
   */
  private $accountSwitcher;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ecms_api_publisher.publisher'),
      $container->get('account_switcher')
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
   * @param \Drupal\Core\Session\AccountSwitcher $accountSwitcher
   *   The account_switcher service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, EcmsApiPublisher $ecmsApiPublisher, AccountSwitcher $accountSwitcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->ecmsApiPublisher = $ecmsApiPublisher;
    $this->accountSwitcher = $accountSwitcher;
  }

  /**
   * Process an item in the queue.
   *
   * @param mixed $data
   *   Data should be an array with the following keys:
   *   - site_entity: \Drupal\ecms_api_publisher\Entity\EcmsApiSiteInterface.
   *   - syndicated_content_entity: \Drupal\node\NodeInterface.
   */
  public function processItem($data): void {
    /** @var \Drupal\ecms_api_publisher\Entity\EcmsApiSiteInterface $ecmsApiSiteEntity */
    $ecmsApiSiteEntity = $data['site_entity'];
    $apiUrl = $ecmsApiSiteEntity->getApiEndpoint()->getUrl();

    /** @var \Drupal\node\NodeInterface $node */
    $node = $data['syndicated_content_entity'];

    // Get the ecms_api_publisher user.
    $publisherAccount = $this->ecmsApiPublisher->getEcmsApiPublisherUser();

    // Guard against a missing publisher account.
    if (empty($publisherAccount)) {
      $message = $this->t('The ecms_api_publisher account is missing from the system.');

      throw new RequeueException($message->render());
    }

    $this->accountSwitcher->switchTo($publisherAccount);

    try {
      $result = $this->ecmsApiPublisher->syndicateNode($apiUrl, $node);
    }
    catch (\Exception $exception) {
      // Trap any exceptions so the account switcher will revert the user.
      $this->accountSwitcher->switchBack();

      // Requeue the item.
      $message = $this->t('An error occurred accessing the API endpoint here: @endpoint', [
        '@endpoint' => $apiUrl->toUriString(),
      ]);

      throw new RequeueException($message->render());
    }

    $this->accountSwitcher->switchBack();

    // If the submission was not successful, requeue the task.
    if (!$result) {
      $message = $this->t('An error occurred accessing the API endpoint here: @endpoint', [
        '@endpoint' => $apiUrl->toUriString(),
      ]);

      throw new RequeueException($message->render());
    }
  }

}
