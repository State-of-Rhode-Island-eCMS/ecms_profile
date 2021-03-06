#!/usr/bin/env bash

# Fail safely if any errors occur.
set -eo pipefail

# Move up a level starting from the scripts directory.
BASE_DIR="$(dirname $(cd ${0%/*} && pwd))"
APP_NAME="develop-ecms-profile"
INSTALL_PROFILE_NAME="ecms_base"
INSTALL_PROFILE_DIRECTORY="ecms_profile"
PATTERN_LAB_DIRECTORY="ecms_patternlab"
REPOSITORY_NAME="rhodeislandecms/ecms_profile"
PATTERN_LAB_FULL_PATH="${BASE_DIR%/*}/${PATTERN_LAB_DIRECTORY}"
PATTERN_LAB_REPOSITORY_NAME="state-of-rhode-island-ecms/ecms_patternlab"

COMPOSER="$(which composer)"
COMPOSER_BIN_DIR="$(composer config bin-dir)"
DOCROOT="web"


# Move up a directory.
cd ..

# Create a new Drupal project.
composer create-project drupal/recommended-project:9.1.9 $APP_NAME --no-install --no-interaction

# Move into the directory.
cd $APP_NAME;

# Change the minimum stability flag.
$COMPOSER config minimum-stability "dev"

$COMPOSER require "zaporylie/composer-drupal-optimizations:^1.1.2" --no-update

# Add the development requirements for testing.
$COMPOSER require "behat/mink-goutte-driver" --dev --no-update
$COMPOSER require "php-mock/php-mock" --dev --no-update
$COMPOSER require "php-mock/php-mock-phpunit" --dev --no-update
$COMPOSER require "weitzman/drupal-test-traits" --dev --no-update
$COMPOSER require 'liuggio/fastest:^1.6' --dev --no-update
$COMPOSER require "phpunit/phpunit:^8" --dev --no-update
$COMPOSER require "symfony/phpunit-bridge:^5.1" --dev --no-update
$COMPOSER require "drupal/coder:^8.3" --dev --no-update
$COMPOSER require "drush/drush:^10.0" --dev --no-update

# Add the migrate_google_sheets repository.
$COMPOSER config repositories.migrate_google_sheets '{"type": "package", "package": {"name": "drupal_git/migrate_google_sheets", "type": "drupal-module", "version": "1.0.0", "source": {"type": "git", "url": "https://git.drupalcode.org/project/migrate_google_sheets.git", "reference": "22944d55be891cfe48d6a6d7c222ff9e89f67b8d"}}}'

# Add the migration_tools repository.
$COMPOSER config repositories.migratation_tools '{"type": "package", "package": {"name": "drupal_git/migration_tools", "type": "drupal-module", "version": "1.0.0", "source": {"type": "git", "url": "https://git.drupalcode.org/project/migration_tools.git", "reference": "3e193bc97d127ea2cff6b80f9509bc161bdee19f"}}}'

# Add the migrate_process_trim repository.
$COMPOSER config repositories.migratation_process_trim '{"type": "package", "package": {"name": "drupal_git/migrate_process_trim", "type": "drupal-module", "version": "1.0.0", "source": {"type": "git", "url": "https://git.drupalcode.org/project/migrate_process_trim.git", "reference": "79c7ceb9113c1e21818bd124135e5d261ccbebbc"}}}'

$COMPOSER config repositories.${INSTALL_PROFILE_DIRECTORY} '{"type": "path", "url": "../'${INSTALL_PROFILE_DIRECTORY}'"}'

# Add the pattern lab installer type.
$COMPOSER config extra.installer-types.2 "pattern-lab"

$COMPOSER config extra.installer-paths.$BASE_DIR/ecms_base/themes/custom/ecms/{\$name} PATTERN_LAB_REPLACE

# Replace the "PATTERN_LAB_REPLACE" text with the actual value.
# sed is different for Macs, detect that here.
if [[ "$OSTYPE" == "darwin"* ]]; then
  sed -i '' 's/"PATTERN_LAB_REPLACE"/\["type:pattern-lab"]/g' composer.json
else
  sed -i 's/"PATTERN_LAB_REPLACE"/\["type:pattern-lab"]/g' composer.json
fi

$COMPOSER config extra.enable-patching true
$COMPOSER require "${REPOSITORY_NAME}:*" --no-progress

# Require pattern lab master branch from Github.
$COMPOSER config repositories.${PATTERN_LAB_DIRECTORY} '{"type": "git", "url": "https://github.com/State-of-Rhode-Island-eCMS/ecms_patternlab.git"}'
$COMPOSER require "${PATTERN_LAB_REPOSITORY_NAME}:dev-master" --no-progress

$COMPOSER install
