<?php
/**
 * Profile REST API Controller
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember\API\Controllers;

use Herzenssache\UltimateMember\API\Router;
use Herzenssache\UltimateMember\Auth\CapabilityChecker;
use Herzenssache\UltimateMember\Auth\RequestValidator;
use Herzenssache\UltimateMember\Repository\ProfileRepository;
use Herzenssache\UltimateMember\Utils\ResponseFormatter;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Handles /profiles/* endpoints
 */
class ProfileController {

	/**
	 * Register routes
	 *
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			Router::get_namespace(),
			'/profiles/(?P<user_id>\d+)',
			array(
				array(
					'methods' => 'GET',
					'callback' => array( __CLASS__, 'get_profile' ),
					'permission_callback' => array( __CLASS__, 'check_read_permission' ),
				),
				array(
					'methods' => 'PATCH',
					'callback' => array( __CLASS__, 'update_profile' ),
					'permission_callback' => array( __CLASS__, 'check_edit_permission' ),
				),
			)
		);
	}

	/**
	 * Get user profile
	 *
	 * GET /wp-json/um/v1/profiles/{user_id}
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_profile( WP_REST_Request $request ) {
		$user_id = (int) $request->get_param( 'user_id' );

		$profile = ProfileRepository::get_profile( $user_id );

		if ( is_wp_error( $profile ) ) {
			return ResponseFormatter::from_wp_error( $profile );
		}

		return ResponseFormatter::success( $profile );
	}

	/**
	 * Update user profile
	 *
	 * PATCH /wp-json/um/v1/profiles/{user_id}
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function update_profile( WP_REST_Request $request ) {
		$user_id = (int) $request->get_param( 'user_id' );
		$params = $request->get_json_params();

		// Validate and sanitize
		$data = RequestValidator::validate_profile_data( $params );

		// Update profile
		$profile = ProfileRepository::update_profile( $user_id, $data );

		if ( is_wp_error( $profile ) ) {
			return ResponseFormatter::from_wp_error( $profile );
		}

		return ResponseFormatter::success( $profile );
	}

	/**
	 * Check read permission
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error
	 */
	public static function check_read_permission( WP_REST_Request $request ) {
		$user_id = (int) $request->get_param( 'user_id' );
		return CapabilityChecker::can_read_profile( $request, $user_id );
	}

	/**
	 * Check edit permission
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error
	 */
	public static function check_edit_permission( WP_REST_Request $request ) {
		$user_id = (int) $request->get_param( 'user_id' );
		return CapabilityChecker::can_edit_profile( $request, $user_id );
	}
}
