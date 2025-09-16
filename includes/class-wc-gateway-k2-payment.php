<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_K2_Payment extends WC_Payment_Gateway
{
    protected string $client_id;
    protected string $client_secret;
    protected string $api_key;
    protected string $environment;

    public function __construct()
    {
        $this->id                 = 'kkwoo';
        $this->icon               = KKWOO_PLUGIN_URL . 'images/svg/k2-logo.svg';
        $this->has_fields         = false;
        $this->method_title       = 'Kopo Kopo for WooCommerce';
        $this->method_description = 'Allows payments with Kopo Kopo for WooCommerce.';

        $this->supports = ['products'];

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Get settings.
        $this->enabled            = $this->get_option('enabled');
        $this->title              = $this->get_option('title');
        $this->description        = $this->get_option('description');
        $this->client_id          = $this->get_option('client_id');
        $this->client_secret      = $this->get_option('client_secret');
        $this->api_key            = $this->get_option('api_key');
        $this->environment        = $this->get_option('environment');

        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            [$this, 'after_settings_updated']
        );

        add_action('admin_notices', [$this, 'admin_missing_settings_notice']);
        add_action('admin_notices', [$this, 'admin_currency_warning']);

    }

    public function init_form_fields(): void
    {
        $this->form_fields = [
          'enabled' => [
            'title'   => 'Enable/Disable',
            'type'    => 'checkbox',
            'label'   => 'Enable Kopo Kopo for WooCommerce',
            'default' => 'no',
          ],
          'title' => [
            'title'       => 'Title',
            'type'        => 'text',
            'description' => 'Title shown to customers at checkout.',
            'default'     => 'Lipa na M-PESA',
          ],
          'description' => [
            'title'       => 'Description',
            'type'        => 'text',
            'description' => 'Description shown to customers at checkout.',
            'default'     => 'Pay using Lipa na M-PESA. Modal will appear after clicking “Lipa na M-PESA”.',
            ],
          'till_number' => [
            'title'       => 'Till Number',
            'type'        => 'text',
            'description' => 'The till number to receive payments',
            'default'     => '',
          ],
          'client_id' => [
            'title'       => 'Client ID',
            'type'        => 'text',
            'description' => 'Your Kopo Kopo Client ID.',
            'default'     => '',
          ],
          'client_secret' => [
            'title'       => 'Client Secret',
            'type'        => 'password',
            'description' => 'Your Kopo Kopo Client Secret.',
            'default'     => '',
          ],
          'api_key' => [
            'title'       => 'API Key',
            'type'        => 'password',
            'description' => 'Your Kopokopo API Key.',
            'default'     => '',
          ],
          'environment' => [
            'title'       => 'Environment',
            'type'        => 'select',
            'description' => 'Choose the Kopo Kopo environment.',
            'default'     => 'sandbox',
            'options'     => [
              'sandbox'    => 'Sandbox (Test)',
              'production' => 'Production (Live)',
            ],
          ],
        ];
    }

    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);

        $order->update_status('pending', 'Waiting for K2 payment...');

        WC()->session->set('kkwoo_order_id', $order_id);

        return [
            'result'   => 'success',
            'redirect' => home_url(add_query_arg([
                'order_key'        => $order->get_order_key(),
            ], '/lipa-na-mpesa-k2/')),
        ];
    }


    public function admin_missing_settings_notice(): void
    {
        if ('yes' === $this->get_option('enabled') && ! $this->is_configured()) {
            echo '<div class="notice notice-error"><p>';
            echo esc_html('Kopo Kopo for WooCommerce is enabled but not fully configured. Click ');
            echo '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=kkwoo')) . '">here</a>';
            echo esc_html(' to complete the setup.');
            echo '</p></div>';
        }
    }

    public function admin_currency_warning(): void
    {
        if (is_admin() && get_woocommerce_currency() !== 'KES' && $this->enabled === 'yes') {
            echo '<div class="notice notice-warning"><p>';
            echo esc_html('Kopo Kopo for WooCommerce requires your store currency to be Kenyan Shillings (KES). Please update it under WooCommerce → Settings → General.');
            echo '</p></div>';
        }
    }

    public function is_configured(): bool
    {
        return ! empty($this->settings['client_id']) &&
               ! empty($this->settings['client_secret']) &&
               ! empty($this->settings['api_key']) &&
               ! empty($this->settings['till_number']) &&
               ! empty($this->settings['environment']);
    }

    public function get_icon(): string
    {
        if (!is_admin()) {
            $icon_url = KKWOO_PLUGIN_URL . 'images/mpesa-logo.png';
            $icon_html = '<img src="' . esc_url($icon_url) . '" alt="' . esc_attr($this->title) . '" style="height:45px;"/>';
            return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
        }

        return parent::get_icon();
    }

    public function is_available(): bool
    {
        $is_available = ('yes' === $this->enabled);
        if (!$this->is_configured() || ('KES' !== get_woocommerce_currency())) {
            $is_available = false;
        }
        return $is_available;
    }

    public function after_settings_updated(): void
    {
        delete_transient('kopokopo_access_token');
    }

}
