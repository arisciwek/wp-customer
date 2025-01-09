<?php
/**
 * Branches Table Schema
 *
 * @package     WP_Customer
 * @subpackage  Database/Tables
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Tables/Branches.php
 *
 * Description: Mendefinisikan struktur tabel branches.
 *              Table prefix yang digunakan adalah 'app_'.
 *              Includes field untuk integrasi wilayah.
 *              Menyediakan foreign key ke customers table.
 *
 * Fields:
 * - id             : Primary key
 * - customer_id    : Foreign key ke customer
 * - code           : Kode branch (4 digit)
 * - name           : Nama branch
 * - type           : Tipe wilayah (cabang)
 * - provinsi_id    : ID provinsi (nullable)
 * - unit_id     : ID cabang (nullable)
 * - created_by     : User ID pembuat
 * - created_at     : Timestamp pembuatan
 * - updated_at     : Timestamp update terakhir
 *
 * Foreign Keys:
 * - customer_id    : REFERENCES app_customers(id) ON DELETE CASCADE
 *
 * Changelog:
 * 1.0.0 - 2024-01-07
 * - Initial version
 * - Added basic branch fields
 * - Added wilayah integration fields
 * - Added foreign key constraint to customers
 */

namespace WPCustomer\Database\Tables;

defined('ABSPATH') || exit;

namespace WPCustomer\Database\Tables;

class Branches {
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_branches';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL auto_increment,
            customer_id bigint(20) UNSIGNED NOT NULL,
            code varchar(4) NOT NULL,
            name varchar(100) NOT NULL,
            type enum('kabupaten','kota') NOT NULL,
            provinsi_id bigint(20) UNSIGNED NULL,
            unit_id bigint(20) UNSIGNED NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY customer_name (customer_id, name),
            UNIQUE KEY code (code),
            KEY created_by_index (created_by),
            CONSTRAINT `{$wpdb->prefix}app_branches_ibfk_1` 
                FOREIGN KEY (customer_id) 
                REFERENCES `{$wpdb->prefix}app_customers` (id) 
                ON DELETE CASCADE
        ) $charset_collate;";
    }
}
