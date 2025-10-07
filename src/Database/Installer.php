<?php
namespace WPCustomer\Database;

defined('ABSPATH') || exit;

class Installer {
    // Complete list of tables to install, in dependency order
    private static $tables = [
        'app_customers',
        'app_customer_branches',
        'app_customer_membership_features', // Added features table
        'app_customer_membership_levels',
        'app_customer_memberships',
        'app_customer_invoices',
        'app_customer_employees'
    ];

    // Table class mappings for easier maintenance
    private static $table_classes = [
        'app_customers' => Tables\CustomersDB::class,
        'app_customer_membership_levels' => Tables\CustomerMembershipLevelsDB::class,
        'app_customer_membership_features' => Tables\CustomerMembershipFeaturesDB::class,
        'app_customer_memberships' => Tables\CustomerMembershipsDB::class,
        'app_customer_invoices' => Tables\CustomerInvoicesDB::class,
        'app_customer_branches' => Tables\BranchesDB::class,
        'app_customer_employees' => Tables\CustomerEmployeesDB::class
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

    /**
     * Installs or updates the database tables
     */
    public static function run() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        global $wpdb;

        try {
            $wpdb->query('START TRANSACTION');
            self::debug("Starting database installation...");

            // Create tables in proper order
            foreach (self::$tables as $table) {
                $class = self::$table_classes[$table];
                self::debug("Creating {$table} table using {$class}...");
                dbDelta($class::get_schema());
            }

            // Run migrations for existing installations
            self::runMigrations();

            // Verify all tables were created
            self::verify_tables();

            self::debug("Database installation completed successfully.");
            $wpdb->query('COMMIT');
            return true;

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            self::debug('Database installation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Run database migrations for existing installations
     */
    private static function runMigrations() {
        global $wpdb;

        self::debug("Running migrations...");

        // Migration for adding agency_id, division_id, inspector_id to branches table
        $table = $wpdb->prefix . 'app_customer_branches';
        $columns = $wpdb->get_results("DESCRIBE {$table}");

        $has_agency_id = false;
        $has_division_id = false;
        $has_inspector_id = false;
        $code_length = 13; // default

        foreach ($columns as $column) {
            if ($column->Field === 'agency_id') $has_agency_id = true;
            if ($column->Field === 'division_id') $has_division_id = true;
            if ($column->Field === 'inspector_id') $has_inspector_id = true;
            if ($column->Field === 'code') {
                // Extract length from Type like varchar(13)
                if (preg_match('/varchar\((\d+)\)/', $column->Type, $matches)) {
                    $code_length = (int) $matches[1];
                }
            }
        }

        if (!$has_agency_id) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN agency_id bigint(20) UNSIGNED NOT NULL AFTER provinsi_id");
            self::debug("Added agency_id column to branches table");
        }

        if (!$has_division_id) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN division_id bigint(20) UNSIGNED NULL AFTER regency_id");
            self::debug("Added division_id column to branches table");
        }

        if (!$has_inspector_id) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN inspector_id bigint(20) UNSIGNED NULL AFTER user_id");
            self::debug("Added inspector_id column to branches table");
        }

        if ($code_length < 20) {
            $wpdb->query("ALTER TABLE {$table} MODIFY COLUMN code varchar(20) NOT NULL");
            self::debug("Modified code column to varchar(20)");
        }

        // Remove unique key for agency_id + inspector_id if exists (inspector can manage multiple branches)
        $index_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = 'inspector_agency'",
            $wpdb->dbname, $table
        ));

        if ($index_exists) {
            $wpdb->query("ALTER TABLE {$table} DROP INDEX inspector_agency");
            self::debug("Dropped unique key inspector_agency from branches table");
        }

        // Add unique key for customer_id + regency_id if not exists (prevent multiple branches per customer in same regency)
        $customer_regency_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = 'customer_regency'",
            $wpdb->dbname, $table
        ));

        if (!$customer_regency_exists) {
            $wpdb->query("ALTER TABLE {$table} ADD UNIQUE KEY customer_regency (customer_id, regency_id)");
            self::debug("Added unique key customer_regency to branches table");
        }

        self::debug("Migrations completed");
    }
}
