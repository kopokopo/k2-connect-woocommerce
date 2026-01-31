<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class WC_Gateway_K2_Blocks extends AbstractPaymentMethodType
{
    private $gateway;
    protected $name = 'kkwoo';

    public function get_name(): string
    {
        return $this->name;
    }

    public function initialize(): void
    {
        $this->settings = get_option('woocommerce_my_custom_gateway_settings', []);
        $gateways = WC()->payment_gateways()->payment_gateways();
        if (isset($gateways['kkwoo'])) {
            $this->gateway = $gateways['kkwoo'];
        } else {
            //If no gateway is available, it is likely that there is an issue in the K2 payment Gateway
            $this->gateway = null;
            KKWoo_Logger::log('WC_Gateway_K2_Payment was not instantiated when WC_Gateway_K2_Blocks initialize() ran.', 'error');
        }
    }

    public function get_payment_method_script_handles(): array
    {
        $asset_path = KKWOO_PLUGIN_PATH . 'build/index.asset.php';

        if (!file_exists($asset_path)) {
            KKWoo_Logger::log('KKWOO: Asset file not found', 'error');
            return [];
        }

        $asset = include $asset_path;
        $handle = 'kkwoo-gateway-block';
        $script_url = KKWOO_PLUGIN_URL . 'build/index.js';

        wp_register_script(
            $handle,
            $script_url,
            [
                'wc-blocks-registry',
                'wc-blocks-checkout',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            $asset['version'],
            true
        );

        wp_localize_script(
            $handle,
            'kkwoo_data',
            $this->get_payment_method_data()
        );

        return [ $handle ];
    }

    public function get_payment_method_data(): array
    {
        return [
            'title'       => $this->gateway->title,
            'description' => $this->gateway->method_description,
            'supports'    => $this->gateway->supports,
        ];
    }

    public function is_active(): bool
    {
        return $this->gateway->is_available();
    }
}
