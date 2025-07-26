<?php

/*
* Plugin Name: Kopo Kopo for WooCommerce
* Plugin URI:
* Description: A Kopo Kopo plugin that integrates seamlessly with your WooCommerce shop, enabling your customers to make secure and convenient payments directly to your Kopo Kopo M-Pesa till.
* Version: 0.1.0
* Requires at least: 6.8.1
* Requires PHP: 7.4
* Author: Doreen Chemweno
* Author URI: https://kopokopo.co.ke
* License:
* License URI:
* Requires Plugins: woocommerce
* WC tested up to: 8.0
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/rest-api.php';

if (!defined('KKWOO_SANDBOX_URL')) {
    define('KKWOO_SANDBOX_URL', 'https://sandbox.kopokopo.com');
}

if (!defined('KKWOO_PRODUCTION_URL')) {
    define('KKWOO_PRODUCTION_URL', 'https://app.kopokopo.com');
}

if (!defined('KKWOO_PLUGIN_PATH')) {
    define('KKWOO_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

if (!defined('KKWOO_PLUGIN_URL')) {
    define('KKWOO_PLUGIN_URL', plugin_dir_url(__FILE__));
}

//TODO: MUST BE REMOVED - necessary for clearing caches in dev mode
// Call it once
add_action('init', 'kkwoo_clear_all_caches', 1);
function kkwoo_clear_all_caches()
{
    // Clear WordPress caches
    wp_cache_flush();

    // Clear WooCommerce transients
    wc_delete_product_transients();

    // Clear any plugin-specific transients
    delete_transient('kkwoo_cache');

    // Opcache
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }

    error_log('KKWOO: All caches cleared');
}
//TODO: END OF MUST BE REMOVED

// Register the gateway on plugins_loaded
add_action('plugins_loaded', 'woocommerce_gateway_k2_payment_init', 0);
function woocommerce_gateway_k2_payment_init()
{
    if (! class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="error"><p><strong>';
            echo esc_html('WooCommerce is not active. Please install and activate WooCommerce to use the Kopo Kopo for WooCommerce plugin.');
            echo '</strong></p></div>';
        });
        return;
    }

    if (! class_exists('WC_Payment_Gateway')) {
        return;
    }

    require_once __DIR__ . '/includes/class-wc-gateway-k2-payment.php';
    require_once __DIR__ . '/includes/class-k2-authorization.php';

    add_filter('woocommerce_payment_gateways', function ($methods) {
        $methods[] = 'WC_Gateway_K2_Payment';
        return $methods;
    });
}

add_action('woocommerce_checkout_init', ['K2_Authorization', 'maybe_authorize']);


add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'k2_wc_settings_link');
function k2_wc_settings_link($links)
{
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=kkwoo') . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Add checkout block support - declare that the plugin is compatible with WooCommerce blocks
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        // TODO: confirm compatibility before deployment
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );

        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks',
            __FILE__,
            true
        );
    }
});

add_action('woocommerce_blocks_loaded', 'k2_register_block_payment_method');
function k2_register_block_payment_method()
{
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        error_log('KKWOO: AbstractPaymentMethodType not available');
        return;
    }

    $blocks_file = __DIR__ . '/includes/class-wc-gateway-k2-blocks.php';
    if (!file_exists($blocks_file)) {
        error_log("KKWOO: Blocks file not found at $blocks_file");
        return;
    }

    require_once $blocks_file;

    if (!class_exists('WC_Gateway_K2_Blocks')) {
        error_log('KKWOO: WC_Gateway_K2_Blocks class not found after including the file');
        return;
    }

    add_action('woocommerce_blocks_payment_method_type_registration', function ($registry) {
        $payment_method = new WC_Gateway_K2_Blocks();
        $registry->register($payment_method);
        error_log("KKWOO: Payment method '{$payment_method->get_name()}' registered successfully");
    });

    require_once __DIR__ . '/includes/class-wc-block-integration-k2.php';
    if (class_exists(WC_Block_Integration_K2::class)) {
        add_action('woocommerce_blocks_checkout_block_registration', function ($integration_registry) {
            $integration_registry->register(new WC_Block_Integration_K2());
            error_log("KKWOO: Block integration registered successfully");
        });
    }
}

add_action('wp_enqueue_scripts', function () {
    if (is_checkout()) {
        wp_enqueue_script(
            'kkwoo-checkout-handler',
            plugin_dir_url(__FILE__) . 'assets/js/classic-checkout-handler.js',
            ['jquery'],
            '1.0',
            true
        );

        wp_enqueue_style(
            'kkwoo-google-font',
            'https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap',
            [],
            null
        );

        wp_localize_script('kkwoo-checkout', 'KKWooData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => esc_url_raw(rest_url('kopo-kopo/v1/stk-push')),
        ]);

        if (!function_exists('wc_get_container')) { // Not block checkout
            wp_enqueue_style(
                'kkwoo-classic-style',
                plugins_url('assets/style.css', __FILE__),
                [],
                '1.0.0'
            );
        }
    }
});
