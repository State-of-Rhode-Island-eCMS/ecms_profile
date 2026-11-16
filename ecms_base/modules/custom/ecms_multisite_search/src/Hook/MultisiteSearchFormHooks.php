<?php

declare(strict_types=1);

namespace Drupal\ecms_multisite_search\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Url;

/**
 * Keeps the multisite search card's exposed form on the page it is placed on.
 *
 * A block display's exposed form submits to the view's first page display
 * (/search-rhode-island), so a search from a Canvas search page would leave
 * that page. The card display is meant to be placed on a page (its exposed
 * form block in a section header, its results below), so its form submits
 * to the current page instead.
 */
class MultisiteSearchFormHooks {

  /**
   * Implements hook_form_FORM_ID_alter() for views_exposed_form.
   */
  #[Hook('form_views_exposed_form_alter')]
  public function viewsExposedFormAlter(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\views\ViewExecutable|null $view */
    $view = $form_state->get('view');
    if (!$view || $view->id() !== 'ecms_multisite_search' || $view->current_display !== 'card') {
      return;
    }

    $form['#action'] = Url::fromRoute('<current>')->toString();
    $form['#cache']['contexts'][] = 'url.path';
  }

}
