<?php
/**
 * Connection Test AJAX Handler.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bachs_Connection_Test {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_bachs_test_connection', array( $this, 'test_connection' ) );
	}

	/**
	 * Test the API connection securely.
	 */
	public function test_connection() {
		check_ajax_referer( 'bachs_test_connection_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'bachs-payments' ) ) );
		}

		$env     = isset( $_POST['environment'] ) ? sanitize_text_field( wp_unslash( $_POST['environment'] ) ) : 'sandbox';
		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';

		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => __( 'API Key is required.', 'bachs-payments' ) ) );
		}

		// Initialize client with testing keys.
		$client = new Bachs_Client( $api_key, $env );
		
		// Ping the API to verify keys.
		$response = $client->get( '/customers' ); 

		if ( is_wp_error( $response ) ) {
			Bachs_Logger::log( 'Connection test failed: ' . $response->get_error_message(), 'error' );
			wp_send_json_error( array( 'message' => __( 'Connection failed: ' . $response->get_error_message(), 'bachs-payments' ) ) );
		} else {
			Bachs_Logger::log( 'Connection test successful.' );
			wp_send_json_success( array( 'message' => __( 'Bachs connection successful.', 'bachs-payments' ) ) );
		}
	}
}

new Bachs_Connection_Test();
