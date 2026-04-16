<?php

declare(strict_types=1);

namespace Drupal\ecms_workflow\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Form hook implementations for ecms_workflow.
 */
class EcmsWorkflowFormHooks {

  use StringTranslationTrait;

  /**
   * Constructs a new EcmsWorkflowFormHooks object.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Implements hook_form_FORM_ID_alter() for the person node form.
   */
  #[Hook('form_node_person_form_alter')]
  #[Hook('form_node_person_edit_form_alter')]
  public function personNodeFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    $this->checkPersonAdditionalFieldsVocabulary($form);
  }

  /**
   * Checks if the person_additional_fields vocabulary has terms.
   *
   * If no terms exist, displays a warning message and hides the paragraph
   * add button to prevent editors from creating unusable paragraph items.
   */
  protected function checkPersonAdditionalFieldsVocabulary(array &$form): void {
    if (!isset($form['field_person_additional_fields'])) {
      return;
    }

    $termCount = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('vid', 'person_additional_fields')
      ->count()
      ->execute();

    if ((int) $termCount > 0) {
      return;
    }

    $url = Url::fromRoute('entity.taxonomy_vocabulary.overview_form', [
      'taxonomy_vocabulary' => 'person_additional_fields',
    ]);

    if ($url->access()) {
      $message = $this->t('No additional field labels have been configured yet. Additional fields cannot be added until at least one term exists in the <em>Person additional fields</em> vocabulary. <a href="@url">Manage the Person additional fields vocabulary</a> to add terms.', [
        '@url' => $url->toString(),
      ]);
    }
    else {
      $message = $this->t('No additional field labels have been configured yet. Additional fields cannot be added until at least one term exists in the <em>Person additional fields</em> vocabulary. Contact a site administrator to add terms.');
    }

    $form['field_person_additional_fields']['ecms_empty_vocabulary_warning'] = [
      '#markup' => '<div class="messages messages--warning">' . $message . '</div>',
      '#weight' => -100,
    ];

    if (isset($form['field_person_additional_fields']['widget']['add_more'])) {
      $form['field_person_additional_fields']['widget']['add_more']['#access'] = FALSE;
    }
  }

}
