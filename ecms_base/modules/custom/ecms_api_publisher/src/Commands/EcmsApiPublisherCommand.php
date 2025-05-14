<?php

namespace Drupal\ecms_api_publisher\Commands;

use Drupal\consumers\Entity\ConsumerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for the ECMS API Publisher module.
 */
final class EcmsApiPublisherCommand extends DrushCommands {

  const PUBLISHER_ID = 'eCMS Publisher';

  /**
   * Construct the EcmsApiPublisherCommand.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct();
  }

  /**
   * Loads and saves a consumer entity.
   *
   * @command ecms:save-publishing-consumer
   *
   * @usage ecms:save-publishing-consumer consumer_id
   *   Loads and saves the specified consumer entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveConsumer(): void {
    $publisherSettings = $this->configFactory->get('ecms_api_publisher.settings');
    $clientId = $publisherSettings->get('oauth_client_id');
    $clientSecret = $publisherSettings->get('oauth_client_secret');
    try {
      // Load the consumer entity by client id.
      $consumers = $this->entityTypeManager->getStorage('consumer')->loadByProperties(['label' => self::PUBLISHER_ID]);
      $consumer = reset($consumers);

      if (!$consumer instanceof ConsumerInterface) {
        $this->logger()->error(dt('Consumer with ID @id not found.', ['@id' => self::PUBLISHER_ID]));
        return;
      }

      // Save the consumer entity.
      $consumer->set('uuid', $clientId);
      $consumer->set('secret', $clientSecret);
      $consumer->save();
      $this->logger()->success(dt('Consumer with ID @id has been saved successfully.', ['@id' => self::PUBLISHER_ID]));
    }
    catch (\Exception $e) {
      $this->logger()->error($e->getMessage());
    }
  }

}
