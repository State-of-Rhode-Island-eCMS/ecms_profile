<?php

declare(strict_types = 1);

namespace Drupal\ecms_workflow;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class EcmsWorkflowBundleCreate.
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
   * Create a new user for use with the API.
   *
   * @param string $contentType
   *   The machine name of the new content type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addContentTypeToWorkflow(string $contentType): void {

    $this->setRolePermissions($contentType);

    // Assign the new content type to the editorial workflow.
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->entityTypeManager
      ->getStorage("workflow")
      ->loadByProperties(["id" => self::WORKFLOW_ID])[self::WORKFLOW_ID];
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
