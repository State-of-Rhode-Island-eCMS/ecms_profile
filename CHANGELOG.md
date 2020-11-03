# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog][] and this project adheres to a
modified Semantic Versioning scheme. See the "Versioning scheme" section of the
[CONTRIBUTING][] file for more information.

[Keep a Changelog]: http://keepachangelog.com/
[CONTRIBUTING]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/README.md

## [Unreleased]
### Added
- RIG-91: Added scheduler permissions for publishers and site admins.
- RIG-67: Added Global Display and Display Title field to promotions.
- RIG-67: Added Promotion Entity Reference Filter view.

### Changed
- RIG-67: Changed promotion body field to use plain text.

### Deprecated

### Removed

### Fixed
- RIG-67: Fixed issue of promotional images using a duplicate source field.

### Security

## [0.1.6] - 2020-10-30
### Added
- RIG-138: Added twig_vardumper

### Changed
- RIG-130: Changed how JSON path is pulled in.
- RIG-69: Update how translate functions are called with file media.

### Fixed
- RIG-91: Add scheduler settings to applicable features.

## [0.1.5] - 2020-10-30
### Added
- RIG-69: Added theming for file list paragraph.
- RIG-130: Added theming for article teaser and article latest.
- RIG-130: Added view media permission for anon users.
- RIG-130: Added media_library_theme_reset module.

### Changed
- RIG-69: Changed display settings for media file.

### Removed
- RIG-69: Removed field_file_title from eCMS_paragraphs.

## [0.1.4] - 2020-10-28
### Added
- RIG-69: Removed several field wrapper divs.
- RIG-69: Passed front_page links to PL.
- RIG-69: Added a preprocess function paragraph media items.

### Removed
- RIG-74: Remove webform encrypt patch from issue 2943344.

### Fixed
- RIG-136: Fixed the AAD login removing manually applied roles.

## [0.1.3] - 2020-10-27
### Added
- RIG-82: Added notification retrieval from the hub on installation.
- RIG-124: Added the external links module and configuration.
- RIG-91: Installation & configuration of scheduler module for all content types.
- RIG-69: Added SVG Image module installed by default.
- RIG-69: Added new paragraph type Icon Card.
- RIG-123: Install and configure Pathauto and Redirect modules.
- RIG-69: Added pattern lab connection for Icon Card.
- RIG-69: Added pattern lab connection for Media Item.
- RIG-69: Added menu_block module.
- RIG-69: Added pattern lab connection for Sidebar nav.
- RIG-69: Defined new regions for ecms_theme.

### Fixed
- RIG-129: Updated the permissions for the site_admin role.

## [0.1.2] - 2020-10-22
### Added
- RIG-37: Added Layout Builder Restrictions module.
- RIG-37: Added Accordion Builder paragraph bundle.
- RIG-37: Add base twig templates for HTML, Page, and Region.
- RIG-37: Add accordion and menu twig templates.
- RIG-37: Added compiled pattern lab js import.
- RIG-37: Added main navigation block to primary menu region.

### Changed
- RIG-37: Update Landing Page full content display with restricted block types.
- RIG-37: Moved ecms.settings.yml to config install folder.
- RIG-37: Changed landing page page.tpl to use classy.
- RIG-37: Add tabledrag patch from 3083051 for core Claro theme.

### Fixed
- RIG-121: Fixed the AAD administrator group name.

## [0.1.1] - 2020-10-19
### Added
- RIG-37: Add Content Components block type
- RIG-37: Add layout_builder_modal contrib module.

## [0.1.0] - 2020-10-15
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
- RIG-22: Added the ecms_workflow module to automatically assign content types to default workflow and set permissions.
- RIG-61: Added the base class to authenticate and syndicate entities with Json API.
- RIG-51: Added the ecms_projects content type as a feature.
- RIG-37: Added components module to the standard installation profile.
- RIG-37: Added ecms.libraries.yml file and included a global styling library.
- RIG-53: Added the Publication content type as a feature.
- RIG-53: Added Rabbit Hole module.
- RIG-47: Added Speech content type as optional feature.
- RIG-58: Added the automated site registration to the hub.
- RIG-49: Added the hotel content type as a feature.
- RIG-45: Added Executive Order content type as optional feature.
- RIG-72: Added the queue and publish service to syndicate content to other sites.
- RIG-67: Added paragraphs feature to base install.
- RIG-62: Added webform node module install by default.
- RIG-78: Added default languages and translations for the default content types.
- RIG-85: Added translation support for the hotel content type.
- RIG-84: Added translation support for the executive order content type.
- RIG-86: Added translation support for the projects content type.
- RIG-87: Added translation support for the publication content type.
- RIG-88: Added translation support for the speech content type.
- RIG-22: Added moderated_content_bulk_publish module, installed by default.
- RIG-94: Added Press Release paragraphs field.
- RIG-95: Added Press Release Topics taxonomy.
- RIG-74: Added Key, Encrypt, Webform Encrypt, and Real AES modules.

### Changed
- RIG-23: Changed from OIDC generic to Windows AAD for authentication.
- Disabled xdebug by default in the develop.sh script.
- RIG-37: Made ECMS custom theme the default.
- RIG-22: Enable Moderation Dashboard module by default.
- RIG-37: Updated the development script to allow for pattern lab development.
- RIG-76: Updated the api recipient installation values.
- RIG-67: Added paragraphs reference field to basic page.
- RIG-80: Fixed the publishing of notifications to only published nodes and translations.
- RIG-22: Updated basic HTML WYSIWYG.

### Removed
- RIG-37: Removed core search from default install.

### Fixed
- RIG-37: Fixed the develop script to properly pull in the pattern lab repo.
- RIG-89: Fixed the Ecms API to work with syndicating translations.

[Unreleased]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.1.6...HEAD
[0.1.6]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.1.5...0.1.6
[0.1.5]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.1.4...0.1.5
[0.1.4]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.1.3...0.1.4
[0.1.3]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.1.2...0.1.3
[0.1.2]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.1.1...0.1.2
[0.1.1]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/releases/tag/0.1.0
