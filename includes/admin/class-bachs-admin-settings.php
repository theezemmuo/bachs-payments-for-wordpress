<?php
/**
 * Admin Settings Handler.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bachs_Admin_Settings {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'plugin_action_links_' . plugin_basename( BACHS_PAYMENTS_PLUGIN_FILE ), array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Add settings link to plugin list.
	 *
	 * @param array $links Plugin links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=bachs' ) . '">' . __( 'Settings', 'bachs-payments' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}
}

new Bachs_Admin_Settings();
