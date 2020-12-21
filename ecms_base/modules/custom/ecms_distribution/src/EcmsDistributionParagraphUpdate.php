<?php

declare( strict_types = 1);

namespace Drupal\ecms_distribution;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;

class EcmsDistributionParagraphUpdate {

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public function updateNodeParagraphs(NodeInterface $node): void {
    $translations = $node->getTranslationLanguages(FALSE);
    // Guard against a node without translations.
    if (empty($translations)) {
      return;
    }

    /** @var \Drupal\paragraphs\ParagraphInterface[] $paragraphs */
    $paragraphs = $this->getParagraphReferences($node);

    // Guard against no paragraphs.
    if (empty($paragraphs)) {
      return;
    }

    foreach ($translations as $translation) {
      $translatedNode = $node->getTranslation($translation->getId());
      $newFields = [];

      foreach ($node->getFields(FALSE) as $field) {
        if ($field->getFieldDefinition()->getType() === 'entity_reference_revisions') {
          foreach ($field as $key => $item) {
            $parent = $item->getParent()->getName();
            $newParagraph = $this->processParagraph($item->entity, $translation);
            $newFields[$parent][$key]['target_id'] = $newParagraph->id();
            $newFields[$parent][$key]['target_revision_id'] = $newParagraph->getRevisionId();
            //$newFields[$parent][$key]['entity'] = $newParagraph;
          }
        }
      }

      if (!empty($newFields)) {
        foreach ($newFields as $fieldName => $value) {
          $translatedNode->set($fieldName, $value);
        }

        // Save the updated translation.
        try {
          $translatedNode->save();
        }
        catch (EntityStorageException $e) {
          $test = 1;
          // Hopefully not ever hit, but just in case.
        }

      }
    }
  }

  private function getParagraphReferences(EntityInterface $entity) {
    $references = $entity->referencedEntities();

    foreach ($references as $key => $reference) {
      if (!$reference instanceof ParagraphInterface) {
        unset($references[$key]);
      }

//      $nestedReferences = $this->getParagraphReferences($reference);
//
//      if (!empty($nestedReferences)) {
//        foreach ($nestedReferences as $nestedReference) {
//          array_push($references, $nestedReference);
//        }
//      }
    }

    return $references;
  }

  private function processParagraph(ParagraphInterface $originalParagraph, LanguageInterface $language) {
    if (!$originalParagraph->hasTranslation($language->getId())) {
      return $originalParagraph;
    }

    $translatedParagraph = $originalParagraph->getTranslation($language->getId());

    $storage = $this->entityTypeManager->getStorage('paragraph');

    $translation = $translatedParagraph->toArray();


    // Clone all sub-paragraphs recursively.
    foreach ($translatedParagraph->getFields(FALSE) as $field) {
      if ($field->getFieldDefinition()->getType() === 'entity_reference_revisions' && $field->getFieldDefinition()->getTargetEntityTypeId() === 'paragraph') {
        foreach ($field as $key => $item) {
          $parent = $item->getParent()->getName();
          $newParagraph = $this->processParagraph($item->entity, $language);
          $translation[$parent][$key]['target_id'] = $newParagraph->id();
          $translation[$parent][$key]['target_revision_id'] = $newParagraph->getRevisionId();
          //$translation[$parent][$key]['entity'] = $newParagraph;
        }
      }
    }

    /** @var \Drupal\paragraphs\ParagraphInterface $newEntity */
    $newEntity = $storage->create($translation);
    $newEntity->enforceIsNew();
    $newEntity->setPublished();

    try {
      $newEntity->save();
    }
    catch(EntityStorageException $e) {
      $bundle = $newEntity->bundle();
      $test =1;

    }

    // Remove the original translation from the paragraph.
    $originalParagraph->removeTranslation($language->getId());
    $originalParagraph->save();

    return $newEntity;
  }
}
