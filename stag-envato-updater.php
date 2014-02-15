<?php
/**
 * Plugin Name: Stag Envato Updater
 * Plugin URI: https://github.com/codestag/stag-envato-updater
 * Description: A small plugin to keep your ThemeForest themes up to date.
 * Version: 1.0
 * Author: Ram Ratan Maurya
 * Author URI: http://mauryaratan.me
 * Requires at least: 3.3
 * Tested up to: 3.8.1
 * License: GPLv2 or later
 *
 * Text Domain: seu
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Plugin class for Stag Custom Sidebars.
 *
 * @package Stag_Envato_Updater
 * @author Ram Ratan Maurya
 * @version 1.0
 * @copyright 2014 Ram Ratan Maurya
 */
final class Stag_Envato_Updater {

	/**
	 * @var Stag_Envato_Updater The single instance of the class
	 * @since 1.0
	 */
	protected static $_instance = null;

	/**
	 * @var string
	 */
	public $version = '1.0';

	public $options;

	/**
	 * Main Stag_Envato_Updater Instance
	 *
	 * Ensures only one instance of Stag_Envato_Updater is loaded or can be loaded.
	 *
	 * @since 1.0
	 * @return Stag_Envato_Updater - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Plugin Constructor.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		$this->options = get_option('seu_options');

		add_action( 'admin_init', array( $this, 'add_settings' ) );
	}

	/**
	 * Add plugin settings under Settings > General.
	 *
	 * @return void
	 */
	function add_settings() {
		add_settings_section( 'seu_section', __( 'Stag Envato Updater Settings', 'seu' ), array( $this, 'settings_description' ), 'general' );
		add_settings_field( 'seu_username', __( 'ThemeForest Username', 'seu' ), array( $this, 'username_settings' ), 'general', 'seu_section', array( 'label_for' => 'seu_options[username]' ) );
		add_settings_field( 'seu_api_key', __( 'ThemeForest API Key', 'seu' ), array( $this, 'api_key_settings' ), 'general', 'seu_section', array( 'label_for' => 'seu_options[api_key]' ) );

		register_setting( 'general', 'seu_options' );
	}

	/**
	 * Settings description callback.
	 * 
	 * @return void
	 */
	function settings_description() {
		printf('<div id="stag_envato_updater">%1$s</div>',
			__( 'Stag Envato Updater allows you to keep your ThemeForest themes up to date via WordPress dashboard instead of having to manually download and upload the theme files. Before automatic updates can be enabled, you must first authenticate your ThemeForest account details below.' )
		);
	}

	/**
	 * Username settings callback.
	 * 
	 * @return void
	 */
	function username_settings() {
		printf(
			'<input name="seu_options[username]" id="seu_options[username]" type="text" value="%1$s" class="regular-text" />
			<p class="description">%2$s</p>',
			( isset( $this->options['username'] ) ? esc_attr( $this->options['username'] ) : '' ),
			esc_html__( 'Please enter your ThemeForest username.', 'seu' )
		);
	}

	/**
	 * API Key settings callback.
	 * 
	 * @return void
	 */
	function api_key_settings() {
		printf(
			'<input name="seu_options[api_key]" id="seu_options[api_key]" type="text" value="%1$s" class="regular-text" />
			<p class="description">%2$s</p>',
			( isset( $this->options['api_key'] ) ? esc_attr( $this->options['api_key'] ) : '' ),
			__( 'Enter your ThemeForest account API Key here.', 'seu' )
		);
	}
}

/**
 * Returns the main instance of SEU to prevent the need to use globals.
 *
 * @since  1.0.6
 * @return Stag_Envato_Updater
 */
function SEU() {
	return Stag_Envato_Updater::instance();
}

// Global for backwards compatibility.
$GLOBALS['stag_envato_updater'] = SEU();
