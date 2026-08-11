<?php
namespace Bachs\API;

use Bachs\Core\Logger;
use Bachs\API\Exceptions\ApiException;

/**
 * Core API Client for Bachs API.
 */
class Client {
    private $api_base = 'https://api.bachs.io/v1';
    private $secret_key;
    
    public $checkout;
    public $transactions;
    public $refunds;

    public function __construct() {
        $env = get_option( 'bachs_environment', 'sandbox' );
        $this->secret_key = get_option( "bachs_{$env}_secret_key" );
        
        $this->checkout     = new Resources\CheckoutSessions( $this );
        $this->transactions = new Resources\Transactions( $this );
        $this->refunds      = new Resources\Refunds( $this );
    }

    /**
     * Make an HTTP request to the Bachs API.
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $endpoint API endpoint relative to base
     * @param array  $params Request parameters or body
     * @return array Decoded JSON response
     * @throws ApiException
     */
    public function request( $method, $endpoint, $params = [] ) {
        if ( empty( $this->secret_key ) ) {
            throw new ApiException( 'Bachs API key is not configured.' );
        }

        $url = rtrim( $this->api_base, '/' ) . '/' . ltrim( $endpoint, '/' );

        $args = [
            'method'  => strtoupper( $method ),
            'headers' => [
                'Authorization' => 'Bearer ' . $this->secret_key,
                'Content-Type'  => 'application/json',
                'User-Agent'    => 'Bachs-WordPress-Plugin/' . (defined('BACHS_VERSION') ? BACHS_VERSION : '1.0.0'),
            ],
            'timeout' => 30,
        ];

        if ( ! empty( $params ) ) {
            if ( strtoupper( $method ) === 'GET' ) {
                $url = add_query_arg( $params, $url );
            } else {
                $args['body'] = wp_json_encode( $params );
            }
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            Logger::log( 'HTTP Request Error: ' . $response->get_error_message(), 'error' );
            throw new ApiException( $response->get_error_message() );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $status_code >= 400 ) {
            $error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Unknown API Error';
            Logger::log( "API Error [{$status_code}]: {$error_message}", 'error' );
            throw new ApiException( $error_message, $status_code );
        }

        return $data;
    }
}
