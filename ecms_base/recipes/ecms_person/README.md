# eCMS Person Recipe

This Drupal recipe creates the **Person** content type for eCMS sites — a structured profile for staff, employees, and other people managed by state agencies or municipalities. It also provides the supporting media type, taxonomies, and paragraph types used to build person listings.

## What This Recipe Does

1. **Installs Required Modules**: `auto_entitylabel`, `content_moderation`, `content_translation`, `entity_reference_revisions`, `image`, `language`, `media`, `media_library`, `metatag`, `node`, `options`, `paragraphs`, `paragraphs_translation_sync`, `pathauto`, `rabbit_hole`, `scheduled_transitions`, `simple_sitemap`, `svg_image`, `taxonomy`, `telephone`, `token`, `views`

2. **Creates the Content Type**: `node.type.person` — a person profile with auto entity label (first + last name), pathauto pattern (`people/[node:title]`), and rabbit hole set to display page

3. **Creates Taxonomy Vocabularies**:
   - `taxonomy.vocabulary.person_taxonomy` — "Staff / Department" for categorizing people
   - `taxonomy.vocabulary.person_additional_fields` — "Person additional fields" for site-specific field labels

4. **Creates the Media Type**: `media.type.person_headshot` — "Personal Photo" image media type with `field_personal_photo_image`

5. **Creates Paragraph Types**:
   - `paragraphs.paragraphs_type.person_additional_fields` — holds key/value pairs for site-specific person fields
   - `paragraphs.paragraphs_type.person_list` — renders a listing of persons, optionally filtered by department/category

6. **Creates Field Storages** (strict — database schema):
   - `field_person_first_name` — string
   - `field_person_last_name` — string
   - `field_person_job_title` — string
   - `field_person_email` — email
   - `field_person_phone` — telephone
   - `field_person_phone_extension` — integer
   - `field_person_mobile` — telephone
   - `field_person_fax` — telephone
   - `field_person_category` — entity_reference → `taxonomy_term` (person_taxonomy)
   - `field_person_list_weight` — list_integer (-10 to 10)
   - `field_person_photo` — entity_reference → `media` (person_headshot)
   - `field_person_additional_fields` — entity_reference_revisions → `paragraph` (person_additional_fields)
   - `field_meta_tags_person` — metatag
   - `field_personal_photo_image` — image (on media.person_headshot)
   - `field_person_field_label` — entity_reference → `taxonomy_term` (person_additional_fields)
   - `field_person_field_value` — string (on paragraph.person_additional_fields)
   - `field_department_category` — entity_reference via views (on paragraph.person_list)

7. **Configures Field Instances** on `node.person`, `media.person_headshot`, `paragraph.person_additional_fields`, and `paragraph.person_list`

8. **Configures View Displays**:
   - `node.person.default` — full profile with all contact fields, photo hidden
   - `node.person.teaser` — name, job title, category, email, phone, photo, and additional fields
   - `media.person_headshot.default` — image rendered with svg_image support
   - `media.person_headshot.media_library` — thumbnail display for the media library picker
   - `paragraph.person_additional_fields.default` — label + value pair
   - `paragraph.person_list.default` — department/category reference

9. **Configures Form Displays** for all bundles above

10. **Creates the Reference View**: `views.view.person_list_reference_filter` — entity reference view used by `field_department_category` to filter person list by taxonomy term

11. **Configures Language & Translation**:
    - `node.person` — translation-enabled, language-alterable
    - `media.person_headshot` — translation-enabled, language-alterable
    - `paragraph.person_additional_fields` — translation-enabled, language-alterable
    - `paragraph.person_list` — not translatable
    - `taxonomy_term.person_taxonomy` — translation-enabled, language-alterable
    - `taxonomy_term.person_additional_fields` — translation-enabled, language-alterable

12. **Configures Additional Modules**:
    - `auto_entitylabel` — auto-generates title from first + last name
    - `pathauto` — `people/[node:title]` URL pattern
    - `rabbit_hole` — display_page behavior on node type and taxonomy vocabularies
    - `simple_sitemap` — includes person nodes in XML sitemap

## Prerequisites

- The **editorial workflow** (`workflows.workflow.editorial`) must exist before applying this recipe, so the `moderation_state` form widget can attach correctly.
- The `ecms_workflow` and `ecms_promotions` modules must be installed.

## Applying the Recipe

```bash
ddev drush recipe ecms_base/recipes/ecms_person
```

## Related Recipes

- **`ecms_person_list`** — extends the `person_list` paragraph type with additional display fields (list title, background color, columns, display images toggle). Depends on this recipe.

## File Structure

```
ecms_base/recipes/ecms_person/
├── recipe.yml
└── config/
    ├── auto_entitylabel.settings.node.person.yml
    ├── core.base_field_override.media.person_headshot.*.yml  (8 files)
    ├── core.base_field_override.node.person.*.yml            (10 files)
    ├── core.base_field_override.paragraph.person_*.*.yml     (4 files)
    ├── core.base_field_override.taxonomy_term.person_taxonomy.changed.yml
    ├── field.storage.media.field_personal_photo_image.yml
    ├── field.storage.node.field_person_*.yml                 (13 files)
    ├── field.storage.paragraph.field_department_category.yml
    ├── field.storage.paragraph.field_person_field_label.yml
    ├── field.storage.paragraph.field_person_field_value.yml
    ├── media.type.person_headshot.yml
    ├── node.type.person.yml
    ├── paragraphs.paragraphs_type.person_additional_fields.yml
    ├── paragraphs.paragraphs_type.person_list.yml
    ├── paragraphs_translation_sync.person_list.yml
    ├── pathauto.pattern.people.yml
    ├── rabbit_hole.behavior_settings.node_type_person.yml
    ├── rabbit_hole.behavior_settings.taxonomy_vocabulary_person_additional_fields.yml
    ├── rabbit_hole.behavior_settings.taxonomy_vocabulary_person_taxonomy.yml
    ├── simple_sitemap.bundle_settings.default.node.person.yml
    ├── taxonomy.vocabulary.person_additional_fields.yml
    ├── taxonomy.vocabulary.person_taxonomy.yml
    └── views.view.person_list_reference_filter.yml
```
