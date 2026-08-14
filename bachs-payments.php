<?php
/**
 * Plugin Name:       Bachs Payments for WordPress
 * Plugin URI:        https://frameio.com.ng/bachs
 * Description:       The secure, native Bachs integration for WordPress and WooCommerce.
 * Version:           1.0.4
 * Author:            Frameio
 * Author URI:        https://frameio.com.ng/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bachs-payments
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'BACHS_PAYMENTS_VERSION', '1.0.2' );
define( 'BACHS_PAYMENTS_PLUGIN_FILE', __FILE__ );
define( 'BACHS_PAYMENTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Include the core class.
require_once BACHS_PAYMENTS_PLUGIN_DIR . 'includes/class-bachs-core.php';

// Initialize the plugin.
function bachs_payments_init() {
	// Initialize core class.
	Bachs_Core::instance();
}
add_action( 'plugins_loaded', 'bachs_payments_init', 0 );
