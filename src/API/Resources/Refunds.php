<?php
namespace Bachs\API\Resources;

use Bachs\API\Client;

/**
 * Refunds Resource
 */
class Refunds {
    private $client;

    public function __construct( Client $client ) {
        $this->client = $client;
    }

    /**
     * Create a refund for a transaction.
     *
     * @param array $params
     * @return array
     */
    public function create( array $params ) {
        return $this->client->request( 'POST', 'refunds', $params );
    }

    /**
     * Retrieve a refund.
     *
     * @param string $refund_id
     * @return array
     */
    public function retrieve( $refund_id ) {
        return $this->client->request( 'GET', "refunds/{$refund_id}" );
    }
}
