<?php

/**
 * Test KKWoo_Payment_Gateway class.
 */
class Test_KKWoo_Payment_Gateway extends WP_UnitTestCase
{
    /** @var KKWoo_Payment_Gateway */
    private $gateway;

    /** @var WC_Order  */
    private $order;

    /** @var WC_Product */
    private $product;

    public function set_up(): void
    {
        parent::set_up();

        // Ensure WooCommerce dependencies are loaded
        if (! class_exists('WC_Payment_Gateway')) {
            require_once WP_PLUGIN_DIR . '/woocommerce/includes/abstracts/abstract-wc-payment-gateway.php';
        }

        // Load your gateway class
        require_once __DIR__ . '/../includes/class-kkwoo-payment-gateway.php';

        // Instantiate the gateway
        $this->gateway = new KKWoo_Payment_Gateway();

        // Define form fields (required before setting or loading settings)
        $this->gateway->init_form_fields();

        $product_id = self::factory()->post->create([
            'post_type'  => 'product',
            'post_title' => 'Factory Product',
        ]);

        $this->product = wc_get_product($product_id);

        $this->order = wc_create_order();
        $this->order->add_product($this->product, 1);
        $this->order->calculate_totals();
        $this->order->save();
    }

    public function tearDown(): void
    {
        // Clean up
        wp_delete_post($this->order->get_id(), true);
        wp_delete_post($this->product->get_id(), true);
        parent::tearDown();
    }

    public function test_gateway_class_exists(): void
    {
        $this->assertInstanceOf(KKWoo_Payment_Gateway::class, $this->gateway);
    }

    public function test_process_admin_options_hook_registered(): void
    {
        $this->gateway->kkwoo_register_gateway_hooks();

        $hook = 'woocommerce_update_options_payment_gateways_' . $this->gateway->id;

        $this->assertNotFalse(
            has_action($hook, [$this->gateway, 'process_admin_options']),
            "process_admin_options should be hooked into {$hook}"
        );
    }

    public function test_after_settings_updated_hook_registered(): void
    {
        $this->gateway->kkwoo_register_gateway_hooks();

        $hook = 'woocommerce_update_options_payment_gateways_' . $this->gateway->id;

        $this->assertNotFalse(
            has_action($hook, [$this->gateway, 'after_settings_updated']),
            "after_settings_updated should be hooked into {$hook}"
        );
    }

    public function test_admin_missing_settings_notice_registered(): void
    {
        $this->gateway->kkwoo_register_gateway_hooks();

        $hook = 'woocommerce_update_options_payment_gateways_' . $this->gateway->id;

        $this->assertNotFalse(
            has_action('admin_notices', [$this->gateway, 'admin_missing_settings_notice']),
            "admin_missing_settings_notice should be hooked into admin_notices"
        );
    }

    public function test_admin_currency_warning_registered(): void
    {
        $this->gateway->kkwoo_register_gateway_hooks();

        $hook = 'woocommerce_update_options_payment_gateways_' . $this->gateway->id;

        $this->assertNotFalse(
            has_action('admin_notices', [$this->gateway, 'admin_currency_warning']),
            "admin_currency_warning should be hooked into admin_notices"
        );
    }

    public function test_form_fields_are_defined(): void
    {
        $fields = $this->gateway->form_fields;

        $this->assertArrayHasKey('enabled', $fields);
        $this->assertArrayHasKey('title', $fields);
        $this->assertArrayHasKey('description', $fields);
        $this->assertArrayHasKey('till_number', $fields);
        $this->assertArrayHasKey('client_id', $fields);
        $this->assertArrayHasKey('client_secret', $fields);
        $this->assertArrayHasKey('api_key', $fields);
        $this->assertArrayHasKey('environment', $fields);
    }

    public function test_is_configured_returns_false_when_empty(): void
    {
        $this->gateway->settings = [
            'till_number'   => '',
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
        update_option('woocommerce_kkwoo_settings', [
            'till_number'   => 'K123456',
            'client_id'     => 'test_client',
            'client_secret' => 'test_secret',
            'api_key'       => 'test_api',
            'environment'   => 'sandbox',
        ]);

        $this->gateway->init_settings();

        $this->assertTrue($this->gateway->is_configured());
    }

    public function test_title_form_field_has_expected_value(): void
    {
        $settings = $this->gateway->form_fields;
        $titleField = $settings['title'];

        $this->assertEquals('Lipa na M-PESA', $titleField['default']);
    }

    public function test_title_form_field_is_readonly()
    {
        $settings = $this->gateway->form_fields;
        $titleField = $settings['title'];

        // Assert readonly attribute exists
        $this->assertArrayHasKey('custom_attributes', $titleField);
        $this->assertArrayHasKey('readonly', $titleField['custom_attributes']);
        $this->assertEquals('readonly', $titleField['custom_attributes']['readonly']);
    }

    public function test_description_form_field_has_expected_value(): void
    {
        $settings = $this->gateway->form_fields;
        $descriptionField = $settings['description'];

        $this->assertEquals('Click "Proceed to Lipa na M-PESA" below to pay with M-PESA', $descriptionField['default']);
    }

    public function test_description_form_field_is_readonly(): void
    {
        $settings = $this->gateway->form_fields;
        $descriptionField = $settings['description'];

        // Assert readonly attribute exists
        $this->assertArrayHasKey('custom_attributes', $descriptionField);
        $this->assertArrayHasKey('readonly', $descriptionField['custom_attributes']);
        $this->assertEquals('readonly', $descriptionField['custom_attributes']['readonly']);
    }

    public function test_process_payment_returns_success_and_redirect(): void
    {
        $order_id = wc_get_order_id_by_order_key($this->order->get_order_key());
        $result = $this->gateway->process_payment($order_id);

        $this->assertIsArray($result);
        $this->assertSame('success', $result['result']);
        $this->assertStringContainsString('/lipa-na-mpesa-k2/', $result['redirect']);
        $this->assertStringContainsString('order_key=' . $this->order->get_order_key(), $result['redirect']);
    }

    public function test_admin_missing_settings_notice_outputs_error_when_enabled_but_not_configured(): void
    {
        update_option('woocommerce_kkwoo_settings', [
            'enabled'       => 'yes',
            'till_number'   => '',
            'client_id'     => '',
            'client_secret' => '',
            'api_key'       => '',
            'environment'   => '',
            'enable_manual_payments' => 'no',
        ]);

        $this->gateway->init_settings();

        $_GET['section'] = $this->gateway->id;

        ob_start();
        $this->gateway->admin_missing_settings_notice();
        $output = ob_get_clean();

        $this->assertStringContainsString('notice-error', $output);
        $this->assertStringContainsString('Kopo Kopo for WooCommerce is enabled but not fully configured', $output);
        $this->assertStringContainsString('admin.php?page=wc-settings', $output);
    }

    public function test_admin_missing_settings_notice_outputs_nothing_when_disabled(): void
    {
        update_option('woocommerce_kkwoo_settings', [
            'enabled'       => 'no',
            'till_number'   => '',
            'client_id'     => '',
            'client_secret' => '',
            'api_key'       => '',
            'environment'   => '',
            'enable_manual_payments' => 'no',

        ]);

        $this->gateway->init_settings();

        ob_start();
        $this->gateway->admin_missing_settings_notice();
        $output = ob_get_clean();

        $this->assertEmpty(trim($output));
    }

    public function test_admin_currency_warning_outputs_warning_if_not_kes(): void
    {
        /** @var KKWoo_Payment_Gateway&\PHPUnit\Framework\MockObject\MockObject $gateway */
        $gateway = $this->getMockBuilder(KKWoo_Payment_Gateway::class)
            ->disableOriginalConstructor() // don’t run constructor
            ->onlyMethods(['wp_is_admin', 'wp_get_currency'])
            ->getMock();

        $gateway->method('wp_is_admin')->willReturn(true);
        $gateway->method('wp_get_currency')->willReturn('USD');
        $gateway->enabled = 'yes';

        $gateway->id = 'kkwoo';
        $_GET['section'] = 'kkwoo';

        ob_start();
        $gateway->admin_currency_warning();
        $output = ob_get_clean();

        $this->assertStringContainsString('notice-warning', $output);
        $this->assertStringContainsString(
            'requires your store currency to be Kenyan Shillings (KES)',
            $output
        );
    }

    public function test_admin_currency_warning_outputs_nothing_if_not_kes_and_not_admin(): void
    {
        /** @var KKWoo_Payment_Gateway&\PHPUnit\Framework\MockObject\MockObject $gateway */
        $gateway = $this->getMockBuilder(KKWoo_Payment_Gateway::class)
            ->disableOriginalConstructor() // don’t run constructor
            ->onlyMethods(['wp_is_admin', 'wp_get_currency'])
            ->getMock();

        $gateway->method('wp_is_admin')->willReturn(false);
        $gateway->method('wp_get_currency')->willReturn('USD');
        $gateway->enabled = 'yes';

        ob_start();
        $gateway->admin_currency_warning();
        $output = ob_get_clean();

        $this->assertEmpty(trim($output));
    }

    public function test_admin_currency_warning_outputs_nothing_if_kes(): void
    {
        /** @var KKWoo_Payment_Gateway&\PHPUnit\Framework\MockObject\MockObject $gateway */
        $gateway = $this->getMockBuilder(KKWoo_Payment_Gateway::class)
            ->disableOriginalConstructor() // don’t run constructor
            ->onlyMethods(['wp_is_admin', 'wp_get_currency'])
            ->getMock();

        $gateway->method('wp_is_admin')->willReturn(true);
        $gateway->method('wp_get_currency')->willReturn('USD');
        $gateway->enabled = 'yes';

        ob_start();
        $this->gateway->admin_currency_warning();
        $output = ob_get_clean();

        $this->assertEmpty(trim($output));
    }

    public function test_get_icon_returns_custom_icon_when_not_admin(): void
    {
        /** @var KKWoo_Payment_Gateway&\PHPUnit\Framework\MockObject\MockObject $gateway */
        $gateway = $this->getMockBuilder(KKWoo_Payment_Gateway::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['wp_is_admin'])
            ->getMock();

        $gateway->method('wp_is_admin')->willReturn(false);
        $gateway->title = 'Lipa na M-PESA';
        $gateway->id = 'kkwoo';

        $icon = $gateway->get_icon();

        $this->assertStringContainsString('mpesa-logo.png', $icon);
        $this->assertStringContainsString('alt="Lipa na M-PESA"', $icon);
        $this->assertStringContainsString('<img', $icon);
    }

    public function test_get_icon_delegates_to_parent_when_admin(): void
    {
        /** @var KKWoo_Payment_Gateway&\PHPUnit\Framework\MockObject\MockObject $gateway */
        $gateway = $this->getMockBuilder(KKWoo_Payment_Gateway::class)
            ->onlyMethods(['wp_is_admin'])
            ->getMock();

        $gateway->method('wp_is_admin')->willReturn(true);

        $icon = $gateway->get_icon();

        $this->assertStringContainsString('k2-logo.svg', $icon);
        $this->assertStringContainsString('<img', $icon);
    }

    /**
     * @dataProvider isAvailableDataProvider
     */
    public function test_is_available($enabled, $configured, $currency, $expected): void
    {
        /** @var KKWoo_Payment_Gateway&\PHPUnit\Framework\MockObject\MockObject $gateway */
        $gateway = $this->getMockBuilder(KKWoo_Payment_Gateway::class)
            ->onlyMethods(['is_configured', 'wp_get_currency'])
            ->getMock();

        $gateway->enabled = $enabled;

        $gateway->method('is_configured')->willReturn($configured);
        $gateway->method('wp_get_currency')->willReturn($currency);

        $this->assertSame($expected, $gateway->is_available());
    }

    public function isAvailableDataProvider(): array
    {
        return [
            'enabled, configured, KES'   => ['yes', true,  'KES', true],
            'enabled, configured, USD'   => ['yes', true,  'USD', false],
            'enabled, not configured'    => ['yes', false, 'KES', false],
            'disabled, configured, KES'  => ['no',  true,  'KES', false],
            'disabled, not configured'   => ['no',  false, 'USD', false],
        ];
    }
}
