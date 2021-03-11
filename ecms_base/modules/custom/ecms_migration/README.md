# ECMS Migration

The ecms_migration custom module will allow for migrations from static websites.
It does this by using Google Sheets with a list of URLs from the website that
is intended to by migrated into the eCMS system.

## URL Gathering
URL Gathering to generate the Google Sheets is a manual process, utilizing the
[Screaming Frog] app. After installing the program and entering the license key,
simply enter the primary domain name of the site you want to crawl, and click
start. Once the crawl is complete, you can filter the URL list by type to
separate the files list from the main pages. Be sure to exclude any assets
such as JS and CSS. Using the status code field to identify all 200 response
codes, you should be able to obtain your final lists. Simply copy and paste
the lists into a new Google Sheet, one sheet for each of HTML pages and files.
Each sheet should contain 2 columns with the headers "Url" and "Status" where
Status will be mapped to the Published field during migration. It's expected
that most pages will have a status value of 1.

### Example Sheets
[Example Pages]

[Example Files]


### Exclude List
To configure a list of excluded urls in Screaming Frog, navigate
to "Configuration > Exclude." A dialog will open where you can enter any
single url, or a regular expression.

### Files list and URL encoding
During early testing some issues with handling URL encoded file names were
encountered. To address this, the files lists should be cleared of all "%20"
encoded spaces, and simply replaced with a space. The resulting file and file
redirect imports will encode the path automatically.


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

### eCMS Publications
The eCMS Publication migration will generate `publication` nodes and will accept
a Google Sheet with the following columns:
- Title (The title of the link and node)
- Language (The language of the publication)
- Url (The link to the external publication)
- Type (The type taxonomy for the publication)

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

## Using Drush to complete problem migrations
If migrations are not completing using the UI, it may be necessary to
use drush to complete them. Here is an example set of steps and commands.
1. SSH into server (e.g. `ssh riecms.01live@web-4876.enterprise-g1.hosting.acquia.com`)
2. Browse to webroot (e.g. `cd /var/www/html/riecms.01live/docroot`)
3. Execute migrate-import command
   * (e.g. `drush10 migrate:import ecms_basic_page --uri=https://eohhs.riecms.acsitefactory.com`)

As with all drush commands on Site Factory, the `--uri` argument must be passed in
     order to target the intended site.
Other useful migration commands
* `drush10 migrate:stop`
* `drush10 migrate:reset`
* `drush10 migrate:stop`

## Validating File Redirects
In addition to crawling a site, Screaming Frog can also be used in "[List Mode]"
to test the resulting migration. Simply switch to list mode ("Mode > List"), then
paste in all the URLs you want to test. To generate the list,
take the original source list of URLs, and replace the domain (e.g. http://eohhs.ri.gov/)
with the new test domain (e.g. https://eohhs.riecms.acsitefactory.com/). Then
execute the crawl, and look for any 404 response codes to identify problem pages
or redirects.


[Screaming Frog]: https://www.screamingfrog.co.uk/seo-spider/
[List Mode]: https://www.screamingfrog.co.uk/how-to-use-list-mode/
[Example Pages]: https://docs.google.com/spreadsheets/d/1ajAEB86ZbTt9NT4NPctFTMdU5DVIWYOWmp8SJDz3PHQ/edit#gid=0
[Example Files]: https://docs.google.com/spreadsheets/d/1jwD_m-HC3depOVZeALE9b6tunZCAtHJCfhwVv16UYiM/edit#gid=0
