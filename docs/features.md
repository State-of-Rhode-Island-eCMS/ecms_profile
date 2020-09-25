# Features

Certain site configuration and functionality will be provided by the [Features module].
This is done to segment certain configuration to allow for some control over the configuration of the sites
that are installed with the profile.

Currently, the content types and their related configuration (fields, display modes, etc.)
will be maintained with Features.

Features will be stored within the following directory: 

```bash
./ecms_profile/*/features/custom
```

## Current Features list
The current list of features is:

### ecms_notification
This contains the notification content type and the related field configuration.

### ecms_press_release
This contains the press_release content type and the related field configuration.

### ecms_event
This contains the event content type and the related field and taxonomy configuration.

### ecms_promotions
This contains the promotions content type and the related field configurations.

### ecms_basic_page
This contains the basic_page content type and the related field configurations.

### ecms_landing_page
This contains the landing page content type and the related field configurations.

### ecms_publications
This contains the publication content type and the related field configurations.

## Development Notes
Developing with features can be tricky. A few items to remember:

- Enable the Features UI module during local development.

- To work with existing project features, browse to /admin/config/development/features and select the "eCMS" bundle.

- Features should only contain configuration that belong together and _should not_ share configuration with other features.
For example, if all content types share the same editorial workflow, that workflow will _NOT_ be able to be packaged
into one or each feature. Instead, that configuration needs to be left out of the feature and a helper module will need to connect
that configuration to each content type.

- Features should not share fields. If two content types both contain a `body` field. That field _MUST_ be created for each content type. Typically, the field should prefix or relate
to the content type to which it belongs. In this example, a Notification and a Press release content type, two fields would be created `field_notification_body` and `field_press_release_body`.

[Online Documentation on Features] is available and gives a good overview of how to use it effectively.
    
## Deployment
On deployment an existing site will revert all features to what is in the codebase. So, if a site admin changes the field of a specific
content type, on the next update, those changes will revert to what is in the feature module.


[Features module]: https://www.drupal.org/project/features
[Online Documentation on Features]: https://www.drupal.org/docs/contributed-modules/features
