<?php
/**
 * Webhook Security Handler.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bachs_Webhook_Security {

	/**
	 * Verify webhook signature.
	 *
	 * @param WP_REST_Request $request
	 * @return bool
	 */
	public static function verify_signature( $request ) {
		$settings       = get_option( 'woocommerce_bachs_settings' );
		$webhook_secret = isset( $settings['webhook_secret'] ) ? $settings['webhook_secret'] : '';

		if ( empty( $webhook_secret ) ) {
			Bachs_Logger::log( 'Cannot verify webhook: Webhook secret is missing in settings.', 'error' );
			return false;
		}

		// Check signature header. Bachs might use something like 'Bachs-Signature'.
		$signature_header = $request->get_header( 'bachs-signature' );
		if ( empty( $signature_header ) ) {
			Bachs_Logger::log( 'Missing Bachs-Signature header.', 'error' );
			return false;
		}

		$payload = $request->get_body();
		
		// Typically an HMAC SHA256 of the payload.
		$expected_signature = hash_hmac( 'sha256', $payload, $webhook_secret );

		if ( ! hash_equals( $expected_signature, $signature_header ) ) {
			Bachs_Logger::log( 'Webhook signature verification failed.', 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Check idempotency to prevent duplicate event processing.
	 *
	 * @param string $event_id
	 * @return bool True if already processed, false otherwise.
	 */
	public static function check_idempotency( $event_id ) {
		$transient_name = 'bachs_event_' . $event_id;
		if ( get_transient( $transient_name ) ) {
			return true;
		}

		// Store for 24 hours to prevent replays.
		set_transient( $transient_name, true, DAY_IN_SECONDS );
		return false;
	}
}
