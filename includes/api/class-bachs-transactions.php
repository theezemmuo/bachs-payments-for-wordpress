<?php
/**
 * Transactions API Wrapper.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bachs_Transactions {

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
	 * Verify a transaction by ID.
	 *
	 * @param string $transaction_id The Bachs transaction ID.
	 * @return array|WP_Error
	 */
	public function verify( $transaction_id ) {
		return $this->client->get( '/transactions/' . urlencode( $transaction_id ) );
	}
}
