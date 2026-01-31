<?php

namespace KKWoo\Database;

class Manual_Payments_Tracker_repository
{
    private static $base_table_name = 'kkwoo_manual_payments_tracker';
    public static function create_table(): void
    {
        global $wpdb;

        $table_name = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED,
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

        $table = self::table_name();

        $wpdb->hide_errors();  // Prevent showing HTML error blocks

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $success = $wpdb->query(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "INSERT INTO {$table} (mpesa_ref_no, order_id, webhook_payload)
                VALUES (%s, %d, %s)
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
            KKWoo_Logger::log(
                sprintf(
                    'DB upsert failed for table %s, mpesa_ref_no %s: %s',
                    $table,
                    $mpesa_ref_no,
                    $wpdb->last_error
                ),
                'error'
            );
        } else {
            wp_cache_delete(
                'kkwoo_mpesa_ref_' . md5($mpesa_ref_no),
                'kkwoo'
            );
        }

        return $wpdb->rows_affected !== false;
    }

    public static function get_by_mpesa_ref(string $mpesa_ref_no): ?array
    {
        global $wpdb;

        $mpesa_ref_no = sanitize_text_field($mpesa_ref_no);

        $cache_key   = 'kkwoo_mpesa_ref_' . md5($mpesa_ref_no);
        $cache_group = 'kkwoo';

        $cached = wp_cache_get($cache_key, $cache_group);
        if (false !== $cached) {
            return $cached;
        }

        $table = self::table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,
        $result = $wpdb->get_row(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->prepare("SELECT * FROM {$table} WHERE mpesa_ref_no = %s LIMIT 1", $mpesa_ref_no),
            ARRAY_A
        );

        if (!is_array($result)) {
            return null;
        }

        wp_cache_set($cache_key, $result, $cache_group, 5 * MINUTE_IN_SECONDS);

        return $result;
    }

    private static function table_name(): string
    {
        global $wpdb;
        return $wpdb->prefix . self::$base_table_name;
    }
}
