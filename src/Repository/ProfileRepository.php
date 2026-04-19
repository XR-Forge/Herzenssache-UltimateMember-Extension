<?php
/**
 * Profile Repository for UltimateMember profile operations
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember\Repository;

use WP_User;
use WP_Error;

/**
 * Handles profile-related operations
 */
class ProfileRepository {

	/**
	 * Get user profile with all fields and metadata
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error Profile data or error
	 */
	public static function get_profile( $user_id ) {
		$user = get_user_by( 'ID', $user_id );

		if ( ! $user instanceof WP_User ) {
			return new WP_Error(
				'user_not_found',
				__( 'User not found', 'herzenssache-um' ),
				array( 'status' => 404 )
			);
		}

		return self::format_profile( $user );
	}

	/**
	 * Update user profile
	 *
	 * @param int   $user_id User ID.
	 * @param array $data Profile data to update {
	 *     'display_name' => string (optional),
	 *     'profile_fields' => array (optional),
	 *     'meta' => array (optional)
	 * }
	 * @return array|WP_Error Updated profile or error
	 */
	public static function update_profile( $user_id, $data ) {
		$user = get_user_by( 'ID', $user_id );

		if ( ! $user instanceof WP_User ) {
			return new WP_Error(
				'user_not_found',
				__( 'User not found', 'herzenssache-um' ),
				array( 'status' => 404 )
			);
		}

		// Update display name if provided
		if ( ! empty( $data['display_name'] ) ) {
			wp_update_user( array(
				'ID' => $user_id,
				'display_name' => $data['display_name'],
			) );
		}

		// Update profile fields (stored as user meta)
		if ( ! empty( $data['profile_fields'] ) && is_array( $data['profile_fields'] ) ) {
			foreach ( $data['profile_fields'] as $field_key => $field_value ) {
				update_user_meta( $user_id, $field_key, $field_value );
			}
		}

		// Update meta
		if ( ! empty( $data['meta'] ) && is_array( $data['meta'] ) ) {
			foreach ( $data['meta'] as $meta_key => $meta_value ) {
				update_user_meta( $user_id, $meta_key, $meta_value );
			}
		}

		// Fetch and return updated profile
		$updated_user = get_user_by( 'ID', $user_id );
		return self::format_profile( $updated_user );
	}

	/**
	 * Format profile data for API response
	 *
	 * @param WP_User $user The user object.
	 * @return array Formatted profile data
	 */
	private static function format_profile( WP_User $user ) {
		$profile_fields = self::get_profile_fields( $user->ID );
		$status = get_user_meta( $user->ID, 'um_status', true ) ?: 'active';
		$member_since = get_user_meta( $user->ID, 'um_member_since', true );

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
			'fields' => $profile_fields,
			'member_since' => $member_since ?: $user->user_registered,
			'meta' => self::get_user_meta( $user->ID ),
		);
	}

	/**
	 * Get profile fields for a user
	 *
	 * Retrieves custom profile fields that were set via UltimateMember
	 *
	 * @param int $user_id User ID.
	 * @return array Profile fields key/value pairs
	 */
	private static function get_profile_fields( $user_id ) {
		$fields = array();

		// Get all user meta
		$all_meta = get_user_meta( $user_id );

		if ( is_array( $all_meta ) ) {
			foreach ( $all_meta as $key => $values ) {
				// Skip private keys (starting with _) and system meta
				if ( 0 === strpos( $key, '_' ) || in_array( $key, array( 'um_status', 'um_member_since' ), true ) ) {
					continue;
				}

				// Skip WordPress default meta
				if ( in_array( $key, array( 'nickname', 'description', 'rich_editing', 'comment_shortcuts', 'admin_color', 'use_ssl', 'show_admin_bar_front' ), true ) ) {
					continue;
				}

				// Use first value if it's an array with one item
				$value = is_array( $values ) && count( $values ) === 1 ? $values[0] : $values;
				$fields[ $key ] = $value;
			}
		}

		return $fields;
	}

	/**
	 * Get user meta (excluding private and system keys)
	 *
	 * @param int $user_id User ID.
	 * @return array User meta
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
