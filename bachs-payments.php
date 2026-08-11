<?php
/**
 * Plugin Name:       Bachs Payments for WordPress
 * Plugin URI:        https://frameio.com.ng/bachs
 * Description:       The secure, native Bachs integration for WordPress and WooCommerce.
 * Version:           1.0.0
 * Author:            Bachs
 * Author URI:        https://frameio.com.ng/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bachs-payments
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin constants
define( 'BACHS_VERSION', '1.0.0' );
define( 'BACHS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BACHS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load custom PSR-4 autoloader
require_once BACHS_PLUGIN_DIR . 'src/autoload.php';


/**
 * The code that runs during plugin activation.
 */
function activate_bachs_payments() {
	// e.g. Setup custom tables, check dependencies, add capabilities
}
register_activation_hook( __FILE__, 'activate_bachs_payments' );

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_bachs_payments() {
	// e.g. Flush rewrite rules
}
register_deactivation_hook( __FILE__, 'deactivate_bachs_payments' );

/**
 * Begins execution of the plugin.
 */
function run_bachs_payments() {
    if ( class_exists( 'Bachs\\Core\\Plugin' ) ) {
        $plugin = \Bachs\Core\Plugin::get_instance();
        $plugin->run();
    }
}
add_action( 'plugins_loaded', 'run_bachs_payments' );
