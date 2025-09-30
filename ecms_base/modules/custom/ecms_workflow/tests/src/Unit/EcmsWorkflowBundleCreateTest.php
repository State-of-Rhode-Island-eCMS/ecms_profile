<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_workflow\Unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecms_workflow\EcmsWorkflowBundleCreate;
use Drupal\Tests\UnitTestCase;
use Drupal\user\RoleInterface;
use Drupal\workflows\WorkflowInterface;
use Drupal\workflows\WorkflowTypeInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for the EcmsWorkflowBundleCreate class.
 *
 * @package Drupal\Tests\ecms_workflow\Unit
 */
#[Group("ecms_workflow")]
#[Group("ecms")]
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
   * Mock of the ConfigFactoryInterface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $configFactory;

  /**
   * Mock of the EntityDisplayRepositoryInterface.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $entityDisplayRepository;

  /**
   * Mock of a mutable Config object.
   *
   * @var \Drupal\Core\Config\Config|\PHPUnit\Framework\MockObject\MockObject
   */
  private $config;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityStorage = $this->createMock(EntityStorageInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->entityDisplayRepository = $this->createMock(EntityDisplayRepositoryInterface::class);
    $this->config = $this->createMock(Config::class);
  }

  /**
   * Test the addTaxonomyPermissions method.
   */
  public function testAddTaxonomyPermissions(): void {
    $bundle = $this->randomMachineName();

    $adminRole = $this->createMock(RoleInterface::class);
    $adminRole->expects($this->exactly(3))
      ->method('grantPermission')
      ->will($this->returnValueMap([
        ["create terms in {$bundle}", $adminRole],
        ["edit terms in {$bundle}", $adminRole],
        ["delete terms in {$bundle}", $adminRole]
      ]));

    $adminRole->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $publisherRole = $this->createMock(RoleInterface::class);
    $publisherRole->expects($this->exactly(3))
      ->method('grantPermission')
      ->will($this->returnValueMap([
        ["create terms in {$bundle}", $publisherRole],
        ["edit terms in {$bundle}", $publisherRole],
        ["delete terms in {$bundle}", $publisherRole]
      ]));

    $publisherRole->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $this->entityStorage->expects($this->exactly(2))
      ->method('load')
      ->will($this->returnValueMap([
        [self::SITE_ADMIN_ROLE, $adminRole], [self::CONTENT_PUBLISHER_ROLE, $publisherRole]
      ]));

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('user_role')
      ->willReturn($this->entityStorage);

    $testClass = new EcmsWorkflowBundleCreate(
      $this->entityTypeManager,
      $this->configFactory,
      $this->entityDisplayRepository,
    );

    $testClass->addTaxonomyTypePermissions($bundle);
  }

  /**
   * Test the addContentTypeToWorkflow method.
   */
  public function testAddContentTypeToWorkflow(): void {
    $contentType = $this->randomMachineName();

    $adminRole = $this->createMock(RoleInterface::class);
    $adminRole->expects($this->exactly(5))
      ->method('grantPermission')
      ->will($this->returnValueMap([
        ["create {$contentType} content", $adminRole],
        ["edit any {$contentType} content", $adminRole],
        ["delete any {$contentType} content", $adminRole],
        ["add scheduled transitions node {$contentType}", $adminRole],
        ["reschedule scheduled transitions node {$contentType}", $adminRole],
      ]));

    $adminRole->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $publisherRole = $this->createMock(RoleInterface::class);
    $publisherRole->expects($this->exactly(5))
      ->method('grantPermission')
      ->will($this->returnValueMap([
        ["create {$contentType} content", $publisherRole],
        ["edit any {$contentType} content", $publisherRole],
        ["delete any {$contentType} content", $publisherRole],
        ["add scheduled transitions node {$contentType}", $publisherRole],
        ["reschedule scheduled transitions node {$contentType}", $publisherRole],
      ]));

    $publisherRole->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $authorRole = $this->createMock(RoleInterface::class);
    $authorRole->expects($this->exactly(2))
      ->method('grantPermission')
      ->will($this->returnValueMap([
        ["create {$contentType} content", $authorRole],
        ["edit own {$contentType} content", $authorRole],
      ]));

    $authorRole->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $this->entityStorage->expects($this->exactly(3))
      ->method('load')
      ->will($this->returnValueMap([
        [self::SITE_ADMIN_ROLE, $adminRole],
        [self::CONTENT_PUBLISHER_ROLE, $publisherRole],
        [self::CONTENT_AUTHOR_ROLE, $authorRole],
      ]));

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

    $this->entityTypeManager->expects($this->exactly(2))
      ->method('getStorage')
      ->will($this->returnValueMap([
        ['user_role', $this->entityStorage],
        ['workflow', $workflowStorage],
      ]));

    $this->config->expects($this->once())
      ->method('get')
      ->with('bundles')
      ->willReturn([]);

    $newBundle = [
      'entity_type' => 'node',
      'bundle' => $contentType,
    ];

    $this->config->expects($this->once())
      ->method('set')
      ->with('bundles', [$newBundle])
      ->willReturnSelf();

    $this->config->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $this->configFactory->expects($this->once())
      ->method('getEditable')
      ->with('scheduled_transitions.settings')
      ->willReturn($this->config);

    $testClass = new EcmsWorkflowBundleCreate(
      $this->entityTypeManager,
      $this->configFactory,
      $this->entityDisplayRepository,
    );

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
      ->setConstructorArgs(
        [
          $this->entityTypeManager,
          $this->configFactory,
          $this->entityDisplayRepository,
        ]
      )
      ->getMock();

    $testClass->expects($this->once())
      ->method('addContentTypeToWorkflow')
      ->with($machineName);

    $testClass->assignWorkflowToActiveTypes();
  }

}
