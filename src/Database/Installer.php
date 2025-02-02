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
        'app_branches',
        'app_customer_employees',
        'app_customer_membership_levels'
    ];

    private static function verify_tables() {
        global $wpdb;
        foreach (self::$tables as $table) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$table}'");
            if (!$table_exists) {
                throw new \Exception("Failed to create table: {$wpdb->prefix}{$table}");
            }
        }
    }
    
    public static function run() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        global $wpdb;
        
        try {
            $wpdb->query('START TRANSACTION');

            // Database Tables
            require_once WP_CUSTOMER_PATH . 'src/Database/Tables/CustomersDB.php';
            require_once WP_CUSTOMER_PATH . 'src/Database/Tables/BranchesDB.php';
            require_once WP_CUSTOMER_PATH . 'src/Database/Tables/CustomerMembershipLevelsDB.php';
            require_once WP_CUSTOMER_PATH . 'src/Database/Tables/CustomerEmployeesDB.php';


            try {
                error_log('Starting table creation');
                dbDelta(Tables\CustomerMembershipLevelsDB::get_schema());
                error_log('Created customer_membership_levels table');
                dbDelta(Tables\CustomersDB::get_schema());
                error_log('Created customers table');
                dbDelta(Tables\BranchesDB::get_schema());
                error_log('Created branches table');
                dbDelta(Tables\CustomerEmployeesDB::get_schema());
                error_log('Created customer_employees table');
                // ...
            } catch (\Exception $e) {
                error_log('Table creation failed: ' . $e->getMessage());
                // ...
            }


            // Verify tables were created
            self::verify_tables();

            // Drop any existing foreign keys for clean slate
            self::ensure_no_foreign_keys();
            
            // Add foreign key constraints
            self::add_foreign_keys();

            // Di Installer.php
            
            $wpdb->query('COMMIT');
            return true;

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Database installation failed: ' . $e->getMessage());
            return false;
        }
    }

    private static function ensure_no_foreign_keys() {
        global $wpdb;
        
        // Tables that might have foreign keys
        $tables_with_fk = ['app_branches', 'app_customer_employees'];
        
        foreach ($tables_with_fk as $table) {
            $foreign_keys = $wpdb->get_results("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '{$wpdb->prefix}{$table}' 
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ");

            foreach ($foreign_keys as $key) {
                $wpdb->query("
                    ALTER TABLE {$wpdb->prefix}{$table} 
                    DROP FOREIGN KEY {$key->CONSTRAINT_NAME}
                ");
            }
        }
    }

    private static function add_foreign_keys() {
        global $wpdb;

        $constraints = [
            // Branches constraints
            [
                'name' => 'fk_branch_customer',
                'sql' => "ALTER TABLE {$wpdb->prefix}app_branches
                         ADD CONSTRAINT fk_branch_customer
                         FOREIGN KEY (customer_id)
                         REFERENCES {$wpdb->prefix}app_customers(id)
                         ON DELETE CASCADE"
            ],
            // Employee constraints
            [
                'name' => 'fk_employee_customer',
                'sql' => "ALTER TABLE {$wpdb->prefix}app_customer_employees
                         ADD CONSTRAINT fk_employee_customer
                         FOREIGN KEY (customer_id)
                         REFERENCES {$wpdb->prefix}app_customers(id)
                         ON DELETE CASCADE"
            ],
            [
                'name' => 'fk_employee_branch',
                'sql' => "ALTER TABLE {$wpdb->prefix}app_customer_employees
                         ADD CONSTRAINT fk_employee_branch
                         FOREIGN KEY (branch_id)
                         REFERENCES {$wpdb->prefix}app_branches(id)
                         ON DELETE CASCADE"
            ]
        ];

        foreach ($constraints as $constraint) {
            $result = $wpdb->query($constraint['sql']);
            if ($result === false) {
                throw new \Exception("Failed to add foreign key {$constraint['name']}: " . $wpdb->last_error);
            }
        }
    }
}
