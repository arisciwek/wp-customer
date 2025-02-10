<?php
namespace WPCustomer\Database;

defined('ABSPATH') || exit;

class Installer {
    // Complete list of tables to install, in dependency order
    private static $tables = [
        'app_customers',
        'app_customer_membership_levels',
        'app_customer_membership_features', // Added features table
        'app_customer_memberships',
        'app_branches',
        'app_customer_employees'
    ];

    // Table class mappings for easier maintenance
    private static $table_classes = [
        'app_customers' => Tables\CustomersDB::class,
        'app_customer_membership_levels' => Tables\CustomerMembershipLevelsDB::class,
        'app_customer_membership_features' => Tables\CustomerMembershipFeaturesDB::class,
        'app_customer_memberships' => Tables\CustomerMembershipsDB::class,
        'app_branches' => Tables\BranchesDB::class,
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

    private static function insert_default_data() {
        self::debug("Starting default data insertion...");

        // Insert membership features first
        self::debug("Inserting default membership features...");
        Tables\CustomerMembershipFeaturesDB::insert_defaults();

        // Then insert membership levels
        self::debug("Inserting default membership levels...");
        Tables\CustomerMembershipLevelsDB::insert_defaults();

        self::debug("Default data insertion completed.");
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

            // Verify all tables were created
            self::verify_tables();

            // Insert default data in proper order
            self::insert_default_data();

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
