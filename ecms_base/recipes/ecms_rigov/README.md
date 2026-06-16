# eCMS RIGov Recipe

This is the **meta recipe** for a complete RI.gov site setup. Applying it installs and configures all features required for the RI.gov experience in a single step by composing the individual feature recipes.

## What This Recipe Does

Applies the following recipes in order:

| Recipe | Purpose |
|---|---|
| `ecms_canvas` | Installs Canvas page builder, configures role permissions for Canvas pages |
| `ecms_canvas_standard` | Applies the RI.gov Canvas component set and standard page template configuration |
| `ecms_online_services` | Creates the Online Service content type, taxonomy vocabulary, and all associated fields |
| `ecms_online_services_search` | Adds Search API indexing, Views search block, and Facets filtering for Online Services |

## Prerequisites

- An **Acquia Search server** (`search_api.server.acquia_search_server`) must be configured in the target environment before applying this recipe (required by `ecms_online_services_search`).
- The editorial workflow (`workflows.workflow.editorial`) must exist (required by `ecms_online_services`).

## Applying the Recipe

```bash
ddev drush recipe ecms_base/recipes/ecms_rigov
```

All dependency recipes are applied automatically in the correct order.

## File Structure

```
ecms_base/recipes/ecms_rigov/
└── recipe.yml
```
