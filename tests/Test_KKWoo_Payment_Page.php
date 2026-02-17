<?php

class Test_KKWoo_Payment_Page extends WP_UnitTestCase {

	/** @var KKWoo_Payment_Page */
	private $payment_page;

	public function set_up(): void {
		parent::set_up();
		$this->payment_page = new KKWoo_Payment_Page();
	}

	public function test_add_query_vars(): void {
		$vars     = array( 'foo', 'bar' );
		$new_vars = $this->payment_page->add_query_vars( $vars );

		$this->assertContains( 'lipa_na_mpesa_k2', $new_vars );
		$this->assertContains( 'kkwoo_order_key', $new_vars );
		$this->assertContains( 'foo', $new_vars );
		$this->assertContains( 'bar', $new_vars );
	}
}
