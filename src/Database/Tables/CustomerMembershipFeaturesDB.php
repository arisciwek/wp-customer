<?php
/**
 * Membership Features Table Schema
 *
 * @package     WP_Customer
 * @subpackage  Database/Tables
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Tables/CustomerMembershipFeaturesDB.php
 *
 * Description: Mendefinisikan struktur tabel feature fields untuk membership.
 *              Fields dikelompokkan dalam 3 grup:
 *              - features: Fitur-fitur yang dapat diakses
 *              - limits: Batasan numerik
 *              - notifications: Pengaturan notifikasi
 *              Table prefix yang digunakan adalah 'app_'.
 *
 * Fields:
 * - id             : Primary key
 * - field_group    : Grup field (features/limits/notifications)
 * - field_name     : Nama unik untuk field
 * - field_label    : Label yang ditampilkan
 * - field_type     : Tipe input (text/textarea/date/number/select/radio/checkbox/email/password)
 * - field_subtype  : Sub-tipe untuk number (integer/float)
 * - is_required    : Field wajib diisi atau tidak
 * - css_class      : CSS class untuk styling
 * - css_id         : CSS ID untuk styling
 * - created_by     : User ID pembuat
 * - created_at     : Timestamp pembuatan
 * - updated_at     : Timestamp update terakhir
 *
 * Changelog:
 * 1.0.0 - 2024-02-10
 * - Initial version
 * - Added group-based field structure
 * - Added basic field types
 * - Added default field definitions
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
            field_group enum('features','limits','notifications') NOT NULL,
            field_name varchar(50) NOT NULL,
            field_label varchar(100) NOT NULL,
            field_type varchar(20) NOT NULL,
            field_subtype varchar(20) NULL,
            is_required tinyint(1) NOT NULL DEFAULT 0,
            css_class varchar(100) NULL,
            css_id varchar(50) NULL,
            sort_order int NOT NULL DEFAULT 0,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            status enum('active','inactive') NOT NULL DEFAULT 'active',
            PRIMARY KEY  (id),
            UNIQUE KEY field_name (field_name),
            KEY field_group (field_group),
            KEY status (status),
            KEY created_by_index (created_by),
            KEY sort_order_index (sort_order)
        ) $charset_collate;";
    }

    /**
     * Insert default field definitions
     */
    public static function insert_defaults() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customer_membership_features';
        $current_user_id = get_current_user_id();

        $defaults = [
            // Features Group
            [
                'field_group' => 'features',
                'field_name' => 'can_add_staff',
                'field_label' => 'Dapat Menambah Staff',
                'field_type' => 'checkbox',
                'is_required' => 1,
                'css_class' => 'feature-checkbox',
                'sort_order' => 10,
                'created_by' => $current_user_id
            ],
            [
                'field_group' => 'features',
                'field_name' => 'can_export',
                'field_label' => 'Dapat Export Data',
                'field_type' => 'checkbox',
                'is_required' => 1,
                'css_class' => 'feature-checkbox',
                'sort_order' => 20,
                'created_by' => $current_user_id
            ],
            [
                'field_group' => 'features',
                'field_name' => 'can_bulk_import',
                'field_label' => 'Dapat Bulk Import',
                'field_type' => 'checkbox',
                'is_required' => 1,
                'css_class' => 'feature-checkbox',
                'sort_order' => 30,
                'created_by' => $current_user_id
            ],

            // Limits Group
            [
                'field_group' => 'limits',
                'field_name' => 'max_staff',
                'field_label' => 'Maksimal Staff',
                'field_type' => 'number',
                'field_subtype' => 'integer',
                'is_required' => 1,
                'css_class' => 'limit-number',
                'sort_order' => 10,
                'created_by' => $current_user_id
            ],
            [
                'field_group' => 'limits',
                'field_name' => 'max_departments',
                'field_label' => 'Maksimal Departemen',
                'field_type' => 'number',
                'field_subtype' => 'integer',
                'is_required' => 1,
                'css_class' => 'limit-number',
                'sort_order' => 20,
                'created_by' => $current_user_id
            ],
            [
                'field_group' => 'limits',
                'field_name' => 'max_active_projects',
                'field_label' => 'Maksimal Projek Aktif',
                'field_type' => 'number',
                'field_subtype' => 'integer',
                'is_required' => 1,
                'css_class' => 'limit-number',
                'sort_order' => 30,
                'created_by' => $current_user_id
            ],

            // Notifications Group
            [
                'field_group' => 'notifications',
                'field_name' => 'email',
                'field_label' => 'Notifikasi Email',
                'field_type' => 'checkbox',
                'is_required' => 1,
                'css_class' => 'notification-checkbox',
                'sort_order' => 10,
                'created_by' => $current_user_id
            ],
            [
                'field_group' => 'notifications',
                'field_name' => 'dashboard',
                'field_label' => 'Notifikasi Dashboard',
                'field_type' => 'checkbox',
                'is_required' => 1,
                'css_class' => 'notification-checkbox',
                'sort_order' => 20,
                'created_by' => $current_user_id
            ],
            [
                'field_group' => 'notifications',
                'field_name' => 'push',
                'field_label' => 'Notifikasi Push',
                'field_type' => 'checkbox',
                'is_required' => 1,
                'css_class' => 'notification-checkbox',
                'sort_order' => 30,
                'created_by' => $current_user_id
            ]
        ];

        foreach ($defaults as $field) {
            $wpdb->insert($table_name, $field);
        }
    }
}
