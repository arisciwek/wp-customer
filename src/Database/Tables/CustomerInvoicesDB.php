<?php
/**
 * Customer Invoices Table Schema
 *
 * @package     WP_Customer
 * @subpackage  Database/Tables
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Tables/CustomerInvoicesDB.php
 *
 * Description: Mendefinisikan struktur tabel untuk customer invoices.
 *              Table prefix yang digunakan adalah 'app_'.
 *              Menyimpan data invoice untuk pembayaran membership dan layanan lainnya.
 *
 * Fields:
 * - id             : Primary key
 * - customer_id    : ID customer
 * - branch_id      : ID branch (nullable, for branch-specific invoices)
 * - membership_id  : ID membership terkait (nullable)
 * - level_id       : ID membership level terkait (nullable)
 * - invoice_type   : Jenis invoice (membership_upgrade, renewal, other)
 * - invoice_number : Nomor invoice unik
 * - amount         : Jumlah yang harus dibayar
 * - status         : Status invoice (pending, paid, overdue, cancelled)
 * - due_date       : Tanggal jatuh tempo
 * - paid_date      : Tanggal pembayaran (nullable)
 * - description    : Deskripsi invoice
 * - created_by     : User ID pembuat
 * - created_at     : Timestamp pembuatan
 * - updated_at     : Timestamp update terakhir
 *
 * Dependencies:
 * - app_customers table
 * - app_customer_branches table
 * - app_customer_memberships table
 * - app_customer_membership_levels table
 *
 * Changelog:
 * 1.1.0 - 2025-01-10
 * - Added membership_id field for membership link
 * - Added level_id field for level link
 * - Added invoice_type field for invoice categorization
 * - Added indexes for new fields
 *
 * 1.0.0 - 2024-10-07
 * - Initial version
 */

namespace WPCustomer\Database\Tables;

defined('ABSPATH') || exit;

class CustomerInvoicesDB {
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customer_invoices';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL auto_increment,
            customer_id bigint(20) UNSIGNED NOT NULL,
            branch_id bigint(20) UNSIGNED NULL,
            membership_id bigint(20) UNSIGNED NULL,
            level_id bigint(20) UNSIGNED NULL,
            invoice_type enum('membership_upgrade','renewal','other') NOT NULL DEFAULT 'other',
            invoice_number varchar(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            status enum('pending','paid','overdue','cancelled') NOT NULL DEFAULT 'pending',
            due_date datetime NOT NULL,
            paid_date datetime NULL,
            description text NULL,
            created_by bigint(20) UNSIGNED NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY invoice_number (invoice_number),
            KEY customer_id (customer_id),
            KEY branch_id (branch_id),
            KEY membership_id (membership_id),
            KEY level_id (level_id),
            KEY invoice_type (invoice_type),
            KEY status (status),
            KEY due_date (due_date)
        ) $charset_collate;";
    }
}
