#!/usr/bin/env bash

# Fail safely if any errors occur.
set -eo pipefail

DRUPAL_CORE="${DRUPAL_CORE:-^10.4}"
PHP_VERSION="${PHP_VERSION:-8.3}"
APP_NAME="ecms-profile"
DOCROOT="develop/web"
PATTERN_LAB_DIRECTORY="ecms_patternlab"
PATTERN_LAB_REPOSITORY_NAME="state-of-rhode-island-ecms/ecms_patternlab"

# Make sure the system has ddev
if ! [ -x "$(command -v ddev)" ]; then
  echo 'Error: DDEV is not installed!' >&2
  exit 1
fi

ddev config \
  --project-name="$APP_NAME" \
  --project-type=drupal \
  --php-version="$PHP_VERSION" \
  --docroot="$DOCROOT" \
  --database="mysql:5.7" \
  --webserver-type="apache-fpm" \
  --nodejs-version=10 \
  --composer-root="develop"

# Get ddev started.
ddev start

# Remove the develop directory so it can be recreated.
ddev exec 'rm -Rf develop'

# Create the Drupal project.
ddev exec "composer create-project drupal/recommended-project:${DRUPAL_CORE} develop --no-install"
# Delete the lock file to add the profile's dependencies.
ddev exec 'rm -f develop/composer.lock'

# Copy the generate composer command to the ~/bin directory.
ddev exec 'ln -s -f /var/www/html/scripts/generate-composer.php ~/bin/generate-composer'

# Symlink the profile
# Create a `contrib` profile directory
ddev exec 'mkdir -p $DDEV_DOCROOT/profiles/contrib/ecms_profile'

# Symlink the base profiles into the `ecms_profile` profile directory.
ddev exec 'ln -s -f $(realpath -s --relative-to=${DDEV_DOCROOT}/profiles/contrib/ecms_profile ecms_base) $DDEV_DOCROOT/profiles/contrib/ecms_profile'
ddev exec 'ln -s -f $(realpath -s --relative-to=${DDEV_DOCROOT}/profiles/contrib/ecms_profile ecms_acquia) $DDEV_DOCROOT/profiles/contrib/ecms_profile'

## Merge the profile's composer into Drupal's default.
ddev exec "test -f develop/composer.lock || (generate-composer > develop/merge.composer.json)"

## If the merge was successful, replace the original composer.json and install.
ddev exec 'test -f develop/merge.composer.json && (mv develop/merge.composer.json develop/composer.json)'

# Composer install the things.
ddev exec 'composer install --working-dir=develop'

## Install the site profile.
ddev exec 'drush site:install ecms_acquia --db-url=mysql://db:db@db:3306/db?module=mysql#tableprefix --yes'
