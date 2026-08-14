<?php
/**
 * Refunds API Wrapper.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bachs_Refunds {

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
	 * Create a refund.
	 *
	 * @param string $transaction_id The transaction to refund.
	 * @param float|null $amount Amount to refund (null for full refund).
	 * @param string $reason Refund reason.
	 * @return array|WP_Error
	 */
	public function create( $transaction_id, $amount = null, $reason = '' ) {
		$data = array(
			'transaction' => $transaction_id,
		);

		if ( null !== $amount ) {
			$data['amount'] = $amount;
		}

		if ( ! empty( $reason ) ) {
			$data['reason'] = $reason;
		}

		return $this->client->post( '/refunds', $data );
	}
}
