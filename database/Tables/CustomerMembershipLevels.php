<?php
/**
 * Membership Levels Table Schema
 *
 * @package     WP_Customer
 * @subpackage  Database/Tables
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/database/Tables/CustomerMembershipLevels.php
 *
 * Description: Mendefinisikan struktur tabel membership levels.
 *              Table prefix yang digunakan adalah 'app_'.
 *              Menyediakan klasifikasi level member customer.
 *              Includes configuration untuk batasan staff.
 *
 * Fields:
 * - id             : Primary key
 * - name           : Nama level membership
 * - slug           : Slug untuk identifikasi unik
 * - description    : Deskripsi level (nullable)
 * - max_staff      : Batas maksimal staff (-1 untuk unlimited)
 * - capabilities   : JSON string untuk konfigurasi fitur
 * - created_by     : User ID pembuat
 * - created_at     : Timestamp pembuatan
 * - status         : Status aktif/nonaktif
 * 
 * Changelog:
 * 1.0.0 - 2024-01-07
 * - Initial version
 * - Added basic membership fields
 * - Added capabilities JSON field
 * - Added status field
 */

namespace WPCustomer\Database\Tables;

defined('ABSPATH') || exit;

class CustomerMembershipLevels {
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customer_membership_levels';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL auto_increment,
            name varchar(50) NOT NULL,
            slug varchar(50) NOT NULL,
            description text NULL,
            max_staff int NOT NULL DEFAULT 2,
            capabilities text NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            status enum('active','inactive') DEFAULT 'active',
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY created_by_index (created_by)
        ) $charset_collate;";
    }

    public static function insert_defaults() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customer_membership_levels';

        $defaults = [
            [
                'name' => 'Regular',
                'slug' => 'regular',
                'description' => 'Paket dasar dengan maksimal 2 staff',
                'max_staff' => 2,
                'capabilities' => json_encode([
                    'can_add_staff' => true,
                    'max_departments' => 1
                ]),
                'created_by' => get_current_user_id(),
                'status' => 'active'
            ],
            [
                'name' => 'Priority',
                'slug' => 'priority',
                'description' => 'Paket menengah dengan maksimal 5 staff',
                'max_staff' => 5,
                'capabilities' => json_encode([
                    'can_add_staff' => true,
                    'can_export' => true,
                    'max_departments' => 3
                ]),
                'created_by' => get_current_user_id(),
                'status' => 'active'
            ],
            [
                'name' => 'Utama',
                'slug' => 'utama',
                'description' => 'Paket premium tanpa batasan staff',
                'max_staff' => -1,
                'capabilities' => json_encode([
                    'can_add_staff' => true,
                    'can_export' => true,
                    'can_bulk_import' => true,
                    'max_departments' => -1
                ]),
                'created_by' => get_current_user_id(),
                'status' => 'active'
            ]
        ];

        foreach ($defaults as $level) {
            $wpdb->insert($table_name, $level);
        }
    }
}
