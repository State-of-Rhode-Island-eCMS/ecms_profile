# ecms_api_notification_publisher

The notification publisher module manages the logic for posting notifications
from the main hub to the syndicate sites. It uses the following hooks:

hook_ENTITY_TYPE_insert
hook_ENTITY_TYPE_update
hook_ENTITY_TYPE_translation_insert

It then calls a custom service that determines if the node is a
global notification and whether the moderation state is 'published'. If these
conditions are met, it syndicates the node into the queue for all currently
registered sites.

This module should only be installed manually on the HUB site, ri.gov.
