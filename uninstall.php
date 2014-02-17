<?php
/**
 * Uninstall Stag Envato Updater
 *
 * @package     Stag_Envato_Updater
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Load Halt file
include_once( 'stag-envato-updater.php' );

/** Delete all the Plugin Options */
delete_option('seu_options');
delete_site_option('username');
delete_site_option('api_key');
