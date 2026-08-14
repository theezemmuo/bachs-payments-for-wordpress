<?php
/**
 * Webhook REST API Handler.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bachs_Webhook_Handler {

	/**
	 * Register the REST API route.
	 */
	public function register_routes() {
		register_rest_route(
			'bachs/v1',
			'/webhook',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_webhook' ),
				'permission_callback' => '__return_true', // Validation happens inside the callback via signature.
			)
		);
	}

	/**
	 * Handle incoming webhook payload.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function handle_webhook( $request ) {
		$payload = $request->get_json_params();

		// Security: Verify Signature
		if ( ! Bachs_Webhook_Security::verify_signature( $request ) ) {
			return new WP_REST_Response( array( 'error' => 'Invalid signature' ), 401 );
		}

		$event_id   = isset( $payload['id'] ) ? sanitize_text_field( $payload['id'] ) : '';
		$event_type = isset( $payload['type'] ) ? sanitize_text_field( $payload['type'] ) : '';

		if ( empty( $event_id ) || empty( $event_type ) ) {
			return new WP_REST_Response( array( 'error' => 'Invalid payload' ), 400 );
		}

		// Security: Idempotency
		if ( Bachs_Webhook_Security::check_idempotency( $event_id ) ) {
			return new WP_REST_Response( array( 'message' => 'Event already processed' ), 200 );
		}

		Bachs_Logger::log( 'Received webhook event: ' . $event_type, 'info' );
		
		do_action( 'bachs_webhook_received', $payload );

		// Process specific events
		switch ( $event_type ) {
			case 'Checkout Completed':
			case 'Collection Succeeded':
				$this->process_payment( $payload, true );
				break;

			case 'Checkout Expired':
			case 'Collection Failed':
				$this->process_payment( $payload, false );
				break;

			// Add more event handlers as needed based on API docs.
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	/**
	 * Process a payment webhook payload.
	 *
	 * @param array $payload Webhook payload.
	 * @param bool  $is_success Whether it's a success event.
	 */
	private function process_payment( $payload, $is_success ) {
		$transaction_data = isset( $payload['data'] ) ? $payload['data'] : array();
		
		$reference = isset( $transaction_data['reference'] ) ? $transaction_data['reference'] : '';
		$order_id  = isset( $transaction_data['metadata']['order_id'] ) ? intval( $transaction_data['metadata']['order_id'] ) : 0;

		if ( ! $order_id && $reference ) {
			$order_id = wc_get_order_id_by_order_key( $reference );
		}

		if ( ! $order_id ) {
			Bachs_Logger::log( 'Webhook processing failed: Could not find order for reference ' . $reference, 'error' );
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			Bachs_Logger::log( 'Webhook processing failed: Invalid order ID ' . $order_id, 'error' );
			return;
		}

		// Re-verify the transaction with the API directly to avoid blind trust.
		$settings   = get_option( 'woocommerce_bachs_settings' );
		$api_key    = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
		$env        = ( strpos( $api_key, 'test' ) !== false || strpos( $api_key, 'sandbox' ) !== false ) ? 'sandbox' : 'live';

		$client       = new Bachs_Client( $api_key, $env );
		$transactions = new Bachs_Transactions( $client );
		
		$transaction_id = isset( $transaction_data['id'] ) ? $transaction_data['id'] : '';
		
		if ( empty( $transaction_id ) ) {
			Bachs_Logger::log( 'Webhook processing failed: Missing transaction ID in payload.', 'error' );
			return;
		}

		$verify_response = $transactions->verify( $transaction_id );

		if ( is_wp_error( $verify_response ) ) {
			Bachs_Logger::log( 'Transaction verification failed: ' . $verify_response->get_error_message(), 'error' );
			return;
		}

		// Ensure the status matches.
		$verified_status = strtolower( $verify_response['status'] );
		
		if ( $is_success && in_array( $verified_status, array( 'successful', 'succeeded' ) ) ) {
			Bachs_Order_Handler::payment_complete( $order, $verify_response );
		} elseif ( ! $is_success || 'failed' === $verified_status ) {
			Bachs_Order_Handler::payment_failed( $order, $verify_response );
		}
	}
}
