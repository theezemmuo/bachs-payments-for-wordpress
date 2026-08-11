<?php
namespace Bachs\WooCommerce;

use Bachs\Core\Plugin;
use Bachs\API\Client;
use Bachs\Core\Logger;

/**
 * WooCommerce Payment Gateway for Bachs.
 */
class Gateway extends \WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'bachs';
        $this->icon               = '';
        $this->has_fields         = false;
        $this->method_title       = __( 'Bachs', 'bachs-payments' );
        $this->method_description = __( 'Accept payments via Bachs.', 'bachs-payments' );

        $this->supports = [
            'products',
            'refunds',
        ];

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => __( 'Enable/Disable', 'bachs-payments' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable Bachs Gateway', 'bachs-payments' ),
                'default' => 'yes',
            ],
            'title' => [
                'title'       => __( 'Title', 'bachs-payments' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'bachs-payments' ),
                'default'     => __( 'Credit Card (Bachs)', 'bachs-payments' ),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => __( 'Description', 'bachs-payments' ),
                'type'        => 'textarea',
                'description' => __( 'This controls the description which the user sees during checkout.', 'bachs-payments' ),
                'default'     => __( 'Pay securely using your credit card via Bachs.', 'bachs-payments' ),
            ],
            // API keys are managed in the main Bachs settings page
        ];
    }

    /**
     * Process the payment and return the result.
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        $client = new Client();

        try {
            // Build checkout session payload
            $line_items = [];
            foreach ( $order->get_items() as $item ) {
                $line_items[] = [
                    'name'     => $item->get_name(),
                    'quantity' => $item->get_quantity(),
                    'amount'   => (int) round( $item->get_total() * 100 ), // cents
                ];
            }

            $payment_methods = get_option( 'bachs_payment_methods', ['card'] );
            if ( ! is_array( $payment_methods ) || empty( $payment_methods ) ) {
                $payment_methods = ['card'];
            }

            $payload = [
                'reference_id'         => $order_id,
                'currency'             => $order->get_currency(),
                'line_items'           => $line_items,
                'payment_method_types' => $payment_methods,
                'return_url'           => $this->get_return_url( $order ),
                'cancel_url'           => $order->get_cancel_order_url_raw(),
            ];

            // Allow devs to filter payload
            $payload = apply_filters( 'bachs_wc_checkout_session_payload', $payload, $order );

            $session = $client->checkout->create( $payload );

            if ( isset( $session['url'] ) ) {
                $order->update_meta_data( '_bachs_session_id', $session['id'] );
                $order->save();

                return [
                    'result'   => 'success',
                    'redirect' => $session['url'],
                ];
            }

            throw new \Exception( 'Invalid session response from Bachs.' );

        } catch ( \Exception $e ) {
            Logger::log( 'Checkout Error for Order #' . $order_id . ': ' . $e->getMessage(), 'error' );
            wc_add_notice( __( 'Payment error:', 'bachs-payments' ) . ' ' . $e->getMessage(), 'error' );
            return [ 'result' => 'fail' ];
        }
    }

    /**
     * Process a refund.
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {
        $order = wc_get_order( $order_id );
        $transaction_id = $order->get_meta( '_bachs_transaction_id' );

        if ( ! $transaction_id ) {
            return new \WP_Error( 'error', __( 'Refund failed: No Bachs transaction ID found.', 'bachs-payments' ) );
        }

        $client = new Client();

        try {
            $payload = [
                'transaction_id' => $transaction_id,
                'amount'         => (int) round( $amount * 100 ),
                'reason'         => $reason,
            ];

            $refund = $client->refunds->create( $payload );
            
            if ( isset( $refund['id'] ) ) {
                $order->add_order_note( sprintf( __( 'Refunded %s via Bachs (Refund ID: %s)', 'bachs-payments' ), wc_price( $amount ), $refund['id'] ) );
                return true;
            }

            return false;

        } catch ( \Exception $e ) {
            Logger::log( 'Refund Error for Order #' . $order_id . ': ' . $e->getMessage(), 'error' );
            return new \WP_Error( 'error', $e->getMessage() );
        }
    }
}
