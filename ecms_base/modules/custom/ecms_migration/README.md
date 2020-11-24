# ECMS Migration

The ecms_migration custom module will allow for migrations from static websites.
It does this by using Google Sheets with a list of URLs from the website that
is intended to by migrated into the eCMS system.

## URL Gathering
URL Gathering to generate the Google Sheet is a manual process. Testing has been
done with the Google Chrome Site Spider Mark II extension with success. This
extension will crawl the site and find all publicly accessible URLs.

From this list, a master URL Google Sheet can be created. This list can then
be filtered to pull out the file URLs from the page URLs.

## Migrations currently included
### eCMS File
The eCMS File migration will take a sheet with URLs of existing files and create
the necessary Drupal file/media/redirect entities to ensure a seamless usage of
pre-existing links within the content.

### eCMS Basic Page
The eCMS Basic Page migration will browse to the URL provided in the Google Sheet
and will use the first _h1_ tag on the page as the title of the node. It will
continue pulling the inner html from three user defined css selectors
and will append that content to the `field_basic_page_body` field.

## Google Sheets
The Google Sheet that is created _MUST_ be publicly accessible and published to
the web. This is accomplished by going to the `File > Publish to the web` menu.

Once the Google Sheet is published you will need to obtain the sheet id from the
URL. You can get this by editing the sheet and inspecting the URL. It will look
similar to the following:

`https://docs.google.com/spreadsheets/d/MY_SHEET_ID/edit#gid=0`

Copy the value of `MY_SHEET_ID` and paste that into the google_sheet_id field
of the appropriate migration at the following settings form:

`admin/structure/migrate/ecms_migration/settings`

## Migrating
Once all of your sheets and selectors have been created and saved as configuration
you can begin migrating. Click `Execute` next to the migration you would like
to run, select `import` and click execute. This will pull each url from
the Google Sheet and will create the appropriate Drupal entities.

## Adding New Migrations
When adding new migrations to this module you'll need to do a few things:

1. Create the migration and export the configuration to the `config/install` directory.
2. In the `ecms_migration.settings` configuration, add a new key with the values
   to be replaced in the migration.
3. In the `ecms_migration.migrations` configuration, add the same key from the
   settings file in #2 above, and the machine names of the migrations that were
   added to the config/install directory.

Doing the above will ensure the settings form will update the correct migration
configurations.

Also, note that the migration tools selector keys need to match the
ecms_migration.settings keys for proper replacement.
