<?php
namespace CustomerManagement\Database\Tables;

defined('ABSPATH') || exit;

class Customers {
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'customers';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL auto_increment,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20),
            address text,
            provinsi_id int unsigned,
            kabupaten_id int unsigned,
            employee_id bigint(20) unsigned,
            branch_id bigint(20) unsigned,
            membership_level_id bigint(20) unsigned NOT NULL,
            created_by bigint(20) unsigned,
            assigned_to bigint(20) unsigned,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            status enum('active','inactive') DEFAULT 'inactive',
            PRIMARY KEY  (id),
            KEY membership_level_id (membership_level_id),
            KEY employee_id (employee_id),
            KEY branch_id (branch_id),
            KEY created_by (created_by),
            KEY assigned_to (assigned_to)
        ) $charset_collate;";
    }
}
