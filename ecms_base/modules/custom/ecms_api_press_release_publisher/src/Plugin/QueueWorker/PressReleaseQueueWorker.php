<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_press_release_publisher\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\ecms_api_press_release_publisher\PressReleasePublisher;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Syndicate the press release entities.
 *
 * @QueueWorker(
 *   id = "ecms_api_press_release_publisher",
 *   title = @Translation("Ecms Api Press Release Creation Queue"),
 *   cron = {"time" = 5}
 * )
 */
class PressReleaseQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /** @var \Drupal\ecms_api_press_release_publisher\PressReleasePublisher  */
  private $pressReleasePublisher;

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ecms_api_press_release_publisher')
    );
  }

  /**
   * PressReleaseQueueWorker constructor.
   *
   * @param array $configuration
   *   The configuration of the plugin.
   * @param $plugin_id
   *   The plugin id.
   * @param $plugin_definition
   *   The plugin definition.
   * @param \Drupal\ecms_api_press_release_publisher\PressReleasePublisher $PressReleasePublisher
   *   The ecms_api_press_release_publisher service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PressReleasePublisher $pressReleasePublisher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->pressReleasePublisher = $pressReleasePublisher;
  }

  /**
   * @inheritDoc
   */
  public function processItem($data) {

    // Guard against a non-entity and move on.
    if (!$data instanceof EntityInterface) {
      return;
    }

    if (!$this->pressReleasePublisher->postEntity($data)) {
      throw new RequeueException('An error occurred submitting the entity to the hub.');
    }
  }

}
