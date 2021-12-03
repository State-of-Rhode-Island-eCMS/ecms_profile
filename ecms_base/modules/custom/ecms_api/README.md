# ECMS API

The ecms_api module manages configuring the core JSON API, [JSON API Extras](https://www.drupal.org/project/jsonapi_extras)
and the [Simple Oauth](https://www.drupal.org/project/simple_oauth) modules for
use with the eCMS profile.

## Configuration

### Simple Oauth
The simple oauth module will need to have public/private keys generated
on the server. These file paths are pointing to `../`, however, these will need
to be overridden with the following `secrets.settings.php`
on the ACSF environment:

```php
$config['simple_oauth.settings']['public_key'] = '/path/to/public_key.key';
$config['simple_oauth.settings']['private_key'] = '/path/to/private_key.key';
```

The public/private keys need to be generated with the following commands:

```bash
openssl genrsa -out private_key.key 2048
openssl rsa -in private_key.key -pubout > public_key.key
```

### Json API
The json api is configured to allow for CRUD operations by default.

### Json API Extras
This configures the endpoint for the API as `/EcmsAPI/`.

### EcmsApiBase
The EcmsApiBase class is the base class that manages getting access tokens from
the sites and syndicating entities to other sites. This class is abstract by
default as other services will extend this class to build upon this functionality.

## Testing

### Local Testing
Enable the eCMS Press Release Publisher module.

Generate a local keypair in the root of /develop-ecms-profile as noted above.
Set the path to your new keys here: /admin/config/people/simple_oauth

Add the publisher and recipient client id and secret values for OAuth in the sites/default/settings.php file
of /develop-ecms-profile.

```php
// Ecms API Variables.
  $recipient_client_id = 'GET_VALUE_FROM_1PASS';
  $recipient_client_secret = 'GET_VALUE_FROM_1PASS';
  $publisher_client_id = 'GET_VALUE_FROM_1PASS';
  $publisher_client_secret = 'GET_VALUE_FROM_1PASS';

  // Ecms Api Publisher settings.
  $config['ecms_api_publisher.settings']['oauth_client_id'] = $publisher_client_id;
  $config['ecms_api_publisher.settings']['oauth_client_secret'] = $publisher_client_secret;
  $config['ecms_api_publisher.settings']['api_recipient_mail'] = 'ecms_api_publisher@ecms.com';
  $config['ecms_api_publisher.settings']['recipient_client_id'] = $recipient_client_id;
  $config['ecms_api_publisher.settings']['recipient_client_secret'] = $recipient_client_secret;

  // Ecms API Recipient settings.
  $config['ecms_api_recipient.settings']['oauth_client_id'] = $recipient_client_id;
  $config['ecms_api_recipient.settings']['oauth_client_secret'] = $recipient_client_secret;
  $config['ecms_api_recipient.settings']['api_recipient_mail'] = 'ecms_api_recipient@ecms.com';
  $config['ecms_api_recipient.settings']['api_main_hub'] = 'http://hub.dev-riecms.acsitefactory.com';
  $config['ecms_api_recipient.settings']['api_main_hub_client_id'] = $publisher_client_id;
  $config['ecms_api_recipient.settings']['api_main_hub_client_secret'] = $publisher_client_secret;
```


Register a new syndicated site here:
`admin/config/ecms_api/ecms_api_publisher/site/add`
It is recommended to add a site on the DEV or TEST environments. The API endpoint value is simply
the main URL of the site (no trailing slash), e.g. `https://hub.test-riecms.acsitefactory.com`

Check the content types you wish to syndicate, check Press Release at a minimum.

Create or edit a press release node and confirm it is created on the target site.
