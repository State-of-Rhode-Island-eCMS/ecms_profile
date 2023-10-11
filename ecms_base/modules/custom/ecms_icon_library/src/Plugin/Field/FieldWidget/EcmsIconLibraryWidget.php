<?php

declare(strict_types = 1);

namespace Drupal\ecms_icon_library\Plugin\Field\FieldWidget;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_example_text' widget.
 *
 * @FieldWidget(
 *   id = "ecms_icon_library_widget",
 *   module = "ecms_icon_library",
 *   label = @Translation("Icon library"),
 *   field_types = {
 *     "ecms_icon_library"
 *   }
 * )
 */
class EcmsIconLibraryWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {

    $element['pl_icon'] =
    [
      '#type' => 'select',
      '#title' => 'Icon',
      '#description' => t('Select an icon from the dropdown. A preview of the icon will be shown below after you select it.'),
      '#options' => $this->getIconOptions(),
      // Do not display a 'multiple' select box if there is only one option.
      '#default_value' => $items[$delta]->get('pl_icon')->getValue(),
      '#field_suffix' => '<div id="qh-icon-field" class="qh__icon-library-preview"></div>',
      '#ajax' => [
        'callback' => [$this, 'ecmsIconPreviewCallback'],
        'event' => 'change',
      ],
    ];

    $element['media_library_icon'] =
    [
      '#type' => 'media_library',
      '#allowed_bundles' => ['icon'],
      '#title' => t('Media Library icon'),
      '#description' => t('Upload or select an existing icon. Note: This WILL override any icon in the above dropdown.'),
      '#default_value' => $items[$delta]->get('media_library_icon')->getValue(),
      /* TODO: FIX THIS.
       * A relevant issue: https://www.drupal.org/project/drupal/issues/1091852.
       * '#states' => [
       *   'visible' => [
       *     'select[name$="[pl_icon]"]' => ['value' => 'media_library_icon'],
       *   ]
       * ]
       */
    ];

    return $element;
  }

  /**
   * Custom #ajax callback to load SVG icon and add as suffix to form element.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response that display validation error messages or represents a
   *   successful submission.
   */
  public function ecmsIconPreviewCallback(array &$form, FormStateInterface $form_state) {

    // Get icon filename from select element.
    $icon_filename = $form_state->getTriggeringElement()['#value'];

    if (!$icon_filename) {
      return new AjaxResponse();
    }

    // Get theme path.
    /** @var \Drupal\Core\Extension\ThemeHandler $themeHandler */
    $themeHandler = \Drupal::service('theme_handler');
    $defaultTheme = $themeHandler->getDefault();
    $path = \Drupal::service('extension.list.theme')->getPath($defaultTheme);

    // If the source file doesn't exist, ignore the form alteration.
    if (!file_exists("{$path}/ecms_patternlab/source/images/icons/{$icon_filename}")) {
      return new AjaxResponse();
    }

    // Generate SVG HTML.
    $svg_file = file_get_contents("{$path}/ecms_patternlab/source/images/icons/{$icon_filename}");

    // Return ajax respons with SVG markup.
    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new HtmlCommand('#qh-icon-field', $svg_file));

    return $ajax_response;
  }

  /**
   * Returns the array of options for the widget.
   *
   * @return array
   *   The array of options for the widget.
   */
  protected function getIconOptions(): array {
    $return = [];

    // Get theme path.
    /** @var \Drupal\Core\Extension\ThemeHandler $themeHandler */
    $themeHandler = \Drupal::service('theme_handler');

    $defaultTheme = $themeHandler->getDefault();

    $path = \Drupal::service('extension.list.theme')->getPath($defaultTheme);

    // If the source file doesn't exist, ignore the form alteration.
    if (!file_exists("{$path}/ecms_patternlab/source/_data/icons.json")) {
      return [];
    }

    $json = json_decode(file_get_contents("{$path}/ecms_patternlab/source/_data/icons.json"));

    if (!property_exists($json, 'icons')) {
      return [];
    }

    foreach ($json->{'icons'} as $key => $object) {
      $return[$object->{'filename'}] = $key;
    }

    /* TODO: Resolve issue with #states api.
     * In the future this value should drive the
     * visible state of the media library element.
     * $return['media_library_icon'] = t("Icon from media library");
     */

    return $return;
  }

}
