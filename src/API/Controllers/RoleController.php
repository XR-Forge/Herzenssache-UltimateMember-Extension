<?php
/**
 * Role REST API Controller
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember\API\Controllers;

use Herzenssache\UltimateMember\API\Router;
use Herzenssache\UltimateMember\Repository\RoleRepository;
use Herzenssache\UltimateMember\Utils\ResponseFormatter;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Handles /roles/* endpoints
 */
class RoleController {

	/**
	 * Register routes
	 *
	 * @return void
	 */
	public static function register_routes() {
		// GET /roles
		register_rest_route(
			Router::get_namespace(),
			'/roles',
			array(
				'methods' => 'GET',
				'callback' => array( __CLASS__, 'get_roles' ),
				'permission_callback' => '__return_true',
			)
		);

		// GET /roles/{role_id}
		register_rest_route(
			Router::get_namespace(),
			'/roles/(?P<role_id>[a-zA-Z0-9_\-]+)',
			array(
				'methods' => 'GET',
				'callback' => array( __CLASS__, 'get_role' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * List all roles
	 *
	 * GET /wp-json/um/v1/roles
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public static function get_roles( WP_REST_Request $request ) {
		$roles = RoleRepository::get_roles();
		return ResponseFormatter::success( $roles );
	}

	/**
	 * Get a single role
	 *
	 * GET /wp-json/um/v1/roles/{role_id}
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_role( WP_REST_Request $request ) {
		$role_id = $request->get_param( 'role_id' );

		$role = RoleRepository::get_role( $role_id );

		if ( is_wp_error( $role ) ) {
			return ResponseFormatter::from_wp_error( $role );
		}

		return ResponseFormatter::success( $role );
	}
}
