# ECMS Custom theme and Pattern Lab integration

This profile includes a custom Drupal 9 theme with [Pattern Lab] integration.
The Pattern Lab repository is maintained in a separate [repository](https://github.com/State-of-Rhode-Island-eCMS/ecms_patternlab).

The Drupal theme is stored in this profile at:

```bash
/ecms_base/themes/custom/ecms
```

Composer defines the following package dependency:
```bash
state-of-rhode-island-ecms/ecms_patternlab
```
and uses composer-installer-extenders to place the package in the root of the custom theme.

## Local Development
All Pattern Lab work should be developed in its own repository. To test the changes locally,
first update the "reference" attribute for the package in `composer.json`
to point to the branch you want to test.
```bash
"package": {
        "name": "state-of-rhode-island-ecms/ecms_patternlab",
        ...
        "source": {
          "url": "https://github.com/State-of-Rhode-Island-eCMS/ecms_patternlab",
          "type": "git",
          "reference": "[branch-name]" or "tags/[tag-name]"
```
Next, you need to run `composer install` to download the package. Note that
this is only for local development, since during the build and deploy process,
the main distribution will require ecms_profile, which lists the Pattern Lab package
as a dependency.

When you run composer install, all project dependencies are going to be installed.
This includes Drupal core, and any contributed modules. You will want to remove
those folders before attempting to reinstall the profile.

### Theme debugging
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


[Pattern Lab]: https://patternlab.io/
[Twig VarDumper]: https://www.drupal.org/project/twig_vardumper

