# eCMS API Publisher

This module manages publishing content to other sites within the eCMS network.
It does this using the JSON API and a custom entity type.

## ecms_api_site Custom entity
The eCMS API Site custom entity stores the endpoints to publish content to and
the content types to send to those endpoints. These endpoints are entities and 
can be managed with the admin form at this path:

Add form: `/admin/config/ecms_api/ecms_api_publisher/site/add`
List: `/admin/config/ecms_api/ecms_api_publisher/sites`


## eCMS API Publisher Installation
Module installation does the following:
1. It creates a new user role, `ecms_api_publisher`.
2. It creates a new user, `ecms_api_publisher`.
3. It creates a new OAuth consumer that is scoped to the user and role created above.

### Configuration
On installation, the `ecms_api_publisher.settings` configuration is installed
and contains the client id, client secret and email address to use for the user
creation steps.

The client id and client secret are redacted by default and need to be managed
securely within `secrets.settings.php`.

```php
$config['ecms_api_publisher.settings']['oauth_client_id'] = 'SECURE-CLIENT-ID';
$config['ecms_api_publisher.settings']['oauth_client_secret'] = 'SECURE-CLIENT-SECRET';
$config['ecms_api_publisher.settings']['api_recipient_mail'] = 'automateduser@email.com';
```

#### oauth_client_id

The OAuth client id is the UUID for the consumer entity and should follow normal
UUID length and formats.

#### oauth_client_secret

The OAuth client secret is essentially the password that is used to allow access
to the consumer entity. Care should be taken to ensure that this secret 
is treated as a password with plenty of length and randomness.

The Client ID and the Client Secret above will be passed to oauth/token route
by the registering site to gain an access token with which to 
create an `ecms_api_site` entity.