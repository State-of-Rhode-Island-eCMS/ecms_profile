# eCMS API Recipient

This module sets up and manages incoming connections from hub sites to allow
for syndicated content from the hub site(s). It manages this by installing the
following:

1. It creates a new user role, `ecms_api_recipient`.
2. It creates a new user, `ecms_api_recipient` that is scoped to the above role.
3. It creates a new OAuth consumer that is scoped to the user and
   role created above.

## Configuration
On installation, the `ecms_api_recipient.settings` configuration is installed
and contains the client id, client secret, email address for the user created in
step 2 above and the allowed content types to accept syndicated content.

The client id, client secret are redacted and need to be managed with secure
configuration overrides. This can be accomplished with the following snippet
within the `secrets.settings.php`:

```php
$config['ecms_api_recipient.settings']['oauth_client_id'] = 'SECURE-CLIENT-ID';
$config['ecms_api_recipient.settings']['oauth_client_secret'] = 'SECURE-CLIENT-SECRET';
$config['ecms_api_recipient.settings']['api_recipient_mail'] = 'automateduser@email.com';
```

Additional configuration has been provided to allow for automated
registration with the main site hub. These settings should be managed within
the `secrets.settings.php` file and should be the same values as the
[ecms_api_publisher settings](../ecms_api_publisher/README.md).

```php
$config['ecms_api_recipient.settings']['api_main_hub'] = 'https://your-hub-site.com';
$config['ecms_api_recipient.settings']['api_main_hub_client_id'] = 'SECURE-PUBLISHER-ID';
$config['ecms_api_recipient.settings']['api_main_hub_client_secret'] = 'SECURE-PUBLISHER-SECRET';
```

### oauth_client_id
The OAuth client id is the UUID for the consumer entity and should follow normal
UUID length and formats.

### oauth_client_secret
The OAuth client secret is essentially the password that is used to allow
access to the consumer entity. Care should be taken to ensure that this secret
is treated as a password with plenty of length and randomness.

The Client ID and the Client Secret above will be passed to `oauth/token` route
by the publishing site to gain an access token with which to create content.

### api_main_hub
This is the URL to the main hub site.

### api_main_hub_client_id
This is the client id to access to hub site. This value should be the same as
the `oauth_client_id` value provided in the *ecms_api_publisher* module.

### api_main_hub_client_secret
This is the client secret to access the hub site. This value should be the same
as the `oauth_client_secret` value provided in the *ecms_api_publisher* module.

### api_main_hub_scope
This is the machine name of the user role that is provided
by the ecms_api_publisher module.

## Allowing JSON API Content Creation
Content types can be toggled with the configuration form at:
`admin/config/ecms_api/ecms_api_recipient/settings`. This will allow an admin
to select the content types to allow JSON API publishing. Once submitted, the
`ecms_api_recipient` role permissions are updated to allow the creation of the
selected nodes.

## Notification Hub Retrieval
On installation, the site will call back to the hub and retrieve all currently
published notifications using the Json API. It does this by querying the API
for a paged amount of notifications. Those notifications will be queued for
creation. If a next page exists, that page will be queued. Cron will query the
second page, and create the remaining notifications. If another page of
notifications exist, this process will repeat.
