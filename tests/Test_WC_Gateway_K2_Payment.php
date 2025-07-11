<?php

/**
 * Test WC_Gateway_K2_Payment class.
 */
class Test_WC_Gateway_K2_Payment extends WP_UnitTestCase
{
    /** @var WC_Gateway_K2_Payment */
    private $gateway;

    public function set_up(): void
    {
        parent::set_up();

        // Ensure WooCommerce dependencies are loaded
        if (! class_exists('WC_Payment_Gateway')) {
            require_once WP_PLUGIN_DIR . '/woocommerce/includes/abstracts/abstract-wc-payment-gateway.php';
        }

        // Load your gateway class
        require_once __DIR__ . '/../includes/class-wc-gateway-k2-payment.php';

        // Instantiate the gateway
        $this->gateway = new WC_Gateway_K2_Payment();

        // Set the id and option_key explicitly so init_settings() works properly
        $this->gateway->id = 'k2_gateway';
        $this->gateway->option_key = 'woocommerce_k2_gateway_settings';

        // Define form fields (required before setting or loading settings)
        $this->gateway->init_form_fields();
    }

    public function test_gateway_class_exists(): void
    {
        $this->assertInstanceOf(WC_Gateway_K2_Payment::class, $this->gateway);
    }

    public function test_form_fields_are_defined(): void
    {
        $fields = $this->gateway->form_fields;

        $this->assertArrayHasKey('enabled', $fields);
        $this->assertArrayHasKey('title', $fields);
        $this->assertArrayHasKey('client_id', $fields);
        $this->assertArrayHasKey('client_secret', $fields);
        $this->assertArrayHasKey('api_key', $fields);
        $this->assertArrayHasKey('environment', $fields);
    }

    public function test_is_configured_returns_false_when_empty(): void
    {
        $this->gateway->settings = [
            'client_id'     => '',
            'client_secret' => '',
            'api_key'       => '',
            'environment'   => '',
        ];
        $this->gateway->init_settings();

        $this->assertFalse($this->gateway->is_configured());
    }

    public function test_is_configured_returns_true_when_all_set(): void
    {
        update_option('woocommerce_k2_gateway_settings', [
            'client_id'     => 'test_client',
            'client_secret' => 'test_secret',
            'api_key'       => 'test_api',
            'environment'   => 'sandbox',
        ]);

        $this->gateway->init_settings();

        $this->assertTrue($this->gateway->is_configured());
    }
}
