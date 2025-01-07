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
                ['code' => '01', 'name' => 'PT Maju Bersama', 'created_by' => 1],
                ['code' => '02', 'name' => 'CV Teknologi Nusantara', 'created_by' => 1],
                ['code' => '03', 'name' => 'PT Sinar Abadi', 'created_by' => 1],
                ['code' => '04', 'name' => 'PT Global Teknindo', 'created_by' => 1],
                ['code' => '05', 'name' => 'CV Mitra Solusi', 'created_by' => 1],
                ['code' => '06', 'name' => 'PT Karya Digital', 'created_by' => 1],
                ['code' => '07', 'name' => 'PT Bumi Perkasa', 'created_by' => 1],
                ['code' => '08', 'name' => 'CV Cipta Kreasi', 'created_by' => 1],
                ['code' => '09', 'name' => 'PT Meta Inovasi', 'created_by' => 1],
                ['code' => '10', 'name' => 'PT Delta Sistem', 'created_by' => 1]
            ];

            $customer_ids = [];
            foreach ($customers as $customer) {
                $wpdb->insert($wpdb->prefix . 'app_customers', $customer);
                if ($wpdb->last_error) throw new \Exception($wpdb->last_error);
                $customer_ids[] = $wpdb->insert_id;
            }

            // Demo branches (30 records)
            $branch_types = ['kabupaten', 'kota'];
            $branch_data = [];
            
            foreach ($customer_ids as $customer_id) {
                // Each customer gets 3 branches
                $customer_code = str_pad($customer_id, 2, '0', STR_PAD_LEFT);
                for ($i = 1; $i <= 3; $i++) {
                    $branch_data[] = [
                        'customer_id' => $customer_id,
                        'code' => $customer_code . str_pad($i, 2, '0', STR_PAD_LEFT),
                        'name' => "Cabang " . self::generateCityName() . " " . $i,
                        'type' => $branch_types[array_rand($branch_types)],
                        'created_by' => 1
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
                // Get random branch for this customer
                $branch_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}app_branches WHERE customer_id = %d ORDER BY RAND() LIMIT 1",
                    $random_customer
                ));

                $employee_data[] = [
                    'customer_id' => $random_customer,
                    'branch_id' => $branch_id,
                    'name' => self::generatePersonName(),
                    'position' => $positions[array_rand($positions)],
                    'department' => $departments[array_rand($departments)],
                    'email' => "employee{$i}@example.com",
                    'phone' => '08' . rand(100000000, 999999999),
                    'created_by' => 1,
                    'status' => 'active'
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
