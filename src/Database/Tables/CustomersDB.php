<?php
/**
 * Customers Table Schema
 *
 * @package     WP_Customer
 * @subpackage  Database/Tables
 * @version     1.0.12
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Tables/CustomersDB.php
 *
 * Description: Mendefinisikan struktur tabel customers.
 *              Table prefix yang digunakan adalah 'app_'.
 *              Includes field untuk integrasi wilayah.
 *              Menyediakan foreign key untuk customer-branch.
 *
 * Fields:
 * - id             : Primary key
 * - code           : Format
 * - name           : Nama customer
 * - nik            : Nomor Induk Kependudukan
 * - npwp           : Nomor Pokok Wajib Pajak
 * - provinsi_id    : ID provinsi (required)
 * - regency_id     : ID kabupaten/kota (required)
 * - user_id        : ID User WP sebagai Owner (nullable)
 * - reg_type       : Tipe registrasi (self/by_admin/generate)
 * - created_by     : User ID pembuat
 * - created_at     : Timestamp pembuatan
 * - updated_at     : Timestamp update terakhir
 *
 * Changelog:
 * 1.0.12 - 2025-11-02
 * - Changed provinsi_id from NULL to NOT NULL (required field)
 * - Changed regency_id from NULL to NOT NULL (required field)
 *
 * 1.0.3 - 2025-01-21 (Task-2165 Form Sync)
 * - Added reg_type field to track registration source
 * - reg_type enum: 'self' (user register), 'by_admin' (admin create), 'generate' (demo data)
 * - Default value: 'self'
 *
 * 1.0.2 - 2024-01-19
 * - Modified code field to varchar(13) for new format CUST-TTTTRRRR
 * - Removed unique constraint from name field
 * - Added unique constraint for name+province+regency
 *
 * 1.0.1 - 2024-01-11
 * - Added nik field with unique constraint
 * - Added npwp field with unique constraint
 *
 * 1.0.0 - 2024-01-07
 * - Initial version
 */

namespace WPCustomer\Database\Tables;

defined('ABSPATH') || exit;

class CustomersDB {
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customers';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL auto_increment,
            code varchar(10) NOT NULL,
            name varchar(100) NOT NULL,
            npwp varchar(20) NULL,
            nib varchar(20) NULL,
            status enum('inactive','active') NOT NULL DEFAULT 'inactive',
            provinsi_id bigint(20) UNSIGNED NOT NULL,
            regency_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NULL,
            reg_type enum('self','by_admin','generate') NOT NULL DEFAULT 'self',
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code),
            UNIQUE KEY nib (nib),
            UNIQUE KEY npwp (npwp),
            UNIQUE KEY name_region (name, provinsi_id, regency_id),
            KEY created_by_index (created_by)
        ) $charset_collate;";
    }
}
