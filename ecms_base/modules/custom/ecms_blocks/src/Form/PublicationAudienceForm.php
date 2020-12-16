<?php

declare(strict_types = 1);

namespace Drupal\ecms_blocks\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a publication audience form.
 *
 * @package Drupal\ecms_blocks\Form
 */
class PublicationAudienceForm extends FormBase {

  const VID = 'publication_audience';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ecms_publication_audience_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    // Load audience values.
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree(self::VID);

    // Build out select options list.
    $audiences = [];
    foreach ($terms as $term) {
      $audiences[$term->tid] = $term->name;
    }

    // @todo Fix default value is not connected to paragraph.
    // See if the audience restriction is in the url.
    $url_restriction = \Drupal::request()->get('audience_restriction');
    if ($url_restriction !== NULL) {
      $default_value = $url_restriction;
    }
    else {
      // If no restriction then the default is Public.
      $default_value = array_search('Public', $audiences);
    }

    $form['audience_restriction'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter publications for:'),
      '#options' => $audiences,
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
    $input = $form_state->getValue('audience_restriction');
    $params['query'] = [
      'audience_restriction' => $input,
    ];

    $current_uri = \Drupal::request()->getRequestUri();

    $form_state->setRedirectUrl(Url::fromUri('internal:' . $current_uri, $params));
  }

}
