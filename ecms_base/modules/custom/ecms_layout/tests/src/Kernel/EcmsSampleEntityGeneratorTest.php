<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_layout\Kernel;

use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TempStore\SharedTempStore;
use Drupal\ecms_layout\EcmsSampleEntityGenerator;
use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\layout_builder\Entity\LayoutBuilderSampleEntityGenerator;
use Drupal\layout_builder\Entity\SampleEntityGeneratorInterface;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that Layout Builder sample entities are created without sample values.
 *
 * The environment set up here is the minimum needed to reproduce the RIGA-895
 * cascade: a node type with an entity_reference_revisions field to a paragraph
 * type, and that paragraph type carrying an entity reference field to an empty
 * vocabulary.
 *
 * @see \Drupal\ecms_layout\EcmsSampleEntityGenerator
 * @see https://thinkoomph.jira.com/browse/RIGA-895
 */
#[Group('ecms_layout')]
#[CoversClass(EcmsSampleEntityGenerator::class)]
class EcmsSampleEntityGeneratorTest extends KernelTestBase {

  use EntityReferenceFieldCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'file',
    'node',
    'taxonomy',
    'block',
    'contextual',
    'layout_discovery',
    'layout_builder',
    'entity_reference_revisions',
    'paragraphs',
    'ecms_layout',
  ];

  /**
   * The tempstore collection Layout Builder keeps its sample entities in.
   */
  private const TEMPSTORE_COLLECTION = 'layout_builder.sample_entity';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('paragraph');
    $this->installConfig(['field', 'filter', 'node', 'taxonomy']);

    // The shared tempstore derives its owner from the current user, falling
    // back to the session for anonymous users. Layout Builder is only reachable
    // by authenticated editors, so mirror that here.
    $this->setUpCurrentUser();

    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();

    ParagraphsType::create([
      'id' => 'publication_list',
      'label' => 'Publication list',
    ])->save();

    // An empty vocabulary is the trigger: EntityReferenceItem only generates a
    // brand new term (with a random langcode and a coin-flip
    // default_langcode) when there is nothing referenceable to pick from.
    Vocabulary::create([
      'vid' => 'publication_audience',
      'name' => 'Publication audience',
    ])->save();

    $this->createEntityReferenceField(
      'paragraph',
      'publication_list',
      'field_publication_list_audience',
      'Audience',
      'taxonomy_term',
      'default',
      ['target_bundles' => ['publication_audience' => 'publication_audience']],
    );

    $this->createParagraphsField('node', 'page', 'field_content', 'publication_list');
  }

  /**
   * The decorator replaces core's generator on the service and its alias.
   */
  public function testDecoratorReplacesCoreGenerator(): void {
    $this->assertInstanceOf(
      EcmsSampleEntityGenerator::class,
      $this->container->get('layout_builder.sample_entity_generator'),
      'ecms_layout.sample_entity_generator must decorate ' .
      'layout_builder.sample_entity_generator. Without the decoration, ' .
      'Layout Builder uses core\'s generator and the sample value cascade ' .
      'remains in place.'
    );

    // DefaultsSectionStorage and other consumers may be injected through the
    // interface alias, which has to resolve to the decorator as well.
    $this->assertInstanceOf(
      EcmsSampleEntityGenerator::class,
      $this->container->get(SampleEntityGeneratorInterface::class)
    );
  }

  /**
   * The sample entity is created empty rather than with generated values.
   */
  public function testSampleEntityHasNoGeneratedFieldValues(): void {
    $entity = $this->sampleEntityGenerator()->get('node', 'page');

    $this->assertInstanceOf(NodeInterface::class, $entity);
    $this->assertSame('page', $entity->bundle());
    $this->assertTrue($entity->isNew(), 'The sample entity must never be saved.');
    $this->assertTrue($entity->in_preview, 'The sample entity must be marked as a preview.');

    // label() returning NULL is what hands NULL to Html::escape() through
    // LinkGenerator, so the label key must be a string.
    $this->assertSame('', $entity->label());

    // Every configurable field must be empty. Core's generator fills all of
    // them through generateSampleItems(). Base field overrides are excluded:
    // they also implement FieldConfigInterface, but they carry the ordinary
    // defaults of base fields such as status, uid and created.
    $asserted = [];
    foreach ($entity->getFields() as $field_name => $field) {
      $definition = $field->getFieldDefinition();
      if ($definition instanceof FieldConfigInterface && !$definition instanceof BaseFieldOverride) {
        $this->assertTrue(
          $field->isEmpty(),
          sprintf('The sample entity field "%s" must have no generated value.', $field_name)
        );
        $asserted[] = $field_name;
      }
    }

    // Guard against the loop above silently asserting nothing.
    $this->assertSame(['field_content'], $asserted);
  }

  /**
   * Generating a sample entity persists no paragraphs and no taxonomy terms.
   *
   * This is the defect itself: the sample values cascade saved real rows into
   * paragraphs_item_field_data and taxonomy_term_field_data on every visit to a
   * "Manage layout" page for a default display.
   */
  public function testSampleEntityPersistsNothing(): void {
    $this->sampleEntityGenerator()->get('node', 'page');

    $this->assertSame(0, $this->countEntities('paragraph'), 'No paragraphs may be saved.');
    $this->assertSame(0, $this->countEntities('taxonomy_term'), 'No taxonomy terms may be saved.');
    $this->assertSame(0, $this->countEntities('node'), 'No nodes may be saved.');
  }

  /**
   * Documents the core behaviour the decorator exists to prevent.
   *
   * If this test starts failing, core or entity_reference_revisions stopped
   * persisting generated sample entities and the decorator may no longer be
   * needed — but the assertions above would then pass trivially, so this test
   * is what keeps them meaningful.
   */
  public function testCoreGeneratorPersistsSampleEntities(): void {
    $core_generator = new LayoutBuilderSampleEntityGenerator(
      $this->container->get('tempstore.shared'),
      $this->container->get('entity_type.manager')
    );

    $entity = $core_generator->get('node', 'page');

    $this->assertFalse(
      $entity->get('field_content')->isEmpty(),
      'Core\'s generator populates the paragraph field with sample values.'
    );
    $this->assertGreaterThan(
      0,
      $this->countEntities('paragraph'),
      'EntityReferenceRevisionsItem::generateSampleValue() saves the paragraph ' .
      'it generates.'
    );
    $this->assertGreaterThan(
      0,
      $this->countEntities('taxonomy_term'),
      'The generated paragraph references a term created for the empty ' .
      'vocabulary, which EntityReferenceItem::preSave() persists.'
    );
  }

  /**
   * The generated entity is reused from the tempstore on subsequent calls.
   */
  public function testGetCachesTheSampleEntityInTempstore(): void {
    $generator = $this->sampleEntityGenerator();

    $first = $generator->get('node', 'page');
    $stored = $this->tempstore()->get('node.page');

    $this->assertNotNull($stored, 'The sample entity must be written to the tempstore.');
    $this->assertSame($first->uuid(), $stored->uuid());

    $second = $generator->get('node', 'page');
    $this->assertSame(
      $first->uuid(),
      $second->uuid(),
      'A second call must return the tempstore copy instead of creating a new entity.'
    );
    $this->assertTrue($second->in_preview, 'The cached entity must still be marked as a preview.');
  }

  /**
   * Deleting removes the tempstore entry and returns the generator.
   */
  public function testDeleteRemovesTheTempstoreEntry(): void {
    $generator = $this->sampleEntityGenerator();
    $generator->get('node', 'page');

    $this->assertSame($generator, $generator->delete('node', 'page'));
    $this->assertNull($this->tempstore()->get('node.page'));
  }

  /**
   * Unsupported entity storage throws, matching core's contract.
   */
  public function testUnsupportedEntityStorageThrows(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The "node_type" entity storage is not supported');

    $this->sampleEntityGenerator()->get('node_type', 'page');
  }

  /**
   * Returns the decorated sample entity generator.
   */
  private function sampleEntityGenerator(): SampleEntityGeneratorInterface {
    return $this->container->get('layout_builder.sample_entity_generator');
  }

  /**
   * Returns the Layout Builder sample entity tempstore.
   */
  private function tempstore(): SharedTempStore {
    return $this->container->get('tempstore.shared')->get(self::TEMPSTORE_COLLECTION);
  }

  /**
   * Counts the saved entities of a given entity type.
   */
  private function countEntities(string $entity_type_id): int {
    return (int) $this->container->get('entity_type.manager')
      ->getStorage($entity_type_id)
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();
  }

  /**
   * Adds an entity_reference_revisions field targeting a paragraph type.
   */
  private function createParagraphsField(string $entity_type, string $bundle, string $field_name, string $paragraph_type): void {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'entity_reference_revisions',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => 'paragraph',
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'label' => 'Content',
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => [
          'target_bundles' => [$paragraph_type => $paragraph_type],
        ],
      ],
    ])->save();
  }

}
