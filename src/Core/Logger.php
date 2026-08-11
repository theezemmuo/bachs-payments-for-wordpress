<?php
namespace Bachs\Core;

/**
 * Secure logging class that redacts sensitive information.
 */
class Logger {
    private static $log_file;

    /**
     * Initialize logger.
     */
    public static function init() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/bachs-logs';
        
        if ( ! file_exists( $log_dir ) ) {
            wp_mkdir_p( $log_dir );
            // Protect log directory
            file_put_contents( $log_dir . '/.htaccess', 'deny from all' );
            file_put_contents( $log_dir . '/index.html', '' );
        }

        self::$log_file = $log_dir . '/bachs-' . date( 'Y-m' ) . '-' . wp_hash( 'bachs' ) . '.log';
    }

    /**
     * Write to log.
     */
    public static function log( $message, $level = 'info' ) {
        if ( ! self::should_log( $level ) ) {
            return;
        }

        $message = is_array( $message ) || is_object( $message ) ? print_r( $message, true ) : $message;
        $message = self::redact( $message );

        $formatted_message = sprintf( "[%s] [%s] %s\n", current_time( 'mysql' ), strtoupper( $level ), $message );
        
        if ( self::$log_file ) {
            error_log( $formatted_message, 3, self::$log_file );
        }
    }

    /**
     * Redact sensitive information (API keys, Auth headers) from the message.
     */
    private static function redact( $message ) {
        // Redact secret keys
        $message = preg_replace( '/(sk_(test|live)_[a-zA-Z0-9]+)/i', 'sk_***', $message );
        // Redact authorization headers
        $message = preg_replace( '/(Bearer\s+)[^\s"\'\n\r]+/i', '$1***', $message );
        
        return $message;
    }

    /**
     * Check if the level should be logged based on settings.
     */
    private static function should_log( $level ) {
        $setting = get_option( 'bachs_logging_level', 'errors' );
        
        if ( 'off' === $setting ) {
            return false;
        }
        
        if ( 'errors' === $setting && ! in_array( $level, [ 'error', 'critical', 'warning' ], true ) ) {
            return false;
        }
        
        return true; // 'all' setting logs everything
    }
}
