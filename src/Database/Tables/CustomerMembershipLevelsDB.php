<?php
/**
* Membership Levels Table Schema
*
* @package     WP_Customer
* @subpackage  Database/Tables
* @version     1.0.2
* @author      arisciwek
* 
* Path: /wp-customer/src/Database/Tables/CustomerMembershipLevelsDB.php
*
* Description: Mendefinisikan struktur tabel membership levels.
*              Includes:
*              - Basic level information (name, description, pricing)
*              - Trial and grace period settings
*              - JSON capabilities berdasarkan feature groups
*              - Pengaturan khusus per level
*              
* Dependencies:
* - WordPress database ($wpdb)
* - MySQL 5.7+ untuk JSON support
* - CustomerMembershipFeaturesDB untuk referensi feature groups
*
* Changelog:
* 1.0.2 - 2025-02-14
* - Updated capabilities JSON structure to match feature groups
* - Added settings column for level-specific configurations
* - Enhanced documentation
* 
* 1.0.1 - 2025-02-11 
* - Added period and pricing fields
* - Added trial and grace period settings
* - Enhanced capabilities JSON structure
* - Added sort order for display
* 
* 1.0.0 - 2025-01-27
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
            settings JSON NULL,           /* Tambahan untuk pengaturan level spesifik */
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            status enum('active','inactive') NOT NULL DEFAULT 'active',
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY status (status),
            KEY created_by_index (created_by),
            KEY sort_order_index (sort_order)
        ) $charset_collate;";
    }
}
