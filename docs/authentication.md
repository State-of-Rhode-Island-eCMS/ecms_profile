---
title: Authentication
tags:
  - config
  - drupal
  - active directory
  - openid_connect
---
# Authentication

Authentication is assumed to be managed with a web application created within
an Active Directory instance. This is done utilizing the [openid_connect module](https://www.drupal.org/project/openid_connect)
and a web application that is configured in the Active Directory instance. 
The ecms_base installation profile will install the OIDC module by default and setup the 
generic connection using REDACTED values by default.

## Settings
Settings for the OIDC connection to Active Directory will be maintained in
`secrets.settings.php`. This file should never be maintained in version control.

The `secrets.settings.php` file is manually placed into each ACSF environment
allowing for different connections depending on the environment. [More information
regarding the secrets.settings.php file in ACSF can be found here](https://docs.acquia.com/resource/secrets/#secrets-settings-php-file).

The settings for OIDC should resemble this:
```php
$config['openid_connect.settings.generic']['settings']['client_id'] = 'CLIENT_ID_NEEDED';
$config['openid_connect.settings.generic']['settings']['client_secret'] = 'CLIENT_SECRET_NEEDED';
$config['openid_connect.settings.generic']['settings']['authorization_endpoint'] = 'https://AUTHORIZATION_URL_ENDPOINT_NEEDED/oauth2/v2.0/authorize';
$config['openid_connect.settings.generic']['settings']['token_endpoint'] = 'https://TOKEN_ENDPOINT_NEEDED/oauth2/v2.0/token';
$config['openid_connect.settings.generic']['settings']['userinfo_endpoint'] = 'https://graph.microsoft.com/oidc/userinfo';
```

## Managing multiple sites
Allowing multiple sites to use this single OIDC connection will require that a new
redirect URL be added to the application within Active Directory. The redirect
URL should be:

`https://new.website.com/openid-connect/generic`
