# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog][] and this project adheres to a
modified Semantic Versioning scheme. See the "Versioning scheme" section of the
[CONTRIBUTING][] file for more information.

[Keep a Changelog]: http://keepachangelog.com/
[CONTRIBUTING]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/README.md

## [Unreleased]
### Added
- RIGA-475: Added the Media PDF Thumbnail module.

### Changed

### Deprecated

### Removed

### Fixed

### Security

## [1.0.2] - 2024-04-24
### Added
- RIGA-462: Added the disease field to the media migration.
- RIGA-467: Added module to enable plugin for CKEditor column widths

### Fixed
- RIGA-471: Fixed a misconfigured permission.
- RIGA-462: Fixed the configuration dependencies.

## [1.0.1] - 2024-03-27
### Added
- RIGA-461: Added Drupal Commerce
- RIGA-461: Added Drupal Commerce Stock
- RIGA-461: Added Drupal LinkIt
- RIGA-461: Added Drupal Facets

### Changed
- RIGA-459: Updated the text formatters and permissions.

### Fixed
- RIGA-458: Fixed the workflow error.
- RIGA-458: Fixed the site installation error.
- RIGA-463: Patched the path redirect import module.

## [1.0.0] - 2024-03-13
### Added
- RIGA-451: Add the Address Phonenumber module to composer.
- RIGA-452: Added the vbo_export module to composer.
- RIGA-448: Added the path redirect import module to the codebase.

### Fixed
- RIGA-446: Drupal 10 upgrade fixes.

## [0.11.0] - 2024-01-25
### Added
- RIGA-416: New health file migrations.
- RIGA-444: New resource_link migration for health site.

### Deprecated
- RIGA-441: Disable Color, Quickedit, RDF core modules to prepare for D10.

## [0.10.9] - 2023-11-30
### Added
- RIGA-434: Add ecms_base_update_9104 to uninstall drupal/advagg.

### Changed
- RIGA-434: Uninstall advagg by default, so it has to be explicitly enabled.

## [0.10.8] - 2023-11-02
### Added
- RIGA-322: Add google_translator patch 3387636 to resolve HTML render error.
- RIGA-401: Add hook ecms_base_update_9102, enable modules and install config.
- RIGA-431: Add hook ecms_base_update_9103, enable Google Tag for anonymous.

## [0.10.7] - 2023-10-19
### Changed
- RIGA-6: Temporarily remove code of error-causing hooks.

## [0.10.6] - 2023-10-19
### Fixed
- RIGA-426: Remove unneeded access check from search response.

## [0.10.5] - 2023-10-19
### Added
- RIGA-322: Add hook 'ecms_base_update_9101' to import updated role config.
- RIGA-401: Add hook 'ecms_base_update_9102' to install new modules.
- RIGA-401: Add hook 'ecms_base_update_9103' to import new module config.
- RIGA-401: Add drupal/focal_point ^2.0.1 to composer.json.
- RIGA-401: Add drupal/iek (Image effect kit) ^1.3 to composer.json.
- RIGA-401: Add drupal/entity_usage ^2.0@beta to composer.json.
- RIGA-415: Add drupal/advagg ^6.0@alpha to composer.json.
- RIGA-415: Add drupal/conditional_fields ^4.0@alpha to composer.json.
- RIGA-415: Add drupal/field_group ^3.4 to composer.json.
- RIGA-415: Add drupal/quick_node_clone ^1.16 to composer.json.

### Changed
- RIGA-322: Update drupal/core-recommended version constraints to also include ^10.0.
- RIGA-322: Include ^10 in core_version_requirement for custom modules, themes, and features.
- RIGA-322: Update development scripts to use PHP 8.1.
- RIGA-322: Update better_exposed_filters version constraint ^5.0 => ^6.0.
- RIGA-322: Update captcha version constraint ^1.2 => ^2.0.
- RIGA-322: Update components version constraint ^2.0 => ^3.0@beta.
- RIGA-322: Update google_tag version constraint ^1.4 => ^2.0.
- RIGA-322: Update google_translator version constraint ^1.0@RC => ^2.1.
- RIGA-322: Update language_cookie version constraint 1.x-dev => ^2.0.
- RIGA-322: Update menu_block version constraint 1.x-dev => ^1.10.
- RIGA-322: Update simple_menu_permissions version constraint ^1.4 => ^2.0.
- RIGA-322: Update svg_image version constraint ^1.14 => ^3.0.
- RIGA-322: Update twig_tweak version constraint ^2.8 => ^3.2.
- RIGA-322: Update webform_encrypt version constraint 1.x-dev@dev => ^2.0@alpha.
- RIGA-322: Update migrate_plus version constraint ^5.1 => ^6.0.
- RIGA-322: Update migrate_tools version constraint ^5.0 => ^6.0.
- RIGA-322: Update views_database_connector version constraint ^1.4 => ^2.0.
- RIGA-322: Update patch for drupal/paragraphs, issue 2887353, comment 54 => 58.

### Deprecated
- RIGA-322: Switch 'assertArrayEquals()' method call to resolve deprecation error.
- RIGA-322: Switch 'drupal_get_path()' method calls to resolve deprecation error.
- RIGA-322: Switch 'file_create_url()' method calls to resolve deprecation error.
- RIGA-322: Switch 'render()' method calls to resolve deprecation error.
- RIGA-322: Add an explicit 'accessCheck()' to all entity queries.

### Removed
- RIGA-322: Remove module drupal/views_ajax_get from composer.
- RIGA-322: Remove orphaned permission 'create content in disabled language'.
- RIGA-322: Remove orphaned permission 'schedule publishing of nodes'.
- RIGA-322: Remove orphaned permission 'translate icon media'.
- RIGA-322: Remove orphaned permission 'translate modal node'.
- RIGA-322: Remove orphaned permission 'translate webform node'.
- RIGA-322: Remove orphaned permission 'update paragraph content button'.
- RIGA-322: Remove orphaned permission 'view disabled languages'.
- RIGA-322: Remove orphaned permission 'view scheduled content'.

### Fixed
- RIGA-429: Add check for unrouted links to fix layout builder error.

## [0.10.4] - 2023-09-28
### Changed
- RIGA-322: Update development scripts to use PHP 8.1.

## [0.10.3] - 2023-08-31
### Changed
- RIGA-378: Change from absolute to relative file path for downloadable files.
- RIGA-409: Update drupal/acsf 2.73 => ^2.75.

## [0.10.2] - 2023-08-10
### Changed
- RIGA-406: Update drupal/admin_toolbar 3.4.0 => 3.4.1.
- RIGA-406: Update drupal/easy_breadcrumb ^2.0 => 2.0.5.
- RIGA-406: Update drupal/metatag ^1.14 => ^1.26.
- RIGA-406: Update drupal/token ^1.7 => ^1.12.

## [0.10.1] - 2023-07-27
### Added
- RIGA-383: Add ecms_base_update_9100 hook to import updated config.

### Changed
- RIGA-383: Update the Event List Type field config to be non-required.

## [0.10.0] - 2023-07-13
### Changed
- RIGA-6: Upgrade to 0.10.0 to reflect drupal/core 9.4.x => 9.5.x upgrade.

## [0.9.31] - 2023-07-13
### Changed
- RIGA-399: Update drupal/core-recommended version constraint ~9.4.8 => ~9.5.9.
- RIGA-399: Update drupal/core patch issue 1356276,688 => issue 3266057,110.
- RIGA-399: Update DRUPAL_CORE_VERSION constant 9.4.8=>9.5.9 in ci-develop.sh.

## [0.9.30] - 2023-06-29
### Added
- RIGA-395: Add 'ecms_base_update_9099' to re-import empty workflow config.

### Changed
- RIGA-395: Update 'ecms_workflow.bundle_create' to show/hide moderation field.

## [0.9.29] - 2023-06-15
### Changed
- RIGA-388: Alphabetize 'Use' statements to fix codesniffer errors.
- RIGA-390: Update drupal/simple_sitemap requirement to ^4.1.

## [0.9.28] - 2023-05-25
### Added
- RIGA-384: Add post update, fix text format permissions for embed_author.

## [0.9.27] - 2023-05-11
### Changed
- RIGA-344: Add form alter hook to remove langcodes from publications selector.

## [0.9.26] - 2023-04-27
### Added
- RIGA-380: Install drupal/feeds_ex ^1.0@beta.
- RIGA-380: Install softcreatr/jsonpath, a sub-module for feeds_ex.

### Changed
- RIGA-380: Replace querypath/querypath with gravitypdf/querypath.

## [0.9.25] - 2023-04-20
### Changed
- RIGA-371: Update user role permissions, add db update to import config.
- RIGA-372: Install, enable by default, and configure drupal/autologout ^1.4.

## [0.9.24] - 2023-04-06
### Added
- RIGA-365: Add custom module to set search config on site by site basis.
- RIGA-365: Add patch for drupal/search_api, issue 3321499, comment #7.

### Fixed
- RIGA-360: Fix disappearing dropdown selector on publications search.
- RIGA-365: Fix undefined variable bug.

## [0.9.23] - 2023-03-09
### Changed
- RIGA-6: Update formatting of library imports for new drupal/components API.
- RIGA-362: Switch from hard-coded to using saved text format value for footer.
- RIGA-364: Update drupal/smart_date version constraint ^3.5 => ^4.0@alpha.

### Removed
- RIGA-362: Remove Delete content and media Permission from Content Publishers.

## [0.9.22] - 2023-01-26
### Added
- RIGA-346: Install drupal/simple_menu_permissions 1.4.0.
- RIGA-346: Install drupal/menu_admin_per_menu 1.5.
- RIGA-346: Add update hook ecms_base_update_9094 to install menu modules.
- RIGA-346: Add update hook ecms_base_update_9095 to update permissions.

### Changed
- RIGA-346: Update Content Publisher permissions to add Main menu links.

### Removed
- RIGA-346: Revoke "Use the administration pages" from Content Publishers.

## [0.9.21] - 2022-12-15
### Changed
- RIGA-336: Updated allowed formats to 2.x.

### Fixed
- RIGA-336: Added patch for allowed formats issue 2950548.

## [0.9.20] - 2022-12-15
### Changed
- RIGA-323: Added cURL-based replacement function for file_get_contents().

## [0.9.19] - 2022-12-08
### Changed
- RIGA-333: Upgrading drupal/paragraphs 1.14.0 => 1.15.0.
- RIGA-333: Upgrading drupal/media_revisions_ui 2.0.0 => 2.1.0.
- RIGA-333: Upgrading drupal/media_library_form_element 2.0.3 => 2.0.4.
- RIGA-333: Upgrading drupal/media_file_delete 1.1.1 => 1.3.0.
- RIGA-333: Upgrading drupal/media_entity_file_replace 1.0.0 => 1.1.0.
- RIGA-333: Upgrading drupal/layout_builder_restrictions 2.13.0 => 2.17.0.
- RIGA-333: Upgrading drupal/acquia_connector 4.0.0 => 4.0.1.

### Fixed
- RIGA-337: Upgrading acquia_connector also fixes fatal error (issue 3316912).

## [0.9.18] - 2022-11-17
### Hotfix
- RIGA-328: Fixed dependency revert in composer.

## [0.9.17] - 2022-11-17
### Added
- RIGA-290: Added drupal/migrate_devel as dev dependency for migration debugging.

### Changed
- RIGA-227: Update components available when Creating a Press Release.
- RIGA-328: Update moderation_dashboard to 2.x.
- RIGA-328: Added page_manager dependency.
- RIGA-328: Update panels dependency.
- RIGA-328: Update Solr search index config from search_api_solr_update_8414().
- RIGA-329: Update Acquia Connector Module
- RIGA-288: Update event form display for type.

### Fixed
- RIGA-6: Fixed pathauto pattern for webform node type.

## [0.9.16] - 2022-10-20
### Added
- RIGA-317: Added layout_builder_tabs and supporting functionality.

### Changed
- RIGA-312: Upgrading drupal/file_delete from 8.x-1.x to 2.x.
- RIGA-315: Update drupal/core 9.4.7 => 9.4.8.

## [0.9.15] - 2022-10-06
### Removed
- RIGA-298: Removed webform_encrypt patches in favor of custom ecms_distribution patch.

## [0.9.14] - 2022-09-22
### Changed
- RIGA-286: Update development scripts to use PHP 8.0.

## [0.9.13] - 2022-09-15
### Changed
- RIGA-308: Update drupal/acsf 2.72 => 2.73.

## [0.9.12] - 2022-08-11
### Added
- RIGA-301: Add increased memory limits and timeouts to .env file.

### Changed
- RIGA-282: Update drupal/core 9.3.19 => 9.4.5.
- RIGA-282: Update core patch for issue 1356276.
- RIGA-297: Save config for Acquia search view after db update.
- RIGA-301: Comment out functional tests from develop script.

## [0.9.11] - 2022-07-28
### Changed
- RIGA-294: Add access site reports permission to site admin role.
- RIGA-285: Updated Paragraphs Translation Sync patch (issue #2887353) to latest version.

### Security
-RIGA-293: Update core to 9.3.19.
-RIGA-293: Drupal core - Moderately critical - Information Disclosure - SA-CORE-2022-012.
-RIGA-293: Drupal core - Moderately critical - Access Bypass - SA-CORE-2022-013.
-RIGA-293: Drupal core - Critical - Arbitrary PHP code execution - SA-CORE-2022-014.
-RIGA-293: Drupal core - Moderately critical - Multiple vulnerabilities - SA-CORE-2022-015.

## [0.9.10] - 2022-07-14
### Added
- RIGA-280: Add webform_views module to codebase.

### Changed
- RIGA-288: Update event category reference to unlimited.

## [0.9.9] - 2022-06-30
### Changed
- RIGA-268: Updated Entity Print to 2.5.

### Fixed
- RIGA-270: Restored functional tests during builds.

## [0.9.8] - 2022-06-16
### Changed
- RIGA-268: Updated Office Hours field storage config for vaccination site per office_hours_update_8004().

### Fixed
- RIGA-277: Fix default allowed formats config.

### Security
- RIGA-278: Updated Drupal core to 9.3.16 (SA-CORE-2022-011).

## [0.9.7] - 2022-06-09
### Added
- RIGA-276: Add Big Menu module to codebase.

### Changed
- RIGA-271: Update core to 9.3.15.
- RIGA-268: Updated config for text editor "allowed_formats" and search api indexes.

## [0.9.6] - 2022-06-02
### Added
- RIGA-273: Add Search API Exclude module to codebase.

### Security
- RIGA-271: Update core to 9.3.14 (SA-CORE-2022-010).

## [0.9.5] - 2022-05-19
### Changed
- RIGA-263: Update core to 9.3.13.
- RIGA-263: Update scheduled transitions to 2.2.1.
- RIGA-263: Update acsf to 2.72.

## [0.9.4] - 2022-05-12
### Fixed
- RIGA-267: Fix php error with getFilename on null media item.

## [0.9.3] - 2022-05-12
### Changed
- RIGA-261: JS aggregate error. Updated smart_date module to 3.5.x.

## [0.9.2] - 2022-04-28
### Security
- RIGA-258: Update core to 9.3.12 (SA-CORE-2022-009).

## [0.9.1] - 2022-04-19
### Added
- RIGA-256: Add modal content type.
- RIGA-256: Add fields to basic page and landing page to reference modal.

## [0.9.0] - 2022-04-07
### Added
- RIGA-244: Add robotstxt module to codebase.
- RIGA-226: Add option to file list components to use absolute file link.

## [0.8.9] - 2022-03-31
### Changed
- RIGA-231: Update form submissions editor role.
- RIGA-231: Install entity_print, webform_entity_print by default.

### Security
- RIGA-233: Updated Drupal core to 9.3.9 (SA-CORE-2022-006).

## [0.8.8] - 2022-03-18
### Fixed
- RIGA-223: Fixed update hook error.

## [0.8.7] - 2022-03-17
### Fixed
- RIGA-222: Update to easy breadcrumb install config.

## [0.8.6] - 2022-03-17
### Added
- RIGA-223: Added eCMS Workflow config to allow sites to exclude content types from default workflow.
- RIGA-221: Added file_delete, media_file_delete, and media_entity_file_replace modules.

### Changed
- RIGA-222: Update core to 9.3.8.
- RIGA-222: Update core patch for issue 1356276 (profiles can define base/parent).
- RIGA-222: Update Simple OAuth from 4.6 to 5.2.
- RIGA-223: Restored press_release to the default workflow.
- RIGA-144: Updated form_author role to be submission viewer only.

### Fixed
- RIGA-222: Add patch for Media Revisions UI (new error in core 9.3).

### Security
- RIGA-222: Updated Drupal core to 9.3.8 (SA-CORE-2022-001).

## [0.8.5] - 2022-02-17
### Fixed
- RIGA-176: Fixed PHP error on term save.
- RIGA-192: Add update hook to sync content_publisher role missed on last deploy.

### Security
- RIGA-218: Updated Drupal core to 9.2.13 (SA-CORE-2022-003, -004).

## [0.8.4] - 2022-02-10
### Added
- RIGA-210: Added media download link to all media types.
- RIGA-199: Add Better Exposed Filters module to codebase.
- RIGA-176: Automatically tag press releases with the current site hostname

### Changed
- RIGA-208: Updated redirect module to 1.7.
- RIGA-208: Updated admin toolbar module to 3.1.x.
- RIGA-208: Updated easy breadcrumb module to 2.0.x.

### Fixed
- RIGA-192: File revert button on file not showing for all users.
- RIGA-191: Fixed aria-label missing from search block input.

## [0.8.3] - 2022-01-26
### Added
- RIGA-182: Added more inline block translation links for admins.

### Security
- RIGA-202: Updated Drupal core to 9.2.11 (SA-CORE-2022-001).

## [0.8.2] - 2022-01-13
### Added
- RIGA-182: Added inline block translation links for admins.

### Changed
- RIGA-103: Use EcmsApiPublisher service to syndicate Press Releases.
- RIGA-103: Updated permissions for eCMS Recipient role.

## [0.8.1] - 2022-01-06
### Changed
- RIGA-185: Remove press releases from editorial workflow.

## [0.8.0] - 2021-12-08
### Changed
- RIGA-172: Update search string placeholder.
- RIGA-174: Added results summary to views; content, media, and files.

### Security
- RIGA-175: Webform security update SA-CONTRIB-2021-045.

## [0.7.9] - 2021-12-02
### Changed
- RIGA-171: Updated site email address.

### Removed
- RIGA-6: Removed dependency for migrate_google_sheets module.
- RIGA-166: Removed feeds import mapping for CREATED field.
- RIGA-167: Removed core patch for 3020876.

## [0.7.8] - 2021-11-18
### Added
- RIGA-162: Added State Search CTA to acquia search results.
- RIGA-163: Added search state block.

### Changed
- RIGA-6: Update oomphinc/drupal-scaffold to 1.2.x branch.
- RIGA-166: Update Smart Date module to 3.4.x.

### Removed
- RIGA-6: Removed dependency for migrate_google_sheets module.
- RIGA-166: Removed feeds import mapping for CREATED field.

## [0.7.7] - 2021-11-04
### Added
- RIGA-160: Add Views Database Connector module to codebase.
- RIGA-151: Add Entity Print module to codebase.

### Fixed
- RIGA-161: Require johngrogg/ics-parser library to fix missing ICal error.

## [0.7.6] - 2021-10-21
### Added
- RIGA-143: Add and enable Media Revisions UI module.
- RIGA-154: Add CAPTCHA module to codebase.

## [0.7.5] - 2021-10-14
### Added
- RIGA-138: Added hook_install function to update null values of field_file_list_weight.
- RIGA-146: Added Asset Injector module to codebase.
- RIGA-111: Added new feature ecms_migration_file to support JSON file uploads.

### Changed
- RIGA-111: Updated migration configuration to use JSON source plugin to replace google sheets.

### Removed
- RIGA-111: Disabled the Migration Google Sheets module.

### Fixed
- RIGA-148: Fixed null argument error.
- RIGA-139: Fixed missing taxonomy permissions for site admin and content publisher.

## [0.7.4] - 2021-09-28
### Fixed
- RIGA-138: Added additional check in media item preprocessing to prevent error.

## [0.7.3] - 2021-09-16
### Added
- RIGA-132: Added sorting to file list by tag by adding a weight field to files.
- RIGA-133: Added edit link for authenticated users to media: file.

## [0.7.2] - 2021-09-09
### Changed
- RIGA-127: For eCMS Paragraphs feature, make media item reference field translatable.
- RIGA-129: Update permissions for form author and site admin to access webform overview.

### Removed
- RIGA-113: Add uninstall HTTP Cache Control module to update hook.

## [0.7.1] - 2021-09-01
### Added
- RIGA-104: Add File list by tag component.

## [0.7.0] - 2021-08-26
### Added
- RIGA-109: Add core patch from issue 2492171 to support filename transliteration.
- RIGA-112: Added theming for audio media items.
- RIGA-113: Include the HTTP Cache Control module.

### Security
- RIGA-106: Updated webform to 6.0.5 (from 6.0.3).
- RIGA-106: Updated Admin Toolbar to 3.x branch (from 2.x).

## [0.6.9] - 2021-08-19
### Added
- RIGA-104: Added File Tags taxonomy for media files.

## [0.6.8] - 2021-08-12
### Added
- RIGA-104: Added file description field to file media type.
- RIGA-104: Added translation handling for file media type in theme.

### Removed
- RIGA-105: Removed deprecated Acquia Search Solr module.

## [0.6.7] 2021-08-05
### Added
- RIGA-98: Added dependency for Acquia Search module.

### Changed
- RIGA-98: Updated Search API Solr to 4.2.x.

### Removed
- RIGA-98: Uninstall hook for the Acquia Search Solr module.

## [0.6.6] - 2021-07-29
### Added
- RIGA-26: Add modified media view with file link examples to config.

### Changed
- RIGA-76: Disable the history module on existing sites.
- RIGA-96: Update Drupal core to 9.2.2.

## [0.6.5] - 2021-07-15
### Added
- RIGA-26: Extend download media permission to anonymous and authenticated users.

### Changed
- RIGA-26: Add language var to download link for file list component url.
- RIGA-76: Views UI module disabled by default.
- RIGA-76: History module disabled by default.

### Removed
- RIGA-76: Honeypot time limit form protection (allows for page caching).

## [0.6.4] - 2021-07-01
### Added
- RIGA-26: Add Media Entity Download module and update file list component url.

## [0.6.3] - 2021-06-30
### Added
- RIGA-70: Added person additional fields taxonomy, field to person content type.
- RIGA-70: Added photo support to person teaser and additional preprocessing to person and person_list.
- RIGA-69: Add geolocation support for locations.
- RIGA-78: Add pathauto pattern for Webform content type.
- RIGA-74: Add theme setting for illustration and pass value to patternlab.

### Changed
- RIGA-21: Remove Admin Tabs Showing for "Unpublish This Translation".

### Removed
- RIGA-70: Removed promotions from person content type.

## [0.6.2] - 2021-06-23
### Added
- RIGA-78: Add pathauto pattern for Webform content type.
- RIGA-68: Add event list paragraph type.
- RIGA-68: Add events archive view.
- RIGA-68: Add page components to event content type.
- RIGA-18: Added patch to webform encrypt to support global encryption for a given form.

### Changed
- RIGA-90: Update (and lock) ACSF module to 2.69.
- RIGA-41: Update Webform content type to not display author info.

### Fixed
- RIGA-64: Fixed additional issues with publication list translation settings.
- RIGA-89: Fixed missing file icons by adding hook_install to copy files.

## [0.6.1] - 2021-06-03
### Fixed
- RIGA-85: Fixed issue with events displaying incorrect month.

## [0.6.0] - 2021-05-21
### Changed
- RIGA-57: Allow table tags within paragraph_text format.

## [0.5.9] - 2021-05-20
### Removed
- RIGA-62: Removed patch for smart_date to fix issue with all day dates.

## [0.5.8] - 2021-05-20
### Added
- RIGA-57: Allow table tags within basic_html format.
- RIGA-62: Support events that span multiple months in teaser view.
- RIGA-62: Added patch for smart_date to fix issue with all day dates.

### Changed
- RIGA-24: Update Drupal core from 9.0.x to 9.1.x.
- RIGA-24: Update Views Ajax Get module to 1.x-dev.
- RIGA-24: Restored functional tests for Github actions.
- RIGA-61: Changed events view to use end date rather than start date for filter.

### Fixed
- RIGA-63: Fixed frontend output for all day events.
- RIGA-77: Fixed events not showing registration url.
- RIGA-64: Fixed issues with publication list translation settings.

## [0.5.7] - 2021-04-22
### Added
- RIGA-35: Add acquia_search module.
- RIGA-43: Added mobile and fax fields to person.

### Changed
- RIGA-35: Moved Acquia Search and related config into new feature ecms_solr_search.
- RIGA-35: Acquia Connector is now installed by default but not required.

### Removed
- RIGA-35: Remove acquia_search_solr from install list.

### Fixed
- RIGA-52: Fixed IE11 not displaying color themes correctly.
- RIGA-56: Embed paragraph preview mode was breaking in admin.

## [0.5.6] - 2021-04-09
### Added
- RIGA-10: Added paragraph translation sync settings to person and location features.

### Changed
- RIGA-10: Enabled translation for icon_card paragraph.

### Fixed
- RIGA-54: Update hook 9056 renames all files without extensions in filename.

## [0.5.5] - 2021-04-05
### Added
- RIGA-10: Patch for paragraphs that introduces the Paragraph Translation Sync module (2887353).

### Changed
- RIGA-54: Updated file migration to use basename.

## [0.5.4] - 2021-03-19
### Added
- RIGA-51: Added migrate_devel module for debugging.

### Changed
- RIGA-42: Added zip to list of allowed file types for media type file.
- RIGA-51: Updated file and file redirect migrations.

## [0.5.3] - 2021-03-10
### Added
- RIGA-8: Updated icon field on icon card to use eCMS icon library.
- RIGA-47: Added executive order view and speech twig templates for teaser and full.
- RIGA-47: Added views_ajax_get contrib module.

### Fixed
- RIGA-45: Added check to custom language negotiation.

## [0.5.2] - 2021-03-04
### Fixed
- RIGA-38: Fixed missing config from 0.5.1 vaccine feature.

## [0.5.1] - 2021-03-04
### Added
- RIG-266: Added optional google translator module.
- RIG-276: Updated basic page migration to use basic_html format.
- RIG-260: Added speech view and speech twig templates for teaser and full.
- RIGA-14: Added location list paragraph and custom form.
- RIGA-14: Added website url and phone extension to location content type.
- RIGA-38: Added Vaccination Site Portal Build Out - Content type, View, geolocation
- RIG-274: Added person teaser theming.
- RIG-274: Added person list paragraph.

### Changed
- RIG-260: Changed field_speech_text to formatted long.
- RIG-274: Added person list paragraph bundle.
- RIG-277: Updated content moderation notification.
- RIG-274: Changed person content type field config.
- RIG-277: Updated content moderation notification message.
- RIG-271: Upgraded Acquia Search Solr to 3.x.
- RIG-271: Added excluded config (search_api_solr) to ecms_basic_page feature.

### Removed
- RIG-274: Removed several person content type fields that were unused.
- RIG-277: Remove patch from content_moderation_notifications issue 3170503.

### Fixed
- RIG-276: Update hook 9051 includes script to update text format to basic_html for all basic pages.

## [0.5.0] - 2021-02-18
### Fixed
- RIG-269: Broken promotion URLs.

## [0.4.9] - 2021-02-17
### Fixed
- RIG-254: Fixed missing module dependency media_library_form_element.

## [0.4.8] - 2021-02-17
### Added
- RIG-254: Added custom ecms_icon_library module.
- RIG-254: Added ecms_icon_library field formatter, widget, and type.
- RIG-254: Added icon button paragraph bundle.

### Changed
- RIG-269: Updated publication migration and url output to ensure no whitespace.
- RIG-269: Updated promotional image media item translation settings.

### Fixed
- RIG-269: Promotions Translation Issue, fixed teaser template to use current langcode.

## [0.4.7] - 2021-02-12
### Added
- RIG-251: Add ecms_feeds module which includes iCal parser and item.
- RIG-253: Add twig template for events related components.
- RIG-253: Add rrule library.
- RIG-188: Add virtual meeting and registration url fields to event.
- RIG-188: Add twig template for event full display.
- RIG-222: Add Acquia Solr Search modules, templates, and config.

### Changed
- RIG-257: Updated translation settings for misc paragraphs.
- RIG-253: Changed ecms_events to include smart_date, rrule, and events view.
- RIG-246: Updated extlink to exlcude top level domain.
- RIG-247: Ensure proper minimum cache lifetime.

## [0.4.6] - 2021-01-28
### Added
- RIG-238: Added numbered step item paragraph bundle, permissions, and preprocess function.

## [0.4.5] - 2021-01-21
### Added
- RIG-239: Added logo only theme setting.
- RIG-229: Added weight field to notifications.
- RIG-244: Added hook_cron to unpublish expired notifications.

## [0.4.4] - 2021-01-19
### Fixed
- RIG-220: Missing translation error in template files.
- RIG-242: Fixed article listing to work with views-view and views-unformatted.

## [0.4.3] - 2021-01-15
### Added
- RIG-233: Add URL encoding to pseudo_basename file redirect migration field.
- RIG-220: Allow admin accounts to specify language.
- RIG-223: Added header, image, and action block type.
- RIG-203: Added minimal text format.

### Changed
- RIG-233: Update hook_prepare_row for file redirect migration.
- RIG-220: Update translation settings for media item image and paragraph gallery items.

### Fixed
- RIG-220: Pass language code to paragraph templates for image rendering.
- RIG-202: Fixed content components missing block description.
- RIG-234: Fixed LB CSS issues with Safari and Firefox.
- RIG-223: Fixed Landing Page content type missing tabs on default layout.

## [0.4.2] - 2021-01-11
### Added
- RIG-212: Install and configure honeypot module.
- RIG-216: Add migration plugin to strip style attributes.

### Changed
- RIG-225: Add notifications block cache tags.

### Removed
- RIG-195: Removed core links field from all content types default display.

### Fixed
- RIG-221: Remove deprecated theme functions for search block.

## [0.4.1] - 2021-01-06
### Added
- RIG-130: Added permissions for gallery and gallery item paragraph bundles.

### Fixed
- RIG-217: Added patch to fix "Call to a member function getEntityTypeId() on null (Layout Builder)".

## [0.4.0] - 2021-01-05
### Added
- RIG-130: Added gallery and gallery item paragraph bundles.
- RIG-130: Added tiny-slider library.
- RIG-125: Added link behavior field to media files.

### Changed
- RIG-196: Updated migration documentation.

### Fixed
- RIG-192: Fixed some missing title errors during migration.
- RIG-207: Fixed the card component translation settings.
- RIG-125: Fixed file display in media library.

## [0.3.9] - 2020-12-18
### Added
- RIG-6: Added shivammathur/setup-php PHP setup action to develop workflow to lock php at 7.3.

### Changed
- RIG-130: Changed notifications block to use node langcode rather than the current users.

### Fixed
- RIG-205: Fixed the content author permissions to prevent 404 redirects.
- RIG-204: Fixed the php error preventing viewing node revisions.

## [0.3.8] - 2020-12-16
### Added
- RIG-177: Added the publisher module for publication nodes to go from hub to syndicated sites.

### Changed
- RIG-198: Update pathauto patterns to only apply to default language (EN).

### Removed
- RIG-198: Removed base field override config from ecms_promotions (metatag and menu_link).

### Fixed
- RIG-200: Fixed the cache contexts for the publication listing paragraph.

## [0.3.7] - 2020-12-16
### Added
- RIG-130: Added exposed form to control publication list paragraph display.

### Fixed
- RIG-176: Allowed the syndication to handle multiple referenced entities.
- RIG-130: Fixed issues with calling functions on null values.

## [0.3.6] - 2020-12-15
### Fixed
- RIG-130: Fixed syntax error in number_format.

## [0.3.5] - 2020-12-15
### Added
- RIG-130: Added lang filter for publication list.

### Changed
- RIG-171: Moved memcache to ecms_install hook.

### Fixed
- RIG-130: Fixed search url errors with NIDs of 4 digits.

## [0.3.4] - 2020-12-14
### Added
- RIG-171: Added memcache and associated config.

### Fixed
- RIG-130: Fixed url value of search result teasers.

## [0.3.3] - 2020-12-14
### Changed
- RIG-182: Updated publication language ordering.

### Fixed
- RIG-180: Fixed the theme settings to make them translatable.
- RIG-181: Gave the content author 'view all revisions' permission by default.
- RIG-184: Gave the site admin 'translate interface' permission by default.

### Security
- RIG-180: Fixed an XSS issue with the output of the theme settings variables.

## [0.3.2] - 2020-12-12
### Fixed
- RIG-6: PHP null error in publications preprocess.

## [0.3.1] - 2020-12-11
### Fixed
- RIG-179: Embed paragraph preview and default views swapped.

## [0.3.0] - 2020-12-10
### Added
- RIG-166: Added ecms_claro admin theme and set it as the default for the profile.
- RIG-175: Added links to the ecms_api_publisher site entity collection.
- RIG-130: Added card paragraph bundle.
- RIG-170: Added Acquia Purge and associated modules and configuration.

### Changed
- RIG-168: Updated moderation dashboard and workflow state labels.
- RIG-168: Updated paragraph preview modes and other misc admin form displays.
- RIG-169: Added the audience terms to the Covid migration.
- RIG-6: Moved update hooks into .install file.

### Fixed
- RIG-160: Fixed the missing contextual links on layout builder blocks allowing for translation.
- RIG-178: Updated user permissions and enabled the ecms_publications by default.

## [0.2.9] - 2020-12-08
### Added
- RIG-163: Added the role delegation module and updated site admin permissions.
- RIG-131: Added cache tags method to global promotions block.
- RIG-130: Added theming to support publications list.

### Removed
- RIG-130: Removed publications_list paragraph in favor of publication_list.

### Fixed
- RIG-141: Fix the rss import to ignore subcategories.

## [0.2.8] - 2020-12-04
### Added
- RIG-141: Added Covid site specific publication migration from an RSS feed.
- RIG-161: Added delete content permissions for the content publisher role.

## [0.2.7] - 2020-12-03
### Added
- RIG-145: Install and Configure Metatag Module.
- RIG-133: Install and configure simple XML sitemap module.
- RIG-132: Install and configure GTM module.
- RIG-130: Added promotion reference block type.
- RIG-130: Added background color field to content components block type.
### Changed
- RIG-161: Updated the default user role permissions.
### Fixed
- RIG-130: Fixed form settings missing for block types.

## [0.2.6] - 2020-12-01
### Added
- RIG-130: Added ecms_hotel_listing custom block plugin.
- RIG-130: Added teaser and full node templates for hotel content type.

### Changed
- RIG-130: Changed hotel rate fields to support percentages.

### Fixed
- RIG-106: Added patch to allow drush to run migration tools migrations.
- RIG-130: Fixed typo in ecms_base.profile update script.

## [0.2.5] - 2020-11-25
### Added
- RIG-156: Added paragraph permissions to anonymous and authenticated roles.

### Changed
- RIG-131: Added current language filter to search view.

## [0.2.4] - 2020-11-24
### Added
- RIG-131: Add Search API and database index.
- RIG-106: Added custom migrations from static websites.
- RIG-155: Add the Audio media item.
- RIG-131: Add custom eCMS search form.
- RIG-131: Theme search page.
- RIG-130: Add text card paragraph.
- RIG-130: Add text card collection block type.
- RIG-130: Add photo title with image block type.

### Fixed
- RIG-153: Updated the language session fix to only apply to entity forms.
- RIG-154: Change path aliases to always be language neutral.

## [0.2.3] - 2020-11-19
### Added
- RIG-130: Adds label to teaser notifications.

## [0.2.2] - 2020-11-18
### Added
- RIG-91: Adds the scheduled transitions config updates to the workflow bundle create class.
- RIG-130: Added generic block and field templates to remove drupal wrappers.
- RIG-130: Add easy_breadcrumbs module and configure.

### Removed
- RIG-130: Removes the Disable Language module dependency.
- RIG-91: Removes the Scheduler module dependency.

### Fixed
- RIG-149: Fixed the language detection to work with all entities.

## [0.2.1] - 2020-11-13
### Added
- RIG-130: Added eCMS Languages custom module.
- RIG-130: Added promotion refernce paragraph bundle.
- RIG-130: Added patch to allow svg to be uploaded for theme logos.

### Changed
- RIG-148: Changed the language detection method to session/cookie.
- RIG-130: Added promotion refernce paragraph bundle.
- RIG-130: Added patch to allow svg to be uploaded for theme logos.

### Changed
- RIG-130: Changed Global Promos to be in custom block rather than view.
- RIG-130: Changed External Link module config.
- RIG-130: Changed Press Release view to support transaltions.

### Removed
- RIG-130: Uninstalls the Disable Language module.
- RIG-91: Uninstall the Scheduler module.

## [0.2.0] - 2020-11-12
### Added
- RIG-130: Added disable language module.

### Fixed
- RIG-143: Fixed a class path php error.

## [0.1.9] - 2020-11-11
### Added
- RIG-142: Added social navigation automatic class functionality.
- RIG-69: Added promotion reference fields for locations, people, events, and press releases.
- RIG-81: Added press release syndication.
- RIG-130: Added custom site notifications block and theming.
- RIG-130: Added theming for social navigation menu.
- RIG-130: Added language switcher block.
- RIG-130: Added Header: Inner, Admin Links, User Settings regions.

### Changed
- RIG-139: CI Improvements.
- RIG-91: Added Scheduled Transitions module to most content types.
- RIG-130: Changed promotions and notifications to use rich text rather than plain text.
- RIG-130: Changed paragraphs text format to include source button.

### Removed
- RIG-130: Removed Header region.

## [0.1.8] - 2020-11-06
### Added
- RIG-67: Added promotion teaser theming.
- RIG-67: Sidebar nav menu preprocess to pass level one link.

### Changed
- RIG-69: Updated media item translation config and template.

## [0.1.7] - 2020-11-05
### Added
- RIG-91: Added scheduler permissions for publishers and site admins.
- RIG-67: Added Global Display and Display Title field to promotions.
- RIG-67: Added Promotion Entity Reference Filter view.
- RIG-67: Added ecms_blocks module and PromotionsNodeSpecific block.
- RIG-67: Added regular and responsive image style config from standard Drupal profile.
- RIG-67: Added Promotions - Global view.
- RIG-67: Added theming for promotions teaser display.
- RIG-67: Added twig_tweak contrib module.
- RIG-67: Added column container paragraph bundle.
- RIG-67: Added link field to media items.

### Changed
- RIG-67: Changed promotion body field to use plain text.
- RIG-67: Changed location of layout templates in PL.

### Removed
- RIG-67: Removed jQuery from eCMS theme.

### Fixed
- RIG-67: Fixed issue of promotional images using a duplicate source field.

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

[Unreleased]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/1.0.2...HEAD
[1.0.2]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.11.0...1.0.0
[0.11.0]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.10.9...0.11.0
[0.10.9]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.10.8...0.10.9
[0.10.8]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.10.7...0.10.8
[0.10.7]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.10.6...0.10.7
[0.10.6]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.10.5...0.10.6
[0.10.5]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.10.4...0.10.5
[0.10.4]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.10.3...0.10.4
[0.10.3]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.10.2...0.10.3
[0.10.2]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.10.1...0.10.2
[0.10.1]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.10.0...0.10.1
[0.10.0]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.31...0.10.0
[0.9.31]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.30...0.9.31
[0.9.30]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.29...0.9.30
[0.9.29]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.28...0.9.29
[0.9.28]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.27...0.9.28
[0.9.27]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.26...0.9.27
[0.9.26]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.25...0.9.26
[0.9.25]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.24...0.9.25
[0.9.24]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.23...0.9.24
[0.9.23]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.22...0.9.23
[0.9.22]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.21...0.9.22
[0.9.21]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.20...0.9.21
[0.9.20]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.19...0.9.20
[0.9.19]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.18...0.9.19
[0.9.18]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.17...0.9.18
[0.9.17]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.16...0.9.17
[0.9.16]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.15...0.9.16
[0.9.15]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.14...0.9.15
[0.9.14]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.13...0.9.14
[0.9.13]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.12...0.9.13
[0.9.12]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.11...0.9.12
[0.9.11]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.10...0.9.11
[0.9.10]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.9...0.9.10
[0.9.9]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.8...0.9.9
[0.9.8]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.7...0.9.8
[0.9.7]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.6...0.9.7
[0.9.6]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.5...0.9.6
[0.9.5]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.4...0.9.5
[0.9.4]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.3...0.9.4
[0.9.3]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.2...0.9.3
[0.9.2]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.1...0.9.2
[0.9.1]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.9.0...0.9.1
[0.9.0]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.8.9...0.9.0
[0.8.9]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.8.8...0.8.9
[0.8.8]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.8.7...0.8.8
[0.8.7]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.8.6...0.8.7
[0.8.6]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.8.5...0.8.6
[0.8.5]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.8.4...0.8.5
[0.8.4]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.8.3...0.8.4
[0.8.3]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.8.2...0.8.3
[0.8.2]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.8.1...0.8.2
[0.8.1]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.8.0...0.8.1
[0.8.0]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.7.9...0.8.0
[0.7.9]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.7.8...0.7.9
[0.7.8]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.7.7...0.7.8
[0.7.7]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.7.6...0.7.7
[0.7.6]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.7.5...0.7.6
[0.7.5]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.7.4...0.7.5
[0.7.4]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.7.3...0.7.4
[0.7.3]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.7.2...0.7.3
[0.7.2]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.7.1...0.7.2
[0.7.1]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.7.0...0.7.1
[0.7.0]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.6.9...0.7.0
[0.6.9]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.6.8...0.6.9
[0.6.8]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.6.7...0.6.8
[0.6.7]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.6.6...0.6.7
[0.6.6]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.6.5...0.6.6
[0.6.5]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.6.4...0.6.5
[0.6.4]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.6.3...0.6.4
[0.6.3]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.6.2...0.6.3
[0.6.2]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.6.1...0.6.2
[0.6.1]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.6.0...0.6.1
[0.6.0]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.5.9...0.6.0
[0.5.9]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.5.8...0.5.9
[0.5.8]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.5.7...0.5.8
[0.5.7]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.5.6...0.5.7
[0.5.6]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.5.5...0.5.6
[0.5.5]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.5.4...0.5.5
[0.5.4]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.5.3...0.5.4
[0.5.3]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.5.2...0.5.3
[0.5.2]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.5.1...0.5.2
[0.5.1]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.5.0...0.5.1
[0.5.0]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.4.9...0.5.0
[0.4.9]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.4.8...0.4.9
[0.4.8]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.4.7...0.4.8
[0.4.7]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.4.6...0.4.7
[0.4.6]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.4.5...0.4.6
[0.4.5]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.4.4...0.4.5
[0.4.4]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.4.3...0.4.4
[0.4.3]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.4.2...0.4.3
[0.4.2]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.4.1...0.4.2
[0.4.1]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.4.0...0.4.1
[0.4.0]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.3.9...0.4.0
[0.3.9]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.3.8...0.3.9
[0.3.8]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.3.7...0.3.8
[0.3.7]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.3.6...0.3.7
[0.3.6]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.3.5...0.3.6
[0.3.5]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.3.4...0.3.5
[0.3.4]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.3.3...0.3.4
[0.3.3]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.3.2...0.3.3
[0.3.2]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.3.1...0.3.2
[0.3.1]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.3.0...0.3.1
[0.3.0]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.2.9...0.3.0
[0.2.9]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.2.9...0.3.0
[0.2.8]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.2.7...0.2.8
[0.2.7]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.2.6...0.2.7
[0.2.6]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.2.5...0.2.6
[0.2.5]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.2.4...0.2.5
[0.2.4]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.2.3...0.2.4
[0.2.3]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.2.2...0.2.3
[0.2.2]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.2.1...0.2.2
[0.2.1]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.1.9...0.2.0
[0.1.9]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.1.8...0.1.9
[0.1.8]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.1.7...0.1.8
[0.1.7]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.1.6...0.1.7
[0.1.6]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.1.5...0.1.6
[0.1.5]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.1.4...0.1.5
[0.1.4]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.1.3...0.1.4
[0.1.3]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.1.2...0.1.3
[0.1.2]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.1.1...0.1.2
[0.1.1]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/State-of-Rhode-Island-eCMS/ecms_profile/releases/tag/0.1.0
