#!/usr/bin/env bash

# Fail safely if any errors occur.
set -eo pipefail

DRUPAL_CORE="${DRUPAL_CORE:-^10.4}"
PHP_VERSION="${PHP_VERSION:-8.3}"
APP_NAME="ecms-profile"
DOCROOT="develop/web"
PATTERN_LAB_DIRECTORY="ecms_patternlab"
PATTERN_LAB_REPOSITORY_NAME="state-of-rhode-island-ecms/ecms_patternlab"

ln -s ./scripts/generate-composer.php /usr/local/bin/generate-composer

which generate-composer

rm -Rf develop

composer create-project drupal/recommended-project:${DRUPAL_CORE} develop --no-install
rm -f develop/composer.lock

mkdir -p $DOCROOT/profiles/contrib/ecms_profile

ln -s -f $(realpath -s --relative-to=${DOCROOT}/profiles/contrib/ecms_profile ecms_base) $DOCROOT/profiles/contrib/ecms_profile
ln -s -f $(realpath -s --relative-to=${DOCROOT}/profiles/contrib/ecms_profile ecms_acquia) $DOCROOT/profiles/contrib/ecms_profile

test -f develop/composer.lock || (generate-composer > develop/merge.composer.json)
test -f develop/merge.composer.json && (mv develop/merge.composer.json develop/composer.json)

composer install --working-dir=develop
drush site:install ecms_acquia --db-url=mysql://db:db@db:3306/db?module=mysql#tableprefix --yes
