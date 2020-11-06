# ECMS Distribution

The ecms_distribution module can be used to install required configuration ahead
of the actual profile configuration installation. This is especially useful when
a feature module requires configuration. Configuration is installed in the
following order:

1. profile dependencies: See the dependencies key in ecms_base.info.yml
2. profile installations: See the install key in ecms_base.info.yml
3. profile configuration: See configuration in ecms_base/config/install

So, if a module that is installed by the profile has configuration dependencies
that need to be installed before the actual profile's configuration, that
configuration can be placed in this module.

## Other functionality

### Social Navigation
The ecms_distribution.module was used to manage the social navigation required
alterations. See: RIG-142.

The menu link form is being form altered to show a pre-defined list of social
navigation that the ecms_patternlab theme defines. This will replace the
title field of the `social-navigation` menu items and will add attributes
to the links in that menu which will relate to icons that the theme provides.
