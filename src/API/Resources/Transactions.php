<?php
namespace Bachs\API\Resources;

use Bachs\API\Client;

/**
 * Transactions Resource
 */
class Transactions {
    private $client;

    public function __construct( Client $client ) {
        $this->client = $client;
    }

    /**
     * Retrieve a transaction by ID.
     *
     * @param string $transaction_id
     * @return array
     */
    public function retrieve( $transaction_id ) {
        return $this->client->request( 'GET', "transactions/{$transaction_id}" );
    }

    /**
     * List transactions.
     *
     * @param array $params Query parameters
     * @return array
     */
    public function all( array $params = [] ) {
        return $this->client->request( 'GET', 'transactions', $params );
    }
}
