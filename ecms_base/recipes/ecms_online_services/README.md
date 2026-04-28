# eCMS Online Services Recipe

This Drupal recipe creates the **Online Service** content type for RI.gov — a structured link-and-description entry representing a service offered by a Rhode Island state agency or municipality.

## What This Recipe Does

1. **Installs Required Modules**: `node`, `field`, `text`, `link`, `options`, `taxonomy`, `metatag`, `content_moderation`, `language`, `content_translation`, `rabbit_hole`

2. **Creates the Content Type**: Imports `node.type.online_service` with:
   - Title field relabeled to "Service Name"
   - Promote-to-front-page default set to off
   - Rabbit Hole redirect behavior configured (no direct node page)

3. **Creates the Taxonomy Vocabulary**: Imports `taxonomy.vocabulary.online_services` for categorizing services, with Rabbit Hole redirect disabled

4. **Creates Field Storages** (strict — these define the database schema):
   - `field_agency_or_muni` — `list_string`: dropdown of state agencies and municipalities
   - `field_service_description` — `text_long`: body copy for the service
   - `field_service_type` — `entity_reference` → `taxonomy_term` (`online_services` vocabulary)
   - `field_service_url` — `link`: the URL to the external service
   - `field_meta_tags_online_services` — `metatag`: SEO meta tags

5. **Creates Field Instances** on `node.online_service`:
   - `field_agency_or_muni` — Agency or Muni (not translatable)
   - `field_service_description` — Service Description (required, not translatable)
   - `field_service_type` — Online-Service Taxonomy (not translatable)
   - `field_service_url` — Service URL (not translatable)
   - `field_meta_tags_online_services` — Meta Tags Online Services (not translatable)

6. **Configures View Displays**:
   - **default**: Shows description, URL (URL-only), agency, and service type
   - **teaser**: Shows description and URL; hides agency, type, and meta tags
   - **search_result**: Shows URL, description, search excerpt, type (linked), and agency

7. **Configures the Form Display**: Ordered edit form with moderation state widget

8. **Configures Language & Translation**:
   - Node bundle: translation-enabled, language-alterable
   - Taxonomy vocabulary: not translatable

## Prerequisites

- The editorial workflow (`workflows.workflow.editorial`) must exist before applying this recipe, so the `moderation_state` form widget can attach correctly.

## Applying the Recipe

```bash
ddev drush recipe ecms_base/recipes/ecms_online_services
```

## File Structure

```
ecms_base/recipes/ecms_online_services/
├── recipe.yml
└── config/
    ├── node.type.online_service.yml
    ├── taxonomy.vocabulary.online_services.yml
    ├── core.base_field_override.node.online_service.title.yml
    ├── core.base_field_override.node.online_service.promote.yml
    ├── field.storage.node.field_agency_or_muni.yml
    ├── field.storage.node.field_service_description.yml
    ├── field.storage.node.field_service_type.yml
    ├── field.storage.node.field_service_url.yml
    ├── field.storage.node.field_meta_tags_online_services.yml
    ├── rabbit_hole.behavior_settings.node_type_online_service.yml
    └── rabbit_hole.behavior_settings.taxonomy_vocabulary_online_services.yml
```
