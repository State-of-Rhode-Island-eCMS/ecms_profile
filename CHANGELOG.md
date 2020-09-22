# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog][] and this project adheres to a
modified Semantic Versioning scheme. See the "Versioning scheme" section of the
[CONTRIBUTING][] file for more information.

[Keep a Changelog]: http://keepachangelog.com/
[CONTRIBUTING]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/README.md

## [Unreleased]
### Added
- Added the [ACSF module](https://www.drupal.org/project/acsf) as a required dependency.
- Added the [openid_connect](https://www.drupal.org/project/openid_connect) module as a dependency.
- Added required modules/configuration based on the standard installation profile.
- RIG-32: Added the webform requirement and installed by default.
- RIG-15: Added the Notification content type as a feature and the editorial workflow.
- RIG-33: Added the press release content type as a feature.
- RIG-35: Added the Location content type as a feature.
- RIG-34: Added the Person content type as a feature.
- RIG-15: Added 5 custom roles to standard install profile.
- RIG-15: Added the Publish Content module and installed by default.
- RIG-40: Added the Event content type as a feature.
- RIG-41: Added the Promotions content type as a feature.
- RIG-39: Added the Basic page content type as a feature.
- RIG-38: Added the Landing page content type as a feature.
- RIG-15: Added Content Moderation Notification module and config.
- RIG-43: Added the ecms_api custom module.
- RIG-16: Added custom theme with Pattern Lab repo dependency.
- RIG-37: Added the Admin Toolbar module.
- RIG-55: Added the ecms_api_recipient custom module.
- RIG-56: Added a configuration form to toggle content types for the eCMS API. 
- RIG-57: Added the ecms_api_publisher module and custom entity.
- RIG-60: Added the ecms_api_publisher module installation steps.
- RIG-22: Added install code to automatically assign base content types to default workflow.

### Changed
- RIG-23: Changed from OIDC generic to Windows AAD for authentication.
- Disabled xdebug by default in the develop.sh script.
- RIG-37: Made ECMS custom theme the default.
- RIG-22: Enable Moderation Dashboard module by default.

### Deprecated

### Removed
- RIG-37: Removed core search from default install.

### Fixed

### Security
