<?php
namespace Bachs\API\Resources;

use Bachs\API\Client;

/**
 * Checkout Sessions Resource
 */
class CheckoutSessions {
    private $client;

    public function __construct( Client $client ) {
        $this->client = $client;
    }

    /**
     * Create a new checkout session.
     *
     * @param array $params Checkout session parameters
     * @return array
     */
    public function create( array $params ) {
        return $this->client->request( 'POST', 'checkout/sessions', $params );
    }

    /**
     * Retrieve a checkout session.
     *
     * @param string $session_id
     * @return array
     */
    public function retrieve( $session_id ) {
        return $this->client->request( 'GET', "checkout/sessions/{$session_id}" );
    }
}
