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

Additionally, the publisher module requires the following configuration values
to connect to all sites that requested content to be syndicated

```php
$config['ecms_api_publisher.settings']['recipient_client_id'] = 'SECURE-CLIENT-ID';
$config['ecms_api_publisher.settings']['recipient_client_secret'] = 'SECURE-CLIENT-SECRET';
```

#### oauth_client_id

The OAuth client id is saved as both the client_id and the UUID for the consumer entity and should follow normal
UUID length and formats.

#### oauth_client_secret

The OAuth client secret is essentially the password that is used to allow access
to the consumer entity. Care should be taken to ensure that this secret
is treated as a password with plenty of length and randomness.

The Client ID and the Client Secret above will be passed to oauth/token route
by the registering site to gain an access token with which to
create an `ecms_api_site` entity.

#### recipient_client_id
This is the oauth client id that is used to connect to sites in order to post
nodes to them.

#### recipient_client_secret
This is the oauth client secret to connect to the recipient sites to post
content with the json api.

#### recipient_client_scope
This is the scope to provide to the recipient site during json api requests.

## Secondary Hubs.
The eCMS Api Publisher module can be used to setup secondary hub sites. For example,
if the Department of Health wanted to be a hub for content to post to the Covid site,
you would do the following:

1. Enable the ecms_api_publisher module on the Department of Health site.
2. On the Department of Health site, browse to `/admin/config/ecms_api/ecms_api_publisher/sites` and
   manually register all sub-sites that should receive content from this secondary hub.
3. Ensure the `ecms_api_recipient` module is enabled on all secondary sites (this is the default on install).
4. On each secondary site, ensure the `ecms_api_recipient` role has the appropriate permissions to create all entities
   that are being syndicated, be sure to include referenced entities and text formats.
5. As of writing, only the publications and notifications can be syndicated.
6. See the `ecms_api_publication_publisher` module for an example of syndicating other content types.
