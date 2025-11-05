# eCMS API Recipient Recipe

This Drupal recipe configures a site to receive notifications from a hub site within the eCMS network.

## What This Recipe Does

1. **Installs Required Modules**: Installs `ecms_api_recipient` module (which brings in dependencies: `ecms_api`, `ecms_notification`, `ecms_workflow`, `simple_oauth`)

2. **Creates User Role**: Creates the `ecms_api_recipient` user role with all necessary permissions for content creation, moderation, translation, and API access

3. **Creates OAuth2 Scope**: Creates the `ecms_api_recipient` OAuth2 scope with:
   - Client credentials grant type enabled
   - Tied to the `ecms_api_recipient` role

4. **Creates API User**: Creates a user account named `ecms_api_recipient` with the appropriate role

5. **Creates OAuth Consumer**: Creates an OAuth consumer entity for API authentication with dynamically configured client ID and secret

## Recipe Inputs

The recipe accepts the following inputs (can be provided via environment variables or will fall back to existing config):

- **oauth_client_id**: OAuth client ID for the recipient
  - Environment variable: `ECMS_RECIPIENT_CLIENT_ID`
  - Fallback: `ecms_api_recipient.settings` config key `oauth_client_id`

- **oauth_client_secret**: OAuth client secret for the recipient
  - Environment variable: `ECMS_RECIPIENT_CLIENT_SECRET`
  - Fallback: `ecms_api_recipient.settings` config key `oauth_client_secret`

- **api_recipient_mail**: Email address for the API recipient user
  - Source: `ecms_api_recipient.settings` config key `api_recipient_mail`

## Applying the Recipe

### Using Environment Variables

```bash
export ECMS_RECIPIENT_CLIENT_ID="your-client-id"
export ECMS_RECIPIENT_CLIENT_SECRET="your-client-secret"
php core/scripts/drupal recipe ecms_base/recipes/ecms_api_recipient
```

### Using Command-line Options

```bash
php core/scripts/drupal recipe ecms_base/recipes/ecms_api_recipient \
  --input=ecms_api_recipient.oauth_client_id=your-client-id \
  --input=ecms_api_recipient.oauth_client_secret=your-client-secret
```

### Using Existing Configuration

If `ecms_api_recipient.settings` config already exists, the recipe will use those values:

```bash
php core/scripts/drupal recipe ecms_base/recipes/ecms_api_recipient
```

## Comparison with Module Installation

Previously, the `ecms_api_recipient` module's `hook_install()` created the user, consumer, and other entities programmatically. With this recipe:

- **Role creation** is now declarative via configuration actions (no strict config checking)
- **OAuth2 scope** is created via configuration import
- **User and consumer** are created as default content with dynamic token replacement
- All entities can be version-controlled and consistently deployed across environments

## Files Structure

```
ecms_base/recipes/ecms_api_recipient/
├── recipe.yml                          # Main recipe definition with inputs and actions
├── config/
│   └── simple_oauth.oauth2_scope.ecms_api_recipient.yml  # OAuth2 scope configuration
└── content/
    ├── user/
    │   └── ecms-api-recipient-user.yml     # API user account
    └── consumer/
        └── ecms-api-recipient-consumer.yml  # OAuth consumer entity
```
