<?php

declare(strict_types=1);

namespace Drupal\ecms_layout;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\layout_builder\Entity\SampleEntityGeneratorInterface;

/**
 * Creates Layout Builder sample entities without generating sample values.
 *
 * Core's generator calls createWithSampleValues(), which runs
 * generateSampleItems() on every field of the placeholder entity — base fields
 * included. On an eCMS content type that cascades:
 *
 * - The node's paragraph field hits
 *   EntityReferenceRevisionsItem::generateSampleValue(), which picks a random
 *   paragraph bundle, fills it with sample values and calls save() on it (it
 *   has to, in order to return a target_revision_id). Those become real,
 *   permanently orphaned lorem ipsum paragraph rows.
 * - A sample paragraph's entity reference field hits
 *   EntityReferenceItem::generateSampleValue(). When the target vocabulary has
 *   no referenceable terms it builds one with createWithSampleValues(), so the
 *   term's base fields are generated too: langcode becomes a random installed
 *   language and default_langcode a coin flip from BooleanItem. A term stored
 *   with default_langcode = 0 has no default translation, so label() returns
 *   NULL, and any formatter with link: TRUE then hands NULL to
 *   Html::escape() — a TypeError that takes down the whole Manage layout page.
 * - That generated term is attached as $values['entity'], so
 *   EntityReferenceItem::preSave() persists it along with the paragraph above.
 *
 * Because the term is only generated while the vocabulary is empty, deleting
 * the junk terms is not enough: they come back on the next visit to a Manage
 * layout page. Creating the placeholder entity empty removes the cascade at
 * its source. Layout Builder renders field blocks with no value as their
 * preview fallback label ("«field name» field"), which is what editors expect
 * on a default display anyway.
 *
 * @see \Drupal\layout_builder\Entity\LayoutBuilderSampleEntityGenerator
 * @see \Drupal\Core\Entity\ContentEntityStorageBase::createWithSampleValues()
 * @see https://thinkoomph.jira.com/browse/RIGA-895
 */
final class EcmsSampleEntityGenerator implements SampleEntityGeneratorInterface {

  /**
   * The tempstore collection Layout Builder keeps its sample entities in.
   *
   * Layout Builder's own delete() callers and tests rely on this name, so it
   * must match core's generator.
   */
  private const TEMPSTORE_COLLECTION = 'layout_builder.sample_entity';

  public function __construct(
    private readonly SharedTempStoreFactory $tempStoreFactory,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function get($entity_type_id, $bundle_id) {
    $tempstore = $this->tempStoreFactory->get(self::TEMPSTORE_COLLECTION);
    if ($entity = $tempstore->get("$entity_type_id.$bundle_id")) {
      return $entity;
    }

    $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
    if (!$entity_storage instanceof ContentEntityStorageInterface) {
      throw new \InvalidArgumentException(sprintf('The "%s" entity storage is not supported', $entity_type_id));
    }

    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $values = [];
    if ($bundle_key = $entity_type->getKey('bundle')) {
      $values[$bundle_key] = $bundle_id;
    }

    // Create the entity without any generated field values.
    $entity = $entity_storage->create($values);

    // Templates and preprocess hooks treat the entity label as a string. An
    // unset label key leaves label() returning NULL, which reintroduces the
    // Html::escape() TypeError on the sample entity itself.
    $label_key = $entity_type->getKey('label');
    if ($label_key && $entity instanceof FieldableEntityInterface && $entity->hasField($label_key)) {
      $entity->set($label_key, '');
    }

    // Mark the sample entity as being a preview.
    $entity->in_preview = TRUE;
    $tempstore->set("$entity_type_id.$bundle_id", $entity);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($entity_type_id, $bundle_id) {
    $tempstore = $this->tempStoreFactory->get(self::TEMPSTORE_COLLECTION);
    $tempstore->delete("$entity_type_id.$bundle_id");
    return $this;
  }

}
