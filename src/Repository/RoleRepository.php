<?php
/**
 * Role Repository for WordPress and UltimateMember roles
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember\Repository;

use WP_Error;

/**
 * Handles role metadata operations
 */
class RoleRepository {

	/**
	 * Get all available roles
	 *
	 * @return array Array of role definitions
	 */
	public static function get_roles() {
		global $wp_roles;

		$roles = array();

		if ( isset( $wp_roles ) && is_object( $wp_roles ) ) {
			foreach ( $wp_roles->roles as $role_id => $role_data ) {
				$roles[] = self::format_role( $role_id, $role_data );
			}
		}

		return $roles;
	}

	/**
	 * Get a single role by ID
	 *
	 * @param string $role_id Role ID/slug.
	 * @return array|WP_Error Role data or error
	 */
	public static function get_role( $role_id ) {
		global $wp_roles;

		$role_id = sanitize_text_field( $role_id );

		if ( ! isset( $wp_roles ) || ! is_object( $wp_roles ) ) {
			return new WP_Error(
				'roles_not_available',
				__( 'Roles not available', 'herzenssache-um' ),
				array( 'status' => 500 )
			);
		}

		if ( ! isset( $wp_roles->roles[ $role_id ] ) ) {
			return new WP_Error(
				'role_not_found',
				__( 'Role not found', 'herzenssache-um' ),
				array( 'status' => 404 )
			);
		}

		$role_data = $wp_roles->roles[ $role_id ];
		return self::format_role( $role_id, $role_data );
	}

	/**
	 * Format role data for API response
	 *
	 * @param string $role_id Role ID/slug.
	 * @param array  $role_data Role data with 'name' and 'capabilities' keys.
	 * @return array Formatted role
	 */
	private static function format_role( $role_id, $role_data ) {
		$capabilities = array();

		if ( isset( $role_data['capabilities'] ) && is_array( $role_data['capabilities'] ) ) {
			foreach ( $role_data['capabilities'] as $cap => $granted ) {
				$capabilities[ $cap ] = (bool) $granted;
			}
		}

		return array(
			'id' => $role_id,
			'label' => isset( $role_data['name'] ) ? $role_data['name'] : $role_id,
			'capabilities' => $capabilities,
		);
	}
}
