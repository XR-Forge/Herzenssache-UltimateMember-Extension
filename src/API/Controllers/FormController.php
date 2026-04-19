<?php
/**
 * Form REST API Controller
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember\API\Controllers;

use Herzenssache\UltimateMember\API\Router;
use Herzenssache\UltimateMember\Auth\CapabilityChecker;
use Herzenssache\UltimateMember\Auth\RequestValidator;
use Herzenssache\UltimateMember\Repository\FormRepository;
use Herzenssache\UltimateMember\Utils\ResponseFormatter;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Handles /forms/* and /submissions/* endpoints
 */
class FormController {

	/**
	 * Register routes
	 *
	 * @return void
	 */
	public static function register_routes() {
		// GET /forms
		register_rest_route(
			Router::get_namespace(),
			'/forms',
			array(
				'methods' => 'GET',
				'callback' => array( __CLASS__, 'get_forms' ),
				'permission_callback' => array( __CLASS__, 'check_read_forms_permission' ),
			)
		);

		// GET /forms/{form_id}
		register_rest_route(
			Router::get_namespace(),
			'/forms/(?P<form_id>\d+)',
			array(
				'methods' => 'GET',
				'callback' => array( __CLASS__, 'get_form' ),
				'permission_callback' => array( __CLASS__, 'check_read_forms_permission' ),
			)
		);

		// GET /forms/{form_id}/submissions, POST /forms/{form_id}/submissions
		register_rest_route(
			Router::get_namespace(),
			'/forms/(?P<form_id>\d+)/submissions',
			array(
				array(
					'methods' => 'GET',
					'callback' => array( __CLASS__, 'get_submissions' ),
					'permission_callback' => array( __CLASS__, 'check_read_submissions_permission' ),
				),
				array(
					'methods' => 'POST',
					'callback' => array( __CLASS__, 'create_submission' ),
					'permission_callback' => array( __CLASS__, 'check_submit_permission' ),
				),
			)
		);

		// GET /submissions/{submission_id}, DELETE /submissions/{submission_id}
		register_rest_route(
			Router::get_namespace(),
			'/submissions/(?P<submission_id>\d+)',
			array(
				array(
					'methods' => 'GET',
					'callback' => array( __CLASS__, 'get_submission' ),
					'permission_callback' => array( __CLASS__, 'check_read_submissions_permission' ),
				),
				array(
					'methods' => 'DELETE',
					'callback' => array( __CLASS__, 'delete_submission' ),
					'permission_callback' => array( __CLASS__, 'check_manage_submissions_permission' ),
				),
			)
		);
	}

	/**
	 * List all forms
	 *
	 * GET /wp-json/um/v1/forms
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public static function get_forms( WP_REST_Request $request ) {
		$forms = FormRepository::get_forms();
		return ResponseFormatter::success( $forms );
	}

	/**
	 * Get a single form
	 *
	 * GET /wp-json/um/v1/forms/{form_id}
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_form( WP_REST_Request $request ) {
		$form_id = (int) $request->get_param( 'form_id' );

		$form = FormRepository::get_form( $form_id );

		if ( is_wp_error( $form ) ) {
			return ResponseFormatter::from_wp_error( $form );
		}

		return ResponseFormatter::success( $form );
	}

	/**
	 * List form submissions
	 *
	 * GET /wp-json/um/v1/forms/{form_id}/submissions
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_submissions( WP_REST_Request $request ) {
		$form_id = (int) $request->get_param( 'form_id' );
		$pagination = RequestValidator::validate_pagination( $request );
		$status = $request->get_param( 'status' );

		$result = FormRepository::get_submissions(
			$form_id,
			$pagination['page'],
			$pagination['per_page'],
			$status
		);

		if ( is_wp_error( $result ) ) {
			return ResponseFormatter::from_wp_error( $result );
		}

		return ResponseFormatter::paginated(
			$result['submissions'],
			$result['total'],
			$result['page'],
			$result['per_page'],
			'submissions'
		);
	}

	/**
	 * Create a form submission
	 *
	 * POST /wp-json/um/v1/forms/{form_id}/submissions
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_submission( WP_REST_Request $request ) {
		$form_id = (int) $request->get_param( 'form_id' );
		$params = $request->get_json_params();

		// Validate and sanitize
		$data = RequestValidator::validate_submission_data( $params );

		if ( is_wp_error( $data ) ) {
			return ResponseFormatter::from_wp_error( $data );
		}

		// Create submission
		$submission = FormRepository::create_submission( $form_id, $data );

		if ( is_wp_error( $submission ) ) {
			return ResponseFormatter::from_wp_error( $submission );
		}

		return ResponseFormatter::success( $submission, 201 );
	}

	/**
	 * Get a single submission
	 *
	 * GET /wp-json/um/v1/submissions/{submission_id}
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_submission( WP_REST_Request $request ) {
		$submission_id = (int) $request->get_param( 'submission_id' );

		$submission = FormRepository::get_submission( $submission_id );

		if ( is_wp_error( $submission ) ) {
			return ResponseFormatter::from_wp_error( $submission );
		}

		return ResponseFormatter::success( $submission );
	}

	/**
	 * Delete a submission
	 *
	 * DELETE /wp-json/um/v1/submissions/{submission_id}
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function delete_submission( WP_REST_Request $request ) {
		$submission_id = (int) $request->get_param( 'submission_id' );

		$result = FormRepository::delete_submission( $submission_id );

		if ( is_wp_error( $result ) ) {
			return ResponseFormatter::from_wp_error( $result );
		}

		return new WP_REST_Response( null, 204 );
	}

	/**
	 * Check permission to read forms
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error
	 */
	public static function check_read_forms_permission( WP_REST_Request $request ) {
		return CapabilityChecker::can_read_forms( $request );
	}

	/**
	 * Check permission to read submissions
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error
	 */
	public static function check_read_submissions_permission( WP_REST_Request $request ) {
		return CapabilityChecker::can_read_submissions( $request );
	}

	/**
	 * Check permission to submit forms
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error
	 */
	public static function check_submit_permission( WP_REST_Request $request ) {
		return CapabilityChecker::can_submit_forms( $request );
	}

	/**
	 * Check permission to manage submissions
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error
	 */
	public static function check_manage_submissions_permission( WP_REST_Request $request ) {
		return CapabilityChecker::can_manage_submissions( $request );
	}
}
