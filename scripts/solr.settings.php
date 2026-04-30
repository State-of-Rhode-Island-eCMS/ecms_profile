<?php

// Search API
$config['search_api.server.acquia_search_server']['backend'] = 'search_api_solr';
$config['search_api.server.acquia_search_server']['backend_config']['connector'] = 'solr_cloud_basic_auth';
$config['search_api.server.acquia_search_server']['backend_config']['connector_config']['scheme'] = 'http';
$config['search_api.server.acquia_search_server']['backend_config']['connector_config']['host'] = 'solr';
$config['search_api.server.acquia_search_server']['backend_config']['connector_config']['port'] = '8983';
$config['search_api.server.acquia_search_server']['backend_config']['connector_config']['path'] = '/';
$config['search_api.server.acquia_search_server']['backend_config']['connector_config']['core'] = 'ecms';
$config['search_api.server.acquia_search_server']['backend_config']['connector_config']['context'] = 'solr';
$config['search_api.server.acquia_search_server']['backend_config']['connector_config']['username'] = 'solr';
$config['search_api.server.acquia_search_server']['backend_config']['connector_config']['password'] = 'SolrRocks';

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
