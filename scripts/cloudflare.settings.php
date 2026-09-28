<?php

// Cloudflare Purger
// Provides per-environment overrides for the Cloudflare cache purger.
// See docs/cloudflare.md for full setup instructions.
//
// CLOUDFLARE_API_TOKEN must be set separately in secrets.settings.php:
//   putenv('CLOUDFLARE_API_TOKEN=[YOUR_API_TOKEN]');
//
// Set CLOUDFLARE_ZONE_ID and optionally CLOUDFLARE_CACHE_TAG_PREFIX in the
// ACSF environment variables or your local .ddev/.env file.
if (getenv('CLOUDFLARE_ZONE_ID')) {
  $config['cloudflare_purger.settings']['zone_id'] = getenv('CLOUDFLARE_ZONE_ID');
}

// Set a unique cache tag prefix per site so that invalidating tags on one
// site does not affect other sites sharing the same Cloudflare zone.
// Falls back to the site's hash_salt if not set.
if (getenv('CLOUDFLARE_CACHE_TAG_PREFIX')) {
  $settings['cloudflare_purger_cache_tag_prefix'] = getenv('CLOUDFLARE_CACHE_TAG_PREFIX');
}
