<?php

/**
 * @file
 * Provides update hooks for previously installed sites.
 */

declare(strict_types=1);

use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\ServerInterface;

/**
 * Restore the drupal_find_theme_functions method.
 *
 * @see https://api.drupal.org/api/drupal/core%21includes%21theme.inc/function/drupal_find_theme_functions/9
 */
function ecms_base_find_theme_functions($cache, $prefixes) {
  $implementations = [];
  $grouped_functions = \Drupal::service('theme.registry')
    ->getPrefixGroupedUserFunctions($prefixes);
  foreach ($cache as $hook => $info) {
    foreach ($prefixes as $prefix) {

      // Find theme functions that implement possible "suggestion" variants of
      // registered theme hooks and add those as new registered theme hooks.
      // The 'pattern' key defines a common prefix that all suggestions must
      // start with. The default is the name of the hook followed by '__'. A
      // 'base hook' key is added to each entry made for a found suggestion,
      // so that common functionality can be implemented for all suggestions of
      // the same base hook. To keep things simple, deep hierarchy of
      // suggestions is not supported: each suggestion's 'base hook' key
      // refers to a base hook, not to another suggestion, and all suggestions
      // are found using the base hook's pattern, not a pattern from an
      // intermediary suggestion.
      $pattern = $info['pattern'] ?? $hook . '__';

      // Grep only the functions which are within the prefix group.
      [
        $first_prefix,
      ] = explode('_', $prefix, 2);
      if (!isset($info['base hook']) && !empty($pattern) && isset($grouped_functions[$first_prefix])) {
        $matches = preg_grep('/^' . $prefix . '_' . $pattern . '/', $grouped_functions[$first_prefix]);
        if ($matches) {
          foreach ($matches as $match) {
            $new_hook = substr($match, strlen($prefix) + 1);
            $arg_name = isset($info['variables']) ? 'variables' : 'render element';
            $implementations[$new_hook] = [
              'function' => $match,
              $arg_name => $info[$arg_name],
              'base hook' => $hook,
            ];
          }
        }
      }

      // Find theme functions that implement registered theme hooks and include
      // that in what is returned so that the registry knows that the theme has
      // this implementation.
      if (function_exists($prefix . '_' . $hook)) {
        $implementations[$hook] = [
          'function' => $prefix . '_' . $hook,
        ];
      }
    }
  }
  return $implementations;
}

/**
 * Batch callback to point every Solr index at the SearchStax server.
 *
 * This lives in the profile file rather than ecms_base.install so that the
 * callback is available when the batch runs, which may be in a separate
 * request or process from the update hook that queued it.
 *
 * The callback is idempotent so that it can be queued or called again by a
 * later update hook: the ACSF deployment runs `drush updatedb` and then
 * `features:import`, and until the ecms_solr_search feature was repointed at
 * SearchStax that import walked acquia_search_index straight back onto the
 * Acquia server.
 *
 * @return string[]|null
 *   The IDs of the indexes the migration could not finish: those still attached
 *   to the Acquia server, and those that could not be queued for a re-index. An
 *   empty array means the migration is complete. NULL if the migration could
 *   not run at all because the SearchStax server is missing. Batch runs ignore
 *   the return value; ecms_base_update_11224() uses it to decide whether to
 *   throw, so that a failed migration is not recorded as a completed update.
 *
 * @see ecms_base_update_11223()
 * @see ecms_base_update_11224()
 */
function ecms_base_searchstax_migrate_indexes(): ?array {
  $logger = \Drupal::logger('ecms_base');
  $searchstax = Server::load('searchstax');

  if (!$searchstax instanceof ServerInterface) {
    $logger->warning('Skipping the search index migration: the searchstax server could not be loaded.');
    return NULL;
  }

  // The recipe only repoints acquia_search_index, so move any other index that
  // is still attached to the Acquia server. Indexes on other servers, such as
  // the multisite Solr core or the database backend, are left alone. The
  // indexes are loaded dynamically so that site specific indexes are included.
  $acquiaServer = Server::load('acquia_search_server');
  $moved = [];
  $failed = [];

  if ($acquiaServer instanceof ServerInterface) {
    foreach ($acquiaServer->getIndexes() as $index) {
      try {
        // Re-enable as well as repoint. Once the Acquia core is gone,
        // acquia_search_search_api_server_load() reports its server as
        // disabled, and Index::preSave() disables any enabled index whose
        // server is disabled. So every save of an index that was still on the
        // Acquia server silently switched it off, including the save
        // features:import made when it reverted the index. Those indexes have
        // to come back on or the site has no search at all; an index an editor
        // disabled on purpose would not be sitting on a dead server.
        // @see acquia_search_search_api_server_load()
        // @see \Drupal\search_api\Entity\Index::preSave()
        $index->setServer($searchstax)->enable()->save();
        $moved[$index->id()] = $index;
      }
      catch (\Exception $e) {
        $failed[] = $index->id();
        $logger->error('Failed to move @index to the searchstax server: @message', [
          '@index' => $index->id(),
          '@message' => $e->getMessage(),
        ]);
      }
    }
  }

  // On the first run the content still lives in the Acquia core, so everything
  // on SearchStax needs a full re-index, including the index the recipe moved
  // before this callback ran. On later runs only the indexes that were actually
  // moved need it, otherwise re-running the migration would queue a needless
  // full re-index of every site.
  $state = \Drupal::state();

  $toReindex = $state->get('ecms_base.searchstax_migrated')
    ? $moved
    : $searchstax->getIndexes();

  $reindexFailed = [];

  foreach ($toReindex as $index) {
    try {
      $index->reindex();
    }
    catch (\Exception $e) {
      $reindexFailed[] = $index->id();
      $logger->error('Failed to reindex @index: @message', [
        '@index' => $index->id(),
        '@message' => $e->getMessage(),
      ]);
    }
  }

  // Only record the first run once every index was queued for a re-index.
  // Setting this regardless would downgrade the next run to "re-index what
  // moved", and an index that failed here moved on an earlier run, so nothing
  // would ever queue its content again.
  if (empty($reindexFailed)) {
    $state->set('ecms_base.searchstax_migrated', TRUE);
  }

  // Nothing should be left on the Acquia server once the move succeeds, so
  // disable it. Leaving it enabled lets an editor re-attach an index to a
  // server that is no longer paid for, and the ecms_solr_search feature now
  // ships status: false, so this keeps the stored config and the feature in
  // agreement without waiting for the next features:import.
  $remaining = [];

  if ($acquiaServer instanceof ServerInterface) {
    $remaining = array_keys($acquiaServer->getIndexes());

    if (!empty($remaining)) {
      $logger->error('The Acquia search server was left enabled: @indexes could not be moved to searchstax.', [
        '@indexes' => implode(', ', $remaining),
      ]);
    }
    else {
      // The stored status has to be read and written through the config
      // factory rather than the loaded entity.
      // acquia_search_search_api_server_load() calls $server->disable() in
      // memory whenever the preferred Acquia core is unavailable, so the
      // entity reports itself as disabled while the stored config still says
      // enabled, and saving that entity would also persist the backend and
      // backend_config the same load hook rewrote. Flipping status needs no
      // dependency recalculation, and no index is attached any more, so
      // nothing is lost by skipping Server::postSave().
      // @see acquia_search_search_api_server_load()
      $acquiaConfig = \Drupal::configFactory()
        ->getEditable('search_api.server.acquia_search_server');

      if (!$acquiaConfig->isNew() && $acquiaConfig->get('status')) {
        $acquiaConfig->set('status', FALSE)->save();
        $logger->info('Disabled the acquia_search_server: every index now lives on searchstax.');
      }
    }
  }

  $logger->info('SearchStax index migration complete. Moved: @moved. Failed to move: @failed. Failed to reindex: @reindex.', [
    '@moved' => $moved ? implode(', ', array_keys($moved)) : 'none',
    '@failed' => $failed ? implode(', ', $failed) : 'none',
    '@reindex' => $reindexFailed ? implode(', ', $reindexFailed) : 'none',
  ]);

  return array_values(array_unique(array_merge($remaining, $reindexFailed)));
}

/**
 * Adds the URI field that the multisite index reads to acquia_search_index.
 *
 * The search_api_solr backend writes only id, index_id, hash, site and
 * timestamp into every document by itself, so the ss_url field that
 * ecms_multisite_index maps its url field to exists only if
 * acquia_search_index carries a field whose machine name is literally 'url'.
 * The add_url processor indexes nothing on its own: it is hidden and locked,
 * and only exposes the search_api_url property, which
 * AddURL::addFieldValues() writes a value for solely when a field points
 * at it.
 *
 * The ecms_multisite_search recipe adds the field, but that recipe is applied
 * once, by ecms_base_update_11222(), so sites that ran that update before the
 * action was added never received it. The ACSF deployment then runs
 * `drush updatedb` followed by `features:import`, and until this release the
 * ecms_solr_search feature shipped acquia_search_index without the field, so
 * that import stripped it straight back off the sites that did get it. This is
 * the same trap ecms_base_update_11224() documents for the server key.
 *
 * Like ecms_base_searchstax_migrate_indexes(), this lives in the profile file
 * and is idempotent so that a later update hook can call it again.
 *
 * @return bool|null
 *   TRUE if the field is on the index, FALSE if it could not be added or the
 *   re-index could not be queued. NULL if acquia_search_index does not exist,
 *   which is the case on sites that never had Solr search.
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\AddURL::addFieldValues()
 * @see ecms_base_update_11225()
 */
function ecms_base_searchstax_add_url_field(): ?bool {
  $logger = \Drupal::logger('ecms_base');
  $index = Index::load('acquia_search_index');

  if (!$index instanceof IndexInterface) {
    $logger->info('Skipping the search URI field: acquia_search_index could not be loaded.');
    return NULL;
  }

  // The recipe, an earlier run or the features:import that follows
  // `drush updatedb` may have got here first.
  if (!$index->getField('url')) {
    try {
      $field = \Drupal::service('search_api.fields_helper')
        ->createField($index, 'url', [
          'label' => 'URI',
          'property_path' => 'search_api_url',
          'type' => 'string',
          // The aggregator links results to the site they came from, so the
          // stored URL has to carry the originating site's host.
          'configuration' => ['absolute' => TRUE],
        ]);
      $index->addField($field)->save();
    }
    catch (\Exception $e) {
      $logger->error('Failed to add the URI field to acquia_search_index: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }

    $logger->info('Added the URI field to acquia_search_index.');
  }

  // Every document already in the core was written without the field, so the
  // multisite index cannot link to any of them until the content is sent
  // again. This is keyed off state rather than off whether this run added the
  // field: whichever of the recipe, features:import and this function lands it
  // first, the stale documents are the same, and a run that found the field
  // already there would otherwise leave them stale forever. The flag is what
  // keeps a later call from queueing a full re-index on every deployment.
  $state = \Drupal::state();

  if ($state->get('ecms_base.search_url_field_reindexed')) {
    return TRUE;
  }

  try {
    $index->reindex();
  }
  catch (\Exception $e) {
    $logger->error('The acquia_search_index URI field is in place but the re-index could not be queued: @message', [
      '@message' => $e->getMessage(),
    ]);
    return FALSE;
  }

  $state->set('ecms_base.search_url_field_reindexed', TRUE);
  $logger->info('Queued a re-index of acquia_search_index so that ss_url is written to Solr.');

  return TRUE;
}
