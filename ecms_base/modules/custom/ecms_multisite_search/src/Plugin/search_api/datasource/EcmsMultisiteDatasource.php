<?php

declare(strict_types=1);

namespace Drupal\ecms_multisite_search\Plugin\search_api\datasource;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api_solr\Plugin\search_api\datasource\SolrMultisiteDocument;

/**
 * Exposes all factory sites' Solr documents without hash restriction.
 *
 * Extends SolrMultisiteDocument to make target_hash optional. When left
 * empty, no hash filter is applied and the index returns content from every
 * site sharing the same Solr core — provided the attached server has
 * site_hash set to false.
 *
 * @SearchApiDatasource(
 *   id = "ecms_multisite_all",
 *   label = @Translation("Solr Multisite All-Sites Document"),
 *   description = @Translation("Search across all factory sites sharing this Solr core. Requires a server with site_hash disabled. (Only works with a Solr-based server.)"),
 * )
 */
class EcmsMultisiteDatasource extends SolrMultisiteDocument {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    // Empty target_hash means no hash filter — returns results from all sites.
    $config['target_hash'] = '';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['target_hash']['#required'] = FALSE;
    $form['target_hash']['#description'] = $this->t('Leave empty to search across all factory sites sharing this Solr core. Enter a specific 6-character site hash to restrict results to a single site. The attached server must have site_hash disabled.');

    // SolrMultisiteDocument defines target_index_machine_name as #type
    // 'machine_name' without an #exists callback, causing a TypeError in
    // MachineName::validateMachineName() when the form is submitted. Change it
    // to a plain textfield since we are referencing an existing index, not
    // creating a new one.
    $form['target_index_machine_name']['#type'] = 'textfield';

    return $form;
  }

}
