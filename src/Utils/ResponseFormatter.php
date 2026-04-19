<?php
/**
 * Response Formatter for standardized API responses
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember\Utils;

use WP_REST_Response;
use WP_Error;

/**
 * Formats responses according to API spec
 */
class ResponseFormatter {

	/**
	 * Create a success response
	 *
	 * @param mixed $data The response data.
	 * @param int   $status_code HTTP status code.
	 * @return WP_REST_Response
	 */
	public static function success( $data = null, $status_code = 200 ) {
		return new WP_REST_Response( $data, $status_code );
	}

	/**
	 * Create an error response
	 *
	 * @param string $code Error code.
	 * @param string $message Error message.
	 * @param int    $status_code HTTP status code.
	 * @param array  $data Additional error data.
	 * @return WP_Error|WP_REST_Response
	 */
	public static function error( $code, $message, $status_code = 400, $data = array() ) {
		$error_data = array_merge(
			array(
				'status' => $status_code,
				'code' => $code,
			),
			$data
		);

		return new WP_Error( $code, $message, $error_data );
	}

	/**
	 * Create a paginated response
	 *
	 * @param array $items Array of items.
	 * @param int   $total Total count.
	 * @param int   $page Current page.
	 * @param int   $per_page Items per page.
	 * @param string $key Key name for items in response (default: 'items').
	 * @return WP_REST_Response
	 */
	public static function paginated( $items, $total, $page, $per_page, $key = 'items' ) {
		$response = array(
			'total' => (int) $total,
			'page' => (int) $page,
			'per_page' => (int) $per_page,
			'pages' => (int) ceil( $total / $per_page ),
			$key => $items,
		);

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Convert WP_Error to proper API error response
	 *
	 * @param WP_Error $error The error object.
	 * @return WP_REST_Response
	 */
	public static function from_wp_error( WP_Error $error ) {
		$data = $error->get_error_data();
		$status = isset( $data['status'] ) ? $data['status'] : 400;

		return new WP_REST_Response(
			array(
				'code' => $error->get_error_code(),
				'message' => $error->get_error_message(),
				'data' => isset( $data['data'] ) ? $data['data'] : null,
			),
			$status
		);
	}
}
