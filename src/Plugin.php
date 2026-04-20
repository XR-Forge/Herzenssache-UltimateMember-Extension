<?php
/**
 * Main Plugin class for Herzenssache UltimateMember REST API Extension
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember;

/**
 * Main plugin class
 */
class Plugin {

	/**
	 * Singleton instance
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Constructor
	 */
	private function __construct() {
		// Private constructor to prevent direct instantiation
	}

	/**
	 * Get singleton instance
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Initialize the plugin
	 *
	 * @return void
	 */
	private function init() {
		// Check dependencies
		$check = DependencyChecker::check();
		if ( ! $check['valid'] ) {
			add_action( 'admin_notices', array( DependencyChecker::class, 'show_admin_notice' ) );
			return;
		}

		// Load text domain for translations
		load_plugin_textdomain( 'herzenssache-um', false, plugin_basename( HZS_UM_PLUGIN_DIR ) . '/languages' );

		// Initialize REST API authentication early
		add_filter( 'determine_current_user', array( API\Router::class, 'determine_current_user_from_request' ), 10, 1 );

		// Initialize components
		$this->init_rest_api();
		$this->init_admin();
		$this->init_admin_menu();
	}

	/**
	 * Initialize REST API routes
	 *
	 * @return void
	 */
	private function init_rest_api() {
		// Register REST API routes on the proper REST init hook.
		// Routes must be registered during rest_api_init, not during plugins_loaded.
		if ( ! class_exists( API\Router::class ) ) {
			return;
		}

		add_action( 'rest_api_init', array( API\Router::class, 'register_routes' ) );
	}

	/**
	 * Initialize admin functionality
	 *
	 * @return void
	 */
	private function init_admin() {
		if ( ! is_admin() ) {
			return;
		}

		// Admin functionality will be initialized here
		// This will be handled by the Admin class
		if ( class_exists( 'Herzenssache\\UltimateMember\\Admin\\Dashboard' ) ) {
			Admin\Dashboard::instance();
		}
	}

	/**
	 * Initialize admin menu items
	 *
	 * @return void
	 */
	private function init_admin_menu() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', function() {
			add_menu_page(
				__( 'UM REST API', 'herzenssache-um' ),
				__( 'UM REST API', 'herzenssache-um' ),
				'manage_options',
				'herzenssache-um-dashboard',
				array( $this, 'render_admin_page' ),
				'dashicons-rest-api',
				26
			);
		} );
	}

	/**
	 * Render the admin page
	 *
	 * @return void
	 */
	public function render_admin_page() {
		if ( class_exists( 'Herzenssache\\UltimateMember\\Admin\\Dashboard' ) ) {
			Admin\Dashboard::instance()->render();
		} else {
			echo '<div class="wrap"><h1>' . esc_html__( 'UM REST API Dashboard', 'herzenssache-um' ) . '</h1>';
			echo '<p>' . esc_html__( 'Dashboard not yet loaded.', 'herzenssache-um' ) . '</p></div>';
		}
	}

	/**
	 * Plugin activation hook
	 *
	 * @return void
	 */
	public static function activate() {
		// Check dependencies on activation
		$check = DependencyChecker::check();
		if ( ! $check['valid'] ) {
			deactivate_plugins( plugin_basename( HZS_UM_PLUGIN_FILE ) );
			wp_die(
				esc_html( implode( ' ', $check['messages'] ) ),
				esc_html__( 'Plugin Activation Error', 'herzenssache-um' ),
				array( 'back_link' => true )
			);
		}

		// Set activation flag
		update_option( 'hzs_um_activated', time() );

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation hook
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Clean up activation flag
		delete_option( 'hzs_um_activated' );

		// Flush rewrite rules
		flush_rewrite_rules();
	}
}
