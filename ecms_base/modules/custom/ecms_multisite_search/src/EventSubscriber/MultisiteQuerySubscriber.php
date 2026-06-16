<?php

declare(strict_types=1);

namespace Drupal\ecms_multisite_search\EventSubscriber;

use Drupal\Core\Language\LanguageInterface;
use Drupal\search_api_solr\Event\PostFieldMappingEvent;
use Drupal\search_api_solr\Event\PreQueryEvent;
use Drupal\search_api_solr\Event\SearchApiSolrEvents;
use Drupal\search_api_solr\SolrBackendInterface;
use Drupal\search_api_solr\Utility\Utility;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Fixes Solr field mapping and index_id filter for solr_multisite_all indexes.
 *
 * SearchApiSolrBackend::getDatasourceConfig() only recognises
 * 'solr_document' and 'solr_multisite_document' plugin IDs. For any other
 * Solr datasource three problems occur:
 *
 * 1. formatSolrFieldNames() reads $config['id_field'] and
 *    $config['language_field'] from the empty array getDatasourceConfig()
 *    returns, producing PHP warnings and an empty search_api_id mapping that
 *    causes "The result does not contain the essential ID field" errors.
 *
 * 2. Fields with datasource_id 'solr_multisite_all' fall through to the
 *    default branch of formatSolrFieldNames(), which generates generic
 *    type-prefixed field names that do not match the names stored in Solr by
 *    the target index. Keyword searches therefore return zero results even
 *    though the filter queries are correct.
 *
 * 3. getTargetedIndexId() falls back to the index's own machine name, so the
 *    backend emits '+index_id:multisite_test_all' which matches nothing in
 *    Solr (the stored value is the target index name,
 *    e.g. acquia_search_index).
 *
 * We subscribe to PostFieldMappingEvent to fix problems 1 and 2, and to
 * PreQueryEvent to fix problem 3.
 */
class MultisiteQuerySubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SearchApiSolrEvents::POST_FIELD_MAPPING => 'onPostFieldMapping',
      SearchApiSolrEvents::PRE_QUERY => 'onPreQuery',
    ];
  }

  /**
   * Fixes field mappings for solr_multisite_all datasource indexes.
   *
   * Two corrections are applied:
   * - search_api_id and search_api_language are populated from the datasource
   *   configuration (getDatasourceConfig() returns [] for unknown plugin IDs).
   * - Fields with datasource_id 'solr_multisite_all' are remapped using the
   *   same property_path language-substitution logic that the backend applies
   *   to 'solr_multisite_document' fields.
   *
   * @param \Drupal\search_api_solr\Event\PostFieldMappingEvent $event
   *   The post-field-mapping event.
   */
  public function onPostFieldMapping(PostFieldMappingEvent $event): void {
    $index = $event->getIndex();

    if (!$index->isValidDatasource('ecms_multisite_all')) {
      return;
    }

    try {
      $config = $index->getDatasource('ecms_multisite_all')->getConfiguration();
    }
    catch (\Exception $e) {
      return;
    }

    $langcode = $event->getLangcode();
    $mapping = $event->getFieldMapping();

    // Fix the essential ID and language field mappings.
    $mapping['search_api_id'] = $config['id_field'] ?? 'id';
    $mapping['search_api_language'] = $config['language_field'] ?? 'ss_search_api_language';

    // Re-map fields belonging to the ecms_multisite_all datasource using the
    // same logic SearchApiSolrBackend applies to solr_multisite_document:
    // decode the property_path, substitute the language code for 'und', then
    // re-encode. This converts stored property paths like
    // 'tcngramm_X3b_und_rendered_item' to the language-specific Solr field
    // name 'tcngramm_X3b_en_rendered_item' at query time.
    $lang_sep = SolrBackendInterface::SEARCH_API_SOLR_LANGUAGE_SEPARATOR;
    $und = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    $pattern = '/^(t[a-z0-9]*[ms]' . preg_quote($lang_sep, '/') . ')' . preg_quote($und, '/') . '(.+)/';

    foreach ($index->getFields() as $search_api_name => $field) {
      if ($field->getDatasourceId() !== 'ecms_multisite_all') {
        continue;
      }
      $mapping[$search_api_name] = Utility::encodeSolrName(
        preg_replace(
          $pattern,
          '$1' . $langcode . '$2',
          Utility::decodeSolrName($field->getPropertyPath())
        )
      );
    }

    $event->setFieldMapping($mapping);
  }

  /**
   * Replaces the index_id filter for solr_multisite_all datasource indexes.
   *
   * @param \Drupal\search_api_solr\Event\PreQueryEvent $event
   *   The pre-query event.
   */
  public function onPreQuery(PreQueryEvent $event): void {
    $index = $event->getSearchApiQuery()->getIndex();

    if (!$index->isValidDatasource('ecms_multisite_all')) {
      return;
    }

    try {
      $config = $index->getDatasource('ecms_multisite_all')->getConfiguration();
    }
    catch (\Exception $e) {
      return;
    }

    $target_index = $config['target_index_machine_name'] ?? '';
    if (!$target_index) {
      return;
    }

    $solarium_query = $event->getSolariumQuery();

    // Remove the index_filter added by the backend. It used the current
    // index's own machine name as the index_id value, which matches no
    // documents in Solr. Replace it with the target index machine name.
    $solarium_query->removeFilterQuery('index_filter');
    $solarium_query->createFilterQuery('index_filter')
      ->setQuery('+index_id:' . $solarium_query->getHelper()->escapeTerm($target_index));
  }

}
