<?php
/**
 * Customer Payments Table Schema
 *
 * @package     WP_Customer
 * @subpackage  Database/Tables
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Tables/CustomerPaymentsDB.php
 *
 * Description: Mendefinisikan struktur tabel untuk customer payments.
 *              Table prefix yang digunakan adalah 'app_'.
 *              Menyimpan data pembayaran untuk membership dan invoice.
 *
 * Fields:
 * - id               : Primary key
 * - payment_id       : Unique payment ID
 * - company_id       : ID branch (company adalah alias untuk branch)
 * - customer_id      : ID customer (untuk query lebih mudah)
 * - amount           : Jumlah pembayaran
 * - payment_method   : Metode pembayaran (transfer_bank, virtual_account, kartu_kredit, e_wallet)
 * - description      : Deskripsi pembayaran
 * - metadata         : JSON data tambahan
 * - status           : Status pembayaran (pending, completed, failed, cancelled)
 * - proof_file_path  : Relative path to uploaded proof file
 * - proof_file_url   : Full URL to uploaded proof file
 * - proof_file_type  : MIME type of uploaded proof file
 * - proof_file_size  : Size of uploaded proof file in bytes
 * - created_at       : Timestamp pembuatan
 * - updated_at       : Timestamp update terakhir
 *
 * Dependencies:
 * - app_customers table
 *
 * Changelog:
 * 1.0.3 - 2025-10-18 (Task-2162 Review-01)
 * - Added customer_id column for easier querying
 * - Clarified: company_id is actually branch_id (company is alias for branch)
 * - Both customer_id and company_id (branch_id) now stored for flexible queries
 *
 * 1.0.2 - 2025-10-18 (Task-2162)
 * - Added proof_file_path, proof_file_url, proof_file_type, proof_file_size columns
 * - Support for payment proof upload functionality
 * - Files stored in: /wp-content/uploads/wp-customer/membership-invoices/{year}/{month}/
 *
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
            customer_id bigint(20) UNSIGNED NOT NULL,
            amount decimal(10,2) NOT NULL,
            payment_method enum('transfer_bank','virtual_account','kartu_kredit','e_wallet') NOT NULL,
            description text NULL,
            metadata longtext NULL,
            status enum('pending','completed','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
            proof_file_path varchar(255) NULL,
            proof_file_url varchar(500) NULL,
            proof_file_type varchar(50) NULL,
            proof_file_size int(11) NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY payment_id (payment_id),
            KEY company_id (company_id),
            KEY customer_id (customer_id),
            KEY status (status),
            KEY payment_method (payment_method)
        ) $charset_collate;";
    }

    /**
     * Add foreign key constraints yang tidak didukung oleh dbDelta
     * Harus dipanggil setelah tabel dibuat
     */
    public static function add_foreign_keys() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customer_payments';

        $constraints = [
            // FK to app_customers
            [
                'name' => 'fk_payment_customer',
                'sql' => "ALTER TABLE {$table_name}
                         ADD CONSTRAINT fk_payment_customer
                         FOREIGN KEY (customer_id)
                         REFERENCES {$wpdb->prefix}app_customers(id)
                         ON DELETE CASCADE"
            ],
            // FK to app_customer_branches (company_id is actually branch_id)
            [
                'name' => 'fk_payment_branch',
                'sql' => "ALTER TABLE {$table_name}
                         ADD CONSTRAINT fk_payment_branch
                         FOREIGN KEY (company_id)
                         REFERENCES {$wpdb->prefix}app_customer_branches(id)
                         ON DELETE CASCADE"
            ]
        ];

        foreach ($constraints as $constraint) {
            // Check if constraint already exists
            $constraint_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                 AND TABLE_NAME = %s
                 AND CONSTRAINT_NAME = %s",
                $table_name,
                $constraint['name']
            ));

            // If constraint exists, drop it first
            if ($constraint_exists > 0) {
                $wpdb->query("ALTER TABLE {$table_name} DROP FOREIGN KEY `{$constraint['name']}`");
            }

            // Add foreign key constraint
            $result = $wpdb->query($constraint['sql']);
            if ($result === false) {
                error_log("[CustomerPaymentsDB] Failed to add FK {$constraint['name']}: " . $wpdb->last_error);
            }
        }
    }
}
