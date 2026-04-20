<?php
/**
 * Plugin Name: Herzenssache UltimateMember REST API Extension
 * Plugin URI: https://github.com/XR-Forge/Herzenssache-UltimateMember-Extension
 * Description: Exposes UltimateMember membership data via a comprehensive REST API with WordPress nonce and JWT authentication.
 * Version: 1.0.0
 * Author: Herzenssache Team
 * Author URI: https://github.com/XR-Forge
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: herzenssache-um
 * Domain Path: /languages
 * Requires Plugins: ultimate-member
 * Requires at least: 5.6
 * Requires PHP: 7.4
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember;

defined( 'ABSPATH' ) || exit;

// Define plugin constants
define( 'HZS_UM_PLUGIN_FILE', __FILE__ );
define( 'HZS_UM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HZS_UM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HZS_UM_VERSION', '1.0.0' );

// Load Composer autoloader if available
$autoloader = HZS_UM_PLUGIN_DIR . 'vendor/autoload.php';
if ( file_exists( $autoloader ) ) {
	require_once $autoloader;
}

// Load plugin files for PSR-4 autoloading
require_once HZS_UM_PLUGIN_DIR . 'src/Autoloader.php';
Autoloader::register();

// Activation and deactivation hooks
register_activation_hook( __FILE__, array( Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Plugin::class, 'deactivate' ) );

// Initialize the plugin
add_action( 'init', array( Plugin::class, 'instance' ), 10 );
