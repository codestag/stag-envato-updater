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

		if( is_multisite() ) {
			$this->options = array(
				'username' => get_site_option('username'),
				'api_key'  => get_site_option('api_key')
			);

			add_action( 'wpmu_options', array( $this, 'show_network_settings' ) );
			add_action( 'update_wpmu_options', array( $this, 'save_network_settings' ) );
		} else {
			add_action( 'admin_init', array( $this, 'add_settings' ) );
		}

		add_filter( 'pre_set_site_transient_update_themes', array( $this, 'check_for_update' ) );
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'add_settings_link' ) );
		add_action( 'plugins_loaded', array( $this, 'localization' ) );
	}

	/**
	 * Sends a request to server, gets current plugins versions.
	 * 
	 * @param  object $transient Update transient option
	 * @return object Update transient option
	 */
	public function check_for_update( $transient ) {

		if ( isset($this->authors) && ! is_array( $this->authors ) ) {
			$this->authors = array($this->authors);
		}

		if ( !isset($transient->checked) ) return $transient;

		if ( ! class_exists( "Envato_Protected_API" ) ) {
			require_once( "lib/class-envato-protected-api.php" );
		}

		$api = new Envato_Protected_API( $this->options['username'], $this->options['api_key'] );
		
		$purchased = $api->wp_list_themes( true );

		$installed = function_exists( "wp_get_themes" ) ? wp_get_themes() : get_themes();
		$filtered = array();

		foreach ( $installed as $theme ) {
			if ( isset($this->authors) && ! in_array( $theme->{'Author Name'}, $this->authors ) )
				continue;
			$filtered[$theme->Name] = $theme;
		}

		foreach ( $purchased as $theme ) {
			if ( isset( $filtered[$theme->theme_name] ) ) {
				// gotcha, compare version now
				$current = $filtered[$theme->theme_name];
				if ( version_compare( $current->Version, $theme->version, '<' ) ) {
					// bingo, inject the update
					if ( $url = $api->wp_download( $theme->item_id ) ) {
						$update = array(
							"url"         => "http://themeforest.net/item/theme/{$theme->item_id}",
							"new_version" => $theme->version,
							"package"     => $url
						);

						$transient->response[$current->Stylesheet] = $update;
					}
				}
			}
		}

		return $transient;
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
		$val = '';

		printf(
			'<input name="seu_options[api_key]" id="seu_options[api_key]" type="text" value="%1$s" class="regular-text" />
			<p class="description">%2$s</p>',
			( isset( $this->options['api_key'] ) ? esc_attr( $this->options['api_key'] ) : '' ),
			__( 'Enter your ThemeForest account API Key here.', 'seu' )
		);
	}

	/**
	 * Display settings panel on multisite.
	 * 
	 * @return void
	 */
	public function show_network_settings() {
		printf( '<h3>%1$s</h3>', __( 'Stag Envato Updater Settings', 'seu' ) );

		self::settings_description();
		?>

		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row"><label for="seu_options[username]"><?php _e( 'ThemeForest Username', 'seu' ); ?></label></th>
					<td>
						<?php self::username_settings(); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="seu_options[api_key]"><?php _e( 'ThemeForest API Key', 'seu' ); ?></label></th>
					<td>
						<?php self::api_key_settings(); ?>
					</td>
				</tr>
			</tbody>
		</table>

		<?php
	}

	/**
	 * Save network settings.
	 * 
	 * @return void
	 */
	public function save_network_settings() {
		$posted_settings  = array_map( 'sanitize_text_field', $_POST['seu_options'] );
		 
        foreach ( $posted_settings as $name => $value ) {
            update_site_option( $name, $value );
        }
	}

	/**
	 * Adds settings link to the plugin on WP-Admin / Plugins page
	 *
	 * @param array $links Default plugin links
	 * @return array Plugin links
	 */
	function add_settings_link( $links ){
		$settings = sprintf( '<a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'options-general.php#stag_envato_updater' ) ),
			esc_html__( 'Settings', 'seu' )
		);
		array_push( $links, $settings );

		return $links;
	}

	/**
	 * Adds plugin localization
	 * Domain: et_automatic_updates
	 *
	 * @return void
	 */
	function localization() {
		load_plugin_textdomain( 'seu', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
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
