#!/usr/bin/env bash

# Fail safely if any errors occur.
set -eo pipefail

DRUPAL_CORE="${DRUPAL_CORE:-^10.4}"
PHP_VERSION="${PHP_VERSION:-8.3}"
APP_NAME="ecms-profile"
DOCROOT="develop/web"
PATTERN_LAB_DIRECTORY="ecms_patternlab"
PATTERN_LAB_REPOSITORY_NAME="state-of-rhode-island-ecms/ecms_patternlab"

rm -Rf develop

composer create-project drupal/recommended-project:${DRUPAL_CORE} develop --no-install
rm -f develop/composer.lock

mkdir -p $DOCROOT/profiles/contrib/ecms_profile

ln -s -f $(realpath -s --relative-to=${DOCROOT}/profiles/contrib/ecms_profile ecms_base) $DOCROOT/profiles/contrib/ecms_profile
ln -s -f $(realpath -s --relative-to=${DOCROOT}/profiles/contrib/ecms_profile ecms_acquia) $DOCROOT/profiles/contrib/ecms_profile

test -f develop/composer.lock || (./scripts/generate-composer.php > develop/merge.composer.json)
test -f develop/merge.composer.json && (mv develop/merge.composer.json develop/composer.json)

composer install --working-dir=develop
