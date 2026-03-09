<?php

// phpcs:ignoreFile

/**
 * @file
 * Local development override configuration feature.
 *
 * To activate this feature, copy and rename it such that its path plus
 * filename is 'sites/default/settings.local.php'. Then, go to the bottom of
 * 'sites/default/settings.php' and uncomment the commented lines that mention
 * 'settings.local.php'.
 *
 * If you are using a site name in the path, such as 'sites/example.com', copy
 * this file to 'sites/example.com/settings.local.php', and uncomment the lines
 * at the bottom of 'sites/example.com/settings.php'.
 */

/**
 * Assertions.
 *
 * The Drupal project primarily uses runtime assertions to enforce the
 * expectations of the API by failing when incorrect calls are made by code
 * under development.
 *
 * @see http://php.net/assert
 * @see https://www.drupal.org/node/2492225
 *
 * It is strongly recommended that you set zend.assertions=1 in the PHP.ini file
 * (It cannot be changed from .htaccess or runtime) on development machines and
 * to 0 or -1 in production.
 */

/**
 * Enable local development services.
 */
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';

/**
 * Show all error messages, with backtrace information.
 *
 * In case the error level could not be fetched from the database, as for
 * example the database connection failed, we rely only on this value.
 */
$config['system.logging']['error_level'] = 'verbose';

/**
 * Disable CSS and JS aggregation.
 */
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

/**
 * Disable the render cache.
 *
 * Note: you should test with the render cache enabled, to ensure the correct
 * cacheability metadata is present. However, in the early stages of
 * development, you may want to disable it.
 *
 * This setting disables the render cache by using the Null cache back-end
 * defined by the development.services.yml file above.
 *
 * Only use this setting once the site has been installed.
 */
# $settings['cache']['bins']['render'] = 'cache.backend.null';

/**
 * Disable caching for migrations.
 *
 * Uncomment the code below to only store migrations in memory and not in the
 * database. This makes it easier to develop custom migrations.
 */
# $settings['cache']['bins']['discovery_migration'] = 'cache.backend.memory';

/**
 * Disable Internal Page Cache.
 *
 * Note: you should test with Internal Page Cache enabled, to ensure the correct
 * cacheability metadata is present. However, in the early stages of
 * development, you may want to disable it.
 *
 * This setting disables the page cache by using the Null cache back-end
 * defined by the development.services.yml file above.
 *
 * Only use this setting once the site has been installed.
 */
# $settings['cache']['bins']['page'] = 'cache.backend.null';

/**
 * Disable Dynamic Page Cache.
 *
 * Note: you should test with Dynamic Page Cache enabled, to ensure the correct
 * cacheability metadata is present (and hence the expected behavior). However,
 * in the early stages of development, you may want to disable it.
 */
# $settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

/**
 * Allow test modules and themes to be installed.
 *
 * Drupal ignores test modules and themes by default for performance reasons.
 * During development it can be useful to install test extensions for debugging
 * purposes.
 */
# $settings['extension_discovery_scan_tests'] = TRUE;

/**
 * Enable access to rebuild.php.
 *
 * This setting can be enabled to allow Drupal's php and database cached
 * storage to be cleared via the rebuild.php page. Access to this page can also
 * be gained by generating a query string from rebuild_token_calculator.sh and
 * using these parameters in a request to rebuild.php.
 */
$settings['rebuild_access'] = TRUE;

/**
 * Skip file system permissions hardening.
 *
 * The system module will periodically check the permissions of your site's
 * site directory to ensure that it is not writable by the website user. For
 * sites that are managed with a version control system, this can cause problems
 * when files in that directory such as settings.php are updated, because the
 * user pulling in the changes won't have permissions to modify files in the
 * directory.
 */
$settings['skip_permissions_hardening'] = TRUE;

/**
 * Exclude modules from configuration synchronization.
 *
 * On config export sync, no config or dependent config of any excluded module
 * is exported. On config import sync, any config of any installed excluded
 * module is ignored. In the exported configuration, it will be as if the
 * excluded module had never been installed. When syncing configuration, if an
 * excluded module is already installed, it will not be uninstalled by the
 * configuration synchronization, and dependent configuration will remain
 * intact. This affects only configuration synchronization; single import and
 * export of configuration are not affected.
 *
 * Drupal does not validate or sanity check the list of excluded modules. For
 * instance, it is your own responsibility to never exclude required modules,
 * because it would mean that the exported configuration can not be imported
 * anymore.
 *
 * This is an advanced feature and using it means opting out of some of the
 * guarantees the configuration synchronization provides. It is not recommended
 * to use this feature with modules that affect Drupal in a major way such as
 * the language or field module.
 */
# $settings['config_exclude_modules'] = ['devel', 'stage_file_proxy'];

/**
 * Location of the site configuration files.
 *
 * The $settings['config_sync_directory'] specifies the location of file system
 * directory used for syncing configuration data. On install, the directory is
 * created. This is used for configuration imports.
 *
 * The default location for this directory is inside a randomly-named
 * directory in the public files path. The setting below allows you to set
 * its location.
 */
$settings['config_sync_directory'] = '../config/sync';

/**
 * Private file path:
 *
 * A local file system path where private files will be stored. This directory
 * must be absolute, outside of the Drupal installation directory and not
 * accessible over the web.
 *
 * Note: Caches need to be cleared when this value is changed to make the
 * private:// stream wrapper available to the system.
 *
 * See https://www.drupal.org/documentation/modules/file for more information
 * about securing private files.
 */
$settings['file_private_path'] = '../private';

/**
 * Configuration Split module configuration (Custom).
 *
 * This is used to configure Configuration Split.
 */
$environ = getenv('ENVIRONMENT_TYPE'); // for brevity

/* Uncomment this to simulate a different server.
 * And run 'drush cim -y' to make the new Config Split take effect.
 */
// $environ='production';

// Environments where extra dev tools will be available.
$dev_servers = [
  'development',    // openshift dev server
  'ci',             // docker localhost
  '',               // docker drush requires a blank string
  'ddev_localhost', // ddev
];


$config['config_split.config_split.dev']['status'] = TRUE;

/**
 * "Environment Indicator" module.
 */

// If we're on a 'dev' server, but not on the actual OpenShift one, then we're "local".
// Set that as a pseudo-environment.
if (in_array($environ, $dev_servers) && $environ != 'development') {
  $environ = 'LOCALHOST';
}

$indicator_config = [
  // server   => [  name                   ,  bg_color  ]
  'LOCALHOST' => ["NEWBB-appname localhost", 'hsl(220, 100%, 67%)'],
  'DEV'       => ['NEWBB-appname Dev',       'hsl(130, 100%, 67%)'],
  'TEST'      => ['NEWBB-appname Test',      'hsl( 65, 100%, 67%)'],
  'PROD'      => ['NEWBB-appname PROD',      'hsl(  8, 100%, 67%)'],
];

if (!empty($indicator_config[$environ])) {
  $config['environment_indicator.indicator']['name']     = $indicator_config[$environ][0];
  $config['environment_indicator.indicator']['bg_color'] = $indicator_config[$environ][1];
  $config['environment_indicator.indicator']['fg_color'] = '#222330';
}
else {
  throw new \Exception("Environment Indicator config problems, in settings.local.php.");
}


/**
 * Shield module configuration (Custom).
 *
 * This is used to configure the credentials for Shield module for basic HTTP
 * authentication.
 */
$config['shield.settings']['credentials']['shield'] = [
  'user' => getenv('SHIELD_USER'),
  'pass' => getenv('SHIELD_PASS'),
];

/**
 * OpenID Connect module configuration (Custom).
 *
 * This used to configure the client for OpenID Connect module for
 * authentication with BC Gov's Pathfinder SSO.
 */
// Set up reference variable, for brevity below.
$keycloak_settings =& $config['openid_connect.client.keycloak']['settings'];

// Get the three values from the OpenShift Secret.
$keycloak_settings['client_id'    ] = getenv('SSO_CLIENT_ID');
$keycloak_settings['client_secret'] = getenv('SSO_CLIENT_SECRET');
$sso_endpoint_baseurl_extended      = getenv('SSO_KEYCLOAK_BASE') . '/realms/standard/protocol/openid-connect';

// Set the endpoints.
$keycloak_settings['authorization_endpoint'] = $sso_endpoint_baseurl_extended . '/auth';
$keycloak_settings['token_endpoint']         = $sso_endpoint_baseurl_extended . '/token';
$keycloak_settings['userinfo_endpoint']      = $sso_endpoint_baseurl_extended . '/userinfo';
$keycloak_settings['end_session_endpoint']   = $sso_endpoint_baseurl_extended . '/logout';

/**
 * File settings (Custom).
 *
 * This will remove orphaned (deleted) files from the file system on the
 * next cron run.
 */
$config['file.settings']['make_unused_managed_files_temporary'] = TRUE;
$config['system.file']['temporary_maximum_age'] = 1;

/**
 * Database schema name.
 * 
 * Postgres requires us to use a schema. DDEV doesn't seem to handle this
 * yet in the settings.ddev.php file, so we do it here.
 */
$databases['default']['default']['schema'] = "NEWBB-DB-schemaname"; # Keep this schema name short and tight.