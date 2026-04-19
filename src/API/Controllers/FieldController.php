<?php
/**
 * Field REST API Controller
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember\API\Controllers;

use Herzenssache\UltimateMember\API\Router;
use Herzenssache\UltimateMember\Auth\CapabilityChecker;
use Herzenssache\UltimateMember\Auth\RequestValidator;
use Herzenssache\UltimateMember\Repository\FieldRepository;
use Herzenssache\UltimateMember\Utils\ResponseFormatter;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Handles /fields/* endpoints
 */
class FieldController {

	/**
	 * Register routes
	 *
	 * @return void
	 */
	public static function register_routes() {
		// GET /fields
		register_rest_route(
			Router::get_namespace(),
			'/fields',
			array(
				'methods' => 'GET',
				'callback' => array( __CLASS__, 'get_fields' ),
				'permission_callback' => '__return_true',
			)
		);

		// GET /fields/{field_key}, PATCH /fields/{field_key}
		register_rest_route(
			Router::get_namespace(),
			'/fields/(?P<field_key>[a-zA-Z0-9_\-]+)',
			array(
				array(
					'methods' => 'GET',
					'callback' => array( __CLASS__, 'get_field' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods' => 'PATCH',
					'callback' => array( __CLASS__, 'update_field' ),
					'permission_callback' => array( __CLASS__, 'check_manage_permission' ),
				),
			)
		);
	}

	/**
	 * List all field definitions
	 *
	 * GET /wp-json/um/v1/fields
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public static function get_fields( WP_REST_Request $request ) {
		$fields = FieldRepository::get_fields();
		return ResponseFormatter::success( $fields );
	}

	/**
	 * Get a single field definition
	 *
	 * GET /wp-json/um/v1/fields/{field_key}
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_field( WP_REST_Request $request ) {
		$field_key = $request->get_param( 'field_key' );

		$field = FieldRepository::get_field( $field_key );

		if ( is_wp_error( $field ) ) {
			return ResponseFormatter::from_wp_error( $field );
		}

		return ResponseFormatter::success( $field );
	}

	/**
	 * Update a field definition
	 *
	 * PATCH /wp-json/um/v1/fields/{field_key}
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function update_field( WP_REST_Request $request ) {
		$field_key = $request->get_param( 'field_key' );
		$params = $request->get_json_params();

		// Validate and sanitize
		$data = RequestValidator::validate_field_data( $params );

		// Update field
		$field = FieldRepository::update_field( $field_key, $data );

		if ( is_wp_error( $field ) ) {
			return ResponseFormatter::from_wp_error( $field );
		}

		return ResponseFormatter::success( $field );
	}

	/**
	 * Check permission to manage fields
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error
	 */
	public static function check_manage_permission( WP_REST_Request $request ) {
		return CapabilityChecker::can_manage_forms( $request );
	}
}
