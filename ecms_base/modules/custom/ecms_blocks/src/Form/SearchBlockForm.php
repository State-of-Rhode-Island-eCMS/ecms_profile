<?php

declare(strict_types=1);

namespace Drupal\ecms_blocks\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a custom site search form.
 *
 * @package Drupal\ecms_blocks\Form
 */
class SearchBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ecms_search_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['search_input'] = [
      '#type' => 'textfield',
      "#placeholder" => $this->t('Search the site'),
      '#required' => TRUE,
      "#attributes" => [
        'type' => "search",
        'aria-label' => $this->t("Search"),
      ],
      '#theme_wrappers' => [],
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $input = $form_state->getValue('search_input');
    $params['query'] = [
      'search_api_fulltext' => $input,
    ];
    $form_state->setRedirectUrl(Url::fromUri('internal:/search', $params));
  }

}
