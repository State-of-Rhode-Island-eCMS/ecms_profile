<?php

declare(strict_types=1);

namespace Drupal\ecms_multisite_search\Commands;

use Drush\Commands\DrushCommands;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Drush commands for seeding Solr with mock multisite press release data.
 *
 * Used during local development and QA to simulate press release content
 * from other factory sites without needing a full multisite environment.
 */
final class MultisiteSearchSeedCommands extends DrushCommands {

  // Default DDEV Solr endpoint — matches settings.local.php override.
  const DEFAULT_SOLR_URL = 'http://solr:8983/solr/ecms';

  // Documents will be indexed under this index ID (the target of ecms_multisite_index).
  const TARGET_INDEX_ID = 'acquia_search_index';

  /**
   * Mock factory sites used to generate seed documents.
   */
  const MOCK_SITES = [
    ['site_url' => 'https://tax.ri.gov/',  'hash' => 'taxriv', 'label' => 'RI Division of Taxation'],
    ['site_url' => 'https://doh.ri.gov/',  'hash' => 'dohriv', 'label' => 'RI Department of Health'],
    ['site_url' => 'https://dem.ri.gov/',  'hash' => 'demriv', 'label' => 'RI Dept of Environmental Management'],
  ];

  public function __construct(
    private readonly ClientInterface $httpClient,
  ) {
    parent::__construct();
  }

  /**
   * Seeds Solr with mock press release documents simulating other factory sites.
   *
   * Documents are POSTed directly to the Solr HTTP API using the field names
   * that Search API Solr's acquia_search_index would produce, allowing
   * ecms_multisite_index to return them via the solr_multisite_all datasource.
   *
   * @command ecms-multisite-search:seed-solr
   * @aliases emss
   *
   * @option delete Delete previously seeded mock documents instead of adding.
   * @option solr-url Solr core base URL (default: http://solr:8983/solr/ecms).
   * @option solr-user Solr basic auth username (default: solr).
   * @option solr-password Solr basic auth password (default: SolrRocks).
   *
   * @usage drush ecms-multisite-search:seed-solr
   *   Seed 9 mock press release documents from 3 simulated factory sites.
   * @usage drush ecms-multisite-search:seed-solr --delete
   *   Remove the 9 previously seeded mock documents.
   * @usage drush emss --solr-url=http://solr:8983/solr/mycore
   *   Seed against a different Solr core.
   */
  public function seedSolr(array $options = [
    'delete' => FALSE,
    'solr-url' => self::DEFAULT_SOLR_URL,
    'solr-user' => 'solr',
    'solr-password' => 'SolrRocks',
  ]): void {
    $documents = $this->buildMockDocuments();
    $auth = [$options['solr-user'], $options['solr-password']];

    if ($options['delete']) {
      $this->deleteDocuments($documents, $options['solr-url'], $auth);
    }
    else {
      $this->indexDocuments($documents, $options['solr-url'], $auth);
    }
  }

  /**
   * Builds mock Solr documents simulating press releases from other sites.
   *
   * Field names follow Search API Solr's encoding conventions:
   * - bs_* = boolean stored
   * - ss_* = string stored
   * - tcngramm_X3b_en_* = solr_text_custom:ngram, language=en
   *   (X3b is the URL-encoded semicolon ';' language separator)
   */
  private function buildMockDocuments(): array {
    $documents = [];
    // Stagger dates so results are distinguishable when sorted. Each document
    // is offset by 7 days from the previous one, oldest first.
    $base_timestamp = strtotime('2025-01-01T00:00:00Z');
    $doc_index = 0;
    foreach (self::MOCK_SITES as $site_idx => $site) {
      for ($i = 1; $i <= 3; $i++) {
        $nid = ($site_idx * 100) + $i;
        $slug = 'press-release-' . $nid;
        $created = gmdate('Y-m-d\TH:i:s\Z', $base_timestamp + ($doc_index * 7 * 86400));
        $doc_index++;
        $documents[] = [
          'id' => "{$site['hash']}-" . self::TARGET_INDEX_ID . "-entity:node/{$nid}:en",
          'index_id' => self::TARGET_INDEX_ID,
          'site' => $site['site_url'],
          'hash' => $site['hash'],
          'bs_status' => TRUE,
          'ss_type' => 'press_release',
          'ss_url' => $site['site_url'] . "press-releases/{$slug}",
          'ss_search_api_language' => 'en',
          'ds_created' => $created,
          'ds_changed' => $created,
          'tcngramm_X3b_en_title' => "{$site['label']}: Announcement {$i}",
          'tcngramm_X3b_en_rendered_item' => "<p>Mock press release {$i} from {$site['label']}.</p>",
        ];
      }
    }
    return $documents;
  }

  /**
   * Posts documents to the Solr /update/json/docs endpoint.
   */
  private function indexDocuments(array $documents, string $solr_url, array $auth): void {
    $count = count($documents);
    $this->logger()->notice(dt('Seeding @count mock documents...', ['@count' => $count]));
    try {
      $response = $this->httpClient->post($solr_url . '/update/json/docs?commit=true', [
        'auth' => $auth,
        'json' => $documents,
      ]);
      $this->logger()->success(dt('HTTP @code: Seeded @count documents. Visit /multisite-press-releases to verify.', [
        '@code' => $response->getStatusCode(),
        '@count' => $count,
      ]));
    }
    catch (GuzzleException $e) {
      $this->logger()->error(dt('Solr request failed: @msg', ['@msg' => $e->getMessage()]));
    }
  }

  /**
   * Deletes previously seeded documents by ID.
   */
  private function deleteDocuments(array $documents, string $solr_url, array $auth): void {
    $ids = array_column($documents, 'id');
    $count = count($ids);
    $this->logger()->notice(dt('Deleting @count mock documents...', ['@count' => $count]));
    try {
      $response = $this->httpClient->post($solr_url . '/update?commit=true', [
        'auth' => $auth,
        'json' => ['delete' => $ids],
      ]);
      $this->logger()->success(dt('HTTP @code: Deleted @count documents.', [
        '@code' => $response->getStatusCode(),
        '@count' => $count,
      ]));
    }
    catch (GuzzleException $e) {
      $this->logger()->error(dt('Solr request failed: @msg', ['@msg' => $e->getMessage()]));
    }
  }

}
