<?php

if (! defined('ABSPATH')) {
    exit;
}

class K2_Payment_Page
{
    public function __construct()
    {
        add_action('init', [self::class, 'add_rewrite_rules']);
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

    public static function flush_rules(): void
    {
        self::add_rewrite_rules();
        flush_rewrite_rules();
    }
}
