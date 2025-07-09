<?php

declare(strict_types=1);

namespace Drupal\ecms_api_recipient\Commands;

use Drupal\consumers\Entity\ConsumerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drush\Commands\DrushCommands;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Drush commands for the ECMS API Recipient module.
 */
final class EcmsApiRecipientCommand extends DrushCommands {

  const RECIPIENT_ID = 'eCMS Recipient';

  /**
   * Construct the EcmsApiRecipientCommand.
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
   * @command ecms:save-recipient-consumer
   *
   * @usage ecms:save-recipient-consumer
   *   Loads and re-saves the recipient consumer entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveConsumer(): void {
    $recipientSettings = $this->configFactory->get('ecms_api_recipient.settings');
    $clientId = $recipientSettings->get('oauth_client_id');
    $clientSecret = $recipientSettings->get('oauth_client_secret');
    try {
      // Load the consumer entity by client id.
      $consumers = $this->entityTypeManager->getStorage('consumer')->loadByProperties(['label' => self::RECIPIENT_ID]);
      $consumer = reset($consumers);

      if (!$consumer instanceof ConsumerInterface) {
        $this->logger()->error(dt('Consumer with ID @id not found.', ['@id' => self::RECIPIENT_ID]));
        return;
      }

      // Save the consumer entity.
      $consumer->set('client_id', $clientId);
      $consumer->set('uuid', $clientId);
      $consumer->set('secret', $clientSecret);
      $consumer->save();
      $this->logger()->success(dt('Consumer with ID @id has been saved successfully.', ['@id' => self::RECIPIENT_ID]));
    }
    catch (\Exception $e) {
      $this->logger()->error($e->getMessage());
    }
  }

}
