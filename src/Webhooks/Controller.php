<?php
namespace Bachs\Webhooks;

use Bachs\API\WebhookSignature;
use Bachs\Core\Logger;
use Bachs\API\Exceptions\ApiException;

/**
 * Controller for the /wp-json/bachs/v1/webhook endpoint.
 */
class Controller {
    public function init() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( 'bachs/v1', '/webhook', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_request' ],
            'permission_callback' => '__return_true', // Validation happens inside via signature
        ] );
    }

    public function handle_request( \WP_REST_Request $request ) {
        $payload = $request->get_body();
        $sig_header = $request->get_header( 'bachs_signature' ) ?: $request->get_header( 'x_bachs_signature' );
        $secret = get_option( 'bachs_webhook_secret' );

        try {
            if ( empty( $secret ) ) {
                throw new ApiException( 'Webhook secret is not configured.' );
            }

            // Verify signature
            WebhookSignature::verify( $payload, $sig_header, $secret );
            
            $event = json_decode( $payload, true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                throw new ApiException( 'Invalid JSON payload.' );
            }

            // Process event
            $processor = new EventProcessor();
            $processor->process( $event );

            return rest_ensure_response( [ 'success' => true ] );

        } catch ( ApiException $e ) {
            Logger::log( 'Webhook Error: ' . $e->getMessage(), 'error' );
            return new \WP_Error( 'bachs_webhook_error', $e->getMessage(), [ 'status' => 400 ] );
        } catch ( \Exception $e ) {
            Logger::log( 'Webhook Uncaught Error: ' . $e->getMessage(), 'error' );
            return new \WP_Error( 'bachs_webhook_error', 'Internal Server Error', [ 'status' => 500 ] );
        }
    }
}
