#!/usr/bin/env bash

# Fail safely if any errors occur.
set -eo pipefail

DRUPAL_CORE="${DRUPAL_CORE:-^11.2}"
PHP_VERSION="${PHP_VERSION:-8.3}"
APP_NAME="ecms-profile"
DOCROOT="develop/web"

rm -Rf develop

composer create-project drupal/recommended-project:${DRUPAL_CORE} develop --no-install
rm -f develop/composer.lock

mkdir -p $DOCROOT/profiles/contrib/ecms_profile

ln -s -f $(realpath -s --relative-to=${DOCROOT}/profiles/contrib/ecms_profile ecms_base) $DOCROOT/profiles/contrib/ecms_profile
ln -s -f $(realpath -s --relative-to=${DOCROOT}/profiles/contrib/ecms_profile ecms_acquia) $DOCROOT/profiles/contrib/ecms_profile

test -f develop/composer.lock || (./scripts/generate-composer.php > develop/merge.composer.json)
test -f develop/merge.composer.json && (mv develop/merge.composer.json develop/composer.json)

## Explicitly constrain drupal/core-recommended to the DRUPAL_CORE version
composer require --working-dir=develop --no-update "drupal/core-recommended:${DRUPAL_CORE}"

## @todo: Remove this workaround once the below modules are D11 compatible.
composer remove --working-dir=develop --no-update drupal/address_phonenumber drupal/iek drupal/migrate_process_trim drupal/webform_encrypt || true

## Allow lenient package installation without user interaction
## @todo: Remove this workaround once the above modules are D11 compatible.
composer config --working-dir=develop allow-plugins.mglaman/composer-drupal-lenient true

## Add composer-drupal-lenient package to handle version constraints
composer require --working-dir=develop --no-update mglaman/composer-drupal-lenient

## Configure lenient plugin allow list for D11 packages
composer config --working-dir=develop --merge --json extra.drupal-lenient.allowed-list '["drupal/address_phonenumber", "drupal/iek", "drupal/migrate_process_trim", "drupal/webform_encrypt"]'

composer install --working-dir=develop

## Re-add the D11 packages that were removed with no-update flag
composer require --working-dir=develop --no-update "drupal/address_phonenumber:^10.0" "drupal/iek:^1.3" "drupal/migrate_process_trim:2.0.x-dev@dev" "drupal/webform_encrypt:^2.0@alpha"

## Run composer update with dependencies to resolve everything
composer update --with-all-dependencies --working-dir=develop
