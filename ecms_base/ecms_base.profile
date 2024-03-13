<?php

/**
 * @file
 * Provides update hooks for previously installed sites.
 */

declare(strict_types=1);

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
