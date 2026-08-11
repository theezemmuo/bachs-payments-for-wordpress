<?php
namespace Bachs\WooCommerce;

use Bachs\Core\Logger;

/**
 * Manages WooCommerce orders sync with Bachs Webhooks.
 */
class OrderManager {
    public function init() {
        // Register Gateway
        add_filter( 'woocommerce_payment_gateways', [ $this, 'add_gateway' ] );

        // Listen to Bachs Webhook state mappings
        add_action( 'bachs_transaction_succeeded', [ $this, 'handle_payment_succeeded' ], 10, 2 );
        add_action( 'bachs_transaction_failed', [ $this, 'handle_payment_failed' ], 10, 2 );
        add_action( 'bachs_transaction_refunded', [ $this, 'handle_payment_refunded' ], 10, 2 );
    }

    public function add_gateway( $gateways ) {
        $gateways[] = 'Bachs\WooCommerce\Gateway';
        return $gateways;
    }

    /**
     * Handle succeeded payment webhook.
     */
    public function handle_payment_succeeded( $transaction, $event ) {
        $order = $this->get_order_from_transaction( $transaction );
        if ( ! $order ) {
            return;
        }

        // Store transaction ID
        $transaction_id = $transaction['id'];
        $order->update_meta_data( '_bachs_transaction_id', $transaction_id );
        $order->save();

        if ( $order->has_status( 'processing' ) || $order->has_status( 'completed' ) ) {
            Logger::log( "Order {$order->get_id()} is already paid. Skipping.", 'info' );
            return;
        }

        $order->payment_complete( $transaction_id );
        $order->add_order_note( sprintf( __( 'Bachs payment succeeded. Transaction ID: %s', 'bachs-payments' ), $transaction_id ) );
        Logger::log( "Order {$order->get_id()} marked as paid via webhook.", 'info' );
    }

    /**
     * Handle failed payment webhook.
     */
    public function handle_payment_failed( $transaction, $event ) {
        $order = $this->get_order_from_transaction( $transaction );
        if ( ! $order ) {
            return;
        }

        $order->update_status( 'failed', __( 'Bachs payment failed or was cancelled.', 'bachs-payments' ) );
        Logger::log( "Order {$order->get_id()} marked as failed via webhook.", 'info' );
    }

    /**
     * Handle refunded payment webhook.
     */
    public function handle_payment_refunded( $transaction, $event ) {
        $order = $this->get_order_from_transaction( $transaction );
        if ( ! $order ) {
            return;
        }

        // If refunded fully, we mark order as refunded
        $order->update_status( 'refunded', __( 'Order was refunded via Bachs dashboard.', 'bachs-payments' ) );
        Logger::log( "Order {$order->get_id()} marked as refunded via webhook.", 'info' );
    }

    /**
     * Find WC Order from transaction reference_id.
     */
    private function get_order_from_transaction( $transaction ) {
        $reference_id = isset( $transaction['reference_id'] ) ? $transaction['reference_id'] : null;

        if ( ! $reference_id ) {
            Logger::log( 'Transaction has no reference_id, cannot map to WC Order.', 'warning' );
            return null;
        }

        $order = wc_get_order( $reference_id );
        
        if ( ! $order ) {
            Logger::log( "WC Order {$reference_id} not found for transaction.", 'warning' );
            return null;
        }

        return $order;
    }
}
