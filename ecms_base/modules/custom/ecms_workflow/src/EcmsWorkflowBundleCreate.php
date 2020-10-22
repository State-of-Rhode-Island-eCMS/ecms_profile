<?php

declare(strict_types = 1);

namespace Drupal\ecms_workflow;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Add node bundles to the editorial workflow.
 *
 * Handles configuration updates when a new content type is added.
 *
 * @package Drupal\ecms_workflow
 */
class EcmsWorkflowBundleCreate {

  use StringTranslationTrait;

  /**
   * The role id for content publishers.
   */
  const CONTENT_PUBLISHER_ROLE = 'content_publisher';

  /**
   * The role id for content authors.
   */
  const CONTENT_AUTHOR_ROLE = 'content_author';

  /**
   * The machine id for the default workflow.
   */
  const WORKFLOW_ID = 'editorial';

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * EcmsWorkflowBundleCreate constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Add scheduler default settings to new content type.
   *
   * @param string $contentType
   *   The machine name of the new content type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function addSchedulerSettings(string $contentType): void {
    $type = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->load($contentType);

    $type->setThirdPartySetting('scheduler', 'expand_fieldset', 'when_required');
    $type->setThirdPartySetting('scheduler', 'fields_display_mode', 'fieldset');
    $type->setThirdPartySetting('scheduler', 'publish_enable', 1);
    $type->setThirdPartySetting('scheduler', 'publish_past_date', 0);
    $type->setThirdPartySetting('scheduler', 'publish_past_date_created', 'error');
    $type->setThirdPartySetting('scheduler', 'publish_required', 0);
    $type->setThirdPartySetting('scheduler', 'publish_revision', 0);
    $type->setThirdPartySetting('scheduler', 'publish_touch', 0);
    $type->setThirdPartySetting('scheduler', 'show_message_after_update', 1);
    $type->setThirdPartySetting('scheduler', 'unpublish_enable', 1);
    $type->setThirdPartySetting('scheduler', 'unpublish_required', 0);
    $type->setThirdPartySetting('scheduler', 'unpublish_revision', 0);

    try {
      $type->save();
    }
    catch (EntityStorageException $e) {
      return;
    }
  }

  /**
   * Grant node permissions to the author and publisher roles.
   *
   * @param string $contentType
   *   The machine name of the new content type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function setRolePermissions(string $contentType): void {
    $storage = $this->entityTypeManager->getStorage('user_role');

    $content_publisher_role = $storage->load(self::CONTENT_PUBLISHER_ROLE);

    // Guard against an empty role.
    if (empty($content_publisher_role)) {
      return;
    }

    // Content Publisher role has all editing permissions.
    $content_publisher_role->grantPermission("create {$contentType} content");
    $content_publisher_role->grantPermission("edit any {$contentType} content");
    $content_publisher_role->grantPermission("delete any {$contentType} content");

    try {
      $content_publisher_role->save();
    }
    catch (EntityStorageException $e) {
      return;
    }

    $content_author_role = $storage->load(self::CONTENT_AUTHOR_ROLE);

    // Guard against an empty role.
    if (empty($content_author_role)) {
      return;
    }

    // Content Author role has limited editing permissions.
    $content_author_role->grantPermission("create {$contentType} content");
    $content_author_role->grantPermission("edit own {$contentType} content");

    try {
      $content_author_role->save();
    }
    catch (EntityStorageException $e) {
      return;
    }
  }

  /**
   * Apply workflow and permissions to the new content type.
   *
   * @param string $contentType
   *   The machine name of the new content type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addContentTypeToWorkflow(string $contentType): void {

    $this->setRolePermissions($contentType);
    $this->addSchedulerSettings($contentType);

    // Assign the new content type to the editorial workflow.
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->entityTypeManager
      ->getStorage("workflow")
      ->loadByProperties(["id" => self::WORKFLOW_ID])[self::WORKFLOW_ID];

    // Guard against an empty workflow.
    if (empty($workflow)) {
      return;
    }

    $config = $workflow->getTypePlugin()->getConfiguration();
    $config["entity_types"]["node"][] = $contentType;
    $workflow->getTypePlugin()->setConfiguration($config);

    try {
      $workflow->save();
    }
    catch (EntityStorageException $e) {
      return;
    }

  }

}
