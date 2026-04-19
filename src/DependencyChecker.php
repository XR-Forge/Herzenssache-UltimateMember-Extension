<?php
/**
 * Dependency Checker for Herzenssache UltimateMember plugin
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember;

/**
 * Checks for required plugin dependencies
 */
class DependencyChecker {

	/**
	 * Check if UltimateMember plugin is active
	 *
	 * @return bool
	 */
	public static function is_ultimate_member_active() {
		return defined( 'ULTIMATE_MEMBER_PLUGIN_DIR' ) || function_exists( 'UM' );
	}

	/**
	 * Get UltimateMember plugin path
	 *
	 * @return string|null
	 */
	public static function get_ultimate_member_plugin_path() {
		// Try to find Ultimate Member in active plugins
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$active_plugins = get_option( 'active_plugins', array() );

		foreach ( $active_plugins as $plugin ) {
			if ( false !== strpos( $plugin, 'ultimate-member' ) ) {
				return WP_PLUGIN_DIR . '/' . $plugin;
			}
		}

		return null;
	}

	/**
	 * Check all dependencies
	 *
	 * @return array {
	 *     'valid' => bool,
	 *     'missing' => array of missing dependency names,
	 *     'messages' => array of human-readable messages
	 * }
	 */
	public static function check() {
		$result = array(
			'valid'    => true,
			'missing'  => array(),
			'messages' => array(),
		);

		// Check PHP version
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			$result['valid']     = false;
			$result['missing'][] = 'PHP 7.4+';
			$result['messages'][] = sprintf(
				// translators: %s is the required PHP version, %s is the current version
				__( 'Herzenssache UltimateMember REST API requires PHP 7.4 or higher. Your site is running PHP %s.', 'herzenssache-um' ),
				PHP_VERSION
			);
		}

		// Check WordPress version
		global $wp_version;
		if ( version_compare( $wp_version, '5.6', '<' ) ) {
			$result['valid']     = false;
			$result['missing'][] = 'WordPress 5.6+';
			$result['messages'][] = sprintf(
				// translators: %s is the required WP version, %s is the current version
				__( 'Herzenssache UltimateMember REST API requires WordPress 5.6 or higher. Your site is running WordPress %s.', 'herzenssache-um' ),
				$wp_version
			);
		}

		// Check for UltimateMember plugin
		if ( ! self::is_ultimate_member_active() ) {
			$result['valid']     = false;
			$result['missing'][] = 'Ultimate Member';
			$result['messages'][] = __( 'Herzenssache UltimateMember REST API requires the Ultimate Member plugin to be active. Please install and activate Ultimate Member first.', 'herzenssache-um' );
		}

		return $result;
	}

	/**
	 * Display admin notice if dependencies are not met
	 *
	 * @return void
	 */
	public static function show_admin_notice() {
		$check = self::check();

		if ( ! $check['valid'] ) {
			echo '<div class="notice notice-error is-dismissible">';
			echo '<p><strong>' . esc_html__( 'Herzenssache UltimateMember REST API', 'herzenssache-um' ) . '</strong></p>';
			foreach ( $check['messages'] as $message ) {
				echo '<p>' . wp_kses_post( $message ) . '</p>';
			}
			echo '</div>';
		}
	}
}
