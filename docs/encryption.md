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
with an associated secret encryption key stored in an environment variable
`ENCRYPTION_PRIVATE_KEY`. This value must exist in the `secrets.settings.php` file.
This file should never be maintained in version control.

The `secrets.settings.php` file will be manually created in each ACSF environment
allowing for different connections depending on the environment. [More information
regarding the secrets.settings.php file in ACSF can be found here](https://docs.acquia.com/resource/secrets/#secrets-settings-php-file).

The value for the environment variable should be established as follows:
```php
putenv('ENCRYPTION_PRIVATE_KEY=[SECRET_KEY_VALUE]');
```

## Local Development
1) Generate a key on your local machine as follows:
    ```shell script
    dd if=/dev/urandom bs=32 count=1 | base64 -i - > path/to/encrypt.key
    ```
   Use the putenv() call as shown above in a settings.local.php file,
   or use a Lando environment file as described in steps 2 & 3 below.

2) Copy the contents from the generated file into your local ENV file.
   Configure a [local environment file] for Lando (e.g.):
    ```yml
    env_file:
      - .env
    ```
   This will require a `lando rebuild`
3) with the following contents (at a minimum):
    ```env
    ENCRYPTION_PRIVATE_KEY=[SECRET_KEY_FROM_STEP_1]

    ```
[local environment file]: https://docs.lando.dev/config/env.html#environment-files
