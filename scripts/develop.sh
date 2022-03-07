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
DRUPAL_CORE_VERSION="9.3.7"

# Whether the source directory should be deleted before rebuilding lando
DELETE_SRC=0

while [ "$1" != "" ]; do
    case $1 in
        -d | --delete )         shift
                                DELETE_SRC=1
                                ;;
    esac
done

# Make sure the system has lando.
if ! [ -x "$(command -v lando)" ]; then
  echo 'Error: Lando is not installed!' >&2
  exit 1
fi

LANDO="$(which lando)"

# Define the color scheme.
FG_C='\033[1;37m'
BG_C='\033[42m'
WBG_C='\033[43m'
EBG_C='\033[41m'
NO_C='\033[0m'

echo -e "\n"
if [ $1 ] ; then
  DEST_DIR="$1"
  echo $1
else
  DEST_DIR="$( dirname $BASE_DIR )/$APP_NAME"
  echo -e "${FG_C}${WBG_C} WARNING ${NO_C} No installation path provided. $INSTALL_PROFILE_NAME will be installed in $DEST_DIR."
  echo -e "${FG_C}${BG_C} USAGE ${NO_C} ${0} [install_path] # to install in a different directory."
fi
DRUSH="$DEST_DIR/$COMPOSER_BIN_DIR/drush"

echo -e "\n\n\n"
echo -e "******************"
echo -e "*   Installing   *"
echo -e "******************"
echo -e "\n\n\n"
echo -e "Installing to: $DEST_DIR\n"

if [ -d "$DEST_DIR" ] && [ "$DELETE_SRC" == "1" ]; then
  set +e
  echo -e "${FG_C}${WBG_C} WARNING ${NO_C} You are about to delete $DEST_DIR to install $INSTALL_PROFILE_NAME in that location."

  # Destroy the lando containers before deletion.
  cd $DEST_DIR
  lando destroy --yes
  cd $BASE_DIR

  rm -Rf $DEST_DIR

  if [ $? -ne 0 ]; then
    echo -e "${FG_C}${EBG_C} ERROR ${NO_C} Sometimes drush adds some files with permissions that are not deletable by the current user."
    echo -e "${FG_C}${BG_C} EXECUTING ${NO_C} sudo rm -Rf $DEST_DIR"
    sudo rm -Rf $DEST_DIR
  fi
  set -e
fi

# Make sure the directory doesn't exist before making a new project.
if [ ! -d "$DEST_DIR" ]; then
  echo "-----------------------------------------------"
  echo " Setup $INSTALL_PROFILE_NAME using composer "
  echo "-----------------------------------------------"
  echo -e "${FG_C}${BG_C} EXECUTING ${NO_C} $COMPOSER create-project --no-interaction oomphinc/drupal-scaffold:^1.2 ${DEST_DIR} --stability dev --no-interaction --no-install\n\n"
  $COMPOSER create-project --no-interaction "oomphinc/drupal-scaffold:^1.2" ${DEST_DIR} --stability dev --no-interaction --no-install

  # Delete the composer.lock file to get the latest packages instead of the scaffolds outdated packages.
  if [ -a "${DEST_DIR}/composer.lock" ]; then
    echo -e "${FG_C}${BG_C} EXECUTING ${NO_C} rm $DEST_DIR/composer.lock\n\n"
    rm $DEST_DIR/composer.lock
  fi

  cd $DEST_DIR
  $COMPOSER require "oomphinc/composer-installers-extender: ^2.0" --no-update
  cd $BASE_DIR

  if [ $? -ne 0 ]; then
    echo -e "${FG_C}${EBG_C} ERROR ${NO_C} There was a problem setting up $INSTALL_PROFILE_NAME using composer."
    echo "Please check your composer configuration and try again."
    exit 2
  fi
fi

echo "----------------------------------"
echo " Initialize lando for local usage "
echo "----------------------------------"
cd ${DEST_DIR}

echo "Lock Drupal core to version ${DRUPAL_CORE_VERSION}."
$COMPOSER require "drupal/core-composer-scaffold:${DRUPAL_CORE_VERSION}" --no-update
$COMPOSER require "drupal/core-project-message:${DRUPAL_CORE_VERSION}" --no-update
$COMPOSER require "drupal/core-recommended:${DRUPAL_CORE_VERSION}" --no-update
$COMPOSER require "drupal/core-vendor-hardening:${DRUPAL_CORE_VERSION}" --no-update


echo -e "${FG_C}${BG_C} EXECUTING ${NO_C} $LANDO init --name $APP_NAME --recipe drupal9 --webroot $DOCROOT --source cwd\n\n"
$LANDO init --name ${APP_NAME} --recipe drupal9 --webroot ${DOCROOT} --source cwd

# Check for a lando local file.
if [ -a "${DEST_DIR}/.lando.local.yml" ]; then
  # Using an if/else block as the negate (!) operator
  # doesn't seem to work with -a.
  echo "Found a .lando.local.yml file."
else
  echo "Create a .lando.local.yml file."

  # Include a .lando.local.yml to include the distribution in the app.
  if [ -d "$PATTERN_LAB_FULL_PATH/.git" ]; then
    echo -e  "${FG_C}${BG_C}Pattern lab git repository found at${NO_C}: $PATTERN_LAB_FULL_PATH, symlinking for development."
    LANDO_SERVICES="services:
  appserver:
    overrides:
      environment:
        SIMPLETEST_BASE_URL: 'https://appserver'
        SIMPLETEST_DB: 'sqlite://appserver/sites/default/files/.ht.sqlite'
        DTT_BASE_URL: 'https://appserver'
        TEMP: '/app/web/sites/default/files/temp'
      volumes:
        - $BASE_DIR:/$INSTALL_PROFILE_DIRECTORY
        - $PATTERN_LAB_FULL_PATH:/$PATTERN_LAB_DIRECTORY
  nodejs:
    overrides:
      volumes:
        - $BASE_DIR:/$INSTALL_PROFILE_DIRECTORY
        - $PATTERN_LAB_FULL_PATH:/$PATTERN_LAB_DIRECTORY"
  else

    echo -e  "${FG_C}${BG_C}Pattern lab git repository NOT found at${NO_C}: $PATTERN_LAB_FULL_PATH, ignoring symlink."
    LANDO_SERVICES="services:
  appserver:
    run_as_root:
      - echo 'deb http://deb.debian.org/debian stretch-backports main' >> /etc/apt/sources.list && apt-get update && apt-get install -y -t stretch-backports sqlite3 libsqlite3-dev
    overrides:
      environment:
        SIMPLETEST_BASE_URL: 'https://appserver'
        SIMPLETEST_DB: 'mysql://drupal9:drupal9@database/drupal9'
        DTT_BASE_URL: 'https://appserver'
        TEMP: '/app/web/sites/default/files/temp'
      volumes:
        - $BASE_DIR:/$INSTALL_PROFILE_DIRECTORY
  nodejs:
    overrides:
      volumes:
        - $BASE_DIR:/$INSTALL_PROFILE_DIRECTORY
  cache:
    type: memcached:1.5.11
    mem: 256
    portforward: 11222"
  fi

  echo "$LANDO_SERVICES
tooling:
  phpunit:
    service: appserver
    cmd: vendor/bin/phpunit --configuration /$INSTALL_PROFILE_DIRECTORY/phpunit.xml
  paratest:
    service: appserver
    cmd: vendor/bin/paratest --configuration /$INSTALL_PROFILE_DIRECTORY/phpunit.xml
  gulp-distro:
    service: nodejs
    cmd: cd /$INSTALL_PROFILE_DIRECTORY && npm install
  phpcs:
    service: appserver
    cmd: vendor/bin/phpcs --standard=/$INSTALL_PROFILE_DIRECTORY/phpcs.xml
  xdebug-on:
    service: appserver
    description: Enable xdebug for apache.
    cmd: 'docker-php-ext-enable xdebug && /etc/init.d/apache2 reload'
    user: root
  xdebug-off:
    service: appserver
    description: Disable xdebug for apache.
    cmd: 'rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && /etc/init.d/apache2 reload'
    user: root
config:
  xdebug: false
env_file:
  - .env  " >> ${DEST_DIR}/.lando.local.yml
fi

# Check for an existing .env file.
if [ -a "${DEST_DIR}/.env" ]; then
  echo "Found a .env file."
else
  echo "Create a .env file."
  # Create the environment file with a temp key for webform encrypt.
  echo -e  "ENCRYPTION_PRIVATE_KEY=$(dd if=/dev/urandom bs=32 count=1 | base64 -i -)" >> .env
fi

# Start the app with lando.
set +e
$LANDO start
set -e

# Remove the coffee module as it currently breaks unit testing.
$LANDO composer remove drupal/coffee

# Add the development requirements for testing.
$LANDO composer require "behat/mink-goutte-driver:~1.2" --dev --no-update
$LANDO composer require "php-mock/php-mock" --dev --no-update
$LANDO composer require "php-mock/php-mock-phpunit" --dev --no-update
$LANDO composer require "weitzman/drupal-test-traits" --dev --no-update
$LANDO composer require 'liuggio/fastest:^1.6' --dev --no-update
$LANDO composer require "phpunit/phpunit:^8" --dev --no-update
$LANDO composer require "symfony/phpunit-bridge:^5.1" --dev --no-update
$LANDO composer require "drupal/coder:^8.3" --dev --no-update
$LANDO composer require "drush/drush:^10.0" --dev --no-update

echo "--------------------------------------------------"
echo " Require ${REPOSITORY_NAME} using lando composer "
echo "--------------------------------------------------"

$LANDO composer config repositories.${INSTALL_PROFILE_DIRECTORY} '{"type": "path", "url": "/'${INSTALL_PROFILE_DIRECTORY}'", "options": {"symlink": true}}'

# Add the pattern lab installer type.
$LANDO composer config extra.installer-types.2 "pattern-lab"

# Add the installer path.
$LANDO composer config extra.installer-paths./${INSTALL_PROFILE_DIRECTORY}/ecms_base/themes/custom/ecms/{\$name} PATTERN_LAB_REPLACE
# Replace the "PATTERN_LAB_REPLACE" text with the actual value.
# sed is different for Macs, detect that here.
if [[ "$OSTYPE" == "darwin"* ]]; then
  sed -i '' 's/"PATTERN_LAB_REPLACE"/\["type:pattern-lab"]/g' composer.json
else
  sed -i 's/"PATTERN_LAB_REPLACE"/\["type:pattern-lab"]/g' composer.json
fi

# Add the migration_tools repository.
$LANDO composer config repositories.migratation_tools '{"type": "package", "package": {"name": "drupal_git/migration_tools", "type": "drupal-module", "version": "1.0.0", "source": {"type": "git", "url": "https://git.drupalcode.org/project/migration_tools.git", "reference": "3e193bc97d127ea2cff6b80f9509bc161bdee19f"}}}'

# Add the migrate_process_trim repository.
$LANDO composer config repositories.migratation_process_trim '{"type": "package", "package": {"name": "drupal_git/migrate_process_trim", "type": "drupal-module", "version": "1.0.0", "source": {"type": "git", "url": "https://git.drupalcode.org/project/migrate_process_trim.git", "reference": "79c7ceb9113c1e21818bd124135e5d261ccbebbc"}}}'

$LANDO composer config extra.enable-patching true
$LANDO composer require "${REPOSITORY_NAME}:*" --no-progress

if [ -d "$PATTERN_LAB_FULL_PATH/.git" ]; then
  # Symlink pattern lab for development.
  $LANDO composer config repositories.${PATTERN_LAB_DIRECTORY} '{"type": "path", "url": "/'${PATTERN_LAB_DIRECTORY}'", "options": {"symlink": true}}'
  $LANDO composer require "${PATTERN_LAB_REPOSITORY_NAME}:*" --no-progress
else
  # Require pattern lab master branch from Github.
  $LANDO composer config repositories.${PATTERN_LAB_DIRECTORY} '{"type": "git", "url": "https://github.com/State-of-Rhode-Island-eCMS/ecms_patternlab.git"}'
  $LANDO composer require "${PATTERN_LAB_REPOSITORY_NAME}:dev-master" --no-progress
fi

# Update the lock file to ensure core patches applied.
$LANDO composer update --lock

if [ -a "${DEST_DIR}/package-lock.json" ]; then
  # Delete the package.lock file directory.
  echo "Deleting: ${DEST_DIR}/package-lock.json"
  rm -Rf $DEST_DIR/package-lock.json
fi

if [ -a "${DEST_DIR}/.stylelintrc.json" ] ; then
  echo "Deleting ${DEST_DIR}/.stylelintrc.json and replacing with the distribution's copy.";
  # Remove the stylelintrc file.
  rm -Rf $DEST_DIR/.stylelintrc.json
fi

# Copy the distribution .stylintrc.json file to the app.
cp $BASE_DIR/.stylelintrc.json $DEST_DIR

if [ -a "${DEST_DIR}/gulpfile.js" ]; then
  # Delete the gulpfile.js file directory.
  echo "Deleting: ${DEST_DIR}/gulpfile.js"
  rm -Rf $DEST_DIR/gulpfile.js
fi

# Copy the distribution gulpfile.js file to the app.
cp $BASE_DIR/gulpfile.js $DEST_DIR

echo -e "${FG_C}${BG_C} EXECUTING ${NO_C} $LANDO npm install gulp@4.0"
$LANDO npm install gulp@4.0

echo -e "${FG_C}${BG_C} EXECUTING ${NO_C} $LANDO npm install gulp-sourcemaps"
$LANDO npm install gulp-sourcemaps

echo -e "${FG_C}${BG_C} EXECUTING ${NO_C} $LANDO npm install"
$LANDO npm install

echo -e "${FG_C}${BG_C} EXECUTING ${NO_C} $LANDO gulp-distro"
$LANDO gulp-distro

# Install the profile.
cd ${DOCROOT}

# Ensure the sites/default directory is writeable.
if [ -d "${DEST_DIR}/${DOCROOT}/sites/default/files" ]; then
  chmod ug+w ${DEST_DIR}/${DOCROOT}/sites/default/files
fi

# Remove the CONFIG_SYNC_DIRECTORY constant.
if [ -a "${DEST_DIR}/${DOCROOT}/sites/default/settings.php" ]; then
  if [[ "$OSTYPE" == "darwin"* ]]; then
    sed -i '' 's/CONFIG_SYNC_DIRECTORY/"CONFIG_SYNC_DIRECTORY_OFF"/g' ${DEST_DIR}/${DOCROOT}/sites/default/settings.php
  else
    sed -i 's/CONFIG_SYNC_DIRECTORY/"CONFIG_SYNC_DIRECTORY_OFF"/g' ${DEST_DIR}/${DOCROOT}/sites/default/settings.php
  fi
fi

echo -e "${FG_C}${BG_C} EXECUTING ${NO_C} $LANDO drush site-install ${INSTALL_PROFILE_NAME}"
$LANDO drush site-install ${INSTALL_PROFILE_NAME} --verbose --yes --site-mail=admin@localhost --account-mail=admin@localhost --site-name="${INSTALL_PROFILE_NAME} Distribution" --account-name=admin --account-pass=admin;

echo -e "${FG_C}${BG_C} EXECUTING ${NO_C} $LANDO drush updb --yes"
$LANDO drush updb --yes

echo -e "${FG_C}${BG_C} EXECUTING ${NO_C} $LANDO drush cr --yes"
$LANDO drush cr --yes

echo -e "\n\n\n"
echo -e "********************************"
echo -e "*    Installation finished     *"
echo -e "********************************"
echo -e "\n\n\n"
