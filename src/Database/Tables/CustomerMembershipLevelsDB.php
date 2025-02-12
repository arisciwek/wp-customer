<?php
/**
 * Membership Levels Table Schema
 *
 * @package     WP_Customer
 * @subpackage  Database/Tables
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Tables/CustomerMembershipLevelsDB.php
 *
 * Description: Mendefinisikan struktur tabel membership levels yang ditingkatkan.
 *              Includes penambahan fields untuk:
 *              - Periode membership dan harga
 *              - Trial dan grace period settings
 *              - Enhanced capabilities dengan format JSON baru
 *              Table prefix yang digunakan adalah 'app_'.
 *
 * Changelog:
 * 2.0.0 - 2024-02-08
 * - Added period and pricing fields
 * - Added trial and grace period settings
 * - Enhanced capabilities JSON structure
 * - Added department limits
 * - Added sort order for display
 * 
 * 1.0.0 - 2024-01-07
 * - Initial version
 * - Basic membership fields
 */

namespace WPCustomer\Database\Tables;

defined('ABSPATH') || exit;

class CustomerMembershipLevelsDB {
    /**
     * Get database schema
     * @return string SQL schema
     */
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customer_membership_levels';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL auto_increment,
            name varchar(50) NOT NULL,
            slug varchar(50) NOT NULL,
            description text NULL,
            available_periods ENUM('0','1','3','6','12') NOT NULL DEFAULT '1',
            default_period int NOT NULL DEFAULT 1,
            price_per_month decimal(10,2) NOT NULL DEFAULT 0.00,
            is_trial_available tinyint(1) NOT NULL DEFAULT 0,
            trial_days int NOT NULL DEFAULT 0,
            grace_period_days int NOT NULL DEFAULT 0,
            sort_order int NOT NULL DEFAULT 0,
            capabilities JSON NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            status enum('active','inactive') NOT NULL DEFAULT 'active',
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY status (status),
            KEY created_by_index (created_by)
        ) $charset_collate;";
    }
}
