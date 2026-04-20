<?php
/**
 * User REST API Controller
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember\API\Controllers;

use Herzenssache\UltimateMember\API\Router;
use Herzenssache\UltimateMember\Auth\CapabilityChecker;
use Herzenssache\UltimateMember\Auth\RequestValidator;
use Herzenssache\UltimateMember\Repository\UserRepository;
use Herzenssache\UltimateMember\Utils\ResponseFormatter;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Handles /users/* endpoints
 */
class UserController {

	/**
	 * Register routes
	 *
	 * @return void
	 */
	public static function register_routes() {
		// GET /users, POST /users
		register_rest_route(
			Router::get_namespace(),
			'/users',
			array(
				array(
					'methods' => 'GET',
					'callback' => array( __CLASS__, 'get_users' ),
					'permission_callback' => array( __CLASS__, 'check_read_permission' ),
				),
				array(
					'methods' => 'POST',
					'callback' => array( __CLASS__, 'create_user' ),
					'permission_callback' => array( __CLASS__, 'check_create_permission' ),
				),
			)
		);

		// GET /users/{user_id}, PATCH /users/{user_id}, DELETE /users/{user_id}
		register_rest_route(
			Router::get_namespace(),
			'/users/(?P<user_id>\d+)',
			array(
				array(
					'methods' => 'GET',
					'callback' => array( __CLASS__, 'get_user' ),
					'permission_callback' => array( __CLASS__, 'check_read_permission' ),
				),
				array(
					'methods' => 'PATCH',
					'callback' => array( __CLASS__, 'update_user' ),
					'permission_callback' => array( __CLASS__, 'check_edit_permission' ),
				),
				array(
					'methods' => 'DELETE',
					'callback' => array( __CLASS__, 'delete_user' ),
					'permission_callback' => array( __CLASS__, 'check_delete_permission' ),
				),
			)
		);
	}

	/**
	 * List users
	 *
	 * GET /wp-json/um/v1/users
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public static function get_users( WP_REST_Request $request ) {
		$current_user = wp_get_current_user();

		// Get pagination parameters
		$pagination = RequestValidator::validate_pagination( $request );

		// Get filter parameters
		$role = $request->get_param( 'role' );
		$search = $request->get_param( 'search' );

		// Non-admins without list_users are only allowed to retrieve their own profile.
		if ( $current_user->ID > 0 && ! $current_user->has_cap( CapabilityChecker::CAP_READ_USERS ) && ! $current_user->has_cap( CapabilityChecker::CAP_MANAGE_USERS ) ) {
			$user = UserRepository::get_user( $current_user->ID );
			if ( is_wp_error( $user ) ) {
				return ResponseFormatter::from_wp_error( $user );
			}

			// Apply optional query filters to the current user.
			if ( ! empty( $role ) && is_array( $current_user->roles ) && ! in_array( sanitize_text_field( $role ), $current_user->roles, true ) ) {
				return ResponseFormatter::paginated( array(), 0, $pagination['page'], $pagination['per_page'], 'users' );
			}

			if ( ! empty( $search ) ) {
				$search_value = sanitize_text_field( $search );
				$user_matches = false;

				if ( false !== stripos( $current_user->user_login, $search_value ) || false !== stripos( $current_user->user_email, $search_value ) || false !== stripos( $current_user->display_name, $search_value ) ) {
					$user_matches = true;
				}

				if ( ! $user_matches ) {
					return ResponseFormatter::paginated( array(), 0, $pagination['page'], $pagination['per_page'], 'users' );
				}
			}

			if ( 1 !== $pagination['page'] ) {
				return ResponseFormatter::paginated( array(), 1, $pagination['page'], $pagination['per_page'], 'users' );
			}

			return ResponseFormatter::paginated( array( $user ), 1, $pagination['page'], $pagination['per_page'], 'users' );
		}

		// Fetch users for admins and list_users-capable accounts.
		$result = UserRepository::get_users(
			$pagination['page'],
			$pagination['per_page'],
			$role,
			$search
		);

		return ResponseFormatter::paginated(
			$result['users'],
			$result['total'],
			$result['page'],
			$result['per_page'],
			'users'
		);
	}

	/**
	 * Get a single user
	 *
	 * GET /wp-json/um/v1/users/{user_id}
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_user( WP_REST_Request $request ) {
		$user_id = (int) $request->get_param( 'user_id' );

		$user = UserRepository::get_user( $user_id );

		if ( is_wp_error( $user ) ) {
			return ResponseFormatter::from_wp_error( $user );
		}

		return ResponseFormatter::success( $user );
	}

	/**
	 * Create a user
	 *
	 * POST /wp-json/um/v1/users
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_user( WP_REST_Request $request ) {
		$params = $request->get_json_params();

		// Validate and sanitize
		$data = RequestValidator::validate_user_data( $params, false );

		if ( is_wp_error( $data ) ) {
			return ResponseFormatter::from_wp_error( $data );
		}

		// Create user
		$user = UserRepository::create_user( $data );

		if ( is_wp_error( $user ) ) {
			return ResponseFormatter::from_wp_error( $user );
		}

		return ResponseFormatter::success( $user, 201 );
	}

	/**
	 * Update a user
	 *
	 * PATCH /wp-json/um/v1/users/{user_id}
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function update_user( WP_REST_Request $request ) {
		$user_id = (int) $request->get_param( 'user_id' );
		$params = $request->get_json_params();

		// Validate and sanitize
		$data = RequestValidator::validate_user_data( $params, true );

		if ( is_wp_error( $data ) ) {
			return ResponseFormatter::from_wp_error( $data );
		}

		// Update user
		$user = UserRepository::update_user( $user_id, $data );

		if ( is_wp_error( $user ) ) {
			return ResponseFormatter::from_wp_error( $user );
		}

		return ResponseFormatter::success( $user );
	}

	/**
	 * Delete a user
	 *
	 * DELETE /wp-json/um/v1/users/{user_id}
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function delete_user( WP_REST_Request $request ) {
		$user_id = (int) $request->get_param( 'user_id' );

		$result = UserRepository::delete_user( $user_id );

		if ( is_wp_error( $result ) ) {
			return ResponseFormatter::from_wp_error( $result );
		}

		return new WP_REST_Response( null, 204 );
	}

	/**
	 * Check read permission for users
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error
	 */
	public static function check_read_permission( WP_REST_Request $request ) {
		$user_id = (int) $request->get_param( 'user_id' );

		if ( ! empty( $user_id ) ) {
			return CapabilityChecker::can_read_users( $request, $user_id );
		}

		return CapabilityChecker::can_read_users( $request );
	}

	/**
	 * Check create permission
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error
	 */
	public static function check_create_permission( WP_REST_Request $request ) {
		return CapabilityChecker::can_manage_users( $request );
	}

	/**
	 * Check edit permission
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error
	 */
	public static function check_edit_permission( WP_REST_Request $request ) {
		$user_id = (int) $request->get_param( 'user_id' );
		return CapabilityChecker::can_manage_users( $request );
	}

	/**
	 * Check delete permission
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error
	 */
	public static function check_delete_permission( WP_REST_Request $request ) {
		return CapabilityChecker::can_manage_users( $request );
	}
}
