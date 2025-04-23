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

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
