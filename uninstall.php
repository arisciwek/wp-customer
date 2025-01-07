<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin tables
global $wpdb;
$tables = array(
    $wpdb->prefix . 'wp_customer_customer'
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Delete plugin options
delete_option('wp_customer_version');
delete_option('wp_customer_db_version');
