<?php
/**
 * Customers Table Schema
 *
 * @package     WP_Customer
 * @subpackage  Database/Tables
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Tables/Customers.php
 *
 * Description: Mendefinisikan struktur tabel customers.
 *              Table prefix yang digunakan adalah 'app_'.
 *              Includes field untuk integrasi wilayah.
 *              Menyediakan foreign key untuk customer-branch.
 *
 * Fields:
 * - id             : Primary key
 * - code           : Kode customer (2 digit)
 * - name           : Nama customer
 * - provinsi_id    : ID provinsi (nullable)
 * - regency_id     : ID cabang (nullable)
 * - user_id        : ID User WP sebagai Owner (nullable)
 * - created_by     : User ID pembuat
 * - created_at     : Timestamp pembuatan
 * - updated_at     : Timestamp update terakhir
 *
 * Changelog:
 * 1.0.0 - 2024-01-07
 * - Initial version
 * - Added basic customer fields
 * - Added wilayah integration fields
 * - Added timestamps and audit fields
 */

namespace WPCustomer\Database\Tables;

defined('ABSPATH') || exit;

namespace WPCustomer\Database\Tables;

class Customers {
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customers';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL auto_increment,
            code varchar(2) NOT NULL,
            name varchar(100) NOT NULL,
            provinsi_id bigint(20) UNSIGNED NULL,
            regency_id bigint(20) UNSIGNED NULL,
            user_id bigint(20) UNSIGNED NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code),
            UNIQUE KEY name (name),
            KEY created_by_index (created_by)
        ) $charset_collate;";
    }
}
