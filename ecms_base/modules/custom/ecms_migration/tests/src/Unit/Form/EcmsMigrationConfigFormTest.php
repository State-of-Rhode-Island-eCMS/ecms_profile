<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_migration\Unit\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\ecms_migration\Form\EcmsMigrationConfigForm;
use Drupal\Tests\UnitTestCase;
use phpmock\MockBuilder;

/**
 * Unit tests for the EcmsMigrationConfigForm.
 *
 * @package Drupal\Tests\ecms_migration\Unit\Form
 *
 * @group ecms_migration
 */
class EcmsMigrationConfigFormTest extends UnitTestCase {

  /**
   * The expected form id.
   */
  const FORM_ID = 'ecms_migration_settings_form';

  /**
   * The expected settings configuration.
   */
  const MIGRATION_SETTINGS_CONFIG = [
    'ecms_file' => [
      'json_source_url' => 'https://filetest.ri.gov',
    ],
    'ecms_basic_page' => [
      'json_source_url' => 'https://pagetest.ri.gov',
      'css_selector_1' => '#css-selector-1',
      'css_selector_2' => '#css-selector-2',
      'css_selector_3' => '#css-selector-3',
    ],
    '_core' => 'TEST',
  ];

  /**
   * The expected migration configuration.
   */
  const MIGRATION_MIGRATIONS_CONFIG = [
    'ecms_file' => [
      'migrate_plus.migration.ecms_file',
      'migrate_plus.migration.ecms_file_media',
      'migrate_plus.migration.ecms_file_redirect',
    ],
    'ecms_basic_page' => [
      'migrate_plus.migration.ecms_basic_page',
      'migrate_plus.migration.ecms_basic_page_url',
    ],
    '_core' => 'TEST',
  ];

  /**
   * Mock of the config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $configFactory;

  /**
   * Mock of the FormStateInterface.
   *
   * @var \Drupal\Core\Form\FormStateInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $formState;

  /**
   * Mock of the editable settings configuration.
   *
   * @var \Drupal\Core\Config\Config|\PHPUnit\Framework\MockObject\MockObject
   */
  private $settingsConfig;

  /**
   * Mock of the immutable migration configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|\PHPUnit\Framework\MockObject\MockObject
   */
  private $migrationConfig;

  /**
   * Mock of the drupal_flush_all_caches() method.
   *
   * @var \phpmock\Mock
   */
  private $mockFlushCache;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->formState = $this->createMock(FormStateInterface::class);
    $this->settingsConfig = $this->createMock(Config::class);
    $this->migrationConfig = $this->createMock(ImmutableConfig::class);

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('messenger', $this->createMock(MessengerInterface::class));

    \Drupal::setContainer($container);

    $mockFlushCache = new MockBuilder();
    $mockFlushCache->setNamespace('Drupal\ecms_migration\Form')
      ->setName('drupal_flush_all_caches')
      ->setFunction(
        function () {
        }
      );

    $this->mockFlushCache = $mockFlushCache->build();
    $this->mockFlushCache->enable();
  }

  /**
   * {@inheritDoc}
   */
  protected function tearDown(): void {
    parent::tearDown();

    $this->mockFlushCache->disable();
  }

  /**
   * Test the getFormId() method.
   */
  public function testGetFormId(): void {
    $form = new EcmsMigrationConfigForm($this->configFactory);

    $id = $form->getFormId();

    $this->assertEquals(self::FORM_ID, $id);
  }

  /**
   * Test the buildForm() method.
   */
  public function testBuildForm(): void {
    $form = [];

    $this->settingsConfig->expects($this->once())
      ->method('getRawData')
      ->willReturn(self::MIGRATION_SETTINGS_CONFIG);

    $this->migrationConfig->expects($this->once())
      ->method('getRawData')
      ->willReturn(self::MIGRATION_MIGRATIONS_CONFIG);

    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('ecms_migration.migrations')
      ->willReturn($this->migrationConfig);

    $this->configFactory->expects($this->once())
      ->method('getEditable')
      ->with('ecms_migration.settings')
      ->willReturn($this->settingsConfig);

    $testForm = new EcmsMigrationConfigForm($this->configFactory);

    $actualFormValues = $testForm->buildForm($form, $this->formState);

    foreach (self::MIGRATION_SETTINGS_CONFIG as $key => $value) {
      if ($key === '_core') {
        $this->assertArrayNotHasKey($key, $actualFormValues);
        continue;
      }

      $this->assertArrayHasKey($key, $actualFormValues);

      foreach ($value as $sub => $subvalue) {
        $this->assertArrayHasKey($sub, $actualFormValues[$key]);
      }
    }
  }

  /**
   * Test the submitForm method.
   */
  public function testSubmitForm(): void {
    $form = [];

    $this->settingsConfig->expects($this->once())
      ->method('getRawData')
      ->willReturn(self::MIGRATION_SETTINGS_CONFIG);

    $this->settingsConfig->expects($this->exactly(2))
      ->method('set')
      ->will($this->returnValueMap([
        ['ecms_file', self::MIGRATION_SETTINGS_CONFIG['ecms_file'], $this->settingsConfig],
          ['ecms_basic_page', self::MIGRATION_SETTINGS_CONFIG['ecms_basic_page'], $this->settingsConfig]
      ]));

    $this->migrationConfig->expects($this->any())
      ->method('getRawData')
      ->willReturn(self::MIGRATION_MIGRATIONS_CONFIG);

    $this->migrationConfig->expects($this->exactly(2))
      ->method('get')
      ->will($this->returnValueMap([
        ['ecms_file', self::MIGRATION_MIGRATIONS_CONFIG['ecms_file']],
        ['ecms_basic_page', self::MIGRATION_MIGRATIONS_CONFIG['ecms_basic_page']]
      ]));

    $this->configFactory->expects($this->any())
      ->method('get')
      ->with('ecms_migration.migrations')
      ->willReturn($this->migrationConfig);

    $this->configFactory->expects($this->exactly(3))
      ->method('getEditable')
      ->with('ecms_migration.settings')
      ->willReturn($this->settingsConfig);

    $this->formState->expects($this->exactly(2))
      ->method('getValue')
      ->will($this->returnValueMap([
        ['ecms_file', self::MIGRATION_SETTINGS_CONFIG['ecms_file']],
        ['ecms_basic_page', self::MIGRATION_SETTINGS_CONFIG['ecms_basic_page']],
      ]));

    $testForm = $this->getMockBuilder(EcmsMigrationConfigForm::class)
      ->onlyMethods(['setJsonUrl', 'setCssSelector'])
      ->setConstructorArgs([$this->configFactory])
      ->getMock();

    $testForm->expects($this->exactly(2))
      ->method('setJsonUrl')
      ->will($this->returnValueMap([[
        self::MIGRATION_SETTINGS_CONFIG['ecms_file']['json_source_url'],
        self::MIGRATION_MIGRATIONS_CONFIG['ecms_file'],
      ],
        [
          self::MIGRATION_SETTINGS_CONFIG['ecms_basic_page']['json_source_url'],
          self::MIGRATION_MIGRATIONS_CONFIG['ecms_basic_page'],
        ]]));

    $testForm->expects($this->exactly(3))
      ->method('setCssSelector')
      ->will($this->returnValueMap([
        [
          'css_selector_1',
          self::MIGRATION_SETTINGS_CONFIG['ecms_basic_page']['css_selector_1'],
          self::MIGRATION_MIGRATIONS_CONFIG['ecms_basic_page'],
        ],
        [
          'css_selector_2',
          self::MIGRATION_SETTINGS_CONFIG['ecms_basic_page']['css_selector_2'],
          self::MIGRATION_MIGRATIONS_CONFIG['ecms_basic_page'],
        ],
        [
          'css_selector_3',
          self::MIGRATION_SETTINGS_CONFIG['ecms_basic_page']['css_selector_3'],
          self::MIGRATION_MIGRATIONS_CONFIG['ecms_basic_page'],
        ]
      ]));

    $testForm->submitForm($form, $this->formState);
  }

}
