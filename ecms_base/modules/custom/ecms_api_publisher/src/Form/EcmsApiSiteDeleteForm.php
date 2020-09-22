<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_publisher\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Builds the form to delete ecms api site entities.
 */
class EcmsApiSiteDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('entity.ecms_api_site.collection');
  }

}
