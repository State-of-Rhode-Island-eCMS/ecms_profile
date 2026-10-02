# ECMS Custom Theme

This profile includes a custom Drupal theme built with [Single Directory Components (SDC)].
The theme is stored in this profile at:

```bash
/ecms_base/themes/custom/ecms
```

Components live alongside their Twig templates, CSS, and JS in:

```bash
/ecms_base/themes/custom/ecms/components
```

Assets (images, icons, compiled styles, scripts) are in:

```bash
/ecms_base/themes/custom/ecms/assets
```

## Theme debugging
The [Twig VarDumper] is available for local theme debugging.
The module is not enabled by default. To enable, run `ddev drush en twig_vardumper`
from the /develop-ecms-profile site root.
Create or edit your local sites/default/settings.local.php file to include the following:
```php
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';
```
Update the contents of the sites/development.services.yml to be:
```yml
parameters:
  http.response.debug_cacheability_headers: true
  twig.config:
    debug: true
    cache: false
    autoload: true
services:
  cache.backend.null:
    class: Drupal\Core\Cache\NullBackendFactory

```
Clear the Drupal cache. You should now be able to add debug calls in twig, e.g.
```twig
{{ dump() }}
{{ dump(variable_name) }}
{{ vardumper() }}
{{ vardumper(variable_name) }}
```


[Single Directory Components (SDC)]: https://www.drupal.org/docs/develop/theming-drupal/using-single-directory-components
[Twig VarDumper]: https://www.drupal.org/project/twig_vardumper
