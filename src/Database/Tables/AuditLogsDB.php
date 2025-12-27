<?php
/**
 * Customer Audit Logs Table Schema
 *
 * @package     WP_Customer
 * @subpackage  Database/Tables
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Tables/AuditLogsDB.php
 *
 * Description: Mendefinisikan struktur tabel audit logs untuk wp-customer plugin.
 *              Table prefix yang digunakan adalah 'app_customer_'.
 *              Polymorphic design untuk track semua customer entities.
 *              Menyimpan perubahan dalam format JSON (efficient storage).
 *
 * Fields:
 * - id                : Primary key
 * - auditable_type    : Entity type (customer, branch, employee, etc)
 * - auditable_id      : Entity ID
 * - event             : Event type (created, updated, deleted, restored)
 * - old_values        : JSON - field yang berubah (nilai lama)
 * - new_values        : JSON - field yang berubah (nilai baru)
 * - user_id           : User yang melakukan aksi
 * - ip_address        : IP address user
 * - user_agent        : Browser/client user agent
 * - created_at        : Timestamp event
 *
 * Scope:
 * Hanya untuk entities di wp-customer plugin:
 * - customer, branch, customer_employee
 * - customer_membership, customer_invoice, customer_payment
 *
 * Design Philosophy:
 * - Polymorphic: 1 tabel untuk semua customer entities
 * - Efficient: Hanya simpan field yang BERUBAH (bukan full row)
 * - Queryable: Index untuk fast lookup per entity, user, event, date
 * - Permanent: No soft-delete, audit log adalah historical record
 *
 * Usage Examples:
 *
 * CREATE Event:
 * {
 *   "event": "created",
 *   "old_values": null,
 *   "new_values": {"code": "CUST-001", "name": "PT ABC", "status": "active"}
 * }
 *
 * UPDATE Event (only changed fields):
 * {
 *   "event": "updated",
 *   "old_values": {"name": "PT ABC", "status": "active"},
 *   "new_values": {"name": "PT ABC Sejahtera", "status": "inactive"}
 * }
 *
 * DELETE Event:
 * {
 *   "event": "deleted",
 *   "old_values": {"code": "CUST-001", "name": "PT ABC Sejahtera"},
 *   "new_values": null
 * }
 *
 * Changelog:
 * 1.0.0 - 2025-12-28
 * - Initial version
 * - Polymorphic design for wp-customer entities only
 * - JSON storage for changed fields only
 * - IP address and user agent tracking
 * - Optimized indexes for common queries
 */

namespace WPCustomer\Database\Tables;

defined('ABSPATH') || exit;

class AuditLogsDB {
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customer_audit_logs';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL auto_increment,
            auditable_type varchar(50) NOT NULL COMMENT 'Entity type: customer, branch, employee, etc',
            auditable_id bigint(20) UNSIGNED NOT NULL COMMENT 'Entity ID',
            event enum('created','updated','deleted','restored') NOT NULL COMMENT 'Event type',
            old_values longtext NULL COMMENT 'JSON: Fields that changed (old values)',
            new_values longtext NULL COMMENT 'JSON: Fields that changed (new values)',
            user_id bigint(20) UNSIGNED NOT NULL COMMENT 'User who performed the action',
            ip_address varchar(45) NULL COMMENT 'IPv4 or IPv6 address',
            user_agent varchar(255) NULL COMMENT 'Browser/client user agent',
            created_at datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Event timestamp',
            PRIMARY KEY  (id),
            KEY auditable_index (auditable_type, auditable_id),
            KEY user_index (user_id),
            KEY event_index (event),
            KEY created_at_index (created_at)
        ) $charset_collate;";
    }

    /**
     * Add foreign key constraints yang tidak didukung oleh dbDelta
     * Harus dipanggil setelah tabel dibuat
     *
     * Note: user_id tidak ada FK karena bisa reference ke:
     * - wp_users (WordPress core users)
     * - app_customer_employees (customer employees)
     * Audit log harus tetap ada bahkan jika entity dihapus (historical record)
     */
    public static function add_foreign_keys() {
        // No foreign keys untuk audit log karena:
        // 1. auditable_type/id bersifat polymorphic (bisa ke berbagai table)
        // 2. user_id bisa reference ke berbagai user tables
        // 3. Audit log harus tetap ada bahkan jika entity dihapus (historical record)

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[AuditLogsDB] No foreign keys needed for polymorphic audit table");
        }
    }

    /**
     * Get supported entity types untuk wp-customer plugin
     *
     * @return array List of valid auditable_type values
     */
    public static function get_entity_types(): array {
        return [
            'customer',
            'branch',
            'customer_employee',
            'customer_membership',
            'customer_invoice',
            'customer_payment',
        ];
    }

    /**
     * Get supported event types
     *
     * @return array List of valid event values
     */
    public static function get_event_types(): array {
        return [
            'created',
            'updated',
            'deleted',
            'restored'
        ];
    }
}
