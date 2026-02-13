<?php

class Test_KKWoo_Rest_Api extends WP_UnitTestCase
{
    /** @var WC_Order  */
    private $order;

    /** @var WC_Product */
    private $product;

    private $payloads;

    public function set_up(): void
    {
        parent::set_up();

        $product_id = self::factory()->post->create([
            'post_type'  => 'product',
            'post_title' => 'Factory Product',
        ]);

        $this->product = wc_get_product($product_id);
        $this->create_test_order();

        $this->payloads = require __DIR__ . '/fixtures/stk_callback_payloads.php';
    }

    private function create_test_order(): void
    {
        $this->order = wc_create_order();
        $this->order->add_product($this->product, 1);
        $this->order->calculate_totals();
        $this->order->save();
    }

    public function tearDown(): void
    {
        if ($this->order) {
            wp_delete_post($this->order->get_id(), true);
            $this->order = null;
        }
        if ($this->product) {
            wp_delete_post($this->product->get_id(), true);
            $this->product = null;
        }
        parent::tearDown();
    }

    public function test_routes_are_registered(): void
    {
        $routes = rest_get_server()->get_routes();

        $this->assertArrayHasKey('/kkwoo/v1/stk-push', $routes);
        $this->assertArrayHasKey('/kkwoo/v1/stk-push-callback', $routes);
        $this->assertArrayHasKey('/kkwoo/v1/payment-status', $routes);
        $this->assertArrayHasKey('/kkwoo/v1/query-incoming-payment-status', $routes);
    }

    public function test_stk_push_returns_400_if_order_not_found_due_to_wrong_key(): void
    {
        // Don’t create any order → it should fail
        $request = new WP_REST_Request('POST', '/kkwoo/v1/stk-push');
        $request->set_param('phone', '700000000');
        $request->set_param('order_key', 'non_existent_key');

        $mockValidator = $this->getMockBuilder(\KKWoo\Security\Request_Validator::class)
                      ->onlyMethods(['validate_stk_push_callback'])
                      ->getMock();

        $mockValidator->method('validate_stk_push_callback')
                    ->willReturn(true);

        $response = rest_get_server()->dispatch($request);

        $this->assertEquals(400, $response->get_status());
        $this->assertEquals('error', $response->get_data()['status']);
    }

    public function test_stk_push_returns_400_if_order_not_pending_or_failed(): void
    {
        $this->order->set_status('on-hold');
        $this->order->save();
        $request = new WP_REST_Request('POST', '/kkwoo/v1/stk-push');
        $request->set_param('phone', '700000000');
        $request->set_param('order_key', $this->order->get_order_key());

        $response = rest_get_server()->dispatch($request);

        $this->assertEquals(400, $response->get_status());
        $this->assertEquals('error', $response->get_data()['status']);
        $this->assertEquals('This order is not currently marked as awaiting payment. If you’ve already completed your payment, please allow some time for it to be processed. If the order was cancelled, you can create a new one to try again.', $response->get_data()['data']);
    }

    public function test_callback_returns_error_if_no_data(): void
    {
        $request = new WP_REST_Request('POST', '/kkwoo/v1/stk-push-callback');
        $request->set_body_params([]);

        $response = kkwoo_process_stk_push_callback($request);

        $this->assertEquals(400, $response->get_status());
        $this->assertEquals('error', $response->get_data()['status']);
        $this->assertEquals('Invalid callback data', $response->get_data()['message']);
    }

    public function test_callback_returns_error_if_missing_reference(): void
    {
        $payload = $this->payloads['validated_failed']('');

        $request = new WP_REST_Request('POST', '/kkwoo/v1/stk-push-callback');
        $request->set_body(json_encode($payload));
        $request->set_header('Content-Type', 'application/json');

        $response = kkwoo_process_stk_push_callback($request);

        $this->assertEquals(400, $response->get_status());
        $this->assertEquals('error', $response->get_data()['status']);
        $this->assertEquals('Missing order reference', $response->get_data()['message']);
    }

    public function test_callback_returns_error_if_order_not_found(): void
    {
        $payload = $this->payloads['validated_success']('non_existent_key');

        $request = new WP_REST_Request('POST', '/kkwoo/v1/stk-push-callback');
        $request->set_body(json_encode($payload));
        $request->set_header('Content-Type', 'application/json');

        $response = kkwoo_process_stk_push_callback($request);

        $this->assertEquals(404, $response->get_status());
        $this->assertEquals('error', $response->get_data()['status']);
        $this->assertEquals('Order not found', $response->get_data()['message']);
    }

    public function test_callback_success_marks_order_paid(): void
    {
        $this->order->set_status('on-hold');
        $this->order->save();
        $order_key = $this->order->get_order_key();
        $payload = $this->payloads['validated_success']($order_key);

        $request = new WP_REST_Request('POST', '/kkwoo/v1/stk-push-callback');
        $request->set_body(json_encode($payload));
        $request->set_header('Content-Type', 'application/json');

        $response = kkwoo_process_stk_push_callback($request);

        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('success', $response->get_data()['status']);

        $order = wc_get_order($this->order->get_id());
        $this->assertEquals('processing', $order->get_status());
        $this->assertEquals('', $order->get_meta('kkwoo_payment_error_msg'));
    }

    public function test_callback_failure_marks_order_failed(): void
    {
        $this->order->set_status('on-hold');
        $this->order->save();
        $order_key = $this->order->get_order_key();

        $payload = $this->payloads['validated_failed']($order_key);

        $request = new WP_REST_Request('POST', '/kkwoo/v1/stk-push-callback');
        $request->set_body(json_encode($payload));
        $request->set_header('Content-Type', 'application/json');

        $response = kkwoo_process_stk_push_callback($request);

        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('error', $response->get_data()['status']);

        $order = wc_get_order($this->order->get_id());
        $this->assertEquals('failed', $order->get_status());
        $this->assertEquals('The balance is insufficient for the transaction', $order->get_meta('kkwoo_payment_error_msg'));
    }

    public function test_payment_status_returns_order_status(): void
    {
        $orderStatus = $this->order->get_status();
        $request = new WP_REST_Request('GET', '/kkwoo/v1/payment-status');
        $request->set_param('order_key', $this->order->get_order_key());

        $response = kkwoo_get_payment_status($request);

        $this->assertEquals($orderStatus, $response['status']);
    }

    public function test_payment_status_returns_not_found_for_invalid_order_key(): void
    {
        $request = new WP_REST_Request('GET', '/kkwoo/v1/payment-status');
        $request->set_param('order_key', 'invalid_order_key');

        $response = kkwoo_get_payment_status($request);

        $this->assertEquals('not_found', $response['status']);
        $this->assertEquals('', $response['data']);
    }

    public function test_payment_status_returns_data_when_present(): void
    {
        $this->order->update_meta_data('kkwoo_payment_error_msg', 'An error occured!');
        $this->order->save();
        $request = new WP_REST_Request('GET', '/kkwoo/v1/payment-status');
        $request->set_param('order_key', $this->order->get_order_key());

        $response = kkwoo_get_payment_status($request);

        $this->assertEquals('An error occured!', $response['data']);
    }
}
