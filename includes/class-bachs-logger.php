<?php
/**
 * Logger class for safe logging.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bachs_Logger {

	/**
	 * Log a message to WooCommerce logger.
	 *
	 * @param string $message The message to log.
	 * @param string $level   The log level (e.g. 'error', 'info', 'debug').
	 */
	public static function log( $message, $level = 'info' ) {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		// Check logging level setting from WooCommerce Gateway if available.
		$settings = get_option( 'woocommerce_bachs_settings' );
		if ( empty( $settings ) ) {
			$settings = array( 'logging' => 'yes' );
		}

		if ( 'no' === $settings['logging'] ) {
			return;
		}

		if ( 'error' === $settings['logging'] && 'error' !== $level ) {
			return;
		}

		$logger  = wc_get_logger();
		$context = array( 'source' => 'bachs-payments' );

		// Redact sensitive information before logging.
		$message = self::redact_sensitive_data( $message );

		$logger->log( $level, $message, $context );
	}

	/**
	 * Redact sensitive data from log messages.
	 *
	 * @param string $message The message.
	 * @return string
	 */
	private static function redact_sensitive_data( $message ) {
		// Redact Bearer tokens / API keys.
		$message = preg_replace( '/(sk_live_|sk_test_|pk_live_|pk_test_)([a-zA-Z0-9]+)/', '$1***', $message );
		$message = preg_replace( '/(Bearer\s+)([a-zA-Z0-9_]+)/', '$1***', $message );
		
		return $message;
	}
}
