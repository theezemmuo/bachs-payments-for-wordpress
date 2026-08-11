<?php
namespace Bachs\Webhooks;

use Bachs\Core\Logger;

/**
 * Processes valid webhook events.
 */
class EventProcessor {
    /**
     * Process an event.
     *
     * @param array $event
     * @throws \Exception
     */
    public function process( array $event ) {
        $event_id = isset( $event['id'] ) ? sanitize_text_field( $event['id'] ) : null;
        
        if ( ! $event_id ) {
            throw new \Exception( 'Event ID missing.' );
        }

        // Idempotency check: see if we already processed this event ID.
        // For simplicity, we use wp_options, but a custom table or transient might be better for scale.
        $processed_key = 'bachs_processed_event_' . $event_id;
        if ( get_option( $processed_key ) ) {
            Logger::log( "Event {$event_id} already processed. Skipping.", 'info' );
            return; // Already processed
        }

        // Lock it to prevent race conditions during long processing
        add_option( $processed_key, time(), '', 'no' );

        $type = isset( $event['type'] ) ? sanitize_text_field( $event['type'] ) : 'unknown';
        $data = isset( $event['data']['object'] ) ? $event['data']['object'] : [];

        Logger::log( "Processing webhook event: {$type}", 'info' );

        // Dispatch specific actions for developers to hook into
        do_action( 'bachs_webhook_received', $event );
        do_action( "bachs_webhook_{$type}", $data, $event );

        // Additionally, trigger state mapping actions if it's a transaction
        if ( strpos( $type, 'transaction.' ) === 0 ) {
            $state = isset( $data['status'] ) ? $data['status'] : null;
            if ( $state ) {
                $mapped_state = StateMapper::map( $state );
                do_action( "bachs_transaction_{$mapped_state}", $data, $event );
            }
        }
    }
}
