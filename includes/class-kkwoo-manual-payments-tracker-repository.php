<?php

namespace KKWoo\Database;

class Manual_Payments_Tracker_repository
{
    private static $base_table_name = 'kkwoo_manual_payments_tracker';
    public static function create_table(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$base_table_name;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED UNIQUE,
    mpesa_ref_no VARCHAR(50) NOT NULL UNIQUE,
    webhook_payload LONGTEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
    ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function upsert(int $order_id = null, string $mpesa_ref_no, string $webhook_payload = null): bool
    {
        global $wpdb;

        $table = $wpdb->prefix . self::$base_table_name;

        $wpdb->hide_errors();  // Prevent showing HTML error blocks

        $success = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO $table (mpesa_ref_no, order_id, webhook_payload)
                VALUES (%d, %s, %s)
                ON DUPLICATE KEY UPDATE
                order_id = CASE
                    WHEN VALUES(order_id) IS NOT NULL AND VALUES(order_id) != 0 THEN VALUES(order_id)
                    ELSE order_id
                END,
                webhook_payload = CASE
                    WHEN VALUES(webhook_payload) IS NOT NULL AND VALUES(webhook_payload) != '' THEN VALUES(webhook_payload)
                    ELSE webhook_payload
                END",
                $mpesa_ref_no,
                $order_id,
                $webhook_payload,
            )
        );

        if ($success === false) {
            error_log("DB upsert failed: " . $wpdb->last_error);
        }

        return $wpdb->rows_affected !== false;
    }

    public static function get_by_mpesa_ref(string $mpesa_ref_no): ?array
    {
        global $wpdb;

        $mpesa_ref_no = sanitize_text_field($mpesa_ref_no);
        $table = $wpdb->prefix . self::$base_table_name;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE mpesa_ref_no = %s LIMIT 1", $mpesa_ref_no),
            ARRAY_A
        );
    }
}
