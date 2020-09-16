<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_publisher\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for eCMS API Site entities.
 *
 * @ingroup ecms_api_publisher
 */
class EcmsApiSiteForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label endpoint.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label endpoint.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.ecms_api_site.canonical', ['ecms_api_site' => $entity->id()]);
  }

}
