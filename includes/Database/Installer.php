<?php
namespace CustomerManagement\Database;

defined('ABSPATH') || exit;

class Installer {
    private static $tables = [
        'customers',
        'customer_employees',
        'customer_branches',
        'customer_membership_levels'
    ];

    public static function run() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        global $wpdb;
        
        try {
            $wpdb->query('START TRANSACTION');

            // Drop existing tables
            foreach (self::$tables as $table) {
                $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
            }

            // Load table classes
            require_once WP_CUSTOMER_PATH . 'includes/Database/Tables/customer-membership-levels.php';
            require_once WP_CUSTOMER_PATH . 'includes/Database/Tables/customers.php';
            require_once WP_CUSTOMER_PATH . 'includes/Database/Tables/customer-branches.php';
            require_once WP_CUSTOMER_PATH . 'includes/Database/Tables/customer-employees.php';

            // Create tables in order
            dbDelta(Tables\Customer_Membership_Levels::get_schema());
            dbDelta(Tables\Customer_Branches::get_schema());
            dbDelta(Tables\Customer_Employees::get_schema());
            dbDelta(Tables\Customers::get_schema());

            // Verify tables were created
            foreach (self::$tables as $table) {
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$table}'");
                if (!$table_exists) {
                    throw new \Exception("Failed to create table: {$wpdb->prefix}{$table}");
                }
            }

            // Drop any existing foreign keys
            self::ensure_no_foreign_keys();
            
            // Add foreign key constraints
            self::add_foreign_keys();

            // Insert membership levels
            Tables\Customer_Membership_Levels::insert_defaults();

            // Verify membership levels were inserted
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}customer_membership_levels");
            if ($count == 0) {
                throw new \Exception("Failed to insert membership levels");
            }

            // Load demo data if in development mode
            require_once WP_CUSTOMER_PATH . 'includes/Database/demo-data.php';
            Demo_Data::load();

            $wpdb->query('COMMIT');
            return true;

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Plugin activation failed: ' . $e->getMessage());
            // Deactivate plugin
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            deactivate_plugins(plugin_basename(WP_CUSTOMER_PATH . 'wp-customer.php'));
            wp_die('Plugin activation failed: ' . $e->getMessage());
            return false;
        }
    }

    private static function ensure_no_foreign_keys() {
        global $wpdb;
        
        // Get all foreign keys for customers table
        $foreign_keys = $wpdb->get_results("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '{$wpdb->prefix}customers' 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");

        // Drop each foreign key if it exists
        foreach ($foreign_keys as $key) {
            $wpdb->query("
                ALTER TABLE {$wpdb->prefix}customers 
                DROP FOREIGN KEY {$key->CONSTRAINT_NAME}
            ");
        }

        // Drop indexes without IF EXISTS
        $indexes = [
            'fk_customer_membership',
            'fk_customer_employee',
            'fk_customer_branch'
        ];
        
        foreach ($indexes as $index) {
            // Check if index exists first
            $index_exists = $wpdb->get_var("
                SELECT COUNT(1) IndexExists 
                FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE table_schema=DATABASE() 
                AND table_name = '{$wpdb->prefix}customers' 
                AND index_name = '{$index}'
            ");
            
            if ($index_exists) {
                $wpdb->query("
                    ALTER TABLE {$wpdb->prefix}customers 
                    DROP INDEX {$index}
                ");
            }
        }
    }

    public static function add_foreign_keys() {
        global $wpdb;

        $constraints = [
            [
                'name' => 'fk_customer_membership_new',
                'sql' => "ALTER TABLE {$wpdb->prefix}customers
                ADD CONSTRAINT fk_customer_membership_new
                FOREIGN KEY (membership_level_id)
                REFERENCES {$wpdb->prefix}customer_membership_levels(id)
                ON DELETE RESTRICT"
            ],
            [
                'name' => 'fk_customer_employee_new',
                'sql' => "ALTER TABLE {$wpdb->prefix}customers
                ADD CONSTRAINT fk_customer_employee_new
                FOREIGN KEY (employee_id)
                REFERENCES {$wpdb->prefix}customer_employees(id)
                ON DELETE SET NULL"
            ],
            [
                'name' => 'fk_customer_branch_new',
                'sql' => "ALTER TABLE {$wpdb->prefix}customers
                ADD CONSTRAINT fk_customer_branch_new
                FOREIGN KEY (branch_id)
                REFERENCES {$wpdb->prefix}customer_branches(id)
                ON DELETE SET NULL"
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
