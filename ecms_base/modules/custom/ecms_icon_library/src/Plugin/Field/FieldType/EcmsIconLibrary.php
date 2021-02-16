<?php

namespace Drupal\ecms_icon_library\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of eCMS Icon Library Icon.
 *
 * @FieldType(
 *   id = "ecms_icon_library",
 *   label = @Translation("eCMS Icon Library Icon"),
 *   module = "ecms_icon_libary",
 *   category = @Translation("Icons"),
 *   description = @Translation("A eCMS Icon Library icon"),
 *   default_widget = "ecms_icon_library_widget",
 *   default_formatter = "ecms_icon_library_formatter"
 * )
 */
class EcmsIconLibrary extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      // Columns contains the values that the field will store.
      'columns' => [
        'pl_icon' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => TRUE,
        ],
        'media_library_icon' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ]
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['pl_icon'] = DataDefinition::create('string')
      ->setLabel(t('eCMS Library Icon'))
      ->setDescription(t('The name of the icon'));
    $properties['media_library_icon'] = DataDefinition::create('string')
      ->setLabel(t('Media Library Icon'))
      ->setDescription(t('The name of the icon'));

    return $properties;
  }
}
