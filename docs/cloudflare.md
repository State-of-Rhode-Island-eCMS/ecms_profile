---
title: Cloudflare Purger
tags:
  - config
  - cloudflare
  - purge
  - cache
---
# Cloudflare Purger

This profile integrates with Cloudflare's Cache API via the
[Cloudflare Purger](https://www.drupal.org/project/cloudflare_purger) module to
ensure Cloudflare's edge caches are invalidated whenever Drupal invalidates cache
tags (e.g. on content save or publish).

Cloudflare sits in front of Acquia as an additional CDN layer. The existing
Acquia Purge module clears Acquia's internal Varnish cache, but without this
integration Cloudflare would continue serving stale content until its TTL expires.

## Environment Variables

Three environment variables control the Cloudflare purger per environment:

| Variable | Required | Description |
|---|---|---|
| `CLOUDFLARE_API_TOKEN` | Yes | Cloudflare API token with `Cache Purge` permission scoped to the zone |
| `CLOUDFLARE_ZONE_ID` | Yes | Cloudflare Zone ID for this site's domain |
| `CLOUDFLARE_CACHE_TAG_PREFIX` | No | Unique prefix to namespace cache tags per site (falls back to `hash_salt`) |

## API Token (`secrets.settings.php`)

The API token is stored securely via the Drupal Key module and read from the
`CLOUDFLARE_API_TOKEN` environment variable. This value must be set in the
`secrets.settings.php` file on each ACSF environment. This file should never
be maintained in version control.

The `secrets.settings.php` file will be manually created in each ACSF environment
allowing for different tokens depending on the environment. [More information
regarding the secrets.settings.php file in ACSF can be found here](https://docs.acquia.com/resource/secrets/#secrets-settings-php-file).

Add the following to `secrets.settings.php`:
```php
putenv('CLOUDFLARE_API_TOKEN=[YOUR_API_TOKEN]');
```

The token must have the `Zone > Cache Purge > Purge` permission scoped to the
relevant zone(s). Use a restricted token — not a global API key.

## Zone ID and Cache Tag Prefix (`cloudflare.settings.php`)

The Zone ID and optional cache tag prefix are set via environment variables read
in `sites/default/cloudflare.settings.php` (deployed from `scripts/cloudflare.settings.php`).

Add to the environment (e.g. ACSF environment variables or a local `.env` file):
```env
CLOUDFLARE_ZONE_ID=[YOUR_ZONE_ID]
CLOUDFLARE_CACHE_TAG_PREFIX=[UNIQUE_SITE_PREFIX]
```

The cache tag prefix ensures that invalidating tags on one site (e.g. `node_list`)
does not purge the same tag on other sites sharing the same Cloudflare zone. If
not set, the site's `hash_salt` is used as a fallback.

## Local Development

For local DDEV development, add the environment variables to your DDEV
environment file (e.g. `.ddev/.env`):

```env
CLOUDFLARE_API_TOKEN=[YOUR_API_TOKEN]
CLOUDFLARE_ZONE_ID=[YOUR_ZONE_ID]
CLOUDFLARE_CACHE_TAG_PREFIX=[UNIQUE_SITE_PREFIX]
```

This will require a `ddev restart`. Without these values set locally, the purger
will be installed but will not make API calls (the zone_id will be empty).

## Token Scope

Generate the API token in the Cloudflare dashboard under
**My Profile > API Tokens > Create Token**:

- **Permissions:** `Zone > Cache Purge > Purge`
- **Zone Resources:** Include the specific zone(s) for this site
- **TTL:** Set an expiry aligned with your rotation policy

Tokens can be rotated without a code deploy — update `secrets.settings.php` on
each ACSF environment and the Key module will pick up the new value immediately.
