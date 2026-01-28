
<?php
/**
 * Plugin uninstall cleanup
 *
 * Removes plugin settings from the database.
 *
 * IMPORTANT:
 * Order-related payment metadata and table are intentionally preserved
 * to allow reconciliation if the plugin is reinstalled and
 * to maintain the history of the transactions.
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$gateway_id = 'kkwoo';

// WooCommerce stores gateway settings under this option name
$option_name = 'woocommerce_' . $gateway_id . '_settings';

// Delete gateway settings
delete_option($option_name);
