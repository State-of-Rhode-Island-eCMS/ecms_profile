<?php

/**
 * @file
 * Provides update hooks for previously installed sites.
 */

declare(strict_types=1);

use Drupal\search_api\Entity\Server;
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
 * @see ecms_base_update_11223()
 */
function ecms_base_searchstax_migrate_indexes(): void {
  $searchstax = Server::load('searchstax');

  if (!$searchstax instanceof ServerInterface) {
    \Drupal::logger('ecms_base')
      ->warning('Skipping the search index migration: the searchstax server could not be loaded.');
    return;
  }

  // The recipe only repoints acquia_search_index, so move any other index that
  // is still attached to the Acquia server. Indexes on other servers, such as
  // the multisite Solr core or the database backend, are left alone. The
  // indexes are loaded dynamically so that site specific indexes are included.
  $acquiaServer = Server::load('acquia_search_server');

  if ($acquiaServer instanceof ServerInterface) {
    foreach ($acquiaServer->getIndexes() as $index) {
      try {
        $index->setServer($searchstax)->save();
      }
      catch (\Exception $e) {
        \Drupal::logger('ecms_base')
          ->error('Failed to move @index to the searchstax server: @message', [
            '@index' => $index->id(),
            '@message' => $e->getMessage(),
          ]);
      }
    }
  }

  // Mark every index on the searchstax server as needing a full re-index.
  foreach ($searchstax->getIndexes() as $index) {
    try {
      $index->reindex();
    }
    catch (\Exception $e) {
      \Drupal::logger('ecms_base')
        ->error('Failed to reindex @index: @message', [
          '@index' => $index->id(),
          '@message' => $e->getMessage(),
        ]);
    }
  }
}
