<?php

class K2_Payment_Page
{
    public function __construct()
    {
        add_action('init', [self::class, 'add_rewrite_rules']);
        add_filter('template_include', array( $this, 'handle_k2_payment_page' ));
        add_filter('query_vars', array( $this, 'add_query_vars' ));
    }

    public static function add_rewrite_rules(): void
    {
        add_rewrite_rule(
            '^lipa-na-mpesa-k2/?$',
            'index.php?lipa_na_mpesa_k2=1',
            'top'
        );
    }

    /**
     * Add custom query vars
     *
     * @param array $vars
     * @return array
     */
    public function add_query_vars($vars): array
    {
        $vars[] = 'lipa_na_mpesa_k2';
        $vars[] = 'order_key';
        return $vars;
    }

    /**
    * Handles the custom K2 payment page template loading.
    *
    * This method checks if the `lipa_na_mpesa_k2` query variable is set,
    * and if so, it loads a custom payment page template from the plugin.
    *
    * @param string $template The path to the current template WordPress is about to load.
    *
    * @return string The path to either the custom payment template or the default template.
    */
    public function handle_k2_payment_page($template): string
    {
        if (get_query_var('lipa_na_mpesa_k2')) {

            if (wp_is_block_theme()) {
                $custom_template = plugin_dir_path(__FILE__) . 'templates/k2-block-payment-page.php';
            } else {
                $custom_template = plugin_dir_path(__FILE__) . 'templates/k2-classic-payment-page.php';
            }

            if (file_exists($custom_template)) {
                return $custom_template;
            }

        }
        return $template;
    }

    public static function flush_rules(): void
    {
        self::add_rewrite_rules();
        flush_rewrite_rules();
    }
}
