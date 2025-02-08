<?php
/**
 * Database Installer
 *
 * @package     WP_Customer
 * @subpackage  Database
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Installer.php
 *
 * Description: Mengelola instalasi database plugin.
 *              Handles table creation dengan foreign keys.
 *              Menggunakan transaction untuk data consistency.
 *              Includes demo data installation.
 *
 * Tables Created:
 * - app_customers
 * - app_branches
 * - app_customer_employees
 * - app_customer_membership_levels
 *
 * Foreign Keys:
 * - fk_branch_customer
 * - fk_employee_customer
 * - fk_employee_branch
 *
 * Changelog:
 * 1.0.0 - 2024-01-07
 * - Initial version
 * - Added table creation
 * - Added foreign key management
 * - Added demo data installation
 */

namespace WPCustomer\Database;

defined('ABSPATH') || exit;

class Installer {
    private static $tables = [
        'app_customers',
        'app_customer_membership_levels',
        'app_customer_memberships',
        'app_branches',
        'app_customer_employees'
    ];

    private static function debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Installer] " . $message);
        }
    }

    private static function verify_tables() {
        global $wpdb;
        foreach (self::$tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            ));
            if (!$table_exists) {
                self::debug("Table not found: {$table_name}");
                throw new \Exception("Failed to create table: {$table_name}");
            }
            self::debug("Verified table exists: {$table_name}");
        }
    }
    
    public static function run() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        global $wpdb;
        
        try {
            $wpdb->query('START TRANSACTION');
            self::debug("Starting database installation...");

            // Create base tables first
            self::debug("Creating customers table...");
            dbDelta(Tables\CustomersDB::get_schema());

            self::debug("Creating membership levels table...");
            dbDelta(Tables\CustomerMembershipLevelsDB::get_schema());

            self::debug("Creating memberships table...");
            dbDelta(Tables\CustomerMembershipsDB::get_schema());

            self::debug("Creating branches table...");
            dbDelta(Tables\BranchesDB::get_schema());

            self::debug("Creating employees table...");
            dbDelta(Tables\CustomerEmployeesDB::get_schema());

            // Verify all tables were created
            self::verify_tables();

            // Insert default data
            self::debug("Inserting default membership levels...");
            Tables\CustomerMembershipLevelsDB::insert_defaults();

            self::debug("Database installation completed successfully.");
            $wpdb->query('COMMIT');
            return true;

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            self::debug('Database installation failed: ' . $e->getMessage());
            return false;
        }
    }
}
