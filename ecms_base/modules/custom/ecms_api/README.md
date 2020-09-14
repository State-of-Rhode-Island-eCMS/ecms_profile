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
openssl rsa -in private.key -pubout > public_key.key
```

### Json API
The json api is configured to allow for CRUD operations by default.

### Json API Extras
This configures the endpoint for the API as `/EcmsAPI/`.
