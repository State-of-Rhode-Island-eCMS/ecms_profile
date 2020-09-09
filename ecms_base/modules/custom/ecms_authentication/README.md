# ECMS Authentication

The ecms_authentication module will handle customizations to 
authenticating with Active Directory. Specifically, it ties into the
`hook_openid_connect_pre_authorize` method to check the AAD groups to which a
user currently belongs. If that group is the Drupal Administrator, the user is
allowed to authenticate. If not, the user's groups are checked against the 
hostname of the current site. If no group matches, the user is not allowed
to authenticate.
