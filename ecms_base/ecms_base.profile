<?php

/**
 * @file
 * Provides update hooks for previously installed sites.
 */

declare(strict_types = 1);

/**
 * Update Basic HTML configuration is updated.
 */
function ecms_base_update_9001(array &$sandbox): void {
  $config_factory = \Drupal::configFactory();
  $config = $config_factory->getEditable('filter.format.basic_html');
  $config->set('filters["filter_html"]["settings"]["allowed_html"]', '<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type> <li> <dl> <dt> <dd> <h2 id> <h3 id> <h4 id> <h5 id> <h6 id> <p> <br> <span> <s> <sup> <sub> <hr>');
  $config->save(TRUE);
}
