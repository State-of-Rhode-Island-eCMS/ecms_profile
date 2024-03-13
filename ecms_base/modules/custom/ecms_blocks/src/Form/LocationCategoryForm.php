<?php

declare(strict_types=1);

namespace Drupal\ecms_blocks\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a location category form.
 *
 * @package Drupal\ecms_blocks\Form
 */
class LocationCategoryForm extends FormBase {

  const VID = 'location_categories';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ecms_location_category_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    // Load category values.
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree(self::VID);

    // Build out select options list.
    $categories = [
      '' => t('All locations'),
    ];
    foreach ($terms as $term) {
      $categories[$term->tid] = $term->name;
    }

    // @todo Fix default value is not connected to paragraph.
    // See if the category restriction is in the url.
    $url_restriction = \Drupal::request()->get('location_restriction');
    if ($url_restriction !== NULL) {
      $default_value = $url_restriction;
    }
    else {
      // If no restriction then the default is Public.
      $default_value = NULL;
    }

    $form['location_restriction'] = [
      '#type' => 'select',
      '#options' => $categories,
      '#default_value' => $default_value,
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $input = $form_state->getValue('location_restriction');
    $params['query'] = [
      'location_restriction' => $input,
    ];

    $current_uri = \Drupal::request()->getRequestUri();

    $form_state->setRedirectUrl(Url::fromUri('internal:' . $current_uri, $params));
  }

}
