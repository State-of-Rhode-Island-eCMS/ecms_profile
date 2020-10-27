<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_workflow\Unit;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecms_workflow\EcmsWorkflowBundleCreate;
use Drupal\Tests\UnitTestCase;
use Drupal\user\RoleInterface;
use Drupal\workflows\WorkflowInterface;
use Drupal\workflows\WorkflowTypeInterface;

/**
 * Unit tests for the EcmsWorkflowBundleCreate class.
 *
 * @package Drupal\Tests\ecms_workflow\Unit
 * @group ecms
 * @group ecms_workflow
 */
class EcmsWorkflowBundleCreateTest extends UnitTestCase {

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
   * Mock of the entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $entityTypeManager;

  /**
   * Mock of the EntityStorageInterface.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $entityStorage;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityStorage = $this->createMock(EntityStorageInterface::class);
  }

  /**
   * Test the addTaxonomyPermissions method.
   */
  public function testAddTaxonomyPermissions(): void {
    $bundle = $this->randomMachineName();

    $adminRole = $this->createMock(RoleInterface::class);
    $adminRole->expects($this->exactly(3))
      ->method('grantPermission')
      ->withConsecutive(
        ["create terms in {$bundle}"],
        ["edit terms in {$bundle}"],
        ["delete terms in {$bundle}"]
      )
      ->willReturnSelf();

    $adminRole->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $publisherRole = $this->createMock(RoleInterface::class);
    $publisherRole->expects($this->exactly(3))
      ->method('grantPermission')
      ->withConsecutive(
        ["create terms in {$bundle}"],
        ["edit terms in {$bundle}"],
        ["delete terms in {$bundle}"]
      )
      ->willReturnSelf();

    $publisherRole->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $this->entityStorage->expects($this->exactly(2))
      ->method('load')
      ->withConsecutive([self::SITE_ADMIN_ROLE], [self::CONTENT_PUBLISHER_ROLE])
      ->willReturnOnConsecutiveCalls($adminRole, $publisherRole);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('user_role')
      ->willReturn($this->entityStorage);

    $testClass = new EcmsWorkflowBundleCreate($this->entityTypeManager);

    $testClass->addTaxonomyTypePermissions($bundle);
  }

  /**
   * Test the addContentTypeToWorkflow method.
   */
  public function testAddContentTypeToWorkflow(): void {
    $contentType = $this->randomMachineName();

    $adminRole = $this->createMock(RoleInterface::class);
    $adminRole->expects($this->exactly(3))
      ->method('grantPermission')
      ->withConsecutive(
        ["create {$contentType} content"],
        ["edit any {$contentType} content"],
        ["delete any {$contentType} content"]
      )
      ->willReturnSelf();

    $adminRole->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $publisherRole = $this->createMock(RoleInterface::class);
    $publisherRole->expects($this->exactly(3))
      ->method('grantPermission')
      ->withConsecutive(
        ["create {$contentType} content"],
        ["edit any {$contentType} content"],
        ["delete any {$contentType} content"]
      )
      ->willReturnSelf();

    $publisherRole->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $authorRole = $this->createMock(RoleInterface::class);
    $authorRole->expects($this->exactly(2))
      ->method('grantPermission')
      ->withConsecutive(
        ["create {$contentType} content"],
        ["edit own {$contentType} content"],
      )
      ->willReturnSelf();

    $authorRole->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $this->entityStorage->expects($this->exactly(3))
      ->method('load')
      ->withConsecutive([self::SITE_ADMIN_ROLE], [self::CONTENT_PUBLISHER_ROLE], [self::CONTENT_AUTHOR_ROLE])
      ->willReturnOnConsecutiveCalls(
        $adminRole,
        $publisherRole,
        $authorRole
      );

    $config = [
      "entity_types" => [
        "node" => [
          $contentType,
        ],
      ],
    ];

    $workFlowTypePlugin = $this->createMock(WorkflowTypeInterface::class);
    $workFlowTypePlugin->expects($this->once())
      ->method('getConfiguration')
      ->willReturn([]);

    $workFlowTypePlugin->expects($this->once())
      ->method('setConfiguration')
      ->with($config)
      ->willReturnSelf();

    $workflow = $this->createMock(WorkflowInterface::class);
    $workflow->expects($this->exactly(2))
      ->method('getTypePlugin')
      ->willReturn($workFlowTypePlugin);

    $workflow->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $workflowStorage = $this->createMock(EntityStorageInterface::class);
    $workflowStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(["id" => self::WORKFLOW_ID])
      ->willReturn([$workflow]);

    $nodeTypeEntity = $this->createMock(ConfigEntityInterface::class);
    $nodeTypeEntity->expects($this->exactly(12))
      ->method('setThirdPartySetting')
      ->withConsecutive(
        ['scheduler', 'expand_fieldset', 'when_required'],
        ['scheduler', 'fields_display_mode', 'fieldset'],
        ['scheduler', 'publish_enable', 1],
        ['scheduler', 'publish_past_date', 0],
        ['scheduler', 'publish_past_date_created', 'error'],
        ['scheduler', 'publish_required', 0],
        ['scheduler', 'publish_revision', 0],
        ['scheduler', 'publish_touch', 0],
        ['scheduler', 'show_message_after_update', 1],
        ['scheduler', 'unpublish_enable', 1],
        ['scheduler', 'unpublish_required', 0],
        ['scheduler', 'unpublish_revision', 0]
      )
      ->willReturnSelf();

    $nodeTypeEntity->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $nodeTypeStorage = $this->createMock(EntityStorageInterface::class);
    $nodeTypeStorage->expects($this->once())
      ->method('load')
      ->with($contentType)
      ->willReturn($nodeTypeEntity);

    $this->entityTypeManager->expects($this->exactly(3))
      ->method('getStorage')
      ->withConsecutive(['user_role'], ['node_type'], ['workflow'])
      ->willReturnOnConsecutiveCalls($this->entityStorage, $nodeTypeStorage, $workflowStorage);

    $testClass = new EcmsWorkflowBundleCreate($this->entityTypeManager);

    $testClass->addContentTypeToWorkflow($contentType);
  }

  /**
   * Test the assignWorkflowToActiveTypes method.
   */
  public function testAssignWorkflowToActiveTypes(): void {
    $machineName = $this->randomMachineName();
    $entityOne = $this->createMock(ConfigEntityInterface::class);
    $entityOne->expects($this->exactly(2))
      ->method('id')
      ->willReturn($machineName);

    $entityTwo = $this->createMock(ConfigEntityInterface::class);
    $entityTwo->expects($this->once())
      ->method('id')
      ->willReturn('notification');

    $types = [
      "{$machineName}" => $entityOne,
      "notification" => $entityTwo,
    ];

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn($types);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('node_type')
      ->willReturn($storage);

    // Get a mock of the class to test.
    $testClass = $this->getMockBuilder(EcmsWorkflowBundleCreate::class)
      ->onlyMethods(['addContentTypeToWorkflow'])
      ->setConstructorArgs([$this->entityTypeManager])
      ->getMock();

    $testClass->expects($this->once())
      ->method('addContentTypeToWorkflow')
      ->with($machineName);

    $testClass->assignWorkflowToActiveTypes();
  }

}
