#!/usr/bin/env php
<?php

// Generates the development-ready `composer.json` file by merging our
// development requirements into the project template. This script must
// be run from the repository root.

$read_json = function (string $file): array {
  $data = file_get_contents($file);
  return json_decode($data, TRUE, flags: JSON_THROW_ON_ERROR);
};

// From \Drupal\Component\Utility\NestedArray::mergeDeep().
$merge_deep = function (array ...$arrays) use (&$merge_deep): array {
  $result = [];
  foreach ($arrays as $array) {
    foreach ($array as $key => $value) {
      // Recurse when both values are arrays.
      if (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
        $result[$key] = $merge_deep($result[$key], $value);
      }
      // Otherwise, use the latter value, overriding any previous value.
      else {
        $result[$key] = $value;
      }
    }
  }
  return $result;
};

$base = $read_json(realpath(sprintf('%s/composer.json', 'develop')));

$profile = $read_json(realpath('composer.json'));
$data = $merge_deep($base, $profile);

// The generated file lives in `develop/`, and every composer command in the
// build passes `--working-dir=develop`, which makes that directory the process
// working directory. cweagans/composer-patches locates a local patch with a
// bare file_exists() call, so a repository-relative path such as
// `patches/foo.patch` is looked for inside `develop/` and is not found; the
// plugin then treats it as a URL and fails with a null $originUrl. Rewrite
// those paths so they resolve from `develop/`. Patches given as URLs, and any
// absolute path, are left alone.
//
// The profile's own composer.json keeps the repository-relative paths, because
// a consumer such as ecms_distribution resolves them from its own root.
if (!empty($data['extra']['patches'])) {
  foreach ($data['extra']['patches'] as $package => $patches) {
    foreach ($patches as $description => $patch) {
      if (!preg_match('#^(?:[a-z][a-z0-9+.-]*://|/)#i', $patch)) {
        $data['extra']['patches'][$package][$description] = sprintf('../%s', $patch);
      }
    }
  }
}

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
