<?php

class Test_K2_Payment_Page extends WP_UnitTestCase
{
    /** @var K2_Payment_Page */
    private $payment_page;

    public function set_up(): void
    {
        parent::set_up();
        $this->payment_page = new K2_Payment_Page();
    }

    public function test_add_query_vars(): void
    {
        $vars = ['foo', 'bar'];
        $new_vars = $this->payment_page->add_query_vars($vars);

        $this->assertContains('lipa_na_mpesa_k2', $new_vars);
        $this->assertContains('order_key', $new_vars);
        $this->assertContains('foo', $new_vars);
        $this->assertContains('bar', $new_vars);
    }

    public function test_handle_k2_payment_page_returns_custom_template_if_query_var_set(): void
    {
        set_query_var('lipa_na_mpesa_k2', 1);

        $template_file = plugin_dir_path(__FILE__) . '_data/templates/k2-classic-payment-page.php';

        // Ensure the dummy template exists
        if (!file_exists($template_file)) {
            file_put_contents($template_file, '<!-- dummy template -->');
        }

        $template = $this->payment_page->handle_k2_payment_page('default-template.php');

        $this->assertStringContainsString('k2-classic-payment-page.php', $template);

        // Clean up dummy template
        unlink($template_file);
    }

    public function test_handle_k2_payment_page_returns_default_template_if_query_var_not_set(): void
    {
        $template = $this->payment_page->handle_k2_payment_page('default-template.php');
        $this->assertEquals('default-template.php', $template);
    }
}
