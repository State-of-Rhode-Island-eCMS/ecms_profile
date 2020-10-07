<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_api_recipient\Unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\ecms_api_recipient\Form\EcmsApiRecipientConfigForm;
use Drupal\Tests\UnitTestCase;
use Drupal\user\RoleInterface;

/**
 * Unit testing for the EcmsApiRecipientConfigForm class.
 *
 * @package Drupal\Tests\ecms_api_recipient\Unit
 * @covers \Drupal\ecms_api_recipient\Form\EcmsApiRecipientConfigForm
 * @group ecms
 * @group ecms_api
 * @group ecms_api_recipient
 */
class EcmsApiRecipientConfigFormTest extends UnitTestCase {

  /**
   * The expected role entity id.
   */
  const RECIPIENT_ROLE = 'ecms_api_recipient';

  /**
   * The expected form id.
   */
  const FORM_ID = 'ecms_api_recipient_settings_form';

  /**
   * The expected configuration names.
   */
  const CONFIG_NAMES = [
    'ecms_api_recipient.settings',
  ];

  /**
   * The expected node types.
   */
  const NODE_TYPES = [
    'type_one' => ['label' => 'Type 1'],
    'type_two' => ['label' => 'Type 2'],
    'type_three' => ['label' => 'Type 3'],
  ];

  /**
   * The expected selected nodes.
   */
  const SELECTED_NODE_TYPES = [
    'type_two' => 'Type 2',
  ];

  /**
   * The existing permissions.
   */
  const EXISTING_PERMISSIONS = [
    'permission one',
    'permission two',
    'administer site',
  ];

  /**
   * Mock of the entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $entityTypeManager;

  /**
   * Mock of the entity_type.bundle.info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $entityBundleInfo;

  /**
   * Mock of the config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $configFactory;

  /**
   * Mock of the form state interface used in the form.
   *
   * @var \Drupal\Core\Form\FormStateInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $formState;

  /**
   * The configuration form being tested.
   *
   * @var \Drupal\Core\Form\ConfigFormBase|\Drupal\ecms_api_recipient\Form\EcmsApiRecipientConfigForm
   */
  private $configForm;

  /**
   * Mock of the container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  private $container;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->formState = $this->createMock(FormStateInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityBundleInfo = $this->createMock(EntityTypeBundleInfoInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);

    $messenger = $this->createMock(MessengerInterface::class);
    $this->container = new ContainerBuilder();
    $this->container->set('config.factory', $this->configFactory);
    $this->container->set('entity_type.manager', $this->entityTypeManager);
    $this->container->set('entity_type.bundle.info', $this->entityBundleInfo);
    $this->container->set('string_translation', $this->getStringTranslationStub());
    $this->container->set('messenger', $messenger);
    \Drupal::setContainer($this->container);

    // Test the create method.
    $this->configForm = EcmsApiRecipientConfigForm::create($this->container);
  }

  /**
   * Test the getFormId method.
   */
  public function testGetFormId(): void {
    $id = $this->configForm->getFormId();
    $this->assertEquals(self::FORM_ID, $id);
  }

  /**
   * Test the getEditableConfigNames method.
   */
  public function testGetEditableConfigNames(): void {
    $configNames = $this->configForm->getEditableConfigNames();
    $this->assertArrayEquals(self::CONFIG_NAMES, $configNames);
  }

  /**
   * Test the buildForm method.
   */
  public function testBuildForm(): void {
    $this->entityBundleInfo->expects($this->once())
      ->method('getBundleInfo')
      ->with('node')
      ->willReturn(self::NODE_TYPES);

    $settingsConfig = $this->createMock(Config::class);
    $settingsConfig->expects($this->once())
      ->method('get')
      ->with('allowed_content_types')
      ->willReturn(self::SELECTED_NODE_TYPES);

    $this->configFactory->expects($this->once())
      ->method('getEditable')
      ->with('ecms_api_recipient.settings')
      ->willReturn($settingsConfig);

    $form = [];
    $formArray = $this->configForm->buildForm($form, $this->formState);

    $this->assertArrayHasKey('allowed_content_types', $formArray);
    $this->assertArrayHasKey('#options', $formArray['allowed_content_types']);

    foreach (self::NODE_TYPES as $key => $value) {
      $this->assertArrayHasKey($key, $formArray['allowed_content_types']['#options']);
    }

    foreach (self::SELECTED_NODE_TYPES as $key => $value) {
      $this->assertArrayHasKey($key, $formArray['allowed_content_types']['#default_value']);
    }
  }

  /**
   * Successfully test the submit form.
   */
  public function testSubmitForm(): void {
    $this->formState->expects($this->once())
      ->method('getValue')
      ->with('allowed_content_types')
      ->willReturn(self::SELECTED_NODE_TYPES);

    $settingsConfig = $this->createMock(Config::class);
    $settingsConfig->expects($this->once())
      ->method('set')
      ->with('allowed_content_types', self::SELECTED_NODE_TYPES)
      ->willReturnSelf();

    $this->configFactory->expects($this->once())
      ->method('getEditable')
      ->with('ecms_api_recipient.settings')
      ->willReturn($settingsConfig);

    $roleEntity = $this->createMock(RoleInterface::class);
    $roleEntity->expects($this->once())
      ->method('getPermissions')
      ->willReturn(self::EXISTING_PERMISSIONS);

    $roleEntity->expects($this->exactly(count(self::EXISTING_PERMISSIONS)))
      ->method('revokePermission')
      ->willReturnSelf();

    $grantCount = (count(self::SELECTED_NODE_TYPES) * 2) + 1;
    $roleEntity->expects($this->exactly($grantCount))
      ->method('grantPermission')
      ->willReturnSelf();

    $roleEntity->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $roleStorage = $this->createMock(EntityStorageInterface::class);
    $roleStorage->expects($this->once())
      ->method('load')
      ->with(self::RECIPIENT_ROLE)
      ->willReturn($roleEntity);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('user_role')
      ->willReturn($roleStorage);

    $form = [];
    $this->configForm->submitForm($form, $this->formState);
  }

  /**
   * Test the submit form with an empty role.
   */
  public function testSubmitFormEntityException(): void {
    $this->formState->expects($this->once())
      ->method('getValue')
      ->with('allowed_content_types')
      ->willReturn(self::SELECTED_NODE_TYPES);

    $settingsConfig = $this->createMock(Config::class);
    $settingsConfig->expects($this->once())
      ->method('set')
      ->with('allowed_content_types', self::SELECTED_NODE_TYPES)
      ->willReturnSelf();

    $this->configFactory->expects($this->once())
      ->method('getEditable')
      ->with('ecms_api_recipient.settings')
      ->willReturn($settingsConfig);

    $roleEntity = $this->createMock(RoleInterface::class);
    $roleEntity->expects($this->never())
      ->method('getPermissions')
      ->willReturn(self::EXISTING_PERMISSIONS);

    $roleEntity->expects($this->never())
      ->method('revokePermission')
      ->willReturnSelf();

    $roleEntity->expects($this->never())
      ->method('grantPermission')
      ->willReturnSelf();

    $roleEntity->expects($this->never())
      ->method('save')
      ->willReturnSelf();

    $roleStorage = $this->createMock(EntityStorageInterface::class);
    $roleStorage->expects($this->once())
      ->method('load')
      ->with(self::RECIPIENT_ROLE)
      ->willReturn(NULL);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('user_role')
      ->willReturn($roleStorage);

    $form = [];
    $this->configForm->submitForm($form, $this->formState);
  }

  /**
   * Test the submit form with a storage exception.
   */
  public function testSubmitFormEntityStorageException(): void {
    $this->formState->expects($this->once())
      ->method('getValue')
      ->with('allowed_content_types')
      ->willReturn(self::SELECTED_NODE_TYPES);

    $settingsConfig = $this->createMock(Config::class);
    $settingsConfig->expects($this->once())
      ->method('set')
      ->with('allowed_content_types', self::SELECTED_NODE_TYPES)
      ->willReturnSelf();

    $this->configFactory->expects($this->once())
      ->method('getEditable')
      ->with('ecms_api_recipient.settings')
      ->willReturn($settingsConfig);

    $roleEntity = $this->createMock(RoleInterface::class);
    $roleEntity->expects($this->once())
      ->method('getPermissions')
      ->willReturn(self::EXISTING_PERMISSIONS);

    $roleEntity->expects($this->exactly(count(self::EXISTING_PERMISSIONS)))
      ->method('revokePermission')
      ->willReturnSelf();

    $grantCount = (count(self::SELECTED_NODE_TYPES) * 2) + 1;
    $roleEntity->expects($this->exactly($grantCount))
      ->method('grantPermission')
      ->willReturnSelf();

    $exception = $this->createMock(EntityStorageException::class);
    $roleEntity->expects($this->once())
      ->method('save')
      ->willThrowException($exception);

    $roleStorage = $this->createMock(EntityStorageInterface::class);
    $roleStorage->expects($this->once())
      ->method('load')
      ->with(self::RECIPIENT_ROLE)
      ->willReturn($roleEntity);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('user_role')
      ->willReturn($roleStorage);

    $form = [];
    $this->configForm->submitForm($form, $this->formState);
  }

}
