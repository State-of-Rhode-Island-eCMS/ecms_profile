<?php

declare(strict_types=1);

namespace Drupal\ecms_search_settings_test\Plugin\search_api\backend;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiBackend;
use Drupal\search_api\Backend\BackendPluginBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\SearchApiException;

/**
 * A search backend that fails like search_api_solr when Solr is down.
 *
 * Searches fail while the state value "ecms_search_settings_test.fail" is
 * TRUE, and return no results otherwise.
 */
#[SearchApiBackend(
  id: 'ecms_search_settings_test_failing',
  label: new TranslatableMarkup('eCMS failing test backend'),
)]
class FailingBackend extends BackendPluginBase {

  /**
   * {@inheritdoc}
   */
  public function indexItems(IndexInterface $index, array $items): array {
    return array_keys($items);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItems(IndexInterface $index, array $item_ids): void {}

  /**
   * {@inheritdoc}
   */
  public function deleteAllIndexItems(IndexInterface $index, $datasource_id = NULL): void {}

  /**
   * {@inheritdoc}
   */
  public function search(QueryInterface $query): void {
    if (\Drupal::state()->get('ecms_search_settings_test.fail', FALSE)) {
      // Same as SearchApiSolrBackend::search() on a Solr exception.
      if ($query instanceof RefinableCacheableDependencyInterface) {
        $query->mergeCacheMaxAge(0);
      }
      throw new SearchApiException('Solr is unavailable.');
    }
    $query->getResults()->setResultCount(0);
  }

}
