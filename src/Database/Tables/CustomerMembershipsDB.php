<?php
/**
 * Customer Memberships Table Schema
 *
 * @package     WP_Customer
 * @subpackage  Database/Tables
 * @version     1.0.1
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Tables/CustomerMembershipsDB.php
 *
 * Description: Mendefinisikan struktur tabel untuk active memberships.
 *              Menyimpan data status membership aktif per customer:
 *              - Status dan periode membership
 *              - Informasi trial dan grace period
 *              - Data pembayaran dasar
 *              - Relasi ke branch
 *              Table prefix yang digunakan adalah 'app_'.
 *
 * Dependencies:
 * - WordPress $wpdb
 * - app_customer_membership_levels table
 * - app_customers table
 * - app_branches table
 *
 * Changelog:
 * 1.0.1 - 2024-02-09
 * - Added branch_id field with foreign key constraint
 * - Added branch relationship tracking
 * 
 * 1.0.0 - 2024-02-08
 * - Initial version
 * - Added membership status tracking
 * - Added period management
 * - Added basic payment tracking
 */
namespace WPCustomer\Database\Tables;

defined('ABSPATH') || exit;

class CustomerMembershipsDB {
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customer_memberships';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL auto_increment,
            customer_id bigint(20) UNSIGNED NOT NULL,
            branch_id bigint(20) UNSIGNED NOT NULL,
            level_id bigint(20) UNSIGNED NOT NULL,
            status enum('active','pending_payment','pending_upgrade','expired','in_grace_period') NOT NULL DEFAULT 'active',
            period_months int NOT NULL DEFAULT 1,
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            trial_end_date datetime NULL,
            grace_period_end_date datetime NULL,
            price_paid decimal(10,2) NOT NULL DEFAULT 0.00,
            payment_method varchar(50) NULL,
            payment_status enum('paid','pending','failed','refunded') NOT NULL DEFAULT 'pending',
            payment_date datetime NULL,
            created_by bigint(20) UNSIGNED NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY customer_id (customer_id),
            KEY branch_id (branch_id),
            KEY level_id (level_id),
            KEY status (status),
            KEY end_date (end_date),
            CONSTRAINT `fk_membership_customer` 
                FOREIGN KEY (customer_id) 
                REFERENCES `{$wpdb->prefix}app_customers` (id)
                ON DELETE CASCADE,
            CONSTRAINT `fk_membership_branch`
                FOREIGN KEY (branch_id)
                REFERENCES `{$wpdb->prefix}app_branches` (id)
                ON DELETE CASCADE,
            CONSTRAINT `fk_membership_level`
                FOREIGN KEY (level_id)
                REFERENCES `{$wpdb->prefix}app_customer_membership_levels` (id)
                ON DELETE RESTRICT
        ) $charset_collate;";
    }
}
