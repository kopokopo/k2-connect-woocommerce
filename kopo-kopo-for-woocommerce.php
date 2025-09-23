<?php

/*
* Plugin Name: Kopo Kopo for WooCommerce
* Plugin URI:
* Description: A Kopo Kopo plugin that integrates seamlessly with your WooCommerce shop, enabling your customers to make secure and convenient payments directly to your Kopo Kopo M-PESA till.
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
require_once __DIR__ . '/includes/class-k2-payment-page.php';
require_once __DIR__ . '/includes/class-kkwoo-logger.php';
require_once __DIR__ . '/includes/class-kkwoo-user-friendly-messages.php';

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

if (!defined('KKWOO_COUNTRY_CODE')) {
    define('KKWOO_COUNTRY_CODE', '+254');
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

register_activation_hook(__FILE__, function () {
    (new K2_Payment_Page())->flush_rules();
});

register_deactivation_hook(__FILE__, function () {
    (new K2_Payment_Page())->flush_rules();
});

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
    });

    require_once __DIR__ . '/includes/class-wc-block-integration-k2.php';
    if (class_exists(WC_Block_Integration_K2::class)) {
        add_action('woocommerce_blocks_checkout_block_registration', function ($integration_registry) {
            $integration_registry->register(new WC_Block_Integration_K2());
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

/**
 * Load assets for virtual page - uses template_redirect because wp_enqueue_scripts fires before virtual page URL/params are available
 * Virtual pages process URL rewriting AFTER wp_enqueue_scripts but BEFORE wp_loaded, creating a timing gap
 * Must inject directly to wp_head/wp_footer since wp_enqueue_scripts hook has already fired
 */

add_action('template_redirect', 'enqueue_virtual_page_assets_late');
function enqueue_virtual_page_assets_late()
{
    if (get_query_var('lipa_na_mpesa_k2')) {

        $order_key = sanitize_text_field(get_query_var('order_key'));
        $order_id  = wc_get_order_id_by_order_key($order_key);
        $order     = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        add_action('wp_footer', function () use ($order, $order_key) {
            $localized_data = [
                'ajax_url' => admin_url('admin-ajax.php'),
                'rest_url' => esc_url_raw(rest_url('kkwoo/v1/stk-push')),
                'nonce'    => wp_create_nonce('wp_rest'),
                'order_key' => $order_key,
                'order_status' => $order->get_status(),
                'total_amount' => $order->get_total(),
                'currency' => $order->get_currency(),
                'store_name' => get_bloginfo('name'),
                'order_received_url' => $order->get_checkout_order_received_url(),
                'this_order_url' => $order->get_view_order_url(),
                'plugin_url' => plugins_url('', __FILE__),
                'phone_icon' => plugins_url('images/svg/phone.svg', __FILE__),
                'spinner_icon' => plugins_url('images/svg/spinner.svg', __FILE__),
                'k2_logo_with_name_img' => plugins_url('images/k2-logo-with-name.png', __FILE__),
                'kenyan_flag_img'    => plugins_url('images/kenyan-flag.png', __FILE__),
                'error_circle_icon'    => plugins_url('images/svg/alert-circle.svg', __FILE__),
                'success_circle_icon'    => plugins_url('images/svg/success-circle.svg', __FILE__),
                'info_circle_icon'    => plugins_url('images/svg/info-circle.svg', __FILE__),
            ];

            echo '<script type="text/javascript">' . "\n";
            echo 'var KKWooData = ' . json_encode($localized_data) . ';' . "\n";
            echo '</script>' . "\n";
            echo '<script src="' . plugin_dir_url(__FILE__) . 'assets/js/ui-templates/ui-templates-init.js?v=1.0.0"></script>' . "\n";
            echo '<script src="' . plugin_dir_url(__FILE__) . 'assets/js/ui-templates/mpesa-number-form.js?v=1.0.0"></script>' . "\n";
            echo '<script src="' . plugin_dir_url(__FILE__) . 'assets/js/ui-templates/pin-instruction.js?v=1.0.0"></script>' . "\n";
            echo '<script src="' . plugin_dir_url(__FILE__) . 'assets/js/ui-templates/polling.js?v=1.0.0"></script>' . "\n";
            echo '<script src="' . plugin_dir_url(__FILE__) . 'assets/js/ui-templates/payment-success.js?v=1.0.0"></script>' . "\n";
            echo '<script src="' . plugin_dir_url(__FILE__) . 'assets/js/ui-templates/payment-error.js?v=1.0.0"></script>' . "\n";
            echo '<script src="' . plugin_dir_url(__FILE__) . 'assets/js/ui-templates/payment-no-result-yet.js?v=1.0.0"></script>' . "\n";
            echo '<script src="' . plugin_dir_url(__FILE__) . 'assets/js/ui-templates/payment-refunded.js?v=1.0.0"></script>' . "\n";
            echo '<script src="' . plugin_dir_url(__FILE__) . 'assets/js/polling-manager.js?v=1.0.0"></script>' . "\n";
            echo '<script src="' . plugin_dir_url(__FILE__) . 'assets/js/k2-validations.js?v=1.0.0"></script>' . "\n";
            echo '<script src="' . plugin_dir_url(__FILE__) . 'assets/js/k2-payment-flow-handler.js?v=1.0.0"></script>' . "\n";
        }, 10);
    }
}
