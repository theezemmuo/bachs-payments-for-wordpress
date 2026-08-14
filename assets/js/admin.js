/**
 * Assets JavaScript for WP Admin settings page.
 */
jQuery(document).ready(function($) {
    $('#bachs-test-connection').on('click', function(e) {
        e.preventDefault();

        var $btn = $(this);
        var $result = $('#bachs-test-result');

        var apiKey = $('#woocommerce_bachs_api_key').val() || '';
        var environment = ( apiKey.indexOf('test') !== -1 || apiKey.indexOf('sandbox') !== -1 ) ? 'sandbox' : 'live';

        if (!apiKey) {
            $result.css('color', 'red').text('Please fill in the API key before testing.');
            return;
        }

        $btn.prop('disabled', true).text('Testing...');
        $result.text('');

        $.post(bachs_admin_params.ajax_url, {
            action: 'bachs_test_connection',
            nonce: bachs_admin_params.nonce,
            environment: environment,
            api_key: apiKey
        }, function(response) {
            $btn.prop('disabled', false).text('Test Connection');

            if (response.success) {
                $result.css('color', 'green').text(response.data.message);
            } else {
                $result.css('color', 'red').text(response.data.message);
            }
        }).fail(function() {
            $btn.prop('disabled', false).text('Test Connection');
            $result.css('color', 'red').text('An unexpected error occurred. Check logs.');
        });
    });
});
