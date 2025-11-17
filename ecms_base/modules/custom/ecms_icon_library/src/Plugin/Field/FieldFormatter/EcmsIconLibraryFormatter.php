<?php

declare(strict_types=1);

namespace Drupal\ecms_icon_library\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\svg_image\Plugin\Field\FieldFormatter\SvgImageFormatterTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'image' formatter.
 *
 * We have to fully override standard field formatter, so we will keep original
 * label and formatter ID.
 *
 * @FieldFormatter(
 *   id = "ecms_icon_library_formatter",
 *   label = @Translation("eCMS Icon Formatter"),
 *   field_types = {
 *     "ecms_icon_library"
 *   },
 * )
 */
class EcmsIconLibraryFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  use SvgImageFormatterTrait;

  /**
   * Constructs an EcmsIconLibraryFormatter object.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    protected LoggerChannelFactoryInterface $logger,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $entity = $items->getEntity();

    foreach ($items as $delta => $item) {

      // Render each element as markup.
      if ($item->get('pl_icon')->getValue()) {
        $elements[$delta]['pl_icon'] = [
          '#plain_text' => $item->get('pl_icon')->getValue(),
        ];
      }

      if ($item->get('media_library_icon')->getValue()) {
        $media = Media::load($item->get('media_library_icon')->getValue());

        if (!$media) {
          return [];
        }

        $fid = $media->field_icon_image->target_id;

        if (!$fid) {
          return [];
        }

        $file = File::load($fid);

        $tempElement = ['#cache' => ['tags' => Cache::mergeTags($entity->getCacheTags(), $file->getCacheTags(), $media->getCacheTags())]];
        $this->renderAsSvg($file, $tempElement, NULL);

        $elements[$delta]['media_library_icon'] = $tempElement;
      }
    }

    return $elements;

  }

}
