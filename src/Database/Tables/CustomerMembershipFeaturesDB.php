<?php
/**
 * Membership Features Table Schema
 *
 * @package     WP_Customer
 * @subpackage  Database/Tables
 * @version     2.0.0
 */

namespace WPCustomer\Database\Tables;

defined('ABSPATH') || exit;

class CustomerMembershipFeaturesDB {
    /**
     * Get database schema
     * @return string SQL schema
     */
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customer_membership_features';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL auto_increment,
            field_name varchar(50) NOT NULL,
            metadata JSON NOT NULL,
            sort_order int NOT NULL DEFAULT 0,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            status enum('active','inactive') NOT NULL DEFAULT 'active',
            PRIMARY KEY  (id),
            UNIQUE KEY field_name (field_name),
            KEY status (status),
            KEY created_by_index (created_by),
            KEY sort_order_index (sort_order)
        ) $charset_collate;";
    }

}
