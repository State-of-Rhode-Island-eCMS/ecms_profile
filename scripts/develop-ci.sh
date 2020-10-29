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
composer create-project drupal/recommended-project $APP_NAME --no-install --no-interaction

# Move into the directory.
cd $APP_NAME;

$COMPOSER require "zaporylie/composer-drupal-optimizations:^1.1.2" --no-update

# Add the development requirements for testing.
$COMPOSER require "behat/mink-goutte-driver" --dev --no-update
$COMPOSER require "php-mock/php-mock" --dev --no-update
$COMPOSER require "php-mock/php-mock-phpunit" --dev --no-update
$COMPOSER require "weitzman/drupal-test-traits" --dev --no-update
$COMPOSER require "brianium/paratest:^4" --dev --no-update
$COMPOSER require "phpunit/phpunit:^8" --dev --no-update
$COMPOSER require "symfony/phpunit-bridge:^5.1" --dev --no-update

$COMPOSER config repositories.${INSTALL_PROFILE_DIRECTORY} '{"type": "path", "url": "../'${INSTALL_PROFILE_DIRECTORY}'", "options": {"symlink": true}}'

# Add the pattern lab installer type.
$COMPOSER config extra.installer-types.2 "pattern-lab"

$COMPOSER config extra.installer-paths./${INSTALL_PROFILE_DIRECTORY}/ecms_base/themes/custom/ecms/{\$name} PATTERN_LAB_REPLACE

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
