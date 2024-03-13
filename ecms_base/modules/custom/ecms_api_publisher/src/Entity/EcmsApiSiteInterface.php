<?php

declare(strict_types=1);

namespace Drupal\ecms_api_publisher\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining the Ecms Api Site entity.
 */
interface EcmsApiSiteInterface extends EntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Get the api endpoint url as a string.
   *
   * @return \Drupal\link\Plugin\Field\FieldType\LinkItem
   *   The api endpoint url as a string.
   */
  public function getApiEndpoint(): LinkItem;

  /**
   * Get the content types to syndicate for this endpoint.
   *
   * @return array
   *   The entity types that should be submitted for syndication.
   */
  public function getContentTypes(): array;

}
