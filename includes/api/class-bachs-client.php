<?php
/**
 * Bachs API Client wrapper.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bachs_Client {

	/**
	 * Live API Base URL.
	 */
	const LIVE_API_URL = 'https://api.bachs.io/v1';

	/**
	 * Sandbox API Base URL.
	 */
	const SANDBOX_API_URL = 'https://sandbox-api.bachs.io/v1';

	/**
	 * @var string
	 */
	private $api_key;

	/**
	 * @var string
	 */
	private $environment;

	/**
	 * Constructor.
	 *
	 * @param string $api_key     API key.
	 * @param string $environment 'live' or 'sandbox'.
	 */
	public function __construct( $api_key, $environment = 'sandbox' ) {
		$this->api_key     = $api_key;
		$this->environment = $environment;
	}

	/**
	 * Get API URL based on environment.
	 *
	 * @return string
	 */
	private function get_api_url() {
		return 'live' === $this->environment ? self::LIVE_API_URL : self::SANDBOX_API_URL;
	}

	/**
	 * Make an API request.
	 *
	 * @param string $method HTTP method (GET, POST, etc).
	 * @param string $endpoint API endpoint (e.g. /checkout/sessions).
	 * @param array  $body Request body for POST/PUT.
	 * @return array|WP_Error Response body or error.
	 */
	public function request( $method, $endpoint, $body = array() ) {
		$url = $this->get_api_url() . $endpoint;

		$args = array(
			'method'    => $method,
			'timeout'   => 30,
			'sslverify' => false, // Bypass local SSL issues
			'headers'   => array(
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
				'User-Agent'    => 'BachsPaymentsWordPress/' . BACHS_PAYMENTS_VERSION,
			),
		);

		if ( ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$parsed_body = json_decode( $body, true );

		if ( $status_code < 200 || $status_code >= 300 ) {
			$error_message = isset( $parsed_body['detail'] ) ? $parsed_body['detail'] : ( isset( $parsed_body['message'] ) ? $parsed_body['message'] : 'Unknown API Error' );
			return new WP_Error( 'bachs_api_error', $error_message, array( 'status' => $status_code ) );
		}

		return $parsed_body;
	}

	/**
	 * Wrapper for GET request.
	 *
	 * @param string $endpoint Endpoint.
	 * @return array|WP_Error
	 */
	public function get( $endpoint ) {
		return $this->request( 'GET', $endpoint );
	}

	/**
	 * Wrapper for POST request.
	 *
	 * @param string $endpoint Endpoint.
	 * @param array  $body Request body.
	 * @return array|WP_Error
	 */
	public function post( $endpoint, $body = array() ) {
		return $this->request( 'POST', $endpoint, $body );
	}
}
