<?php
/**
 * Form Repository for UltimateMember forms and submissions
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember\Repository;

use WP_Post;
use WP_Post_Query;
use WP_Error;

/**
 * Handles form and form submission operations
 */
class FormRepository {

	/**
	 * Get all forms
	 *
	 * @return array Array of forms
	 */
	public static function get_forms() {
		$forms = array();

		// Query UM forms (stored as posts with post_type = 'um_form')
		$args = array(
			'post_type' => 'um_form',
			'posts_per_page' => -1,
			'post_status' => array( 'publish', 'draft' ),
		);

		$query = new WP_Post_Query( $args );

		if ( ! empty( $query->posts ) ) {
			foreach ( $query->posts as $form_post ) {
				$forms[] = self::format_form( $form_post );
			}
		}

		return $forms;
	}

	/**
	 * Get a single form
	 *
	 * @param int $form_id Form post ID.
	 * @return array|WP_Error Form data or error
	 */
	public static function get_form( $form_id ) {
		$form_post = get_post( $form_id );

		if ( ! $form_post instanceof WP_Post || 'um_form' !== $form_post->post_type ) {
			return new WP_Error(
				'form_not_found',
				__( 'Form not found', 'herzenssache-um' ),
				array( 'status' => 404 )
			);
		}

		return self::format_form( $form_post );
	}

	/**
	 * Get form submissions
	 *
	 * @param int    $form_id Form ID.
	 * @param int    $page Page number.
	 * @param int    $per_page Items per page.
	 * @param string $status Optional status filter.
	 * @return array|WP_Error {
	 *     'submissions' => array,
	 *     'total' => int,
	 *     'page' => int,
	 *     'per_page' => int
	 * } or error
	 */
	public static function get_submissions( $form_id, $page = 1, $per_page = 20, $status = '' ) {
		// Verify form exists
		$form = self::get_form( $form_id );
		if ( is_wp_error( $form ) ) {
			return $form;
		}

		// Query submissions
		$args = array(
			'post_type' => 'um_form_submission',
			'posts_per_page' => $per_page,
			'paged' => $page,
			'meta_query' => array(
				array(
					'key' => 'form_id',
					'value' => $form_id,
					'compare' => '=',
				),
			),
		);

		// Add status filter if provided
		if ( ! empty( $status ) ) {
			$args['meta_query'][] = array(
				'key' => 'status',
				'value' => sanitize_text_field( $status ),
				'compare' => '=',
			);
		}

		$query = new WP_Post_Query( $args );

		$submissions = array();
		if ( ! empty( $query->posts ) ) {
			foreach ( $query->posts as $submission_post ) {
				$submissions[] = self::format_submission( $submission_post );
			}
		}

		return array(
			'submissions' => $submissions,
			'total' => $query->found_posts,
			'page' => $page,
			'per_page' => $per_page,
		);
	}

	/**
	 * Create a form submission
	 *
	 * @param int   $form_id Form ID.
	 * @param array $data Submission data {
	 *     'user_id' => int (optional),
	 *     'data' => array (required),
	 *     'status' => string (optional, default: 'pending')
	 * }
	 * @return array|WP_Error Created submission or error
	 */
	public static function create_submission( $form_id, $data ) {
		// Verify form exists
		$form = self::get_form( $form_id );
		if ( is_wp_error( $form ) ) {
			return $form;
		}

		// Create submission post
		$submission_data = array(
			'post_title' => sprintf(
				// translators: %s is the form title
				__( 'Submission for %s', 'herzenssache-um' ),
				$form['title']
			),
			'post_content' => json_encode( $data['data'] ),
			'post_type' => 'um_form_submission',
			'post_status' => 'publish',
		);

		$submission_id = wp_insert_post( $submission_data );

		if ( ! $submission_id || is_wp_error( $submission_id ) ) {
			return new WP_Error(
				'submission_creation_failed',
				__( 'Failed to create submission', 'herzenssache-um' ),
				array( 'status' => 500 )
			);
		}

		// Add submission metadata
		update_post_meta( $submission_id, 'form_id', $form_id );
		update_post_meta( $submission_id, 'data', $data['data'] );
		update_post_meta( $submission_id, 'status', ! empty( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'pending' );

		if ( ! empty( $data['user_id'] ) ) {
			update_post_meta( $submission_id, 'user_id', (int) $data['user_id'] );
		}

		// Get and return the created submission
		$submission_post = get_post( $submission_id );
		return self::format_submission( $submission_post );
	}

	/**
	 * Get a submission
	 *
	 * @param int $submission_id Submission post ID.
	 * @return array|WP_Error Submission data or error
	 */
	public static function get_submission( $submission_id ) {
		$submission_post = get_post( $submission_id );

		if ( ! $submission_post instanceof WP_Post || 'um_form_submission' !== $submission_post->post_type ) {
			return new WP_Error(
				'submission_not_found',
				__( 'Submission not found', 'herzenssache-um' ),
				array( 'status' => 404 )
			);
		}

		return self::format_submission( $submission_post );
	}

	/**
	 * Delete a submission
	 *
	 * @param int $submission_id Submission post ID.
	 * @return bool|WP_Error True if deleted, WP_Error if failed
	 */
	public static function delete_submission( $submission_id ) {
		$submission_post = get_post( $submission_id );

		if ( ! $submission_post instanceof WP_Post || 'um_form_submission' !== $submission_post->post_type ) {
			return new WP_Error(
				'submission_not_found',
				__( 'Submission not found', 'herzenssache-um' ),
				array( 'status' => 404 )
			);
		}

		$result = wp_delete_post( $submission_id, true );

		if ( ! $result ) {
			return new WP_Error(
				'submission_delete_failed',
				__( 'Failed to delete submission', 'herzenssache-um' ),
				array( 'status' => 500 )
			);
		}

		return true;
	}

	/**
	 * Format form post for API response
	 *
	 * @param WP_Post $form_post The form post.
	 * @return array Formatted form
	 */
	private static function format_form( WP_Post $form_post ) {
		$form_meta = get_post_meta( $form_post->ID );
		$fields = array();

		// Get form fields
		$form_fields = get_post_meta( $form_post->ID, 'fields', true );
		if ( ! empty( $form_fields ) && is_array( $form_fields ) ) {
			foreach ( $form_fields as $field ) {
				$fields[] = array(
					'key' => isset( $field['key'] ) ? $field['key'] : '',
					'label' => isset( $field['label'] ) ? $field['label'] : '',
					'type' => isset( $field['type'] ) ? $field['type'] : 'text',
					'required' => isset( $field['required'] ) ? (bool) $field['required'] : false,
					'order' => isset( $field['order'] ) ? (int) $field['order'] : 0,
					'config' => isset( $field['config'] ) ? $field['config'] : array(),
				);
			}
		}

		return array(
			'id' => (int) $form_post->ID,
			'title' => $form_post->post_title,
			'type' => get_post_meta( $form_post->ID, 'form_type', true ) ?: 'form',
			'status' => 'publish' === $form_post->post_status ? 'published' : $form_post->post_status,
			'description' => $form_post->post_content,
			'fields' => $fields,
		);
	}

	/**
	 * Format submission post for API response
	 *
	 * @param WP_Post $submission_post The submission post.
	 * @return array Formatted submission
	 */
	private static function format_submission( WP_Post $submission_post ) {
		$form_id = (int) get_post_meta( $submission_post->ID, 'form_id', true );
		$user_id = get_post_meta( $submission_post->ID, 'user_id', true );
		$data = get_post_meta( $submission_post->ID, 'data', true );
		$status = get_post_meta( $submission_post->ID, 'status', true ) ?: 'pending';

		return array(
			'id' => (int) $submission_post->ID,
			'form_id' => $form_id,
			'user_id' => ! empty( $user_id ) ? (int) $user_id : null,
			'status' => $status,
			'created_at' => $submission_post->post_date,
			'updated_at' => $submission_post->post_modified,
			'data' => ! empty( $data ) ? $data : array(),
		);
	}
}
