<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_publisher;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of eCMS Api Site entities.
 *
 * @ingroup ecms_api_publisher
 */
class EcmsApiSiteListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /* @var \Drupal\ecms_api_publisher\Entity\EcmsApiSite $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ecms_api_site.edit_form',
      ['ecms_api_site' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
