<?php
/**
 * Capability Checker for REST API authorization
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember\Auth;

use WP_Error;
use WP_REST_Request;

/**
 * Handles capability/permission checks for REST API endpoints
 */
class CapabilityChecker {

	/**
	 * Required capability for reading user data
	 *
	 * @var string
	 */
	const CAP_READ_USERS = 'list_users';

	/**
	 * Required capability for managing users
	 *
	 * @var string
	 */
	const CAP_MANAGE_USERS = 'manage_options';

	/**
	 * Required capability for reading forms
	 *
	 * @var string
	 */
	const CAP_READ_FORMS = 'read';

	/**
	 * Required capability for managing forms
	 *
	 * @var string
	 */
	const CAP_MANAGE_FORMS = 'manage_options';

	/**
	 * Ensure user is authenticated for REST API requests
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool Whether user is authenticated
	 */
	private static function ensure_rest_authentication( WP_REST_Request $request ) {
		// First check if user is already authenticated
		if ( is_user_logged_in() ) {
			return true;
		}

		// Check for valid nonce in header
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! empty( $nonce ) && wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			// Nonce is valid, ensure user is logged in
			return is_user_logged_in();
		}

		// Check for JWT authentication
		$auth_header = $request->get_header( 'Authorization' );
		if ( ! empty( $auth_header ) ) {
			// JWT validation is handled by JwtValidator
			return true;
		}

		return false;
	}

	/**
	 * Check if current user can read user data
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @param int             $user_id Optional. User ID to check access for.
	 * @return bool|WP_Error True if allowed, WP_Error if not
	 */
	public static function can_read_users( WP_REST_Request $request, $user_id = null ) {
		// Ensure authentication
		if ( ! self::ensure_rest_authentication( $request ) ) {
			return new WP_Error(
				'unauthenticated',
				__( 'You must be logged in to access this endpoint', 'herzenssache-um' ),
				array( 'status' => 401 )
			);
		}

		// Get current user
		$current_user = wp_get_current_user();

		// Admins can always read users
		if ( $current_user->has_cap( self::CAP_MANAGE_USERS ) ) {
			return true;
		}

		// Users can read their own profile
		if ( $user_id && $user_id === (int) $current_user->ID ) {
			return true;
		}

		// Allow any authenticated user to access /users so they can retrieve their own profile.
		if ( $current_user->ID > 0 ) {
			return true;
		}

		// Subscribers with list_users capability can read all users
		if ( $current_user->has_cap( self::CAP_READ_USERS ) ) {
			return true;
		}

		return new WP_Error(
			'insufficient_permissions',
			__( 'You do not have permission to read user data', 'herzenssache-um' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Check if current user can manage users
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error True if allowed, WP_Error if not
	 */
	public static function can_manage_users( WP_REST_Request $request ) {
		// Ensure authentication
		if ( ! self::ensure_rest_authentication( $request ) ) {
			return new WP_Error(
				'unauthenticated',
				__( 'You must be logged in to access this endpoint', 'herzenssache-um' ),
				array( 'status' => 401 )
			);
		}

		$current_user = wp_get_current_user();

		if ( $current_user->has_cap( self::CAP_MANAGE_USERS ) ) {
			return true;
		}

		return new WP_Error(
			'insufficient_permissions',
			__( 'You do not have permission to manage users', 'herzenssache-um' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Check if current user can read forms
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error True if allowed, WP_Error if not
	 */
	public static function can_read_forms( WP_REST_Request $request ) {
		$current_user = wp_get_current_user();

		// Logged-in users can read forms (public forms)
		if ( $current_user->ID > 0 ) {
			return true;
		}

		// Non-authenticated requests can also read forms if they're public
		return true;
	}

	/**
	 * Check if current user can manage forms
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error True if allowed, WP_Error if not
	 */
	public static function can_manage_forms( WP_REST_Request $request ) {
		// Ensure authentication
		if ( ! self::ensure_rest_authentication( $request ) ) {
			return new WP_Error(
				'unauthenticated',
				__( 'You must be logged in to access this endpoint', 'herzenssache-um' ),
				array( 'status' => 401 )
			);
		}

		$current_user = wp_get_current_user();

		if ( $current_user->has_cap( self::CAP_MANAGE_FORMS ) ) {
			return true;
		}

		return new WP_Error(
			'insufficient_permissions',
			__( 'You do not have permission to manage forms', 'herzenssache-um' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Check if current user can read profile
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @param int             $user_id User ID to check access for.
	 * @return bool|WP_Error True if allowed, WP_Error if not
	 */
	public static function can_read_profile( WP_REST_Request $request, $user_id ) {
		// Ensure authentication
		if ( ! self::ensure_rest_authentication( $request ) ) {
			return new WP_Error(
				'unauthenticated',
				__( 'You must be logged in to access this endpoint', 'herzenssache-um' ),
				array( 'status' => 401 )
			);
		}

		$current_user = wp_get_current_user();

		// Admins can always read profiles
		if ( $current_user->has_cap( self::CAP_MANAGE_USERS ) ) {
			return true;
		}

		// Users can read their own profile
		if ( $user_id === (int) $current_user->ID ) {
			return true;
		}

		// Users with list_users cap can read profiles
		if ( $current_user->has_cap( self::CAP_READ_USERS ) ) {
			return true;
		}

		return new WP_Error(
			'insufficient_permissions',
			__( 'You do not have permission to read this profile', 'herzenssache-um' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Check if current user can edit profile
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @param int             $user_id User ID to check access for.
	 * @return bool|WP_Error True if allowed, WP_Error if not
	 */
	public static function can_edit_profile( WP_REST_Request $request, $user_id ) {
		// Ensure authentication
		if ( ! self::ensure_rest_authentication( $request ) ) {
			return new WP_Error(
				'unauthenticated',
				__( 'You must be logged in to access this endpoint', 'herzenssache-um' ),
				array( 'status' => 401 )
			);
		}

		$current_user = wp_get_current_user();

		// Admins can always edit profiles
		if ( $current_user->has_cap( self::CAP_MANAGE_USERS ) ) {
			return true;
		}

		// Users can edit their own profile
		if ( $user_id === (int) $current_user->ID ) {
			return true;
		}

		return new WP_Error(
			'insufficient_permissions',
			__( 'You do not have permission to edit this profile', 'herzenssache-um' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Check if current user can submit forms
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error True if allowed, WP_Error if not
	 */
	public static function can_submit_forms( WP_REST_Request $request ) {
		// Allow authenticated and unauthenticated users to submit forms
		return true;
	}

	/**
	 * Check if current user can read form submissions
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error True if allowed, WP_Error if not
	 */
	public static function can_read_submissions( WP_REST_Request $request ) {
		// Ensure authentication
		if ( ! self::ensure_rest_authentication( $request ) ) {
			return new WP_Error(
				'unauthenticated',
				__( 'You must be logged in to access this endpoint', 'herzenssache-um' ),
				array( 'status' => 401 )
			);
		}

		$current_user = wp_get_current_user();

		// Admins can always read submissions
		if ( $current_user->has_cap( self::CAP_MANAGE_FORMS ) ) {
			return true;
		}

		return new WP_Error(
			'insufficient_permissions',
			__( 'You do not have permission to read form submissions', 'herzenssache-um' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Check if current user can manage submissions
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error True if allowed, WP_Error if not
	 */
	public static function can_manage_submissions( WP_REST_Request $request ) {
		// Ensure authentication
		if ( ! self::ensure_rest_authentication( $request ) ) {
			return new WP_Error(
				'unauthenticated',
				__( 'You must be logged in to access this endpoint', 'herzenssache-um' ),
				array( 'status' => 401 )
			);
		}

		$current_user = wp_get_current_user();

		if ( $current_user->has_cap( self::CAP_MANAGE_FORMS ) ) {
			return true;
		}

		return new WP_Error(
			'insufficient_permissions',
			__( 'You do not have permission to manage form submissions', 'herzenssache-um' ),
			array( 'status' => 403 )
		);
	}
}
