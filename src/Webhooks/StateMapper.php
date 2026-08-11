<?php
namespace Bachs\Webhooks;

/**
 * Maps Bachs states to internal logical states.
 */
class StateMapper {
    /**
     * Map a Bachs transaction state to an internal simplified state.
     * Bachs states: processing, succeeded, accepted, failed, expired, cancelled, refunded, partially_refunded, underpaid, overpaid
     *
     * @param string $bachs_state
     * @return string
     */
    public static function map( $bachs_state ) {
        switch ( $bachs_state ) {
            case 'succeeded':
            case 'accepted':
            case 'overpaid': // Depends on business logic, assuming successful but needs review
                return 'succeeded';
                
            case 'processing':
                return 'processing';
                
            case 'failed':
            case 'expired':
            case 'cancelled':
                return 'failed';
                
            case 'refunded':
                return 'refunded';
                
            case 'partially_refunded':
                return 'partially_refunded';
                
            case 'underpaid':
                return 'underpaid'; // Needs manual resolution
                
            default:
                return 'unknown';
        }
    }
}
