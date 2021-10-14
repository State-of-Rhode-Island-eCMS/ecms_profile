# ECMS Migration

The ecms_migration custom module will allow for migrations from static websites.
It does this by using a JSON source file with a list of URLs from the website that
is intended to by migrated into the eCMS system.

## URL Gathering
URL Gathering to generate the JSON file is a manual process, utilizing the
[Screaming Frog] app. After installing the program and entering the license key,
simply enter the primary domain name of the site you want to crawl, and click
start. Once the crawl is complete, you can filter the URL list by type to
separate the files list from the main pages. Be sure to exclude any assets
such as JS and CSS. Using the status code field to identify all 200 response
codes, you should be able to obtain your final lists. Simply copy and paste
the lists into a new CSV file or spreadsheet, one file for each of HTML pages
and files. Each sheet should contain 2 columns with the headers "Url" and
"Status" where Status will be mapped to the Published field during migration.
It's expected that most pages will have a status value of 1.
Once you have the CSV saved, use a utility to convert it to JSON.
E.g. https://www.convertcsv.com/csv-to-json.htm

### Existing Broken Links
This initial report will identify any existing broken links on the site.
This 404 list should be provided to the department for their review.

### Example Sheets
[Example Pages]

[Example Files]


### Exclude List
To configure a list of excluded urls in Screaming Frog, navigate
to "Configuration > Exclude." A dialog will open where you can enter any
single url, or a regular expression.

### Files list and URL encoding
During early testing some issues with handling URL encoded file names were
encountered. To address this, the migration scripts have been updated to
first decode, and then transliterate the file paths. Finally, all spaces
are replaced with hyphens.


## Migrations currently included
### eCMS File
The eCMS File migration will take a sheet with URLs of existing files and create
the necessary Drupal file/media/redirect entities to ensure a seamless usage of
pre-existing links within the content.

### eCMS Basic Page
The eCMS Basic Page migration will browse to the URL provided in the JSON file
and will use the first _h1_ tag on the page as the title of the node. It will
continue pulling the inner html from three user defined css selectors
and will append that content to the `field_basic_page_body` field.

### eCMS Publications
The eCMS Publication migration will generate `publication` nodes and will accept
a JSON file with the following fields:
- Title (The title of the link and node)
- Language (The language of the publication)
- Url (The link to the external publication)
- Type (The type taxonomy for the publication)

## JSON source files
The resulting JSON files that are created _MUST_ be publicly accessible and published to
the web. You can upload the file to any web server.

Once the file is published you will need to add the file URL
to the appropriate migration at the following settings form:

`admin/structure/migrate/ecms_migration/settings`

## Migrating
Once all of your file URLs and selectors have been created and saved as configuration
you can begin migrating. Click `Execute` next to the migration you would like
to run, select `import` and click execute. This will pull each url from
the JSON file and will create the appropriate Drupal entities.

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
* `drush10 migrate:rollback`

## Validating File Migrations
In addition to crawling a site, Screaming Frog can also be used in "[List Mode]"
to test the resulting migration. Simply switch to list mode ("Mode > List"), then
paste in all the URLs you want to test. To generate the list,
take the original source list of URLs, and replace the domain (e.g. http://eohhs.ri.gov/)
with the new test domain (e.g. https://eohhs.riecms.acsitefactory.com/). Then
execute the crawl, and look for any 404 response codes to identify problem pages
or redirects.

### Validating the file redirects
Using the list mode above, you should be able to confirm all file redirects are
in place. One of the result columns of the crawl is "Redirect URL." Use that column
to create a new list, which should then be crawled and produce 200 OK responses.
Any missing files or incorrect redirects should be identified by a 404.


[Screaming Frog]: https://www.screamingfrog.co.uk/seo-spider/
[List Mode]: https://www.screamingfrog.co.uk/how-to-use-list-mode/
[Example Pages]: https://docs.google.com/spreadsheets/d/1ajAEB86ZbTt9NT4NPctFTMdU5DVIWYOWmp8SJDz3PHQ/edit#gid=0
[Example Files]: https://docs.google.com/spreadsheets/d/1jwD_m-HC3depOVZeALE9b6tunZCAtHJCfhwVv16UYiM/edit#gid=0
