<?php
/**
 * WordPress Nonce Validator for REST API authentication
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember\Auth;

use WP_Error;
use WP_REST_Request;

/**
 * Handles WordPress nonce validation for REST API requests
 */
class NonceValidator {

	/**
	 * Nonce action name
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'hzs_um_api';

	/**
	 * Nonce name header
	 *
	 * @var string
	 */
	const NONCE_HEADER = 'X-WP-Nonce';

	/**
	 * Validate nonce from request header
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	public static function validate( WP_REST_Request $request ) {
		// Get nonce from header
		$nonce = $request->get_header( self::NONCE_HEADER );

		if ( empty( $nonce ) ) {
			return new WP_Error(
				'missing_nonce',
				__( 'Missing nonce in X-WP-Nonce header', 'herzenssache-um' ),
				array( 'status' => 401 )
			);
		}

		// Accept both plugin-specific and standard WP REST nonce actions.
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) && ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'invalid_nonce',
				__( 'Invalid nonce', 'herzenssache-um' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	/**
	 * Generate a nonce for the REST API
	 *
	 * This can be used in JavaScript or forms to get a valid nonce
	 *
	 * @return string The generated nonce
	 */
	public static function generate() {
		return wp_create_nonce( self::NONCE_ACTION );
	}

	/**
	 * Get nonce via REST endpoint (for external clients)
	 *
	 * @return WP_REST_Response
	 */
	public static function get_nonce_endpoint() {
		return array(
			'nonce' => self::generate(),
			'action' => self::NONCE_ACTION,
		);
	}
}
