<?php
/**
 * WooCommerce Payment Gateway.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bachs_Payment_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'bachs';
		$this->icon               = apply_filters( 'bachs_gateway_icon', 'https://bachs.io/favicon.png' );
		$this->has_fields         = false;
		$this->method_title       = __( 'Bachs', 'bachs-payments' );
		$this->method_description = __( 'Accept payments via Bachs.', 'bachs-payments' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled     = $this->get_option( 'enabled' );
		$this->logging     = 'yes' === $this->get_option( 'logging', 'no' );

		$this->api_key        = $this->get_option( 'api_key' );
		$this->webhook_secret = $this->get_option( 'webhook_secret' );
		$this->testmode       = ( strpos( $this->api_key, 'test' ) !== false || strpos( $this->api_key, 'sandbox' ) !== false );

		// Supports feature.
		$this->supports = array(
			'products',
			'refunds',
		);

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		
		// Enqueue scripts for the connection test in admin.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	/**
	 * Initialize Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'bachs-payments' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Bachs Payments', 'bachs-payments' ),
				'default' => 'no',
			),
			'title' => array(
				'title'       => __( 'Title', 'bachs-payments' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'bachs-payments' ),
				'default'     => __( 'Bachs', 'bachs-payments' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'bachs-payments' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'bachs-payments' ),
				'default'     => __( 'Pay securely with Bachs.', 'bachs-payments' ),
				'desc_tip'    => true,
			),
			'api_key' => array(
				'title'       => __( 'API Key', 'bachs-payments' ),
				'type'        => 'password',
				'default'     => '',
				'description' => __( 'Your Bachs API Key. The environment (Live or Sandbox) will be determined automatically based on the key.', 'bachs-payments' ),
				'desc_tip'    => true,
			),
			'webhook_secret' => array(
				'title'       => __( 'Webhook Secret', 'bachs-payments' ),
				'type'        => 'password',
				'default'     => '',
				'description' => __( 'The webhook secret provided by the Bachs dashboard.', 'bachs-payments' ),
			),
			'logging' => array(
				'title'       => __( 'Logging', 'bachs-payments' ),
				'type'        => 'select',
				'description' => __( 'Log events to assist with troubleshooting.', 'bachs-payments' ),
				'default'     => 'error',
				'options'     => array(
					'no'    => __( 'Disable logging', 'bachs-payments' ),
					'error' => __( 'Log errors only', 'bachs-payments' ),
					'info'  => __( 'Log all messages (Debug)', 'bachs-payments' ),
				),
			),
			'webhook_url' => array(
				'title'       => __( 'Webhook URL', 'bachs-payments' ),
				'type'        => 'title',
				'description' => sprintf(
					/* translators: %s: Webhook URL */
					__( 'Copy this URL and set it up in your Bachs dashboard: <strong>%s</strong>', 'bachs-payments' ),
					rest_url( 'bachs/v1/webhook' )
				),
			),
			'test_connection' => array(
				'title'       => __( 'Test Connection', 'bachs-payments' ),
				'type'        => 'title',
				'description' => '<button type="button" id="bachs-test-connection" class="button">' . __( 'Test Connection', 'bachs-payments' ) . '</button><span id="bachs-test-result" style="margin-left: 10px;"></span>',
			),
		);
	}

	/**
	 * Admin scripts for connection test.
	 */
	public function admin_scripts( $hook ) {
		if ( 'woocommerce_page_wc-settings' !== $hook ) {
			return;
		}

		if ( ! isset( $_GET['section'] ) || 'bachs' !== $_GET['section'] ) {
			return;
		}

		wp_enqueue_script( 'bachs-admin', plugins_url( 'assets/js/admin.js', BACHS_PAYMENTS_PLUGIN_FILE ), array( 'jquery' ), BACHS_PAYMENTS_VERSION, true );
		wp_localize_script( 'bachs-admin', 'bachs_admin_params', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'bachs_test_connection_nonce' ),
		) );
	}

	/**
	 * Get the API client instance.
	 *
	 * @return Bachs_Client
	 */
	public function get_api_client() {
		$env = $this->testmode ? 'sandbox' : 'live';
		return new Bachs_Client( $this->api_key, $env );
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		
		$client   = $this->get_api_client();
		$checkout = new Bachs_Checkout( $client );

		$order_currency = $order->get_currency();
		$api_currency   = $order_currency;

		// Sandbox fallback: Bachs doesn't support NGN in sandbox, so we force USD.
		if ( $this->testmode && 'NGN' === $order_currency ) {
			$api_currency = 'USD';
		}

		$checkout_params = array(
			'pricing'      => array(
				'amount'   => number_format( $order->get_total(), 2, '.', '' ),
				'currency' => $api_currency,
			),
			'reference'    => $order->get_order_key(),
			'customer'     => array(
				'email' => $order->get_billing_email(),
				'name'  => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
			),
			'success_url'  => $this->get_return_url( $order ),
			'cancel_url'   => $order->get_cancel_order_url_raw(),
			'metadata'     => array(
				'order_id' => $order_id,
			),
		);

		$response = $checkout->create_session( $checkout_params );

		if ( is_wp_error( $response ) ) {
			Bachs_Logger::log( 'Checkout session creation failed: ' . $response->get_error_message(), 'error' );
			wc_add_notice( __( 'Payment error: ' . $response->get_error_message(), 'bachs-payments' ), 'error' );
			return array(
				'result' => 'fail',
			);
		}

		// Store session ID if available.
		if ( isset( $response['id'] ) ) {
			$order->update_meta_data( '_bachs_checkout_session_id', $response['id'] );
			$order->save();
		}

		// Check for checkout URL.
		if ( isset( $response['checkout_url'] ) ) {
			return array(
				'result'   => 'success',
				'redirect' => $response['checkout_url'],
			);
		} else {
			Bachs_Logger::log( 'No checkout URL returned from Bachs for order ' . $order_id, 'error' );
			wc_add_notice( __( 'Unable to process checkout. Please try again.', 'bachs-payments' ), 'error' );
			return array(
				'result' => 'fail',
			);
		}
	}

	/**
	 * Process a refund if supported.
	 *
	 * @param int    $order_id Order ID.
	 * @param float  $amount Refund amount.
	 * @param string $reason Refund reason.
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		$transaction_id = $order->get_transaction_id();
		if ( empty( $transaction_id ) ) {
			return new WP_Error( 'error', __( 'Refund failed: No transaction ID found.', 'bachs-payments' ) );
		}

		$client  = $this->get_api_client();
		$refunds = new Bachs_Refunds( $client );

		// Check if amount is equivalent to total, otherwise partial.
		$is_full = ( $amount == $order->get_total() );

		// Ensure amount is in smallest currency unit if not full.
		$api_amount = $is_full ? null : intval( round( $amount * 100 ) );

		$response = $refunds->create( $transaction_id, $api_amount, $reason );

		if ( is_wp_error( $response ) ) {
			Bachs_Logger::log( 'Refund failed for order ' . $order_id . ': ' . $response->get_error_message(), 'error' );
			return new WP_Error( 'error', __( 'Refund failed via Bachs.', 'bachs-payments' ) );
		}

		$order->add_order_note( sprintf( __( 'Refunded %s via Bachs.', 'bachs-payments' ), wc_price( $amount ) ) );

		return true;
	}
}
