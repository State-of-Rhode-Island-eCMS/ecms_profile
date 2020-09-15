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

[Pattern Lab]: https://patternlab.io/

