<?php

namespace Drupal\ecms_blocks\Form;

/**
 * @file
 * Contains \Drupal\ecms_blocks\Form\SearchBlockForm.
 */


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class SearchBlockForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ecms_search_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#theme'] = 'ecms_search_block_form';

    $form['search_input'] = array(
      '#type' => 'textfield',
      "#placeholder" => t('Search the site'),
      '#required' => TRUE,
      "#attributes" => [
        'type' => "search"
      ],
      '#theme_wrappers' => array()
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Register'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getValue('search_input');
    $params['query'] = [
      'search_api_fulltext' => $input,
    ];
    $form_state->setRedirectUrl(Url::fromUri('internal:' . '/search', $params));
  }
}
