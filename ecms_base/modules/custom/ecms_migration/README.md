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

## Migrations included
### eCMS File
The eCMS File migration will take a sheet with URLs of existing files and create
the necessary Drupal file/media/redirect entities to ensure a seamless usage of
pre-existing links within the content.

### eCMS Basic Page
The eCMS Basic Page migration will browse to the URL provided in the Google Sheet
and will use the first <h1> tag on the page as the title of the node. It will
continue pulling the inner html from three css selectors and will append that
content to the
