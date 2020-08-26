#!/usr/bin/env bash

# Fail safely if any errors occur.
set -eo pipefail

# Move up a level starting from the scripts directory.
BASE_DIR="$(dirname $(cd ${0%/*} && pwd))"
APP_NAME="develop-ecms-profile"
INSTALL_PROFILE_NAME="ecms_base"
INSTALL_PROFILE_DIRECTORY="ecms_profile"
REPOSITORY_NAME="rhodeislandecms/ecms_profile"

COMPOSER="$(which composer)"
COMPOSER_BIN_DIR="$(composer config bin-dir)"
DOCROOT="web"

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
  echo -e "${FG_C}${BG_C} EXECUTING ${NO_C} $COMPOSER create-project --no-interaction oomphinc/drupal-scaffold:^1.1 ${DEST_DIR} --stability dev --no-interaction --no-install\n\n"
  $COMPOSER create-project --no-interaction "oomphinc/drupal-scaffold:^1.1" ${DEST_DIR} --stability dev --no-interaction --no-install
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
  echo "services:
  appserver:
    overrides:
      volumes:
        - $BASE_DIR:/$INSTALL_PROFILE_DIRECTORY
  nodejs:
    overrides:
      volumes:
        - $BASE_DIR:/$INSTALL_PROFILE_DIRECTORY
tooling:
  phpunit:
    service: appserver
    cmd: vendor/bin/phpunit --configuration /$INSTALL_PROFILE_DIRECTORY/phpunit.xml
  gulp-distro:
    service: nodejs
    cmd: cd /$INSTALL_PROFILE_DIRECTORY && npm install
  phpcs:
    service: appserver
    cmd: vendor/bin/phpcs --standard=/$INSTALL_PROFILE_DIRECTORY/phpcs.xml
config:
  xdebug: true" >> ${DEST_DIR}/.lando.local.yml
fi

# Start the app with lando.
$LANDO start

# Remove the coffee module as it currently breaks unit testing.
$LANDO composer remove drupal/coffee

# Add the mink driver for functional testing.
$LANDO composer require "behat/mink-goutte-driver" --dev

# Update composer packages from the scaffold.
$LANDO composer update

echo "--------------------------------------------------"
echo " Require ${REPOSITORY_NAME} using lando composer "
echo "--------------------------------------------------"

$LANDO composer config repositories.${INSTALL_PROFILE_DIRECTORY} '{"type": "path", "url": "/'${INSTALL_PROFILE_DIRECTORY}'", "options": {"symlink": true}}'
$LANDO composer config extra.enable-patching true
$LANDO composer require "${REPOSITORY_NAME}:*" --no-progress
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
