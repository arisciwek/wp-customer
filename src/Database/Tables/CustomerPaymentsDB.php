<?php
/**
 * Customer Payments Table Schema
 *
 * @package     WP_Customer
 * @subpackage  Database/Tables
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Tables/CustomerPaymentsDB.php
 *
 * Description: Mendefinisikan struktur tabel untuk customer payments.
 *              Table prefix yang digunakan adalah 'app_'.
 *              Menyimpan data pembayaran untuk membership dan invoice.
 *
 * Fields:
 * - id             : Primary key
 * - payment_id     : Unique payment ID
 * - company_id     : ID customer/company
 * - amount         : Jumlah pembayaran
 * - payment_method : Metode pembayaran (transfer_bank, virtual_account, kartu_kredit, e_wallet)
 * - description    : Deskripsi pembayaran
 * - metadata       : JSON data tambahan
 * - status         : Status pembayaran (pending, completed, failed, cancelled)
 * - created_at     : Timestamp pembuatan
 * - updated_at     : Timestamp update terakhir
 *
 * Dependencies:
 * - app_customers table
 *
 * Changelog:
 * 1.0.1 - 2025-01-17 (Review-05)
 * - Changed payment_method enum: credit_card → kartu_kredit, cash → e_wallet
 * - Matches payment modal options
 *
 * 1.0.0 - 2024-10-07
 * - Initial version
 */

namespace WPCustomer\Database\Tables;

defined('ABSPATH') || exit;

class CustomerPaymentsDB {
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customer_payments';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL auto_increment,
            payment_id varchar(50) NOT NULL,
            company_id bigint(20) UNSIGNED NOT NULL,
            amount decimal(10,2) NOT NULL,
            payment_method enum('transfer_bank','virtual_account','kartu_kredit','e_wallet') NOT NULL,
            description text NULL,
            metadata longtext NULL,
            status enum('pending','completed','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY payment_id (payment_id),
            KEY company_id (company_id),
            KEY status (status),
            KEY payment_method (payment_method)
        ) $charset_collate;";
    }
}
