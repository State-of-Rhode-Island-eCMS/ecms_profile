# ECMS Drupal Installation Profile
Drupal base installation profile for the Rhode Island eCMS system.

## Development Quick Start
To develop this installation profile, start by running

```bash
./scripts/develop.sh
```

This script will create a new lando project based on the Oomphinc scaffold file
in the following directory:

```bash
../develop-ecms-profile
```

[Detailed development instructions can be found here](docs/development.md).

### New profile configuration
When adding new configuration to install, place the configuration into the
`config/install` directory. Then, run `scripts/clean-config.sh` which will
strip any uuid and _core flags from the yml allowing for installation into
a new site.

### Custom Assets
Compiled css files and js files are ignored by default. Any project that uses
this distribution will need to include the following paths in their js/css
compilations:

- web/profiles/contrib/ecms_profile/themes/custom/**/*.scss
- web/profiles/contrib/ecms_profile/modules/custom/**/*.es6.js
