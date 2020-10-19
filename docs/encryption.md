---
title: Encryption
tags:
  - config
  - drupal
  - encryption
  - webform encrypt
---
# Encryption

This profile makes use of the Drupal Encrypt module to provide encrypted data
for modules, most notably the Webform Encrypt module.

## Key
Settings for each encryption profile require the use of the Drupal 'Key' module,
with an associated secret encryption key stored in `secrets.settings.php`.
This file should never be maintained in version control.

The `secrets.settings.php` file will be manually created in each ACSF environment
allowing for different connections depending on the environment. [More information
regarding the secrets.settings.php file in ACSF can be found here](https://docs.acquia.com/resource/secrets/#secrets-settings-php-file).

The settings for the Key override should match this:
```php
$config['key.key.encryption_key']['key_provider_settings']['key_value'] = 'SECRET_KEY_VALUE';
```
