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

class DemoData {
    private static $customer_ids = [];
    private static $branch_ids = [];
    private static $user_ids = [];
    private static $used_names = [];
    private static $used_emails = [];

    /**
     * Main method to load all demo data
     */
    public static function load() {
        global $wpdb;
        
        try {
            $wpdb->query('START TRANSACTION');

            self::clearTables();
            self::generateCustomers();
            self::generateBranches();
            self::generateEmployees();
            // Future data generators can be added here

            $wpdb->query('COMMIT');
            return true;

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Demo data insertion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear all existing data
     */
    private static function clearTables() {
        global $wpdb;
        // Delete in correct order (child tables first)
        $wpdb->query("DELETE FROM {$wpdb->prefix}app_customer_employees");
        $wpdb->query("DELETE FROM {$wpdb->prefix}app_branches");
        $wpdb->query("DELETE FROM {$wpdb->prefix}app_customers");
    }

    /**
     * Generate customer data
     */
    private static function generateCustomers() {
        global $wpdb;
        
        $customers = [
            ['code' => '02', 'name' => 'PT Maju Bersama'],
            ['code' => '03', 'name' => 'CV Teknologi Nusantara'],
            ['code' => '04', 'name' => 'PT Sinar Abadi'],
            ['code' => '05', 'name' => 'PT Global Teknindo'],
            ['code' => '06', 'name' => 'CV Mitra Solusi'],
            ['code' => '07', 'name' => 'PT Karya Digital'],
            ['code' => '08', 'name' => 'PT Bumi Perkasa'],
            ['code' => '09', 'name' => 'CV Cipta Kreasi'],
            ['code' => '10', 'name' => 'PT Meta Inovasi'],
            ['code' => '11', 'name' => 'PT Delta Sistem']
        ];

        foreach ($customers as $customer) {
            // Generate owner name
            $owner_name = self::generatePersonName();
            
            // Create WP user for owner
            $user_id = self::createWPUser($owner_name, 'customer');
            self::$user_ids[$customer['code']] = $user_id;

            // Insert customer data
            $customer_data = [
                'code' => $customer['code'],
                'name' => $customer['name'],
                'created_by' => 1,
                'user_id' => $user_id,
                'status' => 'active'
            ];

            $wpdb->insert($wpdb->prefix . 'app_customers', $customer_data);
            if ($wpdb->last_error) throw new \Exception($wpdb->last_error);
            
            self::$customer_ids[] = $wpdb->insert_id;
        }
    }

    /**
     * Generate branch data
     */
    private static function generateBranches() {
        global $wpdb;
        
        $branch_types = ['kabupaten', 'kota'];
        
        foreach (self::$customer_ids as $index => $customer_id) {
            $customer_code = str_pad($index + 2, 2, '0', STR_PAD_LEFT);
            
            // Each customer gets 3 branches
            for ($i = 1; $i <= 3; $i++) {
                $city = self::generateCityName();
                $branch_data = [
                    'customer_id' => $customer_id,
                    'code' => $customer_code . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'name' => "Cabang " . $city . " " . $i,
                    'type' => $branch_types[array_rand($branch_types)],
                    'address' => 'Jl. ' . $city . ' No. ' . rand(1, 100),
                    'phone' => '08' . rand(100000000, 999999999),
                    'email' => strtolower(str_replace(' ', '', $city)) . $i . '@example.com',
                    'created_by' => 1,
                    'status' => 'active',
                    'user_id' => self::$user_ids[$customer_code]
                ];

                $wpdb->insert($wpdb->prefix . 'app_branches', $branch_data);
                if ($wpdb->last_error) throw new \Exception($wpdb->last_error);
                
                self::$branch_ids[] = $wpdb->insert_id;
            }
        }
    }

    /**
     * Generate employee data
     */
    private static function generateEmployees() {
        global $wpdb;
        
        $positions = ['Manager', 'Supervisor', 'Staff', 'Admin', 'Coordinator'];
        $departments = ['Sales', 'Operations', 'Finance', 'IT', 'HR'];

        foreach (self::$customer_ids as $customer_id) {
            // Generate 2-3 employees per customer
            $employee_count = rand(2, 3);
            
            for ($i = 1; $i <= $employee_count; $i++) {
                // Get random branch for this customer
                $branch_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}app_branches 
                     WHERE customer_id = %d ORDER BY RAND() LIMIT 1",
                    $customer_id
                ));

                // Generate employee name and create matching WP user
                $employee_name = self::generatePersonName();
                $user_id = self::createWPUser($employee_name, 'customer');

                $employee_data = [
                    'customer_id' => $customer_id,
                    'branch_id' => $branch_id,
                    'user_id' => $user_id,
                    'name' => $employee_name,
                    'position' => $positions[array_rand($positions)],
                    'department' => $departments[array_rand($departments)],
                    'email' => self::generateEmail($employee_name),
                    'phone' => '08' . rand(100000000, 999999999),
                    'created_by' => 1,
                    'status' => 'active'
                ];

                $wpdb->insert($wpdb->prefix . 'app_customer_employees', $employee_data);
                if ($wpdb->last_error) throw new \Exception($wpdb->last_error);
            }
        }
    }

    /**
     * Create WordPress user
     */
    private static function createWPUser($display_name, $role = 'customer') {
        // Create username from display_name (lowercase, no space)
        $username = strtolower(str_replace(' ', '_', $display_name));
        
        // Ensure unique username
        $suffix = 1;
        $base_username = $username;
        while (username_exists($username)) {
            $username = $base_username . $suffix;
            $suffix++;
        }

        // Create user with matching email
        $user_id = wp_create_user(
            $username,
            'Demo_Data-2025',
            $username . '@example.com'
        );
        
        if (is_wp_error($user_id)) {
            throw new \Exception('Failed to create WP user: ' . $user_id->get_error_message());
        }

        // Update display name to match real name
        wp_update_user([
            'ID' => $user_id,
            'display_name' => $display_name
        ]);

        // Set role
        $user = new \WP_User($user_id);
        $user->remove_role('subscriber');
        $user->add_role($role);

        error_log("Created user {$username} with display name {$display_name}");
        return $user_id;
    }

    /**
     * Generate email from name
     */
    private static function generateEmail($name) {
        $baseEmail = strtolower(str_replace(' ', '.', $name));
        $email = $baseEmail . '@example.com';
        
        // If email already exists, add a number
        $counter = 1;
        while (in_array($email, self::$used_emails)) {
            $email = $baseEmail . $counter . '@example.com';
            $counter++;
        }
        
        self::$used_emails[] = $email;
        return $email;
    }

    /**
     * Generate random Indonesian city name
     */
    private static function generateCityName() {
        $cities = [
            'Jakarta', 'Surabaya', 'Bandung', 'Medan', 'Semarang',
            'Makassar', 'Palembang', 'Tangerang', 'Depok', 'Bekasi',
            'Malang', 'Bogor', 'Yogyakarta', 'Solo', 'Manado'
        ];
        return $cities[array_rand($cities)];
    }

    /**
     * Generate random Indonesian person name
     */
    private static function generatePersonName() {
        $firstNames = [
            'Budi', 'Siti', 'Andi', 'Dewi', 'Rudi',
            'Nina', 'Joko', 'Rita', 'Doni', 'Sari',
            'Agus', 'Lina', 'Hadi', 'Maya', 'Eko',
            'Tono', 'Wati', 'Bambang', 'Sri', 'Dedi',
            'Rina', 'Hendra', 'Yanti', 'Firman', 'Lia',
            'Dian', 'Reza', 'Susi', 'Adi', 'Nita'
        ];
        $lastNames = [
            'Susanto', 'Wijaya', 'Kusuma', 'Pratama', 'Sanjaya',
            'Hidayat', 'Nugraha', 'Putra', 'Santoso', 'Wibowo',
            'Saputra', 'Permana', 'Utama', 'Suryadi', 'Gunawan',
            'Setiawan', 'Irawan', 'Perdana', 'Atmaja', 'Kusuma',
            'Winata', 'Fitriani', 'Hartono', 'Pranoto', 'Sugiarto'
        ];

        // Generate unique name
        $maxAttempts = 50; // Prevent infinite loop
        $attempts = 0;
        
        do {
            $name = $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
            $attempts++;
            
            if ($attempts >= $maxAttempts) {
                throw new \Exception('Could not generate unique name after ' . $maxAttempts . ' attempts');
            }
        } while (in_array($name, self::$used_names));
        
        self::$used_names[] = $name;
        return $name;
    }
}
