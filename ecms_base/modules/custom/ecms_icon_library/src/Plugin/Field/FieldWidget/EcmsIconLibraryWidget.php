<?php

declare(strict_types = 1);

namespace Drupal\ecms_icon_library\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\OptGroup;

/**
 * Plugin implementation of the 'field_example_text' widget.
 *
 * @FieldWidget(
 *   id = "ecms_icon_library_widget",
 *   module = "ecms_icon_library",
 *   label = @Translation("Icon library"),
 *   field_types = {
 *     "list_string"
 *   },
 *   multiple_values = FALSE
 * )
 */
class EcmsIconLibraryWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['icon_filename'] = [
      '#type' => 'select',
      '#title' => 'Icon',
      '#description' => t('Select an icon from the dropdown. A preview of the icon will be shown below after you select it.'),
      '#options' => $this->getOptions($items->getEntity()),
      // Do not display a 'multiple' select box if there is only one option.
      '#default_value' => $this->getSelectedOptions($items),
      '#field_suffix' => '<div id="qh-icon-field" class="qh__icon-library-preview"></div>',
      '#ajax' => array(
        'callback' => [$this, 'ecms_icon_preview_callback'],
        'event' => 'change',
      ),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function ecms_icon_preview_callback(array &$form, FormStateInterface $form_state) {

    // Get icon filename from select element.
    $icon_filename = $form_state->getTriggeringElement()['#value'];

    if (!$icon_filename) {
      return;
    }

    // Get theme path.
    /** @var \Drupal\Core\Extension\ThemeHandler $themeHandler */
    $themeHandler = \Drupal::service('theme_handler');

    $defaultTheme = $themeHandler->getDefault();

    $path = drupal_get_path('theme', $defaultTheme);

    // If the source file doesn't exist, ignore the form alteration.
    if (!file_exists("{$path}/ecms_patternlab/source/images/icons/{$icon_filename}")) {
      return NULL;
    }

    // Generate SVG HTML.
    $svg_file = file_get_contents("{$path}/ecms_patternlab/source/images/icons/{$icon_filename}");

    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new HtmlCommand('#qh-icon-field', $svg_file));

    return $ajax_response;
  }

  /**
   * Returns the array of options for the widget.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity for which to return options.
   *
   * @return array
   *   The array of options for the widget.
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    $return = [];
    /** @var \Drupal\Core\Extension\ThemeHandler $themeHandler */
    $themeHandler = \Drupal::service('theme_handler');

    $defaultTheme = $themeHandler->getDefault();

    $path = drupal_get_path('theme', $defaultTheme);

    // If the source file doesn't exist, ignore the form alteration.
    if (!file_exists("{$path}/ecms_patternlab/source/_data/icons.json")) {
      return NULL;
    }

    $json = json_decode(file_get_contents("{$path}/ecms_patternlab/source/_data/icons.json"));

    if (!property_exists($json, 'icons')) {
      return NULL;
    }

    foreach ($json->{'icons'} as $key => $object) {
      $return[$object->{'filename'}] = $key;
    }

    $this->options = $return;

    return $this->options;
  }

}
