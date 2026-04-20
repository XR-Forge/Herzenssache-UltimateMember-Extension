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
	 * Check if current user can read user data
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @param int             $user_id Optional. User ID to check access for.
	 * @return bool|WP_Error True if allowed, WP_Error if not
	 */
	public static function can_read_users( WP_REST_Request $request, $user_id = null ) {
		// For users endpoint, be more permissive with authentication
		$current_user = wp_get_current_user();

		// If we have a valid current user, proceed with normal checks
		if ( $current_user && $current_user->ID > 0 ) {
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
		} else {
			// No current user set, check for authentication indicators
			$nonce = $request->get_header( 'X-WP-Nonce' );
			$auth_header = $request->get_header( 'Authorization' );

			// If nonce is provided and valid, or JWT auth header exists, allow access
			if ( (! empty( $nonce ) && wp_verify_nonce( $nonce, 'wp_rest' )) || ! empty( $auth_header ) ) {
				return true;
			}

			// Check for logged in cookie as last resort
			if ( defined( 'LOGGED_IN_COOKIE' ) && isset( $_COOKIE[LOGGED_IN_COOKIE] ) && ! empty( $_COOKIE[LOGGED_IN_COOKIE] ) ) {
				$user_id = wp_validate_auth_cookie( $_COOKIE[LOGGED_IN_COOKIE], 'logged_in' );
				if ( $user_id ) {
					wp_set_current_user( $user_id );
					return true;
				}
			}
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
		$current_user = wp_get_current_user();

		// If we have a valid current user, check capabilities
		if ( $current_user && $current_user->ID > 0 ) {
			if ( $current_user->has_cap( self::CAP_MANAGE_USERS ) ) {
				return true;
			}
		} else {
			// Check for authentication indicators
			$nonce = $request->get_header( 'X-WP-Nonce' );
			$auth_header = $request->get_header( 'Authorization' );

			if ( (! empty( $nonce ) && wp_verify_nonce( $nonce, 'wp_rest' )) || ! empty( $auth_header ) ) {
				// Re-check user after potential authentication
				$current_user = wp_get_current_user();
				if ( $current_user && $current_user->has_cap( self::CAP_MANAGE_USERS ) ) {
					return true;
				}
			}
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
		$current_user = wp_get_current_user();

		// If we have a valid current user, check capabilities
		if ( $current_user && $current_user->ID > 0 ) {
			if ( $current_user->has_cap( self::CAP_MANAGE_FORMS ) ) {
				return true;
			}
		} else {
			// Check for authentication indicators
			$nonce = $request->get_header( 'X-WP-Nonce' );
			$auth_header = $request->get_header( 'Authorization' );

			if ( (! empty( $nonce ) && wp_verify_nonce( $nonce, 'wp_rest' )) || ! empty( $auth_header ) ) {
				// Re-check user after potential authentication
				$current_user = wp_get_current_user();
				if ( $current_user && $current_user->has_cap( self::CAP_MANAGE_FORMS ) ) {
					return true;
				}
			}
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
		$current_user = wp_get_current_user();

		// If we have a valid current user, check permissions
		if ( $current_user && $current_user->ID > 0 ) {
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
		} else {
			// Check for authentication indicators
			$nonce = $request->get_header( 'X-WP-Nonce' );
			$auth_header = $request->get_header( 'Authorization' );

			if ( (! empty( $nonce ) && wp_verify_nonce( $nonce, 'wp_rest' )) || ! empty( $auth_header ) ) {
				// Re-check user after potential authentication
				$current_user = wp_get_current_user();
				if ( $current_user && $current_user->ID > 0 ) {
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
				}
			}
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
		$current_user = wp_get_current_user();

		// If we have a valid current user, check permissions
		if ( $current_user && $current_user->ID > 0 ) {
			// Admins can always edit profiles
			if ( $current_user->has_cap( self::CAP_MANAGE_USERS ) ) {
				return true;
			}

			// Users can edit their own profile
			if ( $user_id === (int) $current_user->ID ) {
				return true;
			}
		} else {
			// Check for authentication indicators
			$nonce = $request->get_header( 'X-WP-Nonce' );
			$auth_header = $request->get_header( 'Authorization' );

			if ( (! empty( $nonce ) && wp_verify_nonce( $nonce, 'wp_rest' )) || ! empty( $auth_header ) ) {
				// Re-check user after potential authentication
				$current_user = wp_get_current_user();
				if ( $current_user && $current_user->ID > 0 ) {
					// Admins can always edit profiles
					if ( $current_user->has_cap( self::CAP_MANAGE_USERS ) ) {
						return true;
					}

					// Users can edit their own profile
					if ( $user_id === (int) $current_user->ID ) {
						return true;
					}
				}
			}
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
		$current_user = wp_get_current_user();

		// If we have a valid current user, check capabilities
		if ( $current_user && $current_user->ID > 0 ) {
			// Admins can always read submissions
			if ( $current_user->has_cap( self::CAP_MANAGE_FORMS ) ) {
				return true;
			}
		} else {
			// Check for authentication indicators
			$nonce = $request->get_header( 'X-WP-Nonce' );
			$auth_header = $request->get_header( 'Authorization' );

			if ( (! empty( $nonce ) && wp_verify_nonce( $nonce, 'wp_rest' )) || ! empty( $auth_header ) ) {
				// Re-check user after potential authentication
				$current_user = wp_get_current_user();
				if ( $current_user && $current_user->has_cap( self::CAP_MANAGE_FORMS ) ) {
					return true;
				}
			}
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
		$current_user = wp_get_current_user();

		// If we have a valid current user, check capabilities
		if ( $current_user && $current_user->ID > 0 ) {
			if ( $current_user->has_cap( self::CAP_MANAGE_FORMS ) ) {
				return true;
			}
		} else {
			// Check for authentication indicators
			$nonce = $request->get_header( 'X-WP-Nonce' );
			$auth_header = $request->get_header( 'Authorization' );

			if ( (! empty( $nonce ) && wp_verify_nonce( $nonce, 'wp_rest' )) || ! empty( $auth_header ) ) {
				// Re-check user after potential authentication
				$current_user = wp_get_current_user();
				if ( $current_user && $current_user->has_cap( self::CAP_MANAGE_FORMS ) ) {
					return true;
				}
			}
		}

		return new WP_Error(
			'insufficient_permissions',
			__( 'You do not have permission to manage form submissions', 'herzenssache-um' ),
			array( 'status' => 403 )
		);
	}
}
