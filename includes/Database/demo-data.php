<?php
namespace CustomerManagement\Database;

defined('ABSPATH') || exit;

class Demo_Data {
    private static function clear_tables() {
        global $wpdb;
        
        // First, delete all data from customers table (child table)
        $wpdb->query("DELETE FROM {$wpdb->prefix}customers");
        
        // Then delete from other tables
        $wpdb->query("DELETE FROM {$wpdb->prefix}customer_employees");
        $wpdb->query("DELETE FROM {$wpdb->prefix}customer_branches");
    }

    public static function load() {
        global $wpdb;

        // Only load demo data if in development mode
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        try {
            // Start transaction
            $wpdb->query('START TRANSACTION');

            // Clear existing data first
            self::clear_tables();

            // Demo branches
            $branches = [
                [
                    'name' => 'Jakarta HQ',
                    'location' => 'Jakarta Pusat',
                    'created_by' => 1,
                    'status' => 'active'
                ],
                [
                    'name' => 'Bandung Branch',
                    'location' => 'Bandung',
                    'created_by' => 1,
                    'status' => 'active'
                ],
                [
                    'name' => 'Surabaya Branch',
                    'location' => 'Surabaya',
                    'created_by' => 1,
                    'status' => 'active'
                ]
            ];

            $branch_ids = [];
            foreach ($branches as $branch) {
                $wpdb->insert($wpdb->prefix . 'customer_branches', $branch);
                if ($wpdb->last_error) throw new \Exception($wpdb->last_error);
                $branch_ids[] = $wpdb->insert_id;
            }

            // Demo employees
            $employees = [
                [
                    'name' => 'John Doe',
                    'position' => 'Account Manager',
                    'department' => 'Sales',
                    'email' => 'john@example.com',
                    'phone' => '08123456789',
                    'created_by' => 1,
                    'status' => 'active'
                ],
                [
                    'name' => 'Jane Smith',
                    'position' => 'Customer Service',
                    'department' => 'Support',
                    'email' => 'jane@example.com',
                    'phone' => '08234567890',
                    'created_by' => 1,
                    'status' => 'active'
                ]
            ];

            $employee_ids = [];
            foreach ($employees as $employee) {
                $wpdb->insert($wpdb->prefix . 'customer_employees', $employee);
                if ($wpdb->last_error) throw new \Exception($wpdb->last_error);
                $employee_ids[] = $wpdb->insert_id;
            }

            // Get first membership level ID for demo data
            $first_membership = $wpdb->get_row("SELECT id FROM {$wpdb->prefix}customer_membership_levels ORDER BY id ASC LIMIT 1");
            if (!$first_membership) {
                throw new \Exception('No membership levels found');
            }
            $membership_id = $first_membership->id;

            // Demo customers
            $customers = [
                [
                    'name' => 'PT Maju Bersama',
                    'email' => 'contact@majubersama.com',
                    'phone' => '021-5551234',
                    'address' => 'Jl. Sudirman No. 123',
                    'provinsi_id' => 31,
                    'kabupaten_id' => 3173,
                    'employee_id' => $employee_ids[0],
                    'branch_id' => $branch_ids[0],
                    'membership_level_id' => $membership_id,
                    'created_by' => 1,
                    'assigned_to' => 1,
                    'status' => 'active'
                ],
                [
                    'name' => 'CV Teknologi Nusantara',
                    'email' => 'info@teknus.co.id',
                    'phone' => '022-5555678',
                    'address' => 'Jl. Pasteur No. 45',
                    'provinsi_id' => 32,
                    'kabupaten_id' => 3273,
                    'employee_id' => $employee_ids[1],
                    'branch_id' => $branch_ids[1],
                    'membership_level_id' => $membership_id,
                    'created_by' => 1,
                    'assigned_to' => 1,
                    'status' => 'active'
                ]
            ];

            foreach ($customers as $customer) {
                $wpdb->insert($wpdb->prefix . 'customers', $customer);
                if ($wpdb->last_error) throw new \Exception($wpdb->last_error);
            }

            // Commit transaction
            $wpdb->query('COMMIT');

        } catch (\Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            error_log('Demo data insertion failed: ' . $e->getMessage());
        }
    }
}
