#!/usr/bin/env bash

# Fail safely if any errors occur.
set -eo pipefail

DRUPAL_CORE="${DRUPAL_CORE:-^11.2}"
PHP_VERSION="${PHP_VERSION:-8.3}"
APP_NAME="ecms-profile"
DOCROOT="develop/web"

# Make sure the system has ddev
if ! [ -x "$(command -v ddev)" ]; then
  echo 'Error: DDEV is not installed!' >&2
  exit 1
fi

# Whether to enable performance mode after installation.
## This flag should be passed _if_ using Mac OS.
PERFORMANCE_MODE=0

while [ "$1" != "" ]; do
    case $1 in
        -p | --performance )         shift
                                PERFORMANCE_MODE=1
                                ;;
    esac
done

DDEV_CONFIG="ddev config \
  --project-name="$APP_NAME" \
  --project-type=drupal11 \
  --php-version="$PHP_VERSION" \
  --docroot="$DOCROOT" \
  --database="mysql:5.7" \
  --webserver-type="apache-fpm" \
  --nodejs-version=10 \
  --composer-root="develop" \
  --performance-mode=none"

# Configure DDEV for the profile.
$DDEV_CONFIG

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

# Symlink the drush directory to the ddev environment.
ddev exec 'ln -s -f $(realpath -s --relative-to=${DDEV_APPROOT}/develop drush) $DDEV_APPROOT/develop/drush'

## Merge the profile's composer into Drupal's default.
ddev exec "test -f develop/composer.lock || (generate-composer > develop/merge.composer.json)"

## If the merge was successful, replace the original composer.json and install.
ddev exec 'test -f develop/merge.composer.json && (mv develop/merge.composer.json develop/composer.json)'

## Remove packages that require Drupal 11 patches to avoid conflicts
## @todo: Remove this workaround once the below modules are D11 compatible.
ddev exec 'composer remove --working-dir=develop --no-update drupal/address_phonenumber drupal/features drupal/iek drupal/migrate_process_trim drupal/webform_encrypt || true'

## Allow lenient package installation without user interaction
## @todo: Remove this workaround once the above modules are D11 compatible.
ddev exec 'composer config --working-dir=develop allow-plugins.mglaman/composer-drupal-lenient true'

## Add composer-drupal-lenient package to handle version constraints
ddev exec 'composer require --working-dir=develop --no-update mglaman/composer-drupal-lenient'

## Configure lenient plugin allow list for D11 packages
ddev exec 'composer config --working-dir=develop --merge --json extra.drupal-lenient.allowed-list '\''["drupal/address_phonenumber", "drupal/features", "drupal/iek", "drupal/migrate_process_trim", "drupal/webform_encrypt"]'\'''

# Composer install the things.
ddev exec 'composer install --working-dir=develop'

## Re-add the D11 packages that were removed with no-update flag
ddev exec 'composer require --working-dir=develop --no-update "drupal/address_phonenumber:^10.0" "drupal/features:3.x-dev" "drupal/iek:^1.3" "drupal/migrate_process_trim:^2.0" "drupal/webform_encrypt:^2.0@alpha"'

## Run composer update with dependencies to resolve everything
ddev exec 'composer update --with-all-dependencies --working-dir=develop'

# Reconfigure DDEV to ensure settings.php is created.
$DDEV_CONFIG

## Add MySQL 57 settings requirement to the default prior to site install.
ddev exec 'echo "require DRUPAL_ROOT . \"/modules/contrib/mysql57/settings.inc\";" >> $DDEV_DOCROOT/sites/default/settings.ddev.php'

## Install the site profile.
ddev exec 'drush site:install ecms_base --yes'

## Install fast404.settings.php
ddev exec 'chmod +w develop/web/sites/default develop/web/sites/default/settings.php && cp scripts/fast404.settings.php develop/web/sites/default/'
ddev exec 'echo "\$fast404Settings = sprintf(\"%s/%s/fast404.settings.php\", \$app_root, \$site_path);
if (file_exists(\$fast404Settings)) {
  require(\$fast404Settings);
}" >> develop/web/sites/default/settings.php'


if [ "$PERFORMANCE_MODE" == "1" ]; then
  ddev stop
  ddev config --performance-mode=mutagen;
  ddev start
fi

