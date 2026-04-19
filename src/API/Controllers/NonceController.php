<?php
/**
 * Nonce REST API Controller
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember\API\Controllers;

use Herzenssache\UltimateMember\API\Router;
use Herzenssache\UltimateMember\Auth\NonceValidator;
use Herzenssache\UltimateMember\Utils\ResponseFormatter;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Handles nonce generation endpoint
 */
class NonceController {

	/**
	 * Register routes
	 *
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			Router::get_namespace(),
			'/nonce',
			array(
				'methods' => array( 'GET' ),
				'callback' => array( __CLASS__, 'get_nonce' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Get a nonce for API authentication
	 *
	 * POST /wp-json/um/v1/nonce
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public static function get_nonce( WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return ResponseFormatter::error(
				'not_authenticated',
				__( 'User must be logged in to get a nonce', 'herzenssache-um' ),
				401
			);
		}

		return ResponseFormatter::success( NonceValidator::get_nonce_endpoint() );
	}
}
