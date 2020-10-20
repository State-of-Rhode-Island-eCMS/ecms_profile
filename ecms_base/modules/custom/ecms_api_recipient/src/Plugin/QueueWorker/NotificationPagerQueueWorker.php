<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_recipient\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Url;
use Drupal\ecms_api_recipient\EcmsApiRecipientRetrieveNotifications;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Get the next page and queue notification nodes for creation.
 *
 * @QueueWorker(
 *   id = "ecms_api_recipient_notification_pager_queue",
 *   title = @Translation("Ecms Api Notification Pager Queue"),
 *   cron = {"time" = 5}
 * )
 */
class NotificationPagerQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The ecms_api_recipient.retrieve_notifications service.
   *
   * @var \Drupal\ecms_api_recipient\EcmsApiRecipientRetrieveNotifications
   */
  private $ecmsNotificationRetriever;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ecms_api_recipient.retrieve_notifications')
    );
  }

  /**
   * NotificationPagerQueueWorker constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ecms_api_recipient\EcmsApiRecipientRetrieveNotifications $ecmsApiRetriever
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, EcmsApiRecipientRetrieveNotifications $ecmsApiRetriever) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->ecmsNotificationRetriever = $ecmsApiRetriever;
  }

  /**
   * @param \Drupal\Core\Url $url
   */
  public function processItem($url): void {
    if (!$url instanceOf Url) {
      return;
    }

    // Call the api retrieval service for pages urls.
    $this->ecmsNotificationRetriever->retrieveNotifications($url);

  }

}
