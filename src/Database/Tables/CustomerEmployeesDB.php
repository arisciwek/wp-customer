<?php
/**
 * Customer Employees Table Schema
 *
 * @package     WP_Customer
 * @subpackage  Database/Tables
 * @version     1.0.10
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Tables/CustomerEmployeesDB.php
 *
 * Description: Mendefinisikan struktur tabel employees.
 *              Table prefix yang digunakan adalah 'app_'.
 *              Includes relasi dengan tabel customers.
 *              Menyediakan data karyawan customer.
 *
 * Fields:
 * - id             : Primary key
 * - customer_id    : Foreign key ke customer
 * - branch_id      : Foreign key ke branch
 * - user_id        : Foreign key ke user
 * - name           : Nama karyawan
 * - position       : Jabatan karyawan
 * - finance        : Department finance (boolean)
 * - operation      : Department operation (boolean)
 * - legal          : Department legal (boolean)
 * - purchase       : Department purchase (boolean)
 * - email          : Email karyawan (unique)
 * - phone          : Nomor telepon
 * - created_by     : User ID pembuat
 * - created_at     : Timestamp pembuatan
 * - updated_at     : Timestamp update terakhir
 * - status         : Status aktif/nonaktif
 *
 * Foreign Keys:
 * - customer_id    : REFERENCES app_customers(id) ON DELETE CASCADE
 *
 * Changelog:
 * 1.0.1 - 2024-01-27
 * - Removed department field
 * - Added boolean fields for specific departments: finance, operation, legal, purchase
 * 
 * 1.0.0 - 2024-01-07
 * - Initial version
 * - Added basic employee fields
 * - Added customer relation
 * - Added contact information fields
 */

namespace WPCustomer\Database\Tables;

defined('ABSPATH') || exit;

class CustomerEmployeesDB {
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customer_employees';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL auto_increment,
            customer_id bigint(20) UNSIGNED NOT NULL,
            branch_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            name varchar(100) NOT NULL,
            position varchar(100) NULL,
            finance boolean NOT NULL DEFAULT 0,
            operation boolean NOT NULL DEFAULT 0,
            legal boolean NOT NULL DEFAULT 0,
            purchase boolean NOT NULL DEFAULT 0,
            keterangan varchar(200) NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status enum('active','inactive') DEFAULT 'active',
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY customer_id_index (customer_id),
            KEY created_by_index (created_by)
        ) $charset_collate;";
    }
}
