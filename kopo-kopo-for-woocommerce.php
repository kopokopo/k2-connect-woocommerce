<?php

/*
* Plugin Name: Kopo Kopo for WooCommerce
* Plugin URI:
* Description: A Kopo Kopo plugin that integrates seamlessly with your WooCommerce shop, enabling your customers to make secure and convenient payments directly to your Kopo Kopo M-PESA till.
* Version: 1.0.0
* Requires at least: 6.8.1
* Requires PHP: 7.4
* Author: Doreen Chemweno
* Author URI: https://kopokopo.co.ke
* License: GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Requires Plugins: woocommerce
* WC tested up to: 10.4.3
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/rest-api.php';
require_once __DIR__ . '/includes/class-k2-payment-page.php';
require_once __DIR__ . '/includes/class-kkwoo-logger.php';
require_once __DIR__ . '/includes/class-kkwoo-user-friendly-messages.php';
require_once __DIR__ . '/includes/class-kkwoo-activation-hook-service.php';
require_once __DIR__ . '/includes/class-wc-k2-check-payment-status.php';
require_once __DIR__ . '/includes/k2-authorization-rest-api.php';
require_once __DIR__ . '/includes/k2-webhooks-rest-api.php';
require_once __DIR__ . '/includes/k2-manual-payments-rest-api.php';
require_once __DIR__ . '/includes/class-kkwoo-manual-payments-tracker-repository.php';
require_once __DIR__ . '/includes/class-kkwoo-manual-payments-service.php';

if (!defined('KKWOO_PLUGIN_VERSION')) {
    $plugin_data = get_file_data(__FILE__, ['Version' => 'Version']);
    define('KKWOO_PLUGIN_VERSION', $plugin_data['Version']);
}

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

register_activation_hook(__FILE__, function () {
    KKWoo_Activation_Service::activate();
});

register_deactivation_hook(__FILE__, function () {
    K2_Payment_Page::flush_rules();
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

    new WC_K2_Check_Payment_Status();
    new K2_Payment_Page();
}

add_action('woocommerce_checkout_init', ['K2_Authorization', 'maybe_authorize']);
add_action('woocommerce_view_order', ['K2_Authorization', 'maybe_authorize'], 10, 1);


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

add_filter('woocommerce_currency_symbol', 'k2_custom_currency_symbol', 10, 2);

function k2_custom_currency_symbol($currency_symbol, $currency)
{
    if ($currency === 'KES') {
        $currency_symbol = 'KSh'; // Use KSh instead of default
    }
    return $currency_symbol;
}

add_action('woocommerce_init', function () {
    $gateways = WC()->payment_gateways()->payment_gateways();
    if (isset($gateways['kkwoo'])) {
        $kkwoo = $gateways['kkwoo'];
        $kkwoo->kkwoo_register_gateway_hooks();
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
                $asset_version
            );
        }
    }

    if (is_wc_endpoint_url('view-order') || is_wc_endpoint_url('order-received')) {
        global $wp;
        if (is_wc_endpoint_url('view-order')) {
            $order_id = absint($wp->query_vars['view-order'] ?? 0);
        }
        if (is_wc_endpoint_url('order-received')) {
            $order_id = absint($wp->query_vars['order-received'] ?? 0);
        }
        $order = $order_id ? wc_get_order($order_id) : null;
        $localized_data = [
            'nonce'    => wp_create_nonce('wp_rest'),
            'order_key' => $order->get_order_key(),
            'spinner_icon' => plugins_url('images/svg/spinner.svg', __FILE__),
        ];

        if ($order) {
            wp_enqueue_script(
                'kkwoo-order-view-handler',
                plugin_dir_url(__FILE__) . 'assets/js/order-view-handler.js',
                ['jquery'],
                '1.1',
                true
            );
            wp_localize_script('kkwoo-order-view-handler', 'KKWooData', $localized_data);

            if (!function_exists('wc_get_container')) { // Not block checkout
                wp_enqueue_style(
                    'kkwoo-classic-style',
                    plugins_url('assets/style.css', __FILE__),
                    [],
                    $asset_version
                );
            }
        }
    }
});

/**
 * Load assets for virtual page - uses template_redirect because wp_enqueue_scripts fires before virtual page URL/params are available
 * Virtual pages process URL rewriting AFTER wp_enqueue_scripts but BEFORE wp_loaded, creating a timing gap
 * Must inject directly to wp_head/wp_footer since wp_enqueue_scripts hook has already fired
 */

add_action('template_redirect', function () {
    if (!get_query_var('lipa_na_mpesa_k2')) {
        return;
    }

    $order_key = sanitize_text_field(get_query_var('order_key'));
    $order_id  = wc_get_order_id_by_order_key($order_key);
    $order     = wc_get_order($order_id);

    $gateways = WC()->payment_gateways()->payment_gateways();
    $kkwoo = $gateways['kkwoo'];
    $enable_manual_payments      = $kkwoo->get_option('enable_manual_payments');
    $manual_payments_till_no     = $kkwoo->get_option('manual_payments_till_no');
    $paybill_business_no         = $kkwoo->get_option('paybill_business_no');
    $paybill_account_no          = $kkwoo->get_option('paybill_account_no');

    if ('yes' === $enable_manual_payments && !empty($manual_payments_till_no)) {
        $selected_manual_payment_method = 'till';
    } elseif (
        'yes' === $enable_manual_payments &&
        empty($manual_payments_till_no) &&
        !empty($paybill_business_no) &&
        !empty($paybill_account_no)
    ) {
        $selected_manual_payment_method = 'paybill';
    } else {
        $selected_manual_payment_method = '';
    }

    if (!$order) {
        status_header(404);
        exit('Order not found');
    }

    remove_all_actions('wp_head');
    remove_all_actions('wp_footer');

    status_header(200);
    header('Content-Type: text/html; charset=utf-8');


    $localized_data = [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('wp_rest'),
        'order_key' => $order_key,
        'order_status' => $order->get_status(),
        'total_amount' => $order->get_total(),
        'currency' => get_woocommerce_currency_symbol($order->get_currency()),
        'store_name' => get_bloginfo('name'),
        'selected_manual_payment_method' => $selected_manual_payment_method,
        'order_received_url' => $order->get_checkout_order_received_url(),
        'this_order_url' => $order->get_user_id() ? $order->get_view_order_url() : $order->get_checkout_order_received_url(),
        'plugin_url' => plugins_url('', __FILE__),
        'phone_icon' => plugins_url('images/svg/phone.svg', __FILE__),
        'spinner_icon' => plugins_url('images/svg/spinner.svg', __FILE__),
        'k2_logo_with_name_img' => plugins_url('images/svg/k2-logo-with-name.svg', __FILE__),
        'kenyan_flag_img'    => plugins_url('images/kenyan-flag.png', __FILE__),
        'error_circle_icon'    => plugins_url('images/svg/alert-circle.svg', __FILE__),
        'success_circle_icon'    => plugins_url('images/svg/success-circle.svg', __FILE__),
        'info_circle_icon'    => plugins_url('images/svg/info-circle.svg', __FILE__),
    ];

    $is_dev = defined('WP_DEBUG') && WP_DEBUG; // true for local dev
    $asset_version = $is_dev ? time() : KKWOO_PLUGIN_VERSION;

    wp_enqueue_script('jquery');

    wp_enqueue_style(
        'kkwoo-classic-style',
        plugins_url('src/style.css', __FILE__),
        [],
        $asset_version
    );

    ?>
    <!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="utf-8">
            <title>Lipa na M-PESA</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <?php
                wp_print_styles('kkwoo-classic-style');
    ?>
        </head>
        <body>

        <main class="wp-block-group">
            <div class="k2 modal-overlay" style="display: none">
                <div class="modal-body">
                    <div class="modal-content"></div>
                    <div class="modal-footer">
                        Powered by
                        <img src="<?= esc_url(plugins_url('images/svg/k2-logo-with-name.svg', __FILE__)) ?>">
                    </div>
                </div>
                <p class='switch-to-manual-payments'>Having trouble? Pay via 
                    <button id='switch-to-manual-payments' class="link">M-PESA Buy Goods</button>
                </p>
            </div>
        </main>

        <?php
        wp_print_scripts('jquery');
    ?>

        <script type="text/javascript">
        window.KKWooData = <?= wp_json_encode($localized_data); ?>;
        </script>

        <script src="<?= esc_url(plugins_url('assets/js/ui-templates/ui-templates-init.js', __FILE__)); ?>?v=<?= $asset_version ?>"></script>
        <script src="<?= esc_url(plugins_url('assets/js/ui-templates/mpesa-number-form.js', __FILE__)); ?>?v=<?= $asset_version ?>"></script>
        <script src="<?= esc_url(plugins_url('assets/js/ui-templates/pin-instruction.js', __FILE__)); ?>?v=<?= $asset_version ?>"></script>
        <script src="<?= esc_url(plugins_url('assets/js/ui-templates/polling.js', __FILE__)); ?>?v=<?= $asset_version ?>"></script>
        <script src="<?= esc_url(plugins_url('assets/js/ui-templates/payment-success.js', __FILE__)); ?>?v=<?= $asset_version ?>"></script>
        <script src="<?= esc_url(plugins_url('assets/js/ui-templates/payment-error.js', __FILE__)); ?>?v=<?= $asset_version ?>"></script>
        <script src="<?= esc_url(plugins_url('assets/js/ui-templates/payment-no-result-yet.js', __FILE__)); ?>?v=<?= $asset_version ?>"></script>
        <script src="<?= esc_url(plugins_url('assets/js/ui-templates/payment-refunded.js', __FILE__)); ?>?v=<?= $asset_version ?>"></script>
        <script src="<?= esc_url(plugins_url('assets/js/ui-templates/manual-payment-instructions.js', __FILE__)); ?>?v=<?= $asset_version ?>"></script>

        <script src="<?= esc_url(plugins_url('assets/js/polling-manager.js', __FILE__)); ?>?v=<?= $asset_version ?>"></script>
        <script src="<?= esc_url(plugins_url('assets/js/k2-validations.js', __FILE__)); ?>?v=<?= $asset_version ?>"></script>
        <script src="<?= esc_url(plugins_url('assets/js/k2-payment-flow-handler.js', __FILE__)); ?>?v=<?= $asset_version ?>"></script>
   </body>
    </html>
    <?php
    exit;
});
