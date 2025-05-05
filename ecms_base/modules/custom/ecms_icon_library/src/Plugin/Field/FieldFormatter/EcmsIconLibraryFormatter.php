<?php

declare(strict_types=1);

namespace Drupal\ecms_icon_library\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Render\Markup;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\svg_image\Plugin\Field\FieldFormatter\SvgImageFormatterTrait;

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
class EcmsIconLibraryFormatter extends FormatterBase {

  use SvgImageFormatterTrait;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

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
          return;
        }

        $fid = $media->field_icon_image->target_id;

        if (!$fid) {
          return;
        }

        $file = File::load($fid);

        $tempElement = [];
        $this->renderAsSvg($file, $tempElement, $elements[$delta]['#url']);

        $elements[$delta]['media_library_icon'] = $tempElement;
      }
    }

    return $elements;

  }

}
