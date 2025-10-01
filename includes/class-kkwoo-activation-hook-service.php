<?php

if (! defined('ABSPATH')) {
    exit;
}

use KKWoo\Database\Manual_Payments_Tracker_repository;

class KKWoo_Activation_Service
{
    public static function activate(): void
    {
        Manual_Payments_Tracker_repository::create_table();
        K2_Payment_Page::flush_rules();
    }
}
