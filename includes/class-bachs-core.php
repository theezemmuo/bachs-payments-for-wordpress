<?php
/**
 * Core plugin class.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bachs_Core {

	/**
	 * The single instance of the class.
	 *
	 * @var Bachs_Core
	 */
	protected static $_instance = null;

	/**
	 * Main Bachs_Core Instance.
	 *
	 * @return Bachs_Core
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0.0' );
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required core files.
	 */
	private function includes() {
		// Include logger.
		require_once BACHS_PAYMENTS_PLUGIN_DIR . 'includes/class-bachs-logger.php';

		// Include API client classes.
		require_once BACHS_PAYMENTS_PLUGIN_DIR . 'includes/api/class-bachs-client.php';
		require_once BACHS_PAYMENTS_PLUGIN_DIR . 'includes/api/class-bachs-checkout.php';
		require_once BACHS_PAYMENTS_PLUGIN_DIR . 'includes/api/class-bachs-transactions.php';
		require_once BACHS_PAYMENTS_PLUGIN_DIR . 'includes/api/class-bachs-refunds.php';

		if ( is_admin() ) {
			require_once BACHS_PAYMENTS_PLUGIN_DIR . 'includes/admin/class-bachs-admin-settings.php';
			require_once BACHS_PAYMENTS_PLUGIN_DIR . 'includes/admin/class-bachs-connection-test.php';
		}

		// Webhooks.
		require_once BACHS_PAYMENTS_PLUGIN_DIR . 'includes/webhooks/class-bachs-webhook-handler.php';
		require_once BACHS_PAYMENTS_PLUGIN_DIR . 'includes/webhooks/class-bachs-webhook-security.php';

		// WooCommerce Integration.
		require_once BACHS_PAYMENTS_PLUGIN_DIR . 'includes/woocommerce/class-bachs-order-handler.php';
	}

	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_payment_gateway' ) );
	}

	/**
	 * Register REST routes for webhooks.
	 */
	public function register_rest_routes() {
		$webhook_handler = new Bachs_Webhook_Handler();
		$webhook_handler->register_routes();
	}

	/**
	 * Add Bachs Payment Gateway to WooCommerce.
	 *
	 * @param array $gateways Existing gateways.
	 * @return array
	 */
	public function add_payment_gateway( $gateways ) {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return $gateways;
		}

		require_once BACHS_PAYMENTS_PLUGIN_DIR . 'includes/woocommerce/class-bachs-payment-gateway.php';
		$gateways[] = 'Bachs_Payment_Gateway';
		return $gateways;
	}
}
