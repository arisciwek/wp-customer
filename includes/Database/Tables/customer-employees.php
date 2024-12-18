<?php
namespace CustomerManagement\Database\Tables;

defined('ABSPATH') || exit;

class Customer_Employees {
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'customer_employees';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL auto_increment,
            name varchar(100) NOT NULL,
            position varchar(100),
            department varchar(100),
            email varchar(100),
            phone varchar(20),
            created_by bigint(20) unsigned,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            status enum('active','inactive') DEFAULT 'active',
            PRIMARY KEY  (id),
            KEY created_by (created_by)
        ) $charset_collate;";
    }
}
