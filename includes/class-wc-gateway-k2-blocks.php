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
        $this->gateway = new WC_Gateway_K2_Payment();
    }

    public function get_payment_method_script_handles(): array
    {
        $asset_path = KKWOO_PLUGIN_PATH . 'build/index.asset.php';

        if (!file_exists($asset_path)) {
            error_log('KKWOO: Asset file not found');
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
