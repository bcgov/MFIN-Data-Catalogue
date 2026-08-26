<?php

/**
 * @file
 * Drupal common configuration file.
 * 
 * This settings.php file is used for both:
 *    - local development (DDEV) and,
 *    - OpenShift deployments
 *
 * For local development, it is just used in place, as-is.
 *
 * For deployment to openshift, it is injected into the image that is 
 * created, by way of the Dockerfile. 
 * 
 * ------
 * default.settings.php
 * 
 * Please look in default.settings.php for a list of all available settings and their descriptions.
 * There are many options there, which we have removed from this file, for brevity.
 */


/**
 * Salt for one-time login links, cancel links, form tokens, etc.
 *
 * This variable will be set to a random value by the installer. All one-time
 * login links will be invalidated if the value is changed. Note that if your
 * site is deployed on a cluster of web servers, you must ensure that this
 * variable has the same value on each server.
 *
 * For enhanced security, you may set this variable to the contents of a file
 * outside your document root, and vary the value across environments (like
 * production and development); you should also ensure that this file is not
 * stored with backups of your database.
 *
 * Example:
 * @code
 *   $settings['hash_salt'] = file_get_contents('/home/example/salt.txt');
 * @endcode
 */
$settings['hash_salt'] = getenv('HASH_SALT') ?: 'Fallback string, just in case. (Store the real one in Vault, not here.)';


/**
 * Access control for update.php script.
 *
 * If you are updating your Drupal installation using the update.php script but
 * are not logged in using either an account with the "Administer software
 * updates" permission or the site maintenance account (the account that was
 * created during installation), you will need to modify the access check
 * statement below. Change the FALSE to a TRUE to disable the access check.
 * After finishing the upgrade, be sure to open this file again and change the
 * TRUE back to a FALSE!
 */
$settings['update_free_access'] = FALSE;


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
$settings['file_private_path'] = DRUPAL_ROOT . "/../private";


/**
 * Load services definition file.
 */
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';


/**
 * The default list of directories that will be ignored by Drupal's file API.
 *
 * By default ignore node_modules and bower_components folders to avoid issues
 * with common frontend tools and recursive scanning of directories looking for
 * extensions.
 *
 * @see \Drupal\Core\File\FileSystemInterface::scanDirectory()
 * @see \Drupal\Core\Extension\ExtensionDiscovery::scanDirectory()
 */
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];


/**
 * The default number of entities to update in a batch process.
 *
 * This is used by update and post-update functions that need to go through and
 * change all the entities on a site, so it is useful to increase this number
 * if your hosting configuration (i.e. RAM allocation, CPU speed) allows for a
 * larger number of entities to be processed in a single batch run.
 */
$settings['entity_update_batch_size'] = 50;


/**
 * Entity update backup.
 *
 * This is used to inform the entity storage handler that the backup tables as
 * well as the original entity type and field storage definitions should be
 * retained after a successful entity update process.
 */
$settings['entity_update_backup'] = TRUE;


/**
 * OpenID Connect module configuration (Custom).
 *
 * This used to configure the client for OpenID Connect module for
 * authentication with BC Gov's Pathfinder SSO.
 */

// Set up reference variable, for brevity below.
$keycloak_settings =& $config['openid_connect.client.keycloak']['settings'];

// Get the three values, loaded from Vault.
$keycloak_settings['client_id'    ] = getenv('SSO_CLIENT_ID');
$keycloak_settings['client_secret'] = getenv('SSO_CLIENT_SECRET');
$sso_endpoint_baseurl_extended      = getenv('SSO_KEYCLOAK_BASE') . '/realms/standard/protocol/openid-connect';

// Set the endpoints.
$keycloak_settings['authorization_endpoint'] = $sso_endpoint_baseurl_extended . '/auth';
$keycloak_settings['token_endpoint']         = $sso_endpoint_baseurl_extended . '/token';
$keycloak_settings['userinfo_endpoint']      = $sso_endpoint_baseurl_extended . '/userinfo';
$keycloak_settings['end_session_endpoint']   = $sso_endpoint_baseurl_extended . '/logout';

$keycloak_settings['scopes'] = ['openid', 'profile', 'email'];


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
* File settings (Custom).
*
* This will remove orphaned (deleted) files from the file system on the
* next cron run.
*/
$config['file.settings']['make_unused_managed_files_temporary'] = TRUE;
$config['system.file']['temporary_maximum_age'] = 1;


/**
 * Load more configuration, when available.
 *
 * Keep this code block at the end of this file to take full effect.
 */

// This file is generated by DDEV for local-only use.
// It should be neither edited, nor committed to the repo.
if (is_file(__DIR__ . '/settings.ddev.php') && getenv('IS_DDEV_PROJECT') == 'true') {
  include __DIR__ . '/settings.ddev.php';
}

// These are additions for local development, and IS tracked in the repo.
if (is_file(__DIR__ . '/settings.localhost.php')) {
  include __DIR__ . '/settings.localhost.php';
}

// These are additions for OpenShift deployments, and is tracked in the GITOPS repo.
if (is_file(__DIR__ . '/settings.openshift.php')) {
  include __DIR__ . '/settings.openshift.php';
}

// If you want to add your own custom settings for local development, 
// create this file: sites/default/settings.mycustom.php
// It should not be tracked in the repo.
if (is_file(__DIR__ . '/settings.mycustom.php')) {
  include __DIR__ . '/settings.mycustom.php';
}