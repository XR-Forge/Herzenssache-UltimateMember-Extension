<?php
/**
 * Request Validator and Sanitizer for REST API
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember\Auth;

use WP_Error;
use WP_REST_Request;

/**
 * Handles request validation and sanitization
 */
class RequestValidator {

	/**
	 * Validate and sanitize user creation/update data
	 *
	 * @param array $data User data to validate.
	 * @param bool  $is_update Whether this is an update (true) or create (false).
	 * @return array|WP_Error Sanitized data or WP_Error
	 */
	public static function validate_user_data( $data, $is_update = false ) {
		$sanitized = array();

		// Username (required for create, ignored for update)
		if ( ! $is_update ) {
			if ( empty( $data['username'] ) ) {
				return new WP_Error(
					'missing_username',
					__( 'Username is required', 'herzenssache-um' ),
					array( 'status' => 400 )
				);
			}
			$sanitized['username'] = sanitize_text_field( $data['username'] );

			// Check if username exists
			if ( username_exists( $sanitized['username'] ) ) {
				return new WP_Error(
					'username_exists',
					__( 'Username already exists', 'herzenssache-um' ),
					array( 'status' => 400 )
				);
			}
		}

		// Email (required for create, optional for update)
		if ( ! $is_update && empty( $data['email'] ) ) {
			return new WP_Error(
				'missing_email',
				__( 'Email is required', 'herzenssache-um' ),
				array( 'status' => 400 )
			);
		}

		if ( ! empty( $data['email'] ) ) {
			if ( ! is_email( $data['email'] ) ) {
				return new WP_Error(
					'invalid_email',
					__( 'Invalid email format', 'herzenssache-um' ),
					array( 'status' => 400 )
				);
			}
			$sanitized['email'] = sanitize_email( $data['email'] );

			// Check if email exists (for create, or for update if different)
			if ( email_exists( $sanitized['email'] ) ) {
				return new WP_Error(
					'email_exists',
					__( 'Email already exists', 'herzenssache-um' ),
					array( 'status' => 400 )
				);
			}
		}

		// Password (required for create, optional for update)
		if ( ! $is_update && empty( $data['password'] ) ) {
			return new WP_Error(
				'missing_password',
				__( 'Password is required', 'herzenssache-um' ),
				array( 'status' => 400 )
			);
		}

		if ( ! empty( $data['password'] ) ) {
			// Validate password strength (minimum 8 characters)
			if ( strlen( $data['password'] ) < 8 ) {
				return new WP_Error(
					'weak_password',
					__( 'Password must be at least 8 characters long', 'herzenssache-um' ),
					array( 'status' => 400 )
				);
			}
			$sanitized['password'] = $data['password'];
		}

		// Display name (optional)
		if ( ! empty( $data['display_name'] ) ) {
			$sanitized['display_name'] = sanitize_text_field( $data['display_name'] );
		}

		// First name (optional)
		if ( ! empty( $data['first_name'] ) ) {
			$sanitized['first_name'] = sanitize_text_field( $data['first_name'] );
		}

		// Last name (optional)
		if ( ! empty( $data['last_name'] ) ) {
			$sanitized['last_name'] = sanitize_text_field( $data['last_name'] );
		}

		// Roles (optional, array of role names)
		if ( ! empty( $data['roles'] ) && is_array( $data['roles'] ) ) {
			$sanitized['roles'] = array_map( 'sanitize_text_field', $data['roles'] );
		}

		// Status (optional)
		if ( ! empty( $data['status'] ) ) {
			$valid_statuses = array( 'active', 'inactive', 'pending', 'approved', 'rejected' );
			$status = sanitize_text_field( $data['status'] );
			if ( in_array( $status, $valid_statuses, true ) ) {
				$sanitized['status'] = $status;
			}
		}

		// Meta/custom fields (optional, object/array)
		if ( ! empty( $data['meta'] ) && is_array( $data['meta'] ) ) {
			$sanitized['meta'] = array();
			foreach ( $data['meta'] as $key => $value ) {
				$sanitized['meta'][ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
			}
		}

		// Profile fields (optional, object/array)
		if ( ! empty( $data['profile_fields'] ) && is_array( $data['profile_fields'] ) ) {
			$sanitized['profile_fields'] = array();
			foreach ( $data['profile_fields'] as $key => $value ) {
				$sanitized['profile_fields'][ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
			}
		}

		return $sanitized;
	}

	/**
	 * Validate pagination parameters
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return array Validated pagination parameters {page, per_page}
	 */
	public static function validate_pagination( WP_REST_Request $request ) {
		$page = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );

		// Defaults
		$page = max( 1, $page || 1 );
		$per_page = max( 1, min( $per_page || 20, 100 ) ); // Max 100 per page

		return array(
			'page' => $page,
			'per_page' => $per_page,
		);
	}

	/**
	 * Validate form submission data
	 *
	 * @param array $data Form submission data.
	 * @return array|WP_Error Sanitized data or WP_Error
	 */
	public static function validate_submission_data( $data ) {
		$sanitized = array();

		// Form data is required
		if ( empty( $data['data'] ) || ! is_array( $data['data'] ) ) {
			return new WP_Error(
				'missing_submission_data',
				__( 'Submission data is required', 'herzenssache-um' ),
				array( 'status' => 400 )
			);
		}

		// Sanitize submission data
		$sanitized['data'] = array();
		foreach ( $data['data'] as $key => $value ) {
			$sanitized['data'][ sanitize_text_field( $key ) ] = is_array( $value ) 
				? array_map( 'sanitize_text_field', $value ) 
				: sanitize_text_field( $value );
		}

		// User ID (optional)
		if ( ! empty( $data['user_id'] ) ) {
			$user_id = (int) $data['user_id'];
			if ( ! get_user_by( 'ID', $user_id ) ) {
				return new WP_Error(
					'invalid_user_id',
					__( 'Invalid user ID', 'herzenssache-um' ),
					array( 'status' => 400 )
				);
			}
			$sanitized['user_id'] = $user_id;
		}

		return $sanitized;
	}

	/**
	 * Validate field definition data
	 *
	 * @param array $data Field definition data.
	 * @return array|WP_Error Sanitized data or WP_Error
	 */
	public static function validate_field_data( $data ) {
		$sanitized = array();

		// Label (optional but common)
		if ( ! empty( $data['label'] ) ) {
			$sanitized['label'] = sanitize_text_field( $data['label'] );
		}

		// Required flag (optional)
		if ( isset( $data['required'] ) ) {
			$sanitized['required'] = (bool) $data['required'];
		}

		// Order (optional)
		if ( isset( $data['order'] ) ) {
			$sanitized['order'] = (int) $data['order'];
		}

		// Config (optional, preserve as-is but sanitize keys/values)
		if ( ! empty( $data['config'] ) && is_array( $data['config'] ) ) {
			$sanitized['config'] = array();
			foreach ( $data['config'] as $key => $value ) {
				$sanitized['config'][ sanitize_text_field( $key ) ] = sanitize_text_field( (string) $value );
			}
		}

		return $sanitized;
	}

	/**
	 * Validate profile update data
	 *
	 * @param array $data Profile data.
	 * @return array|WP_Error Sanitized data or WP_Error
	 */
	public static function validate_profile_data( $data ) {
		$sanitized = array();

		// Display name (optional)
		if ( ! empty( $data['display_name'] ) ) {
			$sanitized['display_name'] = sanitize_text_field( $data['display_name'] );
		}

		// Profile fields (optional)
		if ( ! empty( $data['profile_fields'] ) && is_array( $data['profile_fields'] ) ) {
			$sanitized['profile_fields'] = array();
			foreach ( $data['profile_fields'] as $key => $value ) {
				$sanitized['profile_fields'][ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
			}
		}

		// Meta (optional)
		if ( ! empty( $data['meta'] ) && is_array( $data['meta'] ) ) {
			$sanitized['meta'] = array();
			foreach ( $data['meta'] as $key => $value ) {
				$sanitized['meta'][ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
			}
		}

		return $sanitized;
	}
}
