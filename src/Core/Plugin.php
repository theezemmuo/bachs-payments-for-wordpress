<?php
namespace Bachs\Core;

/**
 * Main plugin orchestrator.
 */
class Plugin {
    private static $instance = null;

    /**
     * Get singleton instance.
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Private constructor to prevent multiple instances
    }

    /**
     * Run the plugin.
     */
    public function run() {
        // Initialize Logger
        Logger::init();

        // Initialize Admin Settings if in admin area
        if ( is_admin() ) {
            $settings = new Admin\Settings();
            $settings->init();
        }

        // Initialize Webhook Engine
        $webhook_controller = new \Bachs\Webhooks\Controller();
        $webhook_controller->init();

        // Initialize WooCommerce Adapter if WooCommerce is active
        // Normally we'd use class_exists('WooCommerce') but for decoupling we can hook into plugins_loaded earlier or just check it here.
        if ( class_exists( 'WooCommerce' ) ) {
            $wc_adapter = new \Bachs\WooCommerce\OrderManager();
            $wc_adapter->init();
        }
    }
}
