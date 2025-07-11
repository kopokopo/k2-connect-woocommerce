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
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

if (!defined('KKWOO_SANDBOX_URL')) {
    define('KKWOO_SANDBOX_URL', 'https://sandbox.kopokopo.com');
}

if (!defined('KKWOO_PRODUCTION_URL')) {
    define('KKWOO_PRODUCTION_URL', 'https://app.kopokopo.com');
}


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
