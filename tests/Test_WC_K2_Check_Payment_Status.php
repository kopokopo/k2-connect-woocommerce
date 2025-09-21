<?php

/**
 * Test WC_K2_Check_Payment_Status class.
 */
class Test_WC_K2_Check_Payment_Status extends WP_UnitTestCase
{
    /** @var WC_K2_Check_Payment_Status */
    private $check_payment_status;

    /** @var WC_Order */
    private $order;

    /** @var WC_Product */
    private $product;

    public function setUp(): void
    {
        parent::setUp();

        $product_id = self::factory()->post->create([
            'post_type'  => 'product',
            'post_title' => 'Factory Product',
        ]);

        $this->product = wc_get_product($product_id);

        $this->order = wc_create_order();
        $this->order->add_product($this->product, 1);
        $this->order->calculate_totals();
        $this->order->save();

        // Initialize the class under test
        $this->check_payment_status = new WC_K2_Check_Payment_Status();
    }

    public function tearDown(): void
    {
        // Clean up
        wp_delete_post($this->order->get_id(), true);
        wp_delete_post($this->product->get_id(), true);
        parent::tearDown();
    }


    /**
     * Test that hooks are properly registered
     */
    public function test_hooks_are_registered(): void
    {
        // Test woocommerce_order_actions hook
        $this->assertNotFalse(
            has_action('woocommerce_order_actions', [$this->check_payment_status, 'k2_custom_order_actions']),
            'woocommerce_order_actions hook should be registered'
        );

        // Test woocommerce_order_action_wc_k2_check_payment_status_action hook
        $this->assertNotFalse(
            has_action('woocommerce_order_action_wc_k2_check_payment_status_action', [$this->check_payment_status, 'wc_k2_check_payment_status_action']),
            'woocommerce_order_action_wc_k2_check_payment_status_action hook should be registered'
        );

        // Test woocommerce_order_details_before_order_table hook
        $this->assertNotFalse(
            has_action('woocommerce_order_details_before_order_table', [$this->check_payment_status, 'wc_k2_customer_check_payment_status_action']),
            'woocommerce_order_details_before_order_table hook should be registered'
        );

        // Test admin_notices hook
        $this->assertTrue(has_action('admin_notices'), 'admin_notices hook should be registered');
    }

    /**
     * Test k2_custom_order_actions method adds custom action
     */
    public function test_k2_custom_order_actions(): void
    {
        $initial_actions = [
            'send_order_details' => 'Send order details to customer',
            'regenerate_download_permissions' => 'Regenerate download permissions'
        ];

        $result = $this->check_payment_status->k2_custom_order_actions($initial_actions);

        // Should add our custom action
        $this->assertArrayHasKey('wc_k2_check_payment_status_action', $result);
        $this->assertEquals('Check payment status', $result['wc_k2_check_payment_status_action']);

        // Should preserve existing actions
        $this->assertArrayHasKey('send_order_details', $result);
        $this->assertArrayHasKey('regenerate_download_permissions', $result);
        $this->assertCount(3, $result);
    }

    /**
     * Test k2_custom_order_actions with empty array
     */
    public function test_k2_custom_order_actions_empty_array(): void
    {
        $result = $this->check_payment_status->k2_custom_order_actions([]);

        $this->assertArrayHasKey('wc_k2_check_payment_status_action', $result);
        $this->assertCount(1, $result);
    }

    /**
     * Test wc_k2_check_payment_status_action with valid order
     */
    public function test_wc_k2_check_payment_status_action_valid_order(): void
    {
        $this->assertInstanceOf(WC_Order::class, $this->order);
        $this->assertTrue(method_exists($this->check_payment_status, 'wc_k2_check_payment_status_action'));
    }

    /**
     * Test customer check payment status action with valid conditions
     */
    public function test_wc_k2_customer_check_payment_status_action_valid_conditions(): void
    {
        // Set up order with correct conditions
        $this->order->set_status('on-hold');
        $this->order->set_payment_method('kkwoo');
        $this->order->save();

        ob_start();
        $this->check_payment_status->wc_k2_customer_check_payment_status_action($this->order);
        $output = ob_get_clean();

        $this->assertStringContainsString('id="kkwoo-flash-messages"', $output);
        $this->assertStringContainsString('class="woocommerce-NoticeGroup"', $output);
        $this->assertStringContainsString('id="check-payment-status"', $output);
        $this->assertStringContainsString('class="k2 outline w-fit"', $output);
        $this->assertStringContainsString('Check payment status', $output);
    }

    /**
     * Test customer check payment status action with wrong status
     */
    public function test_wc_k2_customer_check_payment_status_action_wrong_status(): void
    {
        // Set up order with wrong status
        $this->order->set_status('completed');
        $this->order->set_payment_method('kkwoo');
        $this->order->save();

        ob_start();
        $this->check_payment_status->wc_k2_customer_check_payment_status_action($this->order);
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    /**
     * Test customer check payment status action with wrong payment method
     */
    public function test_wc_k2_customer_check_payment_status_action_wrong_payment_method(): void
    {
        // Set up order with wrong payment method
        $this->order->set_status('on-hold');
        $this->order->set_payment_method('paypal');
        $this->order->save();

        ob_start();
        $this->check_payment_status->wc_k2_customer_check_payment_status_action($this->order);
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    /**
     * Test admin notices functionality
     */
    public function test_admin_notices(): void
    {
        // Set a transient message
        set_transient('kkwoo_admin_notice', 'Test payment status message', 30);

        ob_start();
        $this->check_payment_status->show_admin_notice();
        $output = ob_get_clean();

        $this->assertStringContainsString('class="notice notice-info is-dismissible"', $output);
        $this->assertStringContainsString('Test payment status message', $output);

        // Transient should be deleted after display
        $this->assertFalse(get_transient('kkwoo_admin_notice'));
    }

    /**
     * Test admin notices with no message
     */
    public function test_admin_notices_no_message(): void
    {
        // Ensure no transient exists
        delete_transient('kkwoo_admin_notice');

        ob_start();
        $this->check_payment_status->show_admin_notice();
        $output = ob_get_clean();

        $this->assertStringNotContainsString('notice notice-info', $output);
    }

    /**
     * Test admin notices with HTML in message (should be escaped)
     */
    public function test_admin_notices_html_escaping(): void
    {
        $malicious_message = '<script>alert("xss")</script>Payment updated';
        set_transient('kkwoo_admin_notice', $malicious_message);

        ob_start();
        $this->check_payment_status->show_admin_notice();
        $output = ob_get_clean();

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
        $this->assertStringContainsString('Payment updated', $output);
    }

    /**
     * Test that the order actions are updated to include 'Check payment status' action (Used for the admin order view page order actions)
     */
    public function test_order_actions_filter_integration(): void
    {
        $actions = [];
        $filtered_actions = apply_filters('woocommerce_order_actions', $actions);

        $this->assertArrayHasKey('wc_k2_check_payment_status_action', $filtered_actions);
        $this->assertEquals('Check payment status', $filtered_actions['wc_k2_check_payment_status_action']);
    }

    /**
     * Test multiple orders with different statuses and payment methods
     * @dataProvider orderStatusProvider
     */
    public function test_order_button_visibility_per_status(string $status): void
    {
        $order = $this->createMock(WC_Order::class);
        $order->method('has_status')->willReturn($status === 'on-hold');
        $order->method('get_payment_method')->willReturn('kkwoo');

        ob_start();
        $this->check_payment_status->wc_k2_customer_check_payment_status_action($order);
        $output = ob_get_clean();

        if ($status === 'on-hold') {
            $this->assertStringContainsString('check-payment-status', $output, "Button should be shown for status '{$status}' and payment method 'kkwoo'");
        } else {
            $this->assertStringNotContainsString('check-payment-status', $output, "Button should NOT be shown for status '{$status}' and payment method 'kkwoo'");
        }
    }

    /**
     * Test payment method validation
     * @dataProvider paymentMethodProvider
     */
    public function test_order_button_visibility_per_payment_method(string $payment_method): void
    {
        $this->order->set_status('on-hold');
        $this->order->set_payment_method($payment_method);
        $this->order->save();
        $order = $this->createMock(WC_Order::class);
        $order->method('has_status')->willReturn(true);
        $order->method('get_payment_method')->willReturn('kkwoo');

        ob_start();
        $this->check_payment_status->wc_k2_customer_check_payment_status_action($this->order);
        $output = ob_get_clean();

        if ($payment_method === 'kkwoo') {
            $this->assertStringContainsString('check-payment-status', $output, "Should show button for payment method: {$payment_method}");
        } else {
            $this->assertEmpty($output, "Should NOT show button for payment method: {$payment_method}");
        }
    }

    /**
     * Order status provider
     * @return array
     */
    public function orderStatusProvider(): array
    {
        return [
            ['on-hold'],
            ['completed'],
            ['processing'],
            ['pending'],
            ['cancelled'],
        ];
    }

    /**
     * Payment method provider
     * @return array
     */

    public function paymentMethodProvider(): array
    {
        return [['kkwoo'], ['paypal'], ['stripe'], ['cod'], ['bacs']];
    }
}
