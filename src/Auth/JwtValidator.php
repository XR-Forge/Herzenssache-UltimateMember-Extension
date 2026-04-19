<?php
/**
 * JWT Token Validator for REST API authentication
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember\Auth;

use WP_Error;
use WP_REST_Request;

/**
 * Handles JWT token validation for REST API requests from external applications
 */
class JwtValidator {

	/**
	 * Token type
	 *
	 * @var string
	 */
	const TOKEN_TYPE = 'Bearer';

	/**
	 * Token lifetime in seconds (7 days)
	 *
	 * @var int
	 */
	const TOKEN_LIFETIME = 604800;

	/**
	 * Get the JWT secret key
	 *
	 * Should be defined in wp-config.php as: define('HZS_UM_JWT_SECRET', 'your-secret-key');
	 *
	 * @return string|WP_Error The secret key or WP_Error if not configured
	 */
	private static function get_secret() {
		if ( defined( 'HZS_UM_JWT_SECRET' ) && ! empty( HZS_UM_JWT_SECRET ) ) {
			return HZS_UM_JWT_SECRET;
		}

		return new WP_Error(
			'jwt_not_configured',
			__( 'JWT authentication is not configured. Define HZS_UM_JWT_SECRET in wp-config.php', 'herzenssache-um' )
		);
	}

	/**
	 * Validate JWT token from request header
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error|array True if valid, WP_Error if invalid, or decoded token data
	 */
	public static function validate( WP_REST_Request $request ) {
		// Get authorization header
		$auth_header = $request->get_header( 'Authorization' );

		if ( empty( $auth_header ) ) {
			return new WP_Error(
				'missing_token',
				__( 'Missing Authorization header', 'herzenssache-um' ),
				array( 'status' => 401 )
			);
		}

		// Parse "Bearer <token>" format
		if ( strpos( $auth_header, self::TOKEN_TYPE . ' ' ) !== 0 ) {
			return new WP_Error(
				'invalid_token_format',
				__( 'Invalid token format. Expected "Bearer <token>"', 'herzenssache-um' ),
				array( 'status' => 401 )
			);
		}

		$token = substr( $auth_header, strlen( self::TOKEN_TYPE . ' ' ) );

		// Verify the token
		$result = self::verify_token( $token );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $result;
	}

	/**
	 * Verify and decode a JWT token
	 *
	 * @param string $token The JWT token to verify.
	 * @return array|WP_Error Decoded token data or WP_Error
	 */
	private static function verify_token( $token ) {
		// Get secret
		$secret = self::get_secret();
		if ( is_wp_error( $secret ) ) {
			return $secret;
		}

		// Split token into parts
		$parts = explode( '.', $token );
		if ( count( $parts ) !== 3 ) {
			return new WP_Error(
				'invalid_token',
				__( 'Invalid token structure', 'herzenssache-um' ),
				array( 'status' => 401 )
			);
		}

		list( $header_b64, $payload_b64, $signature_b64 ) = $parts;

		// Decode header
		$header = json_decode( self::base64_url_decode( $header_b64 ), true );
		if ( ! $header || ! isset( $header['alg'] ) || 'HS256' !== $header['alg'] ) {
			return new WP_Error(
				'invalid_token_algorithm',
				__( 'Invalid token algorithm. Only HS256 is supported', 'herzenssache-um' ),
				array( 'status' => 401 )
			);
		}

		// Decode payload
		$payload = json_decode( self::base64_url_decode( $payload_b64 ), true );
		if ( ! $payload ) {
			return new WP_Error(
				'invalid_token_payload',
				__( 'Invalid token payload', 'herzenssache-um' ),
				array( 'status' => 401 )
			);
		}

		// Verify signature
		$signature = self::base64_url_decode( $signature_b64 );
		$expected_signature = hash_hmac( 'sha256', "{$header_b64}.{$payload_b64}", $secret, true );

		if ( ! hash_equals( $signature, $expected_signature ) ) {
			return new WP_Error(
				'invalid_token_signature',
				__( 'Invalid token signature', 'herzenssache-um' ),
				array( 'status' => 401 )
			);
		}

		// Verify expiration
		if ( isset( $payload['exp'] ) && time() > $payload['exp'] ) {
			return new WP_Error(
				'token_expired',
				__( 'Token has expired', 'herzenssache-um' ),
				array( 'status' => 401 )
			);
		}

		return $payload;
	}

	/**
	 * Create a new JWT token
	 *
	 * @param array $data Custom data to include in token.
	 * @return string|WP_Error The JWT token or WP_Error
	 */
	public static function create_token( $data = array() ) {
		$secret = self::get_secret();
		if ( is_wp_error( $secret ) ) {
			return $secret;
		}

		// Header
		$header = array(
			'typ' => 'JWT',
			'alg' => 'HS256',
		);

		// Payload with standard claims
		$payload = array_merge(
			array(
				'iat' => time(),
				'exp' => time() + self::TOKEN_LIFETIME,
				'iss' => get_site_url(),
			),
			$data
		);

		// Encode header and payload
		$header_b64 = self::base64_url_encode( json_encode( $header ) );
		$payload_b64 = self::base64_url_encode( json_encode( $payload ) );

		// Create signature
		$signature = hash_hmac( 'sha256', "{$header_b64}.{$payload_b64}", $secret, true );
		$signature_b64 = self::base64_url_encode( $signature );

		return "{$header_b64}.{$payload_b64}.{$signature_b64}";
	}

	/**
	 * Base64 URL encode
	 *
	 * @param string $data Data to encode.
	 * @return string Base64 URL encoded string
	 */
	private static function base64_url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * Base64 URL decode
	 *
	 * @param string $data Data to decode.
	 * @return string Decoded string
	 */
	private static function base64_url_decode( $data ) {
		// Add padding if needed
		$remainder = strlen( $data ) % 4;
		if ( $remainder ) {
			$data .= str_repeat( '=', 4 - $remainder );
		}

		return base64_decode( strtr( $data, '-_', '+/' ) );
	}
}
