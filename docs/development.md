# Development for the custom ecms_profile

Before starting development, ensure that you have composer installed globally
on your system. [Instructions can be found here](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos).

Development of the ecms_profile can be started by cloning this repository and
running the following script:
```bash
./scripts/develop.sh
```

This script will create a new lando environment and symlink the repository into
the `web/profiles/custom/ecms_profile` directory.

This new environment will be located one directory above your
repository directory like this:

```
- ecms_profile/
- develop-ecms-profile/
```

## Pattern Lab & Profile Development
If you are developing with Pattern Lab and the Ecms Profile, you can develop
both simultaneously by cloning the `ecms_patternlab` repository to a directory
on the same level as the `ecms_profile` repository. Then, run your `develop.sh`
script. The develop.sh script will detect the `ecms_patternlab` directory as a
git repository and will symlink the directory in the lando environment.

The directory structure should look like this:
```
- ecms_patternlab/
- ecms_profile/
- develop-ecms-profile/
```

If you do not have the `ecms_patternlab` repository cloned locally, the
develop.sh script will download the repository as a dependency
and place it in the `ecms_profile/ecms_base/themes/custom/ecms/ecms_patternlab`
directory.

## Adding dependencies
This is the installation profile and does not contain Lando, therefore, you
shouldn’t prefix any terminal commands with "Lando" within this repo.
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
This will spin up a local environment with Lando that is based on
Oomph’s Drupal scaffold and will symlink the profile's
repository to the Lando environment.

### Cleaning configuration
```bash
./scripts/clean-config.sh
```
Removes UUIDs from config files.
As you develop locally, there may be some configuration changes needed.
When you add config files to the profile repository, be sure to run this
shell script to remove the UUIDs.

## Get a local spun up to contribute to the project
1. Download and install Lando if you haven’t already.
   The team is using the latest stable release `https://github.com/lando/lando`
2. Clone the profile repository:
   `git@github.com:State-of-Rhode-Island-eCMS/ecms_profile.git`
3. Change directories into the ecms_profile and run `./scripts/develop.sh`.
   This will create a new Lando enabled directory labeled
   "develop-ecms-profile" directly above your cloned repository.
   _This process can take upwards of 30 minutes on macOS_.
4. Once complete, change into the "develop-ecms-profile" and test run
   `lando gulp build` and it should build without error.
5. You can log into the newly spun up website with user: admin / pass: admin.
   `https://develop-ecms-profile.lndo.site/user/login?showcore=1`

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
 * Pull down ecms_profile master branch, ensure your feature branch is rebased.
 * Browse to ../develop-ecms-profile and run `lando start`
 * As noted above, update composer.json to point to your ecms_profile feature branch.
 * To ensure dependencies are up to date, run `lando composer update rhodeislandecms/ecms_profile --with-dependencies`
 * Note that you will need to perform the previous command any time you make changes to `ecms_profile/composer.json`.

#### Testing updates to the profile installation
Often you want to test the updated installation process to ensure plugins are enabled,
configuration is installing correctly, etc.
To test the updated profile install process locally, browse to ../develop-ecms-profile and run:
```bash
lando drush site-install ecms_base --verbose --yes --site-name="State of Rhode Island Distribution" --account-name=admin --account-pass=admin;
```

## Features
Certain configuration should be managed with the Features module.
[Additional features documentation is available here](./features.md).

## Custom theme and Pattern Lab Integration
[Additional documentation is available here](./theming.md).
