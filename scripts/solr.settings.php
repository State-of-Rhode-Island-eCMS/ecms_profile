<?php

// Search API
// By default, both servers are redirected to the DDEV-managed local Solr
// container. To connect to SearchStax instead, comment out the DDEV block for
// the relevant server and uncomment the SearchStax block beneath it, filling in
// the actual host, core, context, update_endpoint, and update_token values.

// --- searchstax server (used by acquia_search_index) ---

// DDEV local Solr override — remove or comment out to use SearchStax directly.
$config['search_api.server.searchstax']['backend_config']['connector'] = 'solr_cloud_basic_auth';
$config['search_api.server.searchstax']['backend_config']['connector_config']['scheme'] = 'http';
$config['search_api.server.searchstax']['backend_config']['connector_config']['host'] = 'solr';
$config['search_api.server.searchstax']['backend_config']['connector_config']['port'] = '8983';
$config['search_api.server.searchstax']['backend_config']['connector_config']['path'] = '/';
$config['search_api.server.searchstax']['backend_config']['connector_config']['core'] = 'ecms';
$config['search_api.server.searchstax']['backend_config']['connector_config']['context'] = 'solr';
$config['search_api.server.searchstax']['backend_config']['connector_config']['username'] = 'solr';
$config['search_api.server.searchstax']['backend_config']['connector_config']['password'] = 'SolrRocks';

// SearchStax override — uncomment and populate to connect to SearchStax.
//
// All values can be derived from the SearchStax deployment URL, which has the
// form: https://<host>/<context>/<core>/
//
//   host             — the hostname portion, e.g. searchcloud-34-us-east-1.searchstax.com
//   context          — the numeric account/deployment segment, e.g. 29847
//   core             — the core name segment, e.g. ecmslocal-12399
//   update_endpoint  — the full URL with /update appended,
//                      e.g. https://<host>/<context>/<core>/update
//   update_token     — the write token from the SearchStax dashboard
//                      (Deployment > Security > Write Token)
//
// $config['search_api.server.searchstax']['backend_config']['connector_config']['host'] = '';
// $config['search_api.server.searchstax']['backend_config']['connector_config']['port'] = 443;
// $config['search_api.server.searchstax']['backend_config']['connector_config']['core'] = '';
// $config['search_api.server.searchstax']['backend_config']['connector_config']['context'] = '';
// $config['search_api.server.searchstax']['backend_config']['connector_config']['update_endpoint'] = '';
// $config['search_api.server.searchstax']['backend_config']['connector_config']['update_token'] = '';

// --- ecms_multisite_server (used by ecms_multisite_index) ---

// DDEV local Solr override — remove or comment out to use SearchStax directly.
$config['search_api.server.ecms_multisite_server']['backend'] = 'search_api_solr';
$config['search_api.server.ecms_multisite_server']['backend_config']['connector'] = 'solr_cloud_basic_auth';
$config['search_api.server.ecms_multisite_server']['backend_config']['connector_config']['scheme'] = 'http';
$config['search_api.server.ecms_multisite_server']['backend_config']['connector_config']['host'] = 'solr';
$config['search_api.server.ecms_multisite_server']['backend_config']['connector_config']['port'] = '8983';
$config['search_api.server.ecms_multisite_server']['backend_config']['connector_config']['path'] = '/';
$config['search_api.server.ecms_multisite_server']['backend_config']['connector_config']['core'] = 'ecms';
$config['search_api.server.ecms_multisite_server']['backend_config']['connector_config']['context'] = 'solr';
$config['search_api.server.ecms_multisite_server']['backend_config']['connector_config']['username'] = 'solr';
$config['search_api.server.ecms_multisite_server']['backend_config']['connector_config']['password'] = 'SolrRocks';

// SearchStax override — uncomment and populate to connect to SearchStax.
// Both servers connect to the same SearchStax instance; use the same values
// as above. site_hash is disabled in the stored config for this server so that
// cross-site search returns results from all factory sites.
// $config['search_api.server.ecms_multisite_server']['backend_config']['connector_config']['host'] = '';
// $config['search_api.server.ecms_multisite_server']['backend_config']['connector_config']['port'] = 443;
// $config['search_api.server.ecms_multisite_server']['backend_config']['connector_config']['core'] = '';
// $config['search_api.server.ecms_multisite_server']['backend_config']['connector_config']['context'] = '';
// $config['search_api.server.ecms_multisite_server']['backend_config']['connector_config']['update_endpoint'] = '';
// $config['search_api.server.ecms_multisite_server']['backend_config']['connector_config']['update_token'] = '';
