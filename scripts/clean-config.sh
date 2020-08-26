#!/usr/bin/env bash

# Fail safely if any errors occur.
set -eo pipefail

# Move up a level starting from the scripts directory.
BASE_DIR="$(dirname $(cd ${0%/*} && pwd))"

echo -e "**********************"
echo -e "*   Cleanup Config   *"
echo -e "**********************"
echo -e "Cleaning up configuration in: ${BASE_DIR}/*/config/install/\n"
echo -e "Cleaning up configuration in: ${BASE_DIR}/*/modules/custom/*/config/install/\n"

# sed is different for Macs, detect that here.
if [[ "$OSTYPE" == "darwin"* ]]; then
  find ${BASE_DIR}/*/config/ -type f -name "*.yml" -exec sed -i '' '/^uuid: /d' {} \;
  find ${BASE_DIR}/*/config/ -type f -name "*.yml" -exec sed -i '' '/_core:/{N;d;}' {} \;

  # Traverse the custom modules too.
  find ${BASE_DIR}/*/modules/custom -path "*/config/*.yml" -exec sed -i '' '/^uuid: /d' {} \;
  find ${BASE_DIR}/*/modules/custom -path "*/config/*.yml" -exec sed -i '' '/_core:/{N;d;}' {} \;
else
  find ${BASE_DIR}/*/config/install/ -type f -name "*.yml" -exec sed -i '/^uuid: /d' {} \;
  find ${BASE_DIR}/*/config/install/ -type f -name "*.yml" -exec sed -i '/_core:/,+1d' {} \;

  # Traverse the custom modules too.
  find ${BASE_DIR}/*/modules/custom -path "*/config/*.yml" -exec sed -i '/^uuid: /d' {} \;
  find ${BASE_DIR}/*/modules/custom -path "*/config/*.yml" -exec sed -i '/_core:/,+1d' {} \;
fi

echo -e "********************************"
echo -e "*    Config cleanup complete   *"
echo -e "********************************"