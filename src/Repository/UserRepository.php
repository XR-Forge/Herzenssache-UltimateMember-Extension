<?php
/**
 * User Repository for UltimateMember user operations
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember\Repository;

use WP_User;
use WP_User_Query;
use WP_Error;

/**
 * Handles all user-related database operations for UltimateMember
 */
class UserRepository {

	/**
	 * Get a single user by ID
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error User data or error
	 */
	public static function get_user( $user_id ) {
		$user = get_user_by( 'ID', $user_id );

		if ( ! $user instanceof WP_User ) {
			return new WP_Error(
				'user_not_found',
				__( 'User not found', 'herzenssache-um' ),
				array( 'status' => 404 )
			);
		}

		return self::format_user( $user );
	}

	/**
	 * Get users with pagination and filtering
	 *
	 * @param int    $page Page number (1-indexed).
	 * @param int    $per_page Items per page.
	 * @param string $role Optional role to filter by.
	 * @param string $search Optional search query.
	 * @return array {
	 *     'users' => array of formatted users,
	 *     'total' => total count,
	 *     'page' => current page,
	 *     'per_page' => items per page
	 * }
	 */
	public static function get_users( $page = 1, $per_page = 20, $role = '', $search = '' ) {
		$args = array(
			'number' => $per_page,
			'offset' => ( $page - 1 ) * $per_page,
			'count_total' => true,
		);

		// Add role filter
		if ( ! empty( $role ) ) {
			$args['role'] = sanitize_text_field( $role );
		}

		// Add search filter
		if ( ! empty( $search ) ) {
			$args['search'] = '*' . sanitize_text_field( $search ) . '*';
			$args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
		}

		$user_query = new WP_User_Query( $args );
		$users = $user_query->get_results();

		$formatted_users = array();
		foreach ( $users as $user ) {
			$formatted_users[] = self::format_user( $user );
		}

		return array(
			'users' => $formatted_users,
			'total' => $user_query->total_users,
			'page' => $page,
			'per_page' => $per_page,
		);
	}

	/**
	 * Create a new user
	 *
	 * @param array $data User data {
	 *     'username' => string,
	 *     'email' => string,
	 *     'password' => string,
	 *     'first_name' => string (optional),
	 *     'last_name' => string (optional),
	 *     'display_name' => string (optional),
	 *     'roles' => array (optional),
	 *     'meta' => array (optional),
	 *     'profile_fields' => array (optional)
	 * }
	 * @return array|WP_Error Created user data or error
	 */
	public static function create_user( $data ) {
		$userdata = array(
			'user_login' => $data['username'],
			'user_email' => $data['email'],
			'user_pass' => $data['password'],
		);

		// Add optional fields
		if ( ! empty( $data['first_name'] ) ) {
			$userdata['first_name'] = $data['first_name'];
		}

		if ( ! empty( $data['last_name'] ) ) {
			$userdata['last_name'] = $data['last_name'];
		}

		if ( ! empty( $data['display_name'] ) ) {
			$userdata['display_name'] = $data['display_name'];
		}

		// Create the user
		$user_id = wp_insert_user( $userdata );

		if ( is_wp_error( $user_id ) ) {
			return new WP_Error(
				'user_creation_failed',
				$user_id->get_error_message(),
				array( 'status' => 400 )
			);
		}

		// Set roles
		if ( ! empty( $data['roles'] ) && is_array( $data['roles'] ) ) {
			$user = new WP_User( $user_id );
			foreach ( $data['roles'] as $role ) {
				$user->add_role( sanitize_text_field( $role ) );
			}
		}

		// Set user meta
		if ( ! empty( $data['meta'] ) && is_array( $data['meta'] ) ) {
			foreach ( $data['meta'] as $key => $value ) {
				update_user_meta( $user_id, $key, $value );
			}
		}

		// Set profile fields (UltimateMember specific)
		if ( ! empty( $data['profile_fields'] ) && is_array( $data['profile_fields'] ) ) {
			foreach ( $data['profile_fields'] as $key => $value ) {
				update_user_meta( $user_id, $key, $value );
			}
		}

		// Set user status (if using UltimateMember)
		if ( ! empty( $data['status'] ) ) {
			update_user_meta( $user_id, 'um_status', sanitize_text_field( $data['status'] ) );
		} else {
			update_user_meta( $user_id, 'um_status', 'active' );
		}

		$user = get_user_by( 'ID', $user_id );
		return self::format_user( $user );
	}

	/**
	 * Update a user
	 *
	 * @param int   $user_id User ID.
	 * @param array $data User data to update.
	 * @return array|WP_Error Updated user data or error
	 */
	public static function update_user( $user_id, $data ) {
		// Verify user exists
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user instanceof WP_User ) {
			return new WP_Error(
				'user_not_found',
				__( 'User not found', 'herzenssache-um' ),
				array( 'status' => 404 )
			);
		}

		$userdata = array( 'ID' => $user_id );

		// Update allowed fields
		if ( ! empty( $data['email'] ) ) {
			$userdata['user_email'] = $data['email'];
		}

		if ( ! empty( $data['first_name'] ) ) {
			$userdata['first_name'] = $data['first_name'];
		}

		if ( ! empty( $data['last_name'] ) ) {
			$userdata['last_name'] = $data['last_name'];
		}

		if ( ! empty( $data['display_name'] ) ) {
			$userdata['display_name'] = $data['display_name'];
		}

		// Update user
		if ( count( $userdata ) > 1 ) {
			$result = wp_update_user( $userdata );
			if ( is_wp_error( $result ) ) {
				return new WP_Error(
					'user_update_failed',
					$result->get_error_message(),
					array( 'status' => 400 )
				);
			}
		}

		// Update roles
		if ( ! empty( $data['roles'] ) && is_array( $data['roles'] ) ) {
			$user = new WP_User( $user_id );
			// Remove all existing roles
			foreach ( $user->roles as $role ) {
				$user->remove_role( $role );
			}
			// Add new roles
			foreach ( $data['roles'] as $role ) {
				$user->add_role( sanitize_text_field( $role ) );
			}
		}

		// Update meta
		if ( ! empty( $data['meta'] ) && is_array( $data['meta'] ) ) {
			foreach ( $data['meta'] as $key => $value ) {
				update_user_meta( $user_id, $key, $value );
			}
		}

		// Update status
		if ( ! empty( $data['status'] ) ) {
			update_user_meta( $user_id, 'um_status', sanitize_text_field( $data['status'] ) );
		}

		// Fetch and return updated user
		$updated_user = get_user_by( 'ID', $user_id );
		return self::format_user( $updated_user );
	}

	/**
	 * Delete a user
	 *
	 * @param int $user_id User ID.
	 * @param int $reassign_to Optional user ID to reassign posts to.
	 * @return bool|WP_Error True if deleted, WP_Error if failed
	 */
	public static function delete_user( $user_id, $reassign_to = null ) {
		// Verify user exists
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user instanceof WP_User ) {
			return new WP_Error(
				'user_not_found',
				__( 'User not found', 'herzenssache-um' ),
				array( 'status' => 404 )
			);
		}

		// Prevent deleting the current user
		if ( $user_id === get_current_user_id() ) {
			return new WP_Error(
				'cannot_delete_self',
				__( 'You cannot delete your own user account', 'herzenssache-um' ),
				array( 'status' => 400 )
			);
		}

		// Delete the user
		$result = wp_delete_user( $user_id, $reassign_to );

		if ( ! $result ) {
			return new WP_Error(
				'user_delete_failed',
				__( 'Failed to delete user', 'herzenssache-um' ),
				array( 'status' => 500 )
			);
		}

		return true;
	}

	/**
	 * Format a WP_User object for API response
	 *
	 * @param WP_User $user The user object.
	 * @return array Formatted user data
	 */
	private static function format_user( WP_User $user ) {
		$status = get_user_meta( $user->ID, 'um_status', true ) ?: 'active';

		return array(
			'id' => (int) $user->ID,
			'username' => $user->user_login,
			'email' => $user->user_email,
			'display_name' => $user->display_name,
			'first_name' => $user->first_name,
			'last_name' => $user->last_name,
			'roles' => array_values( $user->roles ),
			'status' => $status,
			'registration_date' => $user->user_registered,
			'profile_url' => get_author_posts_url( $user->ID ),
			'avatar_url' => get_avatar_url( $user->ID ),
			'meta' => self::get_user_meta( $user->ID ),
		);
	}

	/**
	 * Get all user meta for a user
	 *
	 * @param int $user_id User ID.
	 * @return array User meta (excluding private keys)
	 */
	private static function get_user_meta( $user_id ) {
		$meta = array();
		$all_meta = get_user_meta( $user_id );

		if ( is_array( $all_meta ) ) {
			foreach ( $all_meta as $key => $values ) {
				// Skip private meta keys (starting with _)
				if ( 0 === strpos( $key, '_' ) ) {
					continue;
				}
				// Use first value if it's an array with one item
				$meta[ $key ] = is_array( $values ) && count( $values ) === 1 ? $values[0] : $values;
			}
		}

		return $meta;
	}
}
