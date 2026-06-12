<?php

declare(strict_types=1);

namespace Drupal\ecms_multisite_search\Plugin\search_api\backend;

use Drupal\acquia_search\Plugin\search_api\backend\AcquiaSearchSolrBackend;
use Drupal\search_api\IndexInterface;

/**
 * Extends Acquia Search Solr to recognise the solr_multisite_all datasource.
 *
 * SearchApiSolrBackend::getDatasourceConfig() only checks for the
 * 'solr_document' and 'solr_multisite_document' plugin IDs. When the index
 * uses 'solr_multisite_all', the method returns an empty array, causing a PHP
 * warning when formatSolrFieldNames() accesses $config['id_field'] before the
 * PostFieldMappingEvent is dispatched.
 *
 * Overriding getDatasourceConfig() here adds the missing check so the backend
 * can read the id_field / language_field values directly from the datasource
 * configuration, eliminating the warning.
 *
 * @SearchApiBackend(
 *   id = "ecms_acquia_search_solr",
 *   label = @Translation("eCMS Acquia Search Solr"),
 *   description = @Translation("Acquia Search Solr backend with support for the solr_multisite_all datasource used by the eCMS cross-site aggregator.")
 * )
 */
class EcmsMultisiteSolrBackend extends AcquiaSearchSolrBackend {

  /**
   * {@inheritdoc}
   *
   * Adds handling for the solr_multisite_all datasource plugin so that
   * formatSolrFieldNames() can resolve id_field / language_field without
   * emitting an "Undefined array key" PHP warning.
   */
  protected function getDatasourceConfig(IndexInterface $index): array {
    $config = parent::getDatasourceConfig($index);
    if (empty($config) && $index->isValidDatasource('solr_multisite_all')) {
      $config = $index->getDatasource('solr_multisite_all')->getConfiguration();
    }
    return $config;
  }

}
