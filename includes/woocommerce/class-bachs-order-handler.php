<?php
/**
 * Order Handler for synchronizing Bachs transactions to WooCommerce orders.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bachs_Order_Handler {

	/**
	 * Map a Bachs status to a WooCommerce status.
	 *
	 * @param string $bachs_status
	 * @return string
	 */
	public static function map_status( $bachs_status ) {
		switch ( strtolower( $bachs_status ) ) {
			case 'successful':
			case 'succeeded':
				return 'processing'; // WooCommerce handles transition from processing to completed for virtual goods if needed.
			case 'pending':
				return 'on-hold';
			case 'failed':
				return 'failed';
			case 'refunded':
				return 'refunded';
			case 'cancelled':
			case 'expired':
				return 'cancelled';
			default:
				return 'on-hold';
		}
	}

	/**
	 * Handle a successful payment.
	 *
	 * @param WC_Order $order
	 * @param array $transaction_data
	 */
	public static function payment_complete( $order, $transaction_data ) {
		if ( $order->has_status( array( 'processing', 'completed' ) ) ) {
			return; // Already processed.
		}

		$transaction_id = isset( $transaction_data['id'] ) ? $transaction_data['id'] : '';

		// Verify amount.
		$expected_amount = intval( round( $order->get_total() * 100 ) );
		$actual_amount   = isset( $transaction_data['amount'] ) ? intval( $transaction_data['amount'] ) : 0;

		if ( $expected_amount !== $actual_amount ) {
			Bachs_Logger::log( sprintf( 'Amount mismatch on order %d. Expected: %d, Actual: %d', $order->get_id(), $expected_amount, $actual_amount ), 'error' );
			$order->update_status( 'on-hold', __( 'Payment amount mismatch. Please verify in Bachs dashboard.', 'bachs-payments' ) );
			return;
		}

		// Verify currency.
		$expected_currency = strtolower( $order->get_currency() );
		$actual_currency   = isset( $transaction_data['currency'] ) ? strtolower( $transaction_data['currency'] ) : '';

		// Sandbox bypass: If order is NGN but transaction is USD in sandbox.
		$settings = get_option( 'woocommerce_bachs_settings' );
		$api_key  = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
		$is_sandbox = ( strpos( $api_key, 'test' ) !== false || strpos( $api_key, 'sandbox' ) !== false );
		
		if ( $is_sandbox && 'ngn' === $expected_currency && 'usd' === $actual_currency ) {
			$actual_currency = 'ngn'; // Bypass for sandbox NGN->USD fallback
		}

		if ( $expected_currency !== $actual_currency ) {
			Bachs_Logger::log( sprintf( 'Currency mismatch on order %d. Expected: %s, Actual: %s', $order->get_id(), $expected_currency, $actual_currency ), 'error' );
			$order->update_status( 'on-hold', __( 'Payment currency mismatch. Please verify in Bachs dashboard.', 'bachs-payments' ) );
			return;
		}

		$order->payment_complete( $transaction_id );
		$order->add_order_note( sprintf( __( 'Bachs payment successful (Transaction ID: %s)', 'bachs-payments' ), $transaction_id ) );

		do_action( 'bachs_payment_succeeded', $transaction_data, $order );
	}

	/**
	 * Handle a failed payment.
	 *
	 * @param WC_Order $order
	 * @param array $transaction_data
	 */
	public static function payment_failed( $order, $transaction_data ) {
		if ( $order->has_status( 'failed' ) ) {
			return;
		}

		$order->update_status( 'failed', __( 'Bachs payment failed.', 'bachs-payments' ) );
		do_action( 'bachs_payment_failed', $transaction_data, $order );
	}
}
