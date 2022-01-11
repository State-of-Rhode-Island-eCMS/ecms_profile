<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_api_publisher\Unit\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldTypePluginManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\ecms_api_publisher\Entity\EcmsApiSite;
use Drupal\link\LinkItemInterface;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\Tests\UnitTestCase;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;
use phpmock\MockBuilder;

/**
 * Unit tests for the EcmsApiSite class.
 *
 * @covers \Drupal\ecms_api_publisher\Entity\EcmsApiSite
 * @group ecms
 * @group ecms_api
 * @group ecms_api_publisher
 */
class EcmsApiSiteTest extends UnitTestCase {

  /**
   * The timestamp to use for testing.
   */
  const TIMESTAMP = 342576000;

  /**
   * The UID to use for testing.
   */
  const UID = 21;

  /**
   * The host for testing.
   */
  const API_HOST = 'https://oomphinc.com';

  /**
   * The custom base fields added to this entity.
   */
  const ENTITY_BASE_FIELDS = [
    'name' => 'string',
    'uid' => 'entity_reference',
    'created' => 'created',
    'changed' => 'changed',
    'content_type' => 'entity_reference',
    'api_host' => 'link',
  ];

  /**
   * Mock of the EcmsApiSite entity.
   *
   * @var \Drupal\ecms_api_publisher\Entity\EcmsApiSite|\PHPUnit\Framework\MockObject\MockObject
   */
  private $entity;

  /**
   * Mock of the Owner of the entity.
   *
   * @var \Drupal\user\EntityOwnerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $owner;

  /**
   * Mock of a new owner for the entity.
   *
   * @var \Drupal\user\UserInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $newOwner;

  /**
   * The Plugin manager to set basefielddefinitions.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManager|\PHPUnit\Framework\MockObject\MockObject
   */
  private $pluginManager;

  /**
   * The container to use in testing.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  private $container;

  /**
   * Mock the global t() function.
   *
   * @var \phpmock\Mock
   */
  private $mockGlobalTFunction;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $typed_data_manager = $this->createMock(TypedDataManagerInterface::class);
    $this->container = new ContainerBuilder();
    $this->container->set('typed_data_manager', $typed_data_manager);
    $this->container->set('string_translation', $this->getStringTranslationStub());
    $currentUser = $this->createMock(AccountInterface::class);
    $currentUser->method('id')
      ->willReturn(self::UID);
    $this->container->set('current_user', $currentUser);
    \Drupal::setContainer($this->container);

    $mockGlobalTFunction = new MockBuilder();
    $mockGlobalTFunction->setNamespace('\Drupal\ecms_api_publisher\Entity')
      ->setName('t')
      ->setFunction(
        function ($string, array $args = [], array $options = []) {
          // @codingStandardsIgnoreLine
          return new TranslatableMarkup($string, $args, $options);
        }
      );

    $this->mockGlobalTFunction = $mockGlobalTFunction->build();
    $this->mockGlobalTFunction->enable();

    $this->entity = $this->getMockBuilder(EcmsApiSite::class)
      ->onlyMethods(['get', 'set'])
      ->disableOriginalConstructor()
      ->getMock();

    $this->owner = $this->createMock(EntityOwnerInterface::class);

    // Mock a UserInterface object.
    $this->newOwner = $this->createMock(UserInterface::class);

    $this->pluginManager = $this
      ->getMockBuilder(FieldTypePluginManager::class)
      ->onlyMethods(['getDefaultStorageSettings', 'getDefaultFieldSettings'])
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Test the preCreate method.
   */
  public function testPreCreate(): void {
    $storageController = $this->createMock(EntityStorageInterface::class);
    $values = [];

    EcmsApiSite::preCreate($storageController, $values);

    $this->assertEquals(self::UID, $values['uid']);
  }

  /**
   * {@inheritDoc}
   */
  protected function tearDown(): void {
    parent::tearDown();

    // Disable the global t().
    $this->mockGlobalTFunction->disable();
  }

  /**
   * Test the getCreatedTime() method.
   */
  public function testGetCreatedTime(): void {
    $field = $this->buildFakeField(self::TIMESTAMP);

    $this->entity->expects($this->once())
      ->method('get')
      ->with('created')
      ->willReturn($field);

    $result = $this->entity->getCreatedTime();
    $this->assertEquals(self::TIMESTAMP, $result);
  }

  /**
   * Test the getOwnerId() method.
   */
  public function testGetOwnerId(): void {
    $field = $this->buildFakeField(self::UID);

    $this->entity->expects($this->once())
      ->method('get')
      ->with('uid')
      ->willReturn($field);

    $result = $this->entity->getOwnerId();
    $this->assertEquals(self::UID, $result);
  }

  /**
   * Test the getOwner() method.
   */
  public function testGetOwner(): void {
    $field = $this->buildFakeField($this->owner);

    $this->entity->expects($this->once())
      ->method('get')
      ->with('uid')
      ->willReturn($field);

    $result = $this->entity->getOwner();
    $this->assertEquals($this->owner, $result);
  }

  /**
   * Test the setCreatedTime() method.
   */
  public function testSetCreatedTime(): void {
    $this->entity
      ->expects($this->once())
      ->method('set')
      ->with('created', self::TIMESTAMP);

    $entity = $this->entity->setCreatedTime(self::TIMESTAMP);
    $this->assertEquals($this->entity, $entity);
  }

  /**
   * Test the setOwnerId() method.
   */
  public function testSetOwnerId(): void {
    $this->entity
      ->expects($this->once())
      ->method('set')
      ->with('uid', self::UID);

    $entity = $this->entity->setOwnerId(self::UID);
    $this->assertEquals($this->entity, $entity);
  }

  /**
   * Test the getApiEndpoint() method.
   */
  public function testGetApiEndpoint(): void {
    $link = $this->createMock(LinkItem::class);

    $this->setGetterValue(
      'api_host',
      $this->buildFakeField($link)
    );

    $result = $this->entity->getApiEndpoint();

    $this->assertEquals($link, $result);
  }

  /**
   * Test the getContentType() method.
   */
  public function testGetContentTypes(): void {
    $field = $this->createMock(FieldItemListInterface::class);
    $field->expects($this->once())
      ->method('getValue')
      ->willReturn(
        [
          ['target_id' => 'node_type_1'],
          ['target_id' => 'node_type_2'],
        ]
      );

    $this->setGetterValue('content_type', $field);

    $result = $this->entity->getContentTypes();
    $this->assertArrayEquals(['node_type_1', 'node_type_2'], $result);
  }

  /**
   * Test the getContentType() empty method.
   */
  public function testGetContentTypesEmpty(): void {
    $field = $this->createMock(FieldItemListInterface::class);
    $field->expects($this->once())
      ->method('getValue')
      ->willReturn([]);

    $this->setGetterValue('content_type', $field);

    $result = $this->entity->getContentTypes();
    $this->assertArrayEquals([], $result);
  }

  /**
   * Test the setOwner() method.
   */
  public function testSetOwner(): void {

    $this->newOwner
      ->expects($this->once())
      ->method('id')
      ->willReturn(self::UID);

    $this->entity
      ->expects($this->once())
      ->method('set')
      ->with('uid', self::UID);

    $entity = $this->entity->setOwner($this->newOwner);
    $this->assertEquals($this->entity, $entity);
  }

  /**
   * Test the baseFieldDefinitions static method.
   */
  public function testBaseFieldDefinitions(): void {
    // Setup the plugin manager and container.
    $this->setupPluginManager();

    // Mock the entitytypeinterface to pass to the baseFieldDefinitions method.
    $entityTypeInterface = $this->getMockBuilder(
      EntityTypeInterface::class
    )->getMock();

    // Setup a array for our fields.
    $fields = [];

    // Mock the fields for this entity.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel('Name')
      ->setDescription('Give a descriptive name for this endpoint.')
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ]);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Author')
      ->setDescription('The author of this entity.')
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel('Created')
      ->setDescription('The time that the entity was created.');

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel('Changed')
      ->setDescription('The time that the entity was last edited.');

    // Add the content type reference field.
    $fields['content_type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Content types')
      ->setDescription('The content types to broadcast.')
      ->setSetting('target_type', 'node_type')
      ->setSetting('handler', 'default:node_type')
      ->setCardinality(-1)
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 1,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ]);

    // The endpoint link field.
    $fields['api_host'] = BaseFieldDefinition::create('link')
      ->setLabel('API endpoint')
      ->setDescription('The API endpoint url for the recipient site. Do not include trailing slash.')
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'link_default',
        'weight' => 2,
      ])
      ->setSettings([
        'link_type' => LinkItemInterface::LINK_EXTERNAL,
        'title' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ]);

    // Get the actual results of the base fields.
    $results = EcmsApiSite::baseFieldDefinitions($entityTypeInterface);

    // Assert the custom fields are returned.
    foreach (self::ENTITY_BASE_FIELDS as $key => $value) {
      $this->assertEquals($fields[$key], $results[$key]);
    }
  }

  /**
   * Setup the plugin manager mocked methods.
   */
  protected function setupPluginManager(): void {
    // Mock entity base fields.
    $this->pluginManager->expects($this->any())
      ->method('getDefaultStorageSettings')
      ->willReturnCallback(function ($name) {
        if (in_array($name, self::ENTITY_BASE_FIELDS)) {
          return [];
        }

        return FALSE;
      });

    $this->pluginManager->expects($this->any())
      ->method('getDefaultFieldSettings')
      ->willReturnCallback(function ($name) {
        if (in_array($name, self::ENTITY_BASE_FIELDS)) {
          return [];
        }

        return FALSE;
      });

    // Setup the container.
    $this->container->set('plugin.manager.field.field_type', $this->pluginManager);
    // Set the container.
    \Drupal::setContainer($this->container);
  }

  /**
   * Set the expected value for "get" function.
   *
   * @param string $fieldName
   *   Field name.
   * @param mixed $value
   *   Value to return.
   */
  protected function setGetterValue(string $fieldName, $value): void {
    $this->entity->expects($this->once())
      ->method('get')
      ->with($fieldName)
      ->willReturn($value);
  }

  /**
   * Build class with a number of fake field methods.
   *
   * @param object $value
   *   Value to return.
   */
  protected function buildFakeField($value): object {

    // Return a new class that mimics the FieldItemsList.
    return new class($value) {

      /**
       * The expected value to be returned.
       *
       * @var mixed
       */
      public $value;

      /**
       * The entity field to be returned.
       *
       * @var mixed
       */
      public $entity;

      /**
       * Constructor.
       *
       * @param mixed $value
       *   The value provided to the method.
       */
      public function __construct($value) {
        $this->value = $value;
        $this->target_id = $value;
        $this->entity = $value;
      }

      /**
       * Mock the first() method.
       *
       * @return mixed
       *   Return the value passed into the class.
       */
      public function first() {
        return $this->value;
      }

      /**
       * Mock the referenced entities method.
       *
       * @return mixed
       *   Return the value passed into the class.
       */
      public function referencedEntities() {
        return $this->value;
      }

    };
  }

}
