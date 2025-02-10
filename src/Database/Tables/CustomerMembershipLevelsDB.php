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
            available_periods text NULL,
            default_period int NOT NULL DEFAULT 1,
            price_per_month decimal(10,2) NOT NULL DEFAULT 0.00,
            is_trial_available tinyint(1) NOT NULL DEFAULT 0,
            trial_days int NOT NULL DEFAULT 0,
            grace_period_days int NOT NULL DEFAULT 0,
            sort_order int NOT NULL DEFAULT 0,
            capabilities text NULL,
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

    /**
     * Insert default membership levels
     */
    public static function insert_defaults() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customer_membership_levels';
        $current_user_id = get_current_user_id();

        $defaults = [
            [
                'name' => 'Regular',
                'slug' => 'regular',
                'description' => 'Paket dasar dengan maksimal 2 staff',
                'available_periods' => json_encode([1, 3, 6, 12]),
                'default_period' => 1,
                'price_per_month' => 50000,
                'is_trial_available' => 1,
                'trial_days' => 7,
                'grace_period_days' => 3,
                'sort_order' => 1,
                'capabilities' => json_encode([
                    'features' => [
                        'can_add_staff' => true,
                        'can_export' => false,
                        'can_bulk_import' => false
                    ],
                    'limits' => [
                        'max_staff' => 2,
                        'max_departments' => 1,
                        'max_active_projects' => 5
                    ],
                    'notifications' => [
                        'email' => true,
                        'dashboard' => true,
                        'push' => false
                    ]
                ]),
                'created_by' => $current_user_id,
                'status' => 'active'
            ],
            [
                'name' => 'Priority',
                'slug' => 'priority',
                'description' => 'Paket menengah dengan maksimal 5 staff',
                'available_periods' => json_encode([1, 3, 6, 12]),
                'default_period' => 1,
                'price_per_month' => 100000,
                'is_trial_available' => 1,
                'trial_days' => 7,
                'grace_period_days' => 5,
                'sort_order' => 2,
                'capabilities' => json_encode([
                    'features' => [
                        'can_add_staff' => true,
                        'can_export' => true,
                        'can_bulk_import' => false
                    ],
                    'limits' => [
                        'max_staff' => 5,
                        'max_departments' => 3,
                        'max_active_projects' => 10
                    ],
                    'notifications' => [
                        'email' => true,
                        'dashboard' => true,
                        'push' => true
                    ]
                ]),
                'created_by' => $current_user_id,
                'status' => 'active'
            ],
            [
                'name' => 'Utama',
                'slug' => 'utama',
                'description' => 'Paket premium tanpa batasan staff',
                'available_periods' => json_encode([1, 3, 6, 12]),
                'default_period' => 1,
                'price_per_month' => 200000,
                'is_trial_available' => 0,
                'trial_days' => 0,
                'grace_period_days' => 7,
                'sort_order' => 3,
                'capabilities' => json_encode([
                    'features' => [
                        'can_add_staff' => true,
                        'can_export' => true,
                        'can_bulk_import' => true
                    ],
                    'limits' => [
                        'max_staff' => -1,
                        'max_departments' => -1,
                        'max_active_projects' => -1
                    ],
                    'notifications' => [
                        'email' => true,
                        'dashboard' => true,
                        'push' => true
                    ]
                ]),
                'created_by' => $current_user_id,
                'status' => 'active'
            ]
        ];

        foreach ($defaults as $level) {
            $wpdb->insert($table_name, $level);
        }
    }
}
