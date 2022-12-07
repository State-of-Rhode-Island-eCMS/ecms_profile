<?php

declare(strict_types = 1);

namespace Drupal\ecms_icon_library\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\Core\Render\Markup;
use Drupal\Core\Field\FormatterBase;

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

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {

      // Render each element as markup.
      if ($item->get('pl_icon')->getValue()) {
        $elements[$delta]['pl_icon'] = $item->get('pl_icon')->getValue();
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

        $file_uri = File::load($fid)->getFileUri();
        $file_url = file_create_url($file_uri);

        // Render as SVG tag.
        $svgRaw = $this->urlGetContents($file_url);

        // Debug.
        $message = "FID: " . $fid . "\n";
        $message = $message . "file_uri: " . $file_uri . "\n";
        $message = $message . "file_url: " . $file_url . "\n";
        $message = $message . "svgRaw: " . $svgRaw . "\n";
        $message = $message . "svgRaw: " . $svgRaw . "\n";
        $message = $message . "substr === Page not found?: " . (substr($svgRaw, 0, 14) === "Page not found");
        if (substr($svgRaw, 0, 14) === "Page not found") {
          \Drupal::logger('my_module')->notice("FID: " . $fid . " can't be loaded as SVG.");
          return;
        }
        else {
          \Drupal::logger('my_module')->notice($message);
        }

        if ($svgRaw) {
          $svgRaw = preg_replace(
            ['/<\?xml.*\?>/i', '/<!DOCTYPE((.|\n|\r)*?)">/i'],
            '',
            $svgRaw
          );
          $svgRaw = trim($svgRaw);

          $elements[$delta]['media_library_icon'] = [
            '#markup' => Markup::create($svgRaw),
          ];
        }
      }
    }

    return $elements;

  }

  /**
   * Replacement for function file_get_contents().
   *
   * See stackoverflow.com/questions/3979802/alternative-to-file-get-contents.
   */
  private function urlGetContents($url) {
    if (!function_exists('curl_init')) {
      die('CURL is not installed!');
    }
    $curl_handle = curl_init();
    curl_setopt($curl_handle, CURLOPT_URL, $url);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
    $output = curl_exec($curl_handle);
    curl_close($curl_handle);
    return $output;
  }

}
