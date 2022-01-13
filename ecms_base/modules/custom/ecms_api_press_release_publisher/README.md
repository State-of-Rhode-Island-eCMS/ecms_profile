# ecms_api_press_release_publisher

The press release publisher module manages the logic for posting press
releases to the main hub. It uses the following hooks:

hook_ENTITY_TYPE_insert
hook_ENTITY_TYPE_update


It then calls the main ecms_api_publisher.syndicate service that queues
up the node for syndicatino.

