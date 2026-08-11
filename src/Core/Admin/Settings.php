<?php
namespace Bachs\Core\Admin;

/**
 * Admin settings page for Bachs integration.
 */
class Settings {
    /**
     * Initialize settings hooks.
     */
    public function init() {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Add options page.
     */
    public function add_menu_page() {
        add_options_page(
            __( 'Bachs Settings', 'bachs-payments' ),
            __( 'Bachs', 'bachs-payments' ),
            'manage_options',
            'bachs-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Register settings and sections.
     */
    public function register_settings() {
        // Connection Section
        add_settings_section(
            'bachs_section_connection',
            __( 'Connection', 'bachs-payments' ),
            null,
            'bachs-settings'
        );

        register_setting( 'bachs_settings_group', 'bachs_environment', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'sandbox'
        ] );

        register_setting( 'bachs_settings_group', 'bachs_sandbox_public_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ] );

        register_setting( 'bachs_settings_group', 'bachs_sandbox_secret_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ] );

        register_setting( 'bachs_settings_group', 'bachs_live_public_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ] );

        register_setting( 'bachs_settings_group', 'bachs_live_secret_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ] );

        // Webhook Section
        add_settings_section(
            'bachs_section_webhooks',
            __( 'Webhooks', 'bachs-payments' ),
            null,
            'bachs-settings'
        );

        register_setting( 'bachs_settings_group', 'bachs_webhook_secret', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ] );

        // Advanced Section
        add_settings_section(
            'bachs_section_advanced',
            __( 'Advanced', 'bachs-payments' ),
            null,
            'bachs-settings'
        );

        register_setting( 'bachs_settings_group', 'bachs_logging_level', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'errors'
        ] );

        register_setting( 'bachs_settings_group', 'bachs_payment_methods', [
            'type' => 'array',
            'sanitize_callback' => [ $this, 'sanitize_payment_methods' ],
            'default' => ['card']
        ] );
    }

    /**
     * Sanitize payment methods array.
     */
    public function sanitize_payment_methods( $input ) {
        if ( ! is_array( $input ) ) {
            return ['card'];
        }
        return array_map( 'sanitize_text_field', $input );
    }

    /**
     * Render the settings page HTML.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $environment = get_option( 'bachs_environment', 'sandbox' );
        $logging_level = get_option( 'bachs_logging_level', 'errors' );
        $payment_methods = get_option( 'bachs_payment_methods', ['card'] );
        if ( ! is_array( $payment_methods ) ) {
            $payment_methods = ['card'];
        }
        
        // Simple HTML form for settings (in reality, using add_settings_field is better for standard UI)
        // But for clarity we'll use a direct HTML rendering.
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Bachs Settings', 'bachs-payments' ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'bachs_settings_group' );
                do_settings_sections( 'bachs_settings_group' );
                ?>
                
                <h2><?php esc_html_e( 'Connection', 'bachs-payments' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Environment', 'bachs-payments' ); ?></th>
                        <td>
                            <label>
                                <input type="radio" name="bachs_environment" value="sandbox" <?php checked( 'sandbox', $environment ); ?>>
                                <?php esc_html_e( 'Sandbox', 'bachs-payments' ); ?>
                            </label><br>
                            <label>
                                <input type="radio" name="bachs_environment" value="live" <?php checked( 'live', $environment ); ?>>
                                <?php esc_html_e( 'Live', 'bachs-payments' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Sandbox Public Key', 'bachs-payments' ); ?></th>
                        <td><input type="text" name="bachs_sandbox_public_key" value="<?php echo esc_attr( get_option( 'bachs_sandbox_public_key' ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Sandbox Secret Key', 'bachs-payments' ); ?></th>
                        <td><input type="password" name="bachs_sandbox_secret_key" value="<?php echo esc_attr( get_option( 'bachs_sandbox_secret_key' ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Live Public Key', 'bachs-payments' ); ?></th>
                        <td><input type="text" name="bachs_live_public_key" value="<?php echo esc_attr( get_option( 'bachs_live_public_key' ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Live Secret Key', 'bachs-payments' ); ?></th>
                        <td><input type="password" name="bachs_live_secret_key" value="<?php echo esc_attr( get_option( 'bachs_live_secret_key' ) ); ?>" class="regular-text"></td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Webhooks', 'bachs-payments' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Webhook Endpoint', 'bachs-payments' ); ?></th>
                        <td>
                            <code><?php echo esc_url( rest_url( 'bachs/v1/webhook' ) ); ?></code>
                            <p class="description"><?php esc_html_e( 'Configure this endpoint in your Bachs dashboard.', 'bachs-payments' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Webhook Secret', 'bachs-payments' ); ?></th>
                        <td><input type="password" name="bachs_webhook_secret" value="<?php echo esc_attr( get_option( 'bachs_webhook_secret' ) ); ?>" class="regular-text"></td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Advanced', 'bachs-payments' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Payment Methods', 'bachs-payments' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="bachs_payment_methods[]" value="card" <?php checked( in_array( 'card', $payment_methods ) ); ?>>
                                <?php esc_html_e( 'Credit / Debit Card', 'bachs-payments' ); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="bachs_payment_methods[]" value="ideal" <?php checked( in_array( 'ideal', $payment_methods ) ); ?>>
                                <?php esc_html_e( 'iDEAL', 'bachs-payments' ); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="bachs_payment_methods[]" value="alipay" <?php checked( in_array( 'alipay', $payment_methods ) ); ?>>
                                <?php esc_html_e( 'Alipay', 'bachs-payments' ); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="bachs_payment_methods[]" value="sepa_debit" <?php checked( in_array( 'sepa_debit', $payment_methods ) ); ?>>
                                <?php esc_html_e( 'SEPA Direct Debit', 'bachs-payments' ); ?>
                            </label>
                            <p class="description"><?php esc_html_e( 'Select which payment methods to offer customers at checkout.', 'bachs-payments' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Logging', 'bachs-payments' ); ?></th>
                        <td>
                            <select name="bachs_logging_level">
                                <option value="off" <?php selected( 'off', $logging_level ); ?>><?php esc_html_e( 'Off', 'bachs-payments' ); ?></option>
                                <option value="errors" <?php selected( 'errors', $logging_level ); ?>><?php esc_html_e( 'Errors Only', 'bachs-payments' ); ?></option>
                                <option value="all" <?php selected( 'all', $logging_level ); ?>><?php esc_html_e( 'All Events', 'bachs-payments' ); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
