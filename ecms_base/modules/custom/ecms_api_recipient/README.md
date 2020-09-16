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

### oauth_client_id
The OAuth client id is the UUID for the consumer entity and should follow normal
UUID length and formats.

### oauth_client_secret
The OAuth client secret is essentially the password that is used to allow
access to the consumer entity. Care should be taken to ensure that this secret 
is treated as a password with plenty of length and randomness.

The Client ID and the Client Secret above will be passed to `oauth/token` route
by the publishing site to gain an access token with which to create content.

## Allowing JSON API Content Creation
Content types can be toggled with the configuration form at: 
`admin/config/ecms_api/ecms_api_recipient/settings`. This will allow an admin
to select the content types to allow JSON API publishing. Once submitted, the
`ecms_api_recipient` role permissions are updated to allow the creation of the
selected nodes.
