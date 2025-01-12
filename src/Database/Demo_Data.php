<?php
/**
 * Demo Data Generator
 *
 * @package     WP_Customer
 * @subpackage  Database
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Demo_Data.php
 *
 * Description: Menyediakan data demo untuk testing.
 *              Includes generator untuk customers, branches, dan employees.
 *              Menggunakan transaction untuk data consistency.
 *              Generates realistic Indonesian names dan alamat.
 *
 * Generated Data:
 * - 10 Customer records with unique codes
 * - 30 Branch records (3 per customer)
 * - 20 Employee records with departments
 *
 * Changelog:
 * 1.0.0 - 2024-01-07
 * - Initial version
 * - Added customer demo data
 * - Added branch demo data
 * - Added employee demo data
 */

namespace WPCustomer\Database;

defined('ABSPATH') || exit;

class Demo_Data {
    private static function clear_tables() {
        global $wpdb;
        
        // Delete in correct order (child tables first)
        $wpdb->query("DELETE FROM {$wpdb->prefix}app_customer_employees");
        $wpdb->query("DELETE FROM {$wpdb->prefix}app_branches");
        $wpdb->query("DELETE FROM {$wpdb->prefix}app_customers");
    }

    public static function load() {
        global $wpdb;

        try {
            // Start transaction
            $wpdb->query('START TRANSACTION');

            // Clear existing data first
            self::clear_tables();

            // Demo customers (10 records)
            $customers = [
                ['code' => '02', 'name' => 'PT Maju Bersama', 'created_by' => 1],
                ['code' => '03', 'name' => 'CV Teknologi Nusantara', 'created_by' => 1],
                ['code' => '04', 'name' => 'PT Sinar Abadi', 'created_by' => 1],
                ['code' => '05', 'name' => 'PT Global Teknindo', 'created_by' => 1],
                ['code' => '06', 'name' => 'CV Mitra Solusi', 'created_by' => 1],
                ['code' => '07', 'name' => 'PT Karya Digital', 'created_by' => 1],
                ['code' => '08', 'name' => 'PT Bumi Perkasa', 'created_by' => 1],
                ['code' => '09', 'name' => 'CV Cipta Kreasi', 'created_by' => 1],
                ['code' => '10', 'name' => 'PT Meta Inovasi', 'created_by' => 1],
                ['code' => '11', 'name' => 'PT Delta Sistem', 'created_by' => 1]
            ];

            $customer_ids = [];
            $user_ids = []; // Array untuk menyimpan user_id

            foreach ($customers as $customer) {
                // Create WP user for each customer with 'customer' role
                $user_id = self::create_wp_user($customer['name'], 'customer'); 
                $user_ids[$customer['code']] = $user_id;

                // Insert customer data with user_id
                $customer_data = [
                    'code' => $customer['code'],
                    'name' => $customer['name'],
                    'created_by' => $customer['created_by'],
                    'user_id' => $user_id
                ];
                $wpdb->insert($wpdb->prefix . 'app_customers', $customer_data);
                if ($wpdb->last_error) throw new \Exception($wpdb->last_error);
                $customer_ids[] = $wpdb->insert_id;
            }

            // Demo branches (30 records)
            $branch_types = ['kabupaten', 'kota'];
            $branch_data = [];
            
            foreach ($customer_ids as $index => $customer_id) {
                // Each customer gets 3 branches
                $customer_code = str_pad($customer_id, 2, '0', STR_PAD_LEFT);
                for ($i = 1; $i <= 3; $i++) {
                    $city = self::generateCityName();
                    $branch_data[] = [
                        'customer_id' => $customer_id,
                        'code' => $customer_code . str_pad($i, 2, '0', STR_PAD_LEFT),
                        'name' => "Cabang " . $city . " " . $i,
                        'type' => $branch_types[array_rand($branch_types)],
                        'address' => 'Jl. ' . $city . ' No. ' . rand(1, 100),
                        'phone' => '08' . rand(100000000, 999999999),
                        'email' => strtolower(str_replace(' ', '', $city)) . $i . '@example.com',
                        'created_by' => 1,
                        'status' => 'active',
                        'user_id' => $user_ids[$customers[$index]['code']]
                    ];
                }
            }

            foreach ($branch_data as $branch) {
                $wpdb->insert($wpdb->prefix . 'app_branches', $branch);
                if ($wpdb->last_error) throw new \Exception($wpdb->last_error);
            }

            // Demo employees (20 records)
            $positions = ['Manager', 'Supervisor', 'Staff', 'Admin', 'Coordinator'];
            $departments = ['Sales', 'Operations', 'Finance', 'IT', 'HR'];
            $employee_data = [];

            for ($i = 1; $i <= 20; $i++) {
                $random_customer = $customer_ids[array_rand($customer_ids)];
                $branch_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}app_branches WHERE customer_id = %d ORDER BY RAND() LIMIT 1",
                    $random_customer
                ));

                // Create WP user for each employee with 'customer' role
                $user_id = self::create_wp_user("Employee {$i}", 'customer');

                $employee_data[] = [
                    'customer_id' => $random_customer,
                    'branch_id' => $branch_id,
                    'name' => self::generatePersonName(),
                    'position' => $positions[array_rand($positions)],
                    'department' => $departments[array_rand($departments)],
                    'email' => "employee{$i}@example.com",
                    'phone' => '08' . rand(100000000, 999999999),
                    'created_by' => 1,
                    'status' => 'active',
                    'user_id' => $user_id
                ];
            }

            foreach ($employee_data as $employee) {
                $wpdb->insert($wpdb->prefix . 'app_customer_employees', $employee);
                if ($wpdb->last_error) throw new \Exception($wpdb->last_error);
            }

            $wpdb->query('COMMIT');
            return true;

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Demo data insertion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create WP user with specified role
     * 
     * @param string $username Username for the new user
     * @param string $role Role to assign ('customer' or 'surveyor')
     * @return int User ID
     * @throws \Exception on failure
     */
    private static function create_wp_user($username, $role = 'customer') {
        $sanitized_username = sanitize_user($username);
        $email = sanitize_email($sanitized_username . '@example.com');
        $password = 'Demo_Data-2025';

        // Create user
        $user_id = wp_create_user($sanitized_username, $password, $email);
        
        if (is_wp_error($user_id)) {
            throw new \Exception('Failed to create WP user: ' . $user_id->get_error_message());
        }

        // Get user object
        $user = new \WP_User($user_id);

        // Remove default role
        $user->remove_role('subscriber');

        // Add specified role
        $user->add_role($role);

        // Log success
        error_log("Created user {$sanitized_username} with role {$role}");

        return $user_id;
    }

    private static function generateCityName() {
        $cities = [
            'Jakarta', 'Surabaya', 'Bandung', 'Medan', 'Semarang',
            'Makassar', 'Palembang', 'Tangerang', 'Depok', 'Bekasi',
            'Malang', 'Bogor', 'Yogyakarta', 'Solo', 'Manado'
        ];
        return $cities[array_rand($cities)];
    }

    private static function generatePersonName() {
        $firstNames = [
            'Budi', 'Siti', 'Andi', 'Dewi', 'Rudi',
            'Nina', 'Joko', 'Rita', 'Doni', 'Sari',
            'Agus', 'Lina', 'Hadi', 'Maya', 'Eko'
        ];
        $lastNames = [
            'Susanto', 'Wijaya', 'Kusuma', 'Pratama', 'Sanjaya',
            'Hidayat', 'Nugraha', 'Putra', 'Dewi', 'Santoso',
            'Wibowo', 'Saputra', 'Permana', 'Utama', 'Suryadi'
        ];
        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }
}
