<?php
/**
 * Field Repository for UltimateMember field definitions
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember\Repository;

use WP_Error;

/**
 * Handles custom field definition operations
 */
class FieldRepository {

	/**
	 * Get all field definitions
	 *
	 * @return array Field definitions or empty array if UltimateMember not available
	 */
	public static function get_fields() {
		$fields = array();

		// Try to get fields from UltimateMember
		if ( function_exists( 'UM' ) ) {
			// If using UltimateMember API
			$um_fields = \UM()->builtin()->get_fields();
			if ( is_array( $um_fields ) ) {
				foreach ( $um_fields as $key => $field ) {
					$fields[] = self::format_field( $key, $field );
				}
			}
		} else {
			// Fallback: get fields from options
			$custom_fields = get_option( 'um_custom_fields', array() );
			if ( is_array( $custom_fields ) ) {
				foreach ( $custom_fields as $key => $field ) {
					$fields[] = self::format_field( $key, $field );
				}
			}
		}

		return $fields;
	}

	/**
	 * Get a single field definition
	 *
	 * @param string $field_key Field key identifier.
	 * @return array|WP_Error Field definition or error
	 */
	public static function get_field( $field_key ) {
		$field_key = sanitize_text_field( $field_key );

		// Try to get field from UltimateMember
		if ( function_exists( 'UM' ) ) {
			$um_field = \UM()->builtin()->get_field( $field_key );
			if ( $um_field && is_array( $um_field ) ) {
				return self::format_field( $field_key, $um_field );
			}
		} else {
			// Fallback: get from options
			$custom_fields = get_option( 'um_custom_fields', array() );
			if ( isset( $custom_fields[ $field_key ] ) ) {
				return self::format_field( $field_key, $custom_fields[ $field_key ] );
			}
		}

		return new WP_Error(
			'field_not_found',
			__( 'Field not found', 'herzenssache-um' ),
			array( 'status' => 404 )
		);
	}

	/**
	 * Update a field definition
	 *
	 * @param string $field_key Field key identifier.
	 * @param array  $data Field data to update {
	 *     'label' => string (optional),
	 *     'required' => bool (optional),
	 *     'order' => int (optional),
	 *     'config' => array (optional)
	 * }
	 * @return array|WP_Error Updated field definition or error
	 */
	public static function update_field( $field_key, $data ) {
		$field_key = sanitize_text_field( $field_key );

		// Get existing field
		$existing_field = self::get_field( $field_key );
		if ( is_wp_error( $existing_field ) ) {
			return $existing_field;
		}

		// Merge with existing data
		$updated_field = $existing_field;

		if ( ! empty( $data['label'] ) ) {
			$updated_field['label'] = $data['label'];
		}

		if ( isset( $data['required'] ) ) {
			$updated_field['required'] = (bool) $data['required'];
		}

		if ( isset( $data['order'] ) ) {
			$updated_field['order'] = (int) $data['order'];
		}

		if ( ! empty( $data['config'] ) && is_array( $data['config'] ) ) {
			$updated_field['config'] = array_merge(
				isset( $updated_field['config'] ) ? $updated_field['config'] : array(),
				$data['config']
			);
		}

		// Try to save via UltimateMember
		if ( function_exists( 'UM' ) ) {
			// Update via UltimateMember API if available
			\UM()->builtin()->save_field( $field_key, $updated_field );
		} else {
			// Fallback: save to options
			$custom_fields = get_option( 'um_custom_fields', array() );
			$custom_fields[ $field_key ] = $updated_field;
			update_option( 'um_custom_fields', $custom_fields );
		}

		return $updated_field;
	}

	/**
	 * Format field data for API response
	 *
	 * @param string $key Field key.
	 * @param array  $field Field data.
	 * @return array Formatted field
	 */
	private static function format_field( $key, $field ) {
		return array(
			'key' => $key,
			'label' => isset( $field['label'] ) ? $field['label'] : $key,
			'type' => isset( $field['type'] ) ? $field['type'] : 'text',
			'form_id' => isset( $field['form_id'] ) ? (int) $field['form_id'] : null,
			'required' => isset( $field['required'] ) ? (bool) $field['required'] : false,
			'order' => isset( $field['order'] ) ? (int) $field['order'] : 0,
			'config' => isset( $field['config'] ) ? $field['config'] : array(),
		);
	}
}
