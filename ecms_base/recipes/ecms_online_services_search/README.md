# eCMS Online Services Search Recipe

This Drupal recipe sets up **Search API indexing, an exposed Views search block, and Facets filtering** for the Online Services feature on RI.gov. It depends on the `ecms_online_services` recipe for the content type and fields.

## What This Recipe Does

1. **Applies Dependency Recipe**: `ecms_online_services` — ensures the content type, taxonomy, and fields exist before configuring search

2. **Installs Required Modules**: `search_api`, `facets`, `views`

3. **Creates the Search API Index**: `search_api.index.rigov_online_services`
   - Indexes `online_service` nodes
   - Fields indexed: title, service description, service type, agency/muni, service URL

4. **Creates the Views Search Block**: `views.view.online_services`
   - Exposed block display (`block_1`) driven by the Search API index
   - Provides keyword search with facet integration

5. **Creates the Facet Source**: `facets.facet_source.search_api__views_block__online_services__block_1`
   - Ties the Facets module to the Views block display

6. **Creates the Services Facet**: `facets.facet.services`
   - Facets on the `field_service_type` taxonomy term reference
   - Appears alongside the search results block

7. **Reconfigures the Default View Display**: Adjusts `node.online_service.default` to include the `search_api_excerpt` component (search keyword highlighting) and removes the content moderation control from public display

## Prerequisites

- An **Acquia Search server** (`search_api.server.acquia_search_server`) must be configured in the target environment. The index references this server. Local DDEV environments use a locally configured Solr server instead.
- The `ecms_online_services` recipe (applied automatically as a dependency).

## Applying the Recipe

```bash
ddev drush recipe ecms_base/recipes/ecms_online_services_search
```

The `ecms_online_services` dependency is applied automatically. To apply only the content type without search:

```bash
ddev drush recipe ecms_base/recipes/ecms_online_services
```

## File Structure

```
ecms_base/recipes/ecms_online_services_search/
├── recipe.yml
└── config/
    ├── search_api.index.rigov_online_services.yml
    ├── views.view.online_services.yml
    ├── facets.facet_source.search_api__views_block__online_services__block_1.yml
    └── facets.facet.services.yml
```
