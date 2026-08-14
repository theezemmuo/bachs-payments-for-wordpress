<?php
/**
 * Checkout API Wrapper.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bachs_Checkout {

	/**
	 * @var Bachs_Client
	 */
	private $client;

	/**
	 * Constructor.
	 *
	 * @param Bachs_Client $client API Client instance.
	 */
	public function __construct( $client ) {
		$this->client = $client;
	}

	/**
	 * Create a checkout session.
	 *
	 * @param array $data Checkout parameters.
	 * @return array|WP_Error
	 */
	public function create_session( $data ) {
		/**
		 * Filter the checkout parameters before sending to API.
		 *
		 * @param array $data Parameters.
		 */
		$data = apply_filters( 'bachs_checkout_parameters', $data );

		return $this->client->post( '/checkout-sessions', $data );
	}
}
