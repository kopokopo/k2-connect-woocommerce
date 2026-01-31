
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

const KKWOO_GATEWAY_ID = 'kkwoo';

// WooCommerce stores gateway settings under this option name
const KKWOO_OPTION_NAME = 'woocommerce_' . KKWOO_GATEWAY_ID . '_settings';

// Delete gateway settings
delete_option(KKWOO_OPTION_NAME);
