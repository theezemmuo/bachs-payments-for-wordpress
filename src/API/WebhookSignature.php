<?php
namespace Bachs\API;

/**
 * Handles Webhook signature verification.
 */
class WebhookSignature {
    /**
     * Verify the webhook signature.
     *
     * @param string $payload The raw JSON payload body
     * @param string $sig_header The signature header (e.g., from HTTP_BACHS_SIGNATURE)
     * @param string $secret The webhook secret
     * @param int $tolerance Time tolerance in seconds
     * @return bool
     * @throws Exceptions\ApiException
     */
    public static function verify( $payload, $sig_header, $secret, $tolerance = 300 ) {
        if ( empty( $sig_header ) ) {
            throw new Exceptions\ApiException( 'No signature header provided.' );
        }

        // Assume signature header format: "t=1620000000,v1=abcdef123456..."
        $parts = explode( ',', $sig_header );
        $timestamp = null;
        $signatures = [];

        foreach ( $parts as $part ) {
            $kv = explode( '=', trim( $part ), 2 );
            if ( count( $kv ) === 2 ) {
                if ( $kv[0] === 't' ) {
                    $timestamp = (int) $kv[1];
                } elseif ( $kv[0] === 'v1' ) {
                    $signatures[] = $kv[1];
                }
            }
        }

        if ( null === $timestamp || empty( $signatures ) ) {
            throw new Exceptions\ApiException( 'Invalid signature header format.' );
        }

        // Check tolerance
        if ( time() - $timestamp > $tolerance ) {
            throw new Exceptions\ApiException( 'Webhook signature timestamp is outside of the tolerance zone.' );
        }

        // Calculate expected signature
        $signed_payload = $timestamp . '.' . $payload;
        $expected_sig = hash_hmac( 'sha256', $signed_payload, $secret );

        // Constant time comparison to prevent timing attacks
        foreach ( $signatures as $signature ) {
            if ( hash_equals( $expected_sig, $signature ) ) {
                return true;
            }
        }

        throw new Exceptions\ApiException( 'Webhook signature verification failed.' );
    }
}
