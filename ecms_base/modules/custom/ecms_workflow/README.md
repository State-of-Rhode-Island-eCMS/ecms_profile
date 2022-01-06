# ECMS Workflow

The ecms_workflow custom module provides functionality to help manage
the editorial workflow settings for content types. It provides the
default workflow configuration, and implements `hook_entity_bundle_create()`
to automate the application of the preferred workflow to all new content types.

## Exclusion

Not all content types are managed by the default workflow. The current
exceptions are press release and notifications.

