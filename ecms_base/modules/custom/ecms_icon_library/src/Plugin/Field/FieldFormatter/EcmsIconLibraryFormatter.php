<?php

declare(strict_types=1);

namespace Drupal\ecms_icon_library\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Render\Markup;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

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

        $file_uri = File::load($fid)->getFileUri();
        $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file_uri);

        // Render as SVG tag.
        $svgRaw = $this->urlGetContents($file_url);

        if ($svgRaw === FALSE) {
          \Drupal::logger('ecms_icon_library')->notice(
            'File ID ' . $fid . ' cannot be loaded as SVG.'
          );
          continue;
        }

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

    return $elements;

  }

  /**
   * Replacement for function file_get_contents().
   *
   * See stackoverflow.com/questions/3979802/alternative-to-file-get-contents.
   *
   * From curl_exec() docs: "If the CURLOPT_RETURNTRANSFER option is set,
   * it will return the result on success, false on failure."
   *
   * @param string $url
   *   The URL of the file to be fetched.
   *
   * @return string|bool
   *   The output of cURL response, if successful; or FALSE.
   */
  private function urlGetContents(string $url): string|bool {
    if (!function_exists('curl_init')) {
      \Drupal::logger('ecms_icon_library')->notice(
        '`urlGetContents()` cannot work because `CURL` is not available.'
      );
      return FALSE;
    }
    $curl_handle = curl_init();
    curl_setopt($curl_handle, CURLOPT_URL, $url);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
    $output = curl_exec($curl_handle);
    curl_close($curl_handle);
    return $output;
  }

}
