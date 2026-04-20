<?php
/**
 * REST API Router for registering all API routes
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember\API;

use Herzenssache\UltimateMember\Auth\NonceValidator;
use Herzenssache\UltimateMember\Auth\JwtValidator;
use Herzenssache\UltimateMember\Auth\CapabilityChecker;
use WP_REST_Request;

/**
 * Registers all REST API routes and handles authentication
 */
class Router {

	/**
	 * API namespace
	 *
	 * @var string
	 */
	const NAMESPACE = 'um/v1';

	/**
	 * Register all REST routes
	 *
	 * @return void
	 */
	public static function register_routes() {
		// Ensure routes are registered during rest_api_init.
		if ( ! did_action( 'rest_api_init' ) && ! doing_action( 'rest_api_init' ) ) {
			add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
			return;
		}

		// Register endpoint classes
		Controllers\UserController::register_routes();
		Controllers\ProfileController::register_routes();
		Controllers\FieldController::register_routes();
		Controllers\FormController::register_routes();
		Controllers\RoleController::register_routes();
		Controllers\NonceController::register_routes();
	}

	/**
	 * Authenticate a REST request
	 *
	 * Checks for either WordPress nonce or JWT token
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error True if authenticated, WP_Error if not
	 */
	public static function authenticate( WP_REST_Request $request ) {
		// Check if user is logged in (WordPress nonce)
		if ( is_user_logged_in() ) {
			$nonce_check = NonceValidator::validate( $request );
			if ( true === $nonce_check ) {
				return true;
			}
			// If nonce provided but invalid, fail
			if ( ! empty( $request->get_header( 'X-WP-Nonce' ) ) ) {
				return $nonce_check;
			}
		}

		// Check for JWT token (external app access)
		$auth_header = $request->get_header( 'Authorization' );
		if ( ! empty( $auth_header ) ) {
			$jwt_check = JwtValidator::validate( $request );
			if ( is_wp_error( $jwt_check ) ) {
				return $jwt_check;
			}
			// Authenticate as JWT user
			do_action( 'hzs_um_jwt_authenticated', $jwt_check );
			return true;
		}

		// Allow public endpoints to work without authentication
		// (specific endpoints may enforce capability checks)
		return true;
	}

	/**
	 * Get the full namespace for routes
	 *
	 * @return string
	 */
	public static function get_namespace() {
		return self::NAMESPACE;
	}

	/**
	 * Register a route with standard authentication
	 *
	 * @param string   $route Route path (without namespace).
	 * @param array    $methods Array of HTTP methods and callbacks.
	 * @param array    $args Additional route arguments.
	 * @param bool     $auth_required Whether authentication is required.
	 * @return void
	 */
	public static function register_route( $route, $methods, $args = array(), $auth_required = false ) {
		$route_args = array_merge(
			array(
				'methods' => array_keys( $methods ),
				'callback' => $methods,
				'permission_callback' => function( WP_REST_Request $request ) use ( $auth_required ) {
					if ( $auth_required && ! is_user_logged_in() ) {
						return new \WP_Error(
							'unauthenticated',
							__( 'You must be logged in to access this endpoint', 'herzenssache-um' ),
							array( 'status' => 401 )
						);
					}
					return true;
				},
			),
			$args
		);

		register_rest_route( self::NAMESPACE, $route, $route_args );
	}
}
