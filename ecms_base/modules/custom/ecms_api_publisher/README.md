# eCMS API Publisher

This module manages publishing content to other sites within the eCMS network.
It does this using the JSON API and a custom entity type.

## ecms_api_site Custom entity
The eCMS API Site custom entity stores the endpoints to publish content to and
the content types to send to those endpoints. These endpoints are entities and 
can be managed with the admin form at this path:

Add form: `/admin/config/ecms_api/ecms_api_publisher/site/add`
List: `/admin/config/ecms_api/ecms_api_publisher/sites`
