<?php
/**
 * Customer Invoices Table Schema
 *
 * @package     WP_Customer
 * @subpackage  Database/Tables
 * @version     1.0.11
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
 * - from_level_id  : ID level asal/saat ini (nullable, for upgrade tracking)
 * - level_id       : ID membership level tujuan (nullable)
 * - invoice_type   : Jenis invoice (membership_upgrade, renewal, other)
 * - invoice_number : Nomor invoice unik
 * - amount         : Jumlah yang harus dibayar
 * - period_months  : Periode berlangganan dalam bulan (1, 3, 6, 12)
 * - status         : Status invoice (pending, paid, pending_payment, cancelled)
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
 * 1.3.0 - 2025-10-10
 * - Added from_level_id field for upgrade tracking and analytics
 * - Added index for from_level_id
 * - Enables tracking of level changes (upgrade/renewal/downgrade)
 *
 * 1.2.0 - 2025-10-10
 * - Added period_months field for subscription period tracking
 *
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
            from_level_id bigint(20) UNSIGNED NULL,
            level_id bigint(20) UNSIGNED NULL,
            invoice_type enum('membership_upgrade','renewal','other') NOT NULL DEFAULT 'other',
            invoice_number varchar(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            period_months int(11) NOT NULL DEFAULT 1,
            status enum('pending','paid','pending_payment','cancelled') NOT NULL DEFAULT 'pending',
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
            KEY from_level_id (from_level_id),
            KEY level_id (level_id),
            KEY invoice_type (invoice_type),
            KEY status (status),
            KEY due_date (due_date)
        ) $charset_collate;";
    }

    /**
     * Add foreign key constraints yang tidak didukung oleh dbDelta
     * Harus dipanggil setelah tabel dibuat
     */
    public static function add_foreign_keys() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customer_invoices';

        $constraints = [
            // FK to app_customers
            [
                'name' => 'fk_invoice_customer',
                'sql' => "ALTER TABLE {$table_name}
                         ADD CONSTRAINT fk_invoice_customer
                         FOREIGN KEY (customer_id)
                         REFERENCES {$wpdb->prefix}app_customers(id)
                         ON DELETE CASCADE"
            ],
            // FK to app_customer_branches (nullable)
            [
                'name' => 'fk_invoice_branch',
                'sql' => "ALTER TABLE {$table_name}
                         ADD CONSTRAINT fk_invoice_branch
                         FOREIGN KEY (branch_id)
                         REFERENCES {$wpdb->prefix}app_customer_branches(id)
                         ON DELETE SET NULL"
            ],
            // FK to app_customer_memberships (nullable)
            [
                'name' => 'fk_invoice_membership',
                'sql' => "ALTER TABLE {$table_name}
                         ADD CONSTRAINT fk_invoice_membership
                         FOREIGN KEY (membership_id)
                         REFERENCES {$wpdb->prefix}app_customer_memberships(id)
                         ON DELETE SET NULL"
            ],
            // FK to app_customer_membership_levels (from_level_id, nullable)
            [
                'name' => 'fk_invoice_from_level',
                'sql' => "ALTER TABLE {$table_name}
                         ADD CONSTRAINT fk_invoice_from_level
                         FOREIGN KEY (from_level_id)
                         REFERENCES {$wpdb->prefix}app_customer_membership_levels(id)
                         ON DELETE SET NULL"
            ],
            // FK to app_customer_membership_levels (level_id, nullable)
            [
                'name' => 'fk_invoice_level',
                'sql' => "ALTER TABLE {$table_name}
                         ADD CONSTRAINT fk_invoice_level
                         FOREIGN KEY (level_id)
                         REFERENCES {$wpdb->prefix}app_customer_membership_levels(id)
                         ON DELETE SET NULL"
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
                error_log("[CustomerInvoicesDB] Failed to add FK {$constraint['name']}: " . $wpdb->last_error);
            }
        }
    }
}
