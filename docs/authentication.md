---
title: Authentication
tags:
  - config
  - drupal
  - active directory
  - openid_connect
  - openid_connect_windows_aad
---
# Authentication

Authentication is assumed to be managed with a web application created within
an Azure Active Directory instance. This is done utilizing the [OpenID Connect Microsoft Azure Active Directory client module](https://www.drupal.org/project/openid_connect_windows_aad)
and a web application that is configured in the Active Directory instance.
The ecms_base installation profile will install the OIDC AAD module by default and setup the
connection using REDACTED values.

## Settings
Settings for the OIDC connection to Active Directory will be maintained in
`secrets.settings.php`. This file should never be maintained in version control.

The `secrets.settings.php` file will be manually created in each ACSF environment
allowing for different connections depending on the environment. [More information
regarding the secrets.settings.php file in ACSF can be found here](https://docs.acquia.com/resource/secrets/#secrets-settings-php-file).

The settings for OIDC AAD should resemble this:
```php
// D10 OIDC Settings.
$config['openid_connect.client.windows_aad']['settings']['client_id'] = 'CLIENT_ID_OVERRIDDEN';
// This line must be removed so the `key` integration works.
// $config['openid_connect.client.windows_aad']['settings']['client_secret'] = 'CLIENT_SECRET_OVERRIDDEN';
$config['openid_connect.client.windows_aad']['settings']['authorization_endpoint_wa'] = 'https://AUTHORIZATION_URL_ENDPOINT_NEEDED/oauth2/v2.0/authorize';
$config['openid_connect.client.windows_aad']['settings']['token_endpoint_wa'] = 'https://TOKEN_ENDPOINT_NEEDED/oauth2/v2.0/token';
putenv("ECMS_WINDOWS_AAD_CLIENT_SECRET=CLIENT_SECRET_OVERRIDDEN");
```

Note: Since `drupal/openid_connect_windows_aad:^2.0-beta`, the client secret
became an environment variable and _must_ use the key module.

## Managing multiple sites
Allowing multiple sites to use this single OIDC connection will require that a new
redirect URL be added to the application within Active Directory. The redirect
URL should be:

`https://new.website.com/openid-connect/windows_aad`

## Drupal Roles
Groups created in AAD will be mapped to roles in Drupal via the group
name, and the role name _only if_ those roles exist in Drupal. Any Drupal role
that is manually applied to a user will remain the next time a user
authenticates with AAD. The only role that will _not remain_ is the
`Drupal_Admin` role. If a user is manually given the `Drupal_Admin` role
in Drupal and the user does not have that group in AAD. On their next login,
they will be removed from the `Drupal_Admin` role. All other Drupal roles
will remain with the account.

### Role Assumptions
It is to be assumed that the AAD group `Drupal_Admin` will be mapped to the Drupal role
titled `Drupal_Admin`. If a user authenticates and has this group, the user will
be allowed to administer _ALL SITES_ in the system.

### Site access
Site access will be determined by AAD role identified by the site's URI.
Any user who authenticates through AAD and is NOT in the AAD group `Drupal_Admin`
will be required to have a group name that matches domain name of the site.
If the user does not have a group with the domain name of the site, they will be denied access.

## Azure Active Directory Application Configuration
In order to create the necessary AAD application one must:
1. Login to `portal.azure.com`
2. Browse to `Manage Azure Active Directory`
3. Choose the appropriate tenant to create the Drupal application
4. Choose `App Registrations` from the menu
5. Click `New Registration` from the top menu
6. Name the new application e.g. `eCMS Application`
7. Choose the appropriate account types allowed to use the application
   (likely Single tenant).
8. Add an initial `Web` redirect URI pointing to a production URI:
   https://new.website.com/openid-connect/windows_aad
9. Copy the `Application (client) ID`
9. Browse to `Endpoints` and copy the following values:
     - `OAuth 2.0 authorization endpoint (v1)`
     - `OAuth 2.0 token endpoint (v1)`
10. Browse to `Certificates & Secrets`
     - Create a `New Client Secret` that never expires and copy that value.
11. Browse to `API Permissions`
     - Click `Add Permission`
     - Choose the `Microsoft Graph` API
     - Select `Delegated Permissions`
     - Under `Directory` select:
         - Directory.AccessAsUser.All
         - Directory.Read.All
     - Save these new API permissions
     - Grant Admin Approval for the new API permissions.
12. Save the copied values to the `secrets.settings.php` file on the ACSF environments.




