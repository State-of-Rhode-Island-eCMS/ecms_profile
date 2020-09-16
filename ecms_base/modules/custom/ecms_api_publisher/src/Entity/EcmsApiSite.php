<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_publisher\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\link\LinkItemInterface;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

/**
 * Defines the eCMS syndicate site entity.
 *
 * @ContentEntityType(
 *   id = "ecms_api_site",
 *   label = @Translation("eCMS Syndicate Site"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ecms_api_publisher\EcmsApiSiteListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ecms_api_publisher\Form\EcmsApiSiteForm",
 *       "edit" = "Drupal\ecms_api_publisher\Form\EcmsApiSiteForm",
 *       "delete" = "Drupal\ecms_api_publisher\Form\EcmsApiSiteDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\ecms_api_publisher\EcmsApiSiteHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\ecms_api_publisher\EcmsApiSiteAccessControlHandler",
 *   },
 *   base_table = "ecms_api_site",
 *   data_table = "ecms_api_site_field_data",
 *   translatable = FALSE,
 *   admin_permission = "administer ecms api site entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "langcode" = "langcode"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/ecms_api/ecms_api_publisher/site/{ecms_api_site}",
 *     "add-form" = "/admin/config/ecms_api/ecms_api_publisher/site/add",
 *     "edit-form" = "/admin/config/ecms_api/ecms_api_publisher/site/{ecms_api_site}/edit",
 *     "delete-form" = "/admin/config/ecms_api/ecms_api_publisher/site/{ecms_api_site}/delete",
 *     "collection" = "/admin/config/ecms_api/ecms_api_publisher/sites"
 *   }
 * )
 */
class EcmsApiSite extends ContentEntityBase implements EcmsApiSiteInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values): void {
    parent::preCreate($storage_controller, $values);
    $values += [
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime(int $timestamp): EntityInterface {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner(): EntityOwnerInterface {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId(): int {
    return (int) $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid): EcmsApiSiteInterface {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account): EcmsApiSiteInterface {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getApiEndpoint(): LinkItem {
    return $this->get('api_host')->first();
  }

  /**
   * {@inheritDoc}
   */
  public function getContentTypes(): array {
    $contentTypes = $this->get('content_type')->getValue();
    if (empty($contentTypes)) {
      return [];
    }
    return array_map(function ($type) {
      return $type['target_id'];
    }, $contentTypes);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    // Get the base fields from the parent class.
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Give a descriptive name for this endpoint.'))
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
      ->setLabel(t('Author'))
      ->setDescription(t('The author of this entity.'))
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
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    // Add the content type reference field.
    $fields['content_type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Content types'))
      ->setDescription(t('The content types to broadcast.'))
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
      ->setLabel(t('API endpoint'))
      ->setDescription(t('The API endpoint url for the recipient site.'))
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

    return $fields;
  }

}
