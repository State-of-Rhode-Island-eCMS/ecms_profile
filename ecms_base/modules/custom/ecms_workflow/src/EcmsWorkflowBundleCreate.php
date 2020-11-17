<?php

declare(strict_types = 1);

namespace Drupal\ecms_workflow;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;

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
   * The id for site admins.
   */
  const SITE_ADMIN_ROLE = 'site_admin';

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
   * Array of content types to exclude.
   */
  const EXCLUDED_TYPES = ['notification'];

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * EcmsWorkflowBundleCreate constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
  }

  /**
   * EcmsWorkflowBundleCreate destructor. Ensures caches are cleared.
   */
  public function __destruct() {
    if (function_exists('drupal_flush_all_caches')) {
      drupal_flush_all_caches();
    }
  }

  /**
   * Add scheduled transitions default settings to new content type.
   *
   * @param string $contentType
   *   The machine name of the new content type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function addSchedulerSettings(string $contentType): void {
    /** @var \Drupal\Core\Config\Config $scheduledTransitionsConfig */
    $scheduledTransitionsConfig = $this->configFactory->getEditable('scheduled_transitions.settings');
    $currentBundles = $scheduledTransitionsConfig->get('bundles');

    // Add the new content type to the entity types for scheduled transitions.
    $newBundle = [
      'entity_type' => 'node',
      'bundle' => $contentType,
    ];
    $currentBundles[] = $newBundle;
    $scheduledTransitionsConfig
      ->set('bundles', $currentBundles)
      ->save();
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

    $site_admin_role = $storage->load(self::SITE_ADMIN_ROLE);

    if (empty($site_admin_role)) {
      return;
    }

    // Site admin role has all editing permissions.
    $site_admin_role->grantPermission("create {$contentType} content");
    $site_admin_role->grantPermission("edit any {$contentType} content");
    $site_admin_role->grantPermission("delete any {$contentType} content");

    // Site admin role can manage scheduled transitions.
    $site_admin_role->grantPermission("add scheduled transitions node {$contentType}");
    $site_admin_role->grantPermission("reschedule scheduled transitions node {$contentType}");

    try {
      $site_admin_role->save();
    }
    catch (EntityStorageException $e) {
      return;
    }

    $content_publisher_role = $storage->load(self::CONTENT_PUBLISHER_ROLE);

    // Guard against an empty role.
    if (empty($content_publisher_role)) {
      return;
    }

    // Content Publisher role has all editing permissions.
    $content_publisher_role->grantPermission("create {$contentType} content");
    $content_publisher_role->grantPermission("edit any {$contentType} content");
    $content_publisher_role->grantPermission("delete any {$contentType} content");

    // Content Publisher role can manage scheduled transitions.
    $content_publisher_role->grantPermission("add scheduled transitions node {$contentType}");
    $content_publisher_role->grantPermission("reschedule scheduled transitions node {$contentType}");

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

    // Guard against any excluded content types.
    if (in_array($contentType, self::EXCLUDED_TYPES)) {
      return;
    }

    $this->setRolePermissions($contentType);
    $this->addSchedulerSettings($contentType);

    // Assign the new content type to the editorial workflow.
    $workflowEntities = $this->entityTypeManager
      ->getStorage("workflow")
      ->loadByProperties(["id" => self::WORKFLOW_ID]);

    // Guard against an empty workflow.
    if (empty($workflowEntities)) {
      return;
    }

    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = array_shift($workflowEntities);

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

  /**
   * Apply workflow, schedule, and permissions to active content types.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function assignWorkflowToActiveTypes(): void {

    $types = $this->entityTypeManager
      ->getStorage('node_type')
      ->loadMultiple();
    foreach ($types as $type) {
      // Skip if this is an excluded type.
      if (in_array($type->id(), self::EXCLUDED_TYPES)) {
        continue;
      }

      // Call the workflow service to update configuration.
      $this->addContentTypeToWorkflow($type->id());
    }

  }

  /**
   * Add taxonomy permissions to the correct roles.
   *
   * @param string $taxonomyType
   *   The machine name of the new taxonomy bundle.
   */
  public function addTaxonomyTypePermissions(string $taxonomyType): void {
    $this->setTaxonomyRolePermissions($taxonomyType);
  }

  /**
   * Set the correct role permissions for the taxonomy bundle.
   *
   * @param string $taxonomyType
   *   The machine name of the new taxonomy bundle.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function setTaxonomyRolePermissions(string $taxonomyType): void {
    $storage = $this->entityTypeManager->getStorage('user_role');

    $site_admin_role = $storage->load(self::SITE_ADMIN_ROLE);

    // Ensure we have the admin role.
    if (!empty($site_admin_role)) {
      // Site admin role has all editing permissions.
      $site_admin_role->grantPermission("create terms in {$taxonomyType}");
      $site_admin_role->grantPermission("edit terms in {$taxonomyType}");
      $site_admin_role->grantPermission("delete terms in {$taxonomyType}");

      try {
        $site_admin_role->save();
      }
      catch (EntityStorageException $e) {
        // Trap any errors, but continue processing.
      }
    }

    $content_publisher_role = $storage->load(self::CONTENT_PUBLISHER_ROLE);

    // Ensure we have the publisher role.
    if (!empty($content_publisher_role)) {
      // Content Publisher role has all editing permissions.
      $content_publisher_role->grantPermission("create terms in {$taxonomyType}");
      $content_publisher_role->grantPermission("edit terms in {$taxonomyType}");
      $content_publisher_role->grantPermission("delete terms in {$taxonomyType}");

      try {
        $content_publisher_role->save();
      }
      catch (EntityStorageException $e) {
        // Trap any errors.
      }
    }
  }

}
