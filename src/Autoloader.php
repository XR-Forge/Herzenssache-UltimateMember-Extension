<?php
/**
 * PSR-4 Autoloader for Herzenssache UltimateMember plugin
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember;

/**
 * PSR-4 Autoloader class
 */
class Autoloader {

	/**
	 * Namespace prefix
	 *
	 * @var string
	 */
	private static $prefix = 'Herzenssache\\UltimateMember\\';

	/**
	 * Base directory for the namespace
	 *
	 * @var string
	 */
	private static $base_dir;

	/**
	 * Register the autoloader
	 *
	 * @return void
	 */
	public static function register() {
		self::$base_dir = HZS_UM_PLUGIN_DIR . 'src/';
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Load a class file based on PSR-4
	 *
	 * @param string $class The fully-qualified class name.
	 * @return void
	 */
	public static function load( $class ) {
		// Check if the class uses the plugin namespace
		if ( 0 !== strpos( $class, self::$prefix ) ) {
			return;
		}

		// Remove the namespace prefix
		$relative_class = substr( $class, strlen( self::$prefix ) );

		// Convert namespace separators to directory separators and append .php
		$file = self::$base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		// Require the class file if it exists
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
