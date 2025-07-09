# Development for the custom ecms_profile

Before starting development, ensure that you have composer installed globally
on your system. [Instructions can be found here](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos).

Development of the ecms_profile can be started by cloning this repository and
running the following script:
```bash
./scripts/develop.sh
```

This script will create a new ddev environment and symlink the profiles into
the `web/profiles/custom/ecms_profile` directory.

This new environment will live inside the project, with the webroot in
`./develop/web`

## Pattern Lab & Profile Development
Pattern lab will be included as a dependecy and pull the `dev-master` branch.

## Adding dependencies
This is the installation profile and does not contain Lando, therefore, you
shouldn’t prefix any terminal commands with "ddev" within this repo.
For example, if you need an additional module use:
```bash
composer require drupal/module --no-update
```

The repo should NOT contain a `composer.lock` file (hence the --no-update flag)
and one should not be committed when creating a pull request
(why it's in the .gitignore). If a module needs to be installed to the profile,
require it with composer but use the --no-update flag to keep the command
from creating a composer.lock file.
To enable a module on the distribution install, add it to the
`ecms_profile.info.yml` file as a dependency.

Config files for the profile are stored in the `config/install` directory.
If config files pertain to an existing custom module, then the yml file
should live within that module’s config/install directory.
Note: Always run the config-clean shell script below to remove UUIDs.

## Scripts
This repo has two shell scripts that can be run:

### Starting a development environment
```bash
./scripts/develop.sh
```
This will spin up a local environment with DDEV that is based on
Drupal's core-recommended proejct and will symlink the profile's
repository to the DDEV environment.

### Cleaning configuration
```bash
./scripts/clean-config.sh
```
Removes UUIDs from config files.
As you develop locally, there may be some configuration changes needed.
When you add config files to the profile repository, be sure to run this
shell script to remove the UUIDs.

## Get a local spun up to contribute to the project
1. Download and install ddev if you haven’t already.
   The team is using the latest stable release `https://ddev.com/get-started/`
2. Clone the profile repository:
   `git@github.com:State-of-Rhode-Island-eCMS/ecms_profile.git`
3. Change directories into the ecms_profile and run `./scripts/develop.sh`.
   This will create a new ddev project in the `./develop` directory of the
   project's repository.
4. Once complete run `ddev npm install` to add packages.
5. Then run `ddev gulp build` and it should build without error.
6. You can log into the newly spun up website with user: admin / pass: admin.
   `https://ecms-profile.ddev.site/user/login?showcore=1`

With the distribution site now fully installed, you can make your code updates
from within the main "ecms_profile" repository directory.
Changes will show in your demo site "develop-ecms-profile"
as the distro is symlinked.

### Feature branches
When making changes to the "ecms_profile" repository in a feature branch, you may
have to update the composer.json package version in the "develop-ecms-profile" to
point to your feature branch.

```json
"require": {
        "rhodeislandecms/ecms_profile": "dev-[feature-branch-name]",
    },
```

This will be the case if you update any requirements, such as a new module. Composer
won't be aware of the changes unless it's looking at the updated dependencies.

Example: A feature branch named `RIG-37/pattern-lab-integration`

```json
"require": {
        "rhodeislandecms/ecms_profile": "dev-RIG-37/pattern-lab-integration",
    },
```

#### Steps to update local environment
 * Checkout your feature branch
 * Run `./scripts/develop.sh`
 * The ddev project created should be pointed to your code's feature branch.

#### Testing updates to the profile installation
Often you want to test the updated installation process to ensure plugins are enabled,
configuration is installing correctly, etc.
To test the updated profile install process locally, browse to ../develop-ecms-profile and run:
```bash
ddev drush site-install ecms_base --verbose --yes --site-name="State of Rhode Island Distribution" --account-name=admin --account-pass=admin;
```

## Features
Certain configuration should be managed with the Features module.
[Additional features documentation is available here](./features.md).

## Custom theme and Pattern Lab Integration
[Additional documentation is available here](./theming.md).
