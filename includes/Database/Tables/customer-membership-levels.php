<?php
namespace CustomerManagement\Database\Tables;

defined('ABSPATH') || exit;

class Customer_Membership_Levels {
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'customer_membership_levels';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL auto_increment,
            name varchar(50) NOT NULL,
            slug varchar(50) NOT NULL,
            description text,
            max_staff int NOT NULL DEFAULT 2,
            capabilities text,
            created_by bigint(20) unsigned,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            status enum('active','inactive') DEFAULT 'active',
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY created_by (created_by)
        ) $charset_collate;";
    }

    public static function insert_defaults() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'customer_membership_levels';

        $defaults = array(
            array(
                'name' => 'Regular',
                'slug' => 'regular',
                'description' => 'Paket dasar dengan maksimal 2 staff',
                'max_staff' => 2,
                'capabilities' => json_encode(array(
                    'can_add_staff' => true,
                    'max_departments' => 1
                ))
            ),
            array(
                'name' => 'Priority',
                'slug' => 'priority',
                'description' => 'Paket menengah dengan maksimal 5 staff',
                'max_staff' => 5,
                'capabilities' => json_encode(array(
                    'can_add_staff' => true,
                    'can_export' => true,
                    'max_departments' => 3
                ))
            ),
            array(
                'name' => 'Utama',
                'slug' => 'utama',
                'description' => 'Paket premium tanpa batasan staff',
                'max_staff' => -1,
                'capabilities' => json_encode(array(
                    'can_add_staff' => true,
                    'can_export' => true,
                    'can_bulk_import' => true,
                    'max_departments' => -1
                ))
            )
        );

        foreach ($defaults as $level) {
            $wpdb->insert($table_name, $level);
        }
    }
}
