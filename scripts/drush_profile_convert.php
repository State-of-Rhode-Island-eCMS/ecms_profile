<?php

/**
 * @file
 * Drush command to convert ECMS profile from ecms_acquia to ecms_base.
 *
 * Usage: drush scr drush_profile_convert.php
 *        drush scr drush_profile_convert.php --yes
 *        drush scr drush_profile_convert.php -y
 *
 * This script uses Drupal's database API for safer database operations
 * and includes proper error handling and rollback capabilities.
 *
 * Options:
 *   --yes, -y    Skip all confirmation prompts and proceed automatically
 */

use Drupal\Core\Database\Database;

// Ensure we're running in a Drush context
if (!class_exists('\Drush\Drush')) {
  echo "This script must be run with drush scr command.\n";
  echo "Usage: drush scr drush_profile_convert.php\n";
  exit(1);
}

/**
 * Cross-version compatible print function.
 */
function safe_drush_print($message) {
  if (function_exists('drush_print')) {
    // Legacy Drush
    drush_print($message);
  } else {
    // Modern Drush
    echo $message . "\n";
  }
}

/**
 * Cross-version compatible confirm function.
 */
function safe_drush_confirm($message) {
  if (function_exists('drush_confirm')) {
    // Legacy Drush
    return drush_confirm($message);
  } else {
    // Modern Drush - simple CLI prompt
    echo $message . " (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    return trim(strtolower($line)) === 'y' || trim(strtolower($line)) === 'yes';
  }
}

/**
 * Main conversion function.
 */
function convert_ecms_profile() {
  safe_drush_print("=== ECMS Profile Conversion: ecms_acquia → ecms_base ===");
  safe_drush_print("");

  // Check for --yes flag
  $auto_yes = in_array('--yes', $_SERVER['argv'] ?? []) || in_array('-y', $_SERVER['argv'] ?? []);

  // Confirm before proceeding
  if (!$auto_yes && !safe_drush_confirm('Are you sure you want to convert from ecms_acquia to ecms_base profile?')) {
    safe_drush_print("Conversion cancelled.");
    return;
  }

  if (!$auto_yes && !safe_drush_confirm('Have you created a database backup?')) {
    safe_drush_print("Please create a backup before proceeding. Conversion cancelled.");
    return;
  }

  $database = Database::getConnection();
  $transaction = $database->startTransaction();

  try {
    safe_drush_print("Starting profile conversion...");

    // Phase 1: Update ACSF Variables
    safe_drush_print("Phase 1: Updating ACSF variables...");
    $acsf_updated = update_acsf_variables($database);
    safe_drush_print("  → Updated {$acsf_updated} ACSF variable entries");

    // Phase 2: Update Core Configuration
    safe_drush_print("Phase 2: Updating core configuration...");
    $config_updated = update_core_configuration($database);
    safe_drush_print("  → Updated {$config_updated} configuration entries");

    // Phase 3: Update System State
    safe_drush_print("Phase 3: Updating system state...");
    $state_updated = update_system_state($database);
    safe_drush_print("  → Updated {$state_updated} system state entries");

    // Phase 4: Clear only specific profile-related caches
    safe_drush_print("Phase 4: Clearing specific profile caches...");
    $cache_cleared = clear_profile_caches($database);
    safe_drush_print("  → Cleared {$cache_cleared} cache entries");

    // Verification
    safe_drush_print("Phase 5: Verification...");
    $verification = verify_conversion($database);

    if ($verification['success']) {
      safe_drush_print("  ✓ Conversion verification successful");
      safe_drush_print("  ✓ Found {$verification['ecms_base_refs']} ecms_base references");

      if ($verification['ecms_acquia_refs'] > 0) {
        safe_drush_print("  ⚠ Warning: Found {$verification['ecms_acquia_refs']} remaining ecms_acquia references");
      }

      // Commit transaction
      unset($transaction);
      safe_drush_print("");
      safe_drush_print("✓ Profile conversion completed successfully!");

      // Post-conversion steps
      safe_drush_print("");
      safe_drush_print("=== Next Steps ===");
      safe_drush_print("1. Run: drush cache:rebuild");
      safe_drush_print("2. Run: drush updatedb");
      safe_drush_print("3. Run: drush status");
      safe_drush_print("4. Verify: drush config:status");

      // Automatically run cache rebuild
      if ($auto_yes || safe_drush_confirm('Run cache rebuild now?')) {
        safe_drush_print("Running cache rebuild...");
        drupal_flush_all_caches();
        safe_drush_print("✓ Cache rebuild completed");
      }

    } else {
      throw new Exception("Conversion verification failed: " . $verification['error']);
    }

  } catch (Exception $e) {
    // Rollback on error
    $transaction->rollBack();
    safe_drush_print("✗ Error occurred: " . $e->getMessage());
    safe_drush_print("✓ Transaction rolled back - no changes applied");
    safe_drush_print("Please check the error and try again.");
    return FALSE;
  }

  return TRUE;
}

/**
 * Update ACSF variables.
 */
function update_acsf_variables($database) {
  $updated = 0;

  // Check if acsf_variables table exists
  if (!$database->schema()->tableExists('acsf_variables')) {
    safe_drush_print("  → ACSF variables table not found - skipping");
    return 0;
  }

  // Update site_info entries
  $query = $database->select('acsf_variables', 'av')
    ->fields('av', ['name', 'group_name', 'value'])
    ->condition('name', 'site_info')
    ->execute();

  foreach ($query as $row) {
    $value = $row->value;
    if (is_string($value) && strpos($value, 'ecms_acquia') !== FALSE) {
      $new_value = str_replace(
        ['s:11:"ecms_acquia"', 'ecms_acquia'],
        ['s:9:"ecms_base"', 'ecms_base'],
        $value
      );

      $database->update('acsf_variables')
        ->fields(['value' => $new_value])
        ->condition('name', $row->name)
        ->condition('group_name', $row->group_name)
        ->execute();

      $updated++;
    }
  }

  return $updated;
}

/**
 * Update core configuration.
 */
function update_core_configuration($database) {
  $updated = 0;

  // Check if config table exists
  if (!$database->schema()->tableExists('config')) {
    safe_drush_print("  → Config table not found - skipping");
    return 0;
  }

  // Update core.extension
  $query = $database->select('config', 'c')
    ->fields('c', ['collection', 'name', 'data'])
    ->condition('name', 'core.extension')
    ->execute();

  foreach ($query as $row) {
    $data = $row->data;
    if (!is_string($data)) {
      continue;
    }

    // Try to unserialize the current data
    $config = @unserialize($data);
    if ($config === FALSE) {
      safe_drush_print("  ⚠ Warning: Could not unserialize core.extension - attempting string replacement");
      // Fallback to string replacement if unserialize fails
      if (strpos($data, 'ecms_acquia') !== FALSE) {
        $new_data = str_replace('ecms_acquia', 'ecms_base', $data);
        $database->update('config')
          ->fields(['data' => $new_data])
          ->condition('collection', $row->collection)
          ->condition('name', $row->name)
          ->execute();
        $updated++;
      }
      continue;
    }

    $modified = FALSE;

    // Fix profile field - ensure it's set to ecms_base
    if (!isset($config['profile']) || $config['profile'] === NULL || $config['profile'] === 'ecms_acquia') {
      $config['profile'] = 'ecms_base';
      $modified = TRUE;
      safe_drush_print("  → Set profile to ecms_base in core.extension");
    }

    // Fix module list - ensure ecms_base is present with correct weight
    if (isset($config['module'])) {
      // Check if ecms_acquia exists and remove it
      if (isset($config['module']['ecms_acquia'])) {
        unset($config['module']['ecms_acquia']);
        $modified = TRUE;
        safe_drush_print("  → Removed ecms_acquia from module list");
      }

      // Ensure ecms_base exists with weight 1000
      if (!isset($config['module']['ecms_base'])) {
        $config['module']['ecms_base'] = 1000;
        $modified = TRUE;
        safe_drush_print("  → Added ecms_base to module list with weight 1000");
      } elseif ($config['module']['ecms_base'] != 1000) {
        $config['module']['ecms_base'] = 1000;
        $modified = TRUE;
        safe_drush_print("  → Updated ecms_base weight to 1000");
      }
    }

    if ($modified) {
      $new_data = serialize($config);
      $database->update('config')
        ->fields(['data' => $new_data])
        ->condition('collection', $row->collection)
        ->condition('name', $row->name)
        ->execute();

      $updated++;
    }
  }

  return $updated;
}

/**
 * Update system state entries.
 */
function update_system_state($database) {
  $updated = 0;

  // Check if key_value table exists
  if (!$database->schema()->tableExists('key_value')) {
    safe_drush_print("  → Key-value table not found - skipping");
    return 0;
  }

  $collections = ['system.schema', 'state'];

  foreach ($collections as $collection) {
    $query = $database->select('key_value', 'kv')
      ->fields('kv', ['collection', 'name', 'value'])
      ->condition('collection', $collection)
      ->execute();

    foreach ($query as $row) {
      $value = $row->value;
      if (is_string($value) && strpos($value, 'ecms_acquia') !== FALSE) {
        $new_value = str_replace('ecms_acquia', 'ecms_base', $value);

        $database->update('key_value')
          ->fields(['value' => $new_value])
          ->condition('collection', $row->collection)
          ->condition('name', $row->name)
          ->execute();

        $updated++;
      }
    }
  }

  // Ensure install_profile state is set to ecms_base
  $profile_value = $database->select('key_value', 'kv')
    ->fields('kv', ['value'])
    ->condition('collection', 'state')
    ->condition('name', 'install_profile')
    ->execute()
    ->fetchField();

  $expected_value = serialize('ecms_base');

  if ($profile_value === FALSE) {
    // Insert install_profile state
    $database->insert('key_value')
      ->fields([
        'collection' => 'state',
        'name' => 'install_profile',
        'value' => $expected_value,
      ])
      ->execute();
    safe_drush_print("  → Created install_profile state with value ecms_base");
    $updated++;
  } elseif ($profile_value !== $expected_value) {
    // Update install_profile state
    $database->update('key_value')
      ->fields(['value' => $expected_value])
      ->condition('collection', 'state')
      ->condition('name', 'install_profile')
      ->execute();
    safe_drush_print("  → Updated install_profile state to ecms_base");
    $updated++;
  }

  // Delete the old ecms_acquia schema entry to prevent conflicts
  $deleted = $database->delete('key_value')
    ->condition('collection', 'system.schema')
    ->condition('name', 'ecms_acquia')
    ->execute();

  if ($deleted > 0) {
    safe_drush_print("  → Removed old ecms_acquia schema entry");
    $updated += $deleted;
  }

  return $updated;
}

/**
 * Clear only specific profile-related caches to avoid wiping update info.
 */
function clear_profile_caches($database) {
  $cleared = 0;

  // Only clear specific cache IDs related to profile/extension discovery
  // DO NOT clear entire cache tables as that removes pending update information
  $cache_ids_to_clear = [
    'cache_bootstrap' => [
      'module_implements',
      'system_list',
      'system.module.files',
      'hook_info',
      'profile_list',
    ],
    'cache_config' => [
      'core.extension',
    ],
    'cache_discovery' => [
      'extension',
      'system_info',
    ],
  ];

  foreach ($cache_ids_to_clear as $table => $cids) {
    if ($database->schema()->tableExists($table)) {
      foreach ($cids as $cid) {
        $count = $database->delete($table)
          ->condition('cid', $cid, 'LIKE')
          ->execute();
        $cleared += $count;
      }
    }
  }

  return $cleared;
}

/**
 * Verify the conversion was successful.
 */
function verify_conversion($database) {
  $result = [
    'success' => FALSE,
    'ecms_base_refs' => 0,
    'ecms_acquia_refs' => 0,
    'error' => ''
  ];

  try {
    // Check install_profile state
    $profile = $database->select('key_value', 'kv')
      ->fields('kv', ['value'])
      ->condition('collection', 'state')
      ->condition('name', 'install_profile')
      ->execute()
      ->fetchField();

    if ($profile && strpos($profile, 'ecms_base') !== FALSE) {
      $result['ecms_base_refs']++;
    }

    // Check core.extension config
    $config_data = $database->select('config', 'c')
      ->fields('c', ['data'])
      ->condition('name', 'core.extension')
      ->execute()
      ->fetchField();

    if ($config_data) {
      $config = @unserialize($config_data);
      if ($config !== FALSE && isset($config['profile']) && $config['profile'] === 'ecms_base') {
        $result['ecms_base_refs']++;
      }

      if (strpos($config_data, 'ecms_acquia') !== FALSE) {
        $result['ecms_acquia_refs']++;
      }
    }

    // Check for ecms_acquia schema entry
    $acquia_schema = $database->select('key_value', 'kv')
      ->fields('kv', ['value'])
      ->condition('collection', 'system.schema')
      ->condition('name', 'ecms_acquia')
      ->execute()
      ->fetchField();

    if ($acquia_schema !== FALSE) {
      $result['ecms_acquia_refs']++;
    }

    $result['success'] = ($result['ecms_base_refs'] >= 2 && $result['ecms_acquia_refs'] == 0);

  } catch (Exception $e) {
    $result['error'] = $e->getMessage();
  }

  return $result;
}

// Execute the conversion
convert_ecms_profile();