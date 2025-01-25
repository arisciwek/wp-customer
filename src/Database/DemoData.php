<?php
/**
 * Demo Data Generator
 *
 * @package     WP_Customer
 * @subpackage  Database
 * @version     1.0.1
 * @author      arisciwek
 *
 * Description: Menyediakan data demo untuk testing.
 *              Includes generator untuk customers, branches, dan employees.
 *              Menggunakan transaction untuk data consistency.
 *              Generates realistic Indonesian names dan alamat.
 */

namespace WPCustomer\Database;

use WPCustomer\Models\CustomerModel;
use WPCustomer\Models\Branch\BranchModel;

defined('ABSPATH') || exit;

class DemoData {
    private static $customer_ids = [];
    private static $branch_ids = [];
    private static $user_ids = [];
    private static $used_names = [];
    private static $used_emails = [];
    private static $used_npwp = [];
    private static $used_nib = [];
    private static $customerModel;
    private static $branchModel;

    /**
     * Main method to load all demo data
     */
    public static function load() {
        global $wpdb;
        
        try {
            $wpdb->query('START TRANSACTION');

            // Initialize models
            self::$customerModel = new CustomerModel();
            self::$branchModel = new BranchModel();

            self::clearTables();
            self::generateCustomers();
            self::generateBranches();
            self::generateEmployees();

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

    private static function generateNPWP() {
        do {
            // Format: XX.XXX.XXX.X-XXX.XXX (15 digits + formatting)
            $npwp = sprintf("%02d.%03d.%03d.%d-%03d.%03d",
                rand(0, 99),
                rand(0, 999),
                rand(0, 999),
                rand(0, 9),
                rand(0, 999),
                rand(0, 999)
            );
        } while (in_array($npwp, self::$used_npwp));
        
        self::$used_npwp[] = $npwp;
        return $npwp;
    }

    private static function generateNIB() {
        do {
            // Format: 13 digits
            $nib = sprintf("%013d", rand(1000000000000, 9999999999999));
        } while (in_array($nib, self::$used_nib));
        
        self::$used_nib[] = $nib;
        return $nib;
    }

    /**
     * Generate customer data
     */
    private static function generateCustomers() {
        $customers = [
            ['name' => 'PT Maju Bersama'],
            ['name' => 'CV Teknologi Nusantara'],
            ['name' => 'PT Sinar Abadi'],
            ['name' => 'PT Global Teknindo'],
            ['name' => 'CV Mitra Solusi'],
            ['name' => 'PT Karya Digital'],
            ['name' => 'PT Bumi Perkasa'],
            ['name' => 'CV Cipta Kreasi'],
            ['name' => 'PT Meta Inovasi'],
            ['name' => 'PT Delta Sistem']
        ];

        foreach ($customers as $customer) {
            // Generate owner name
            $owner_name = self::generatePersonName();
            
            // Create WP user for owner
            $user_id = self::createWPUser($owner_name, 'customer');

            // Create customer data using model
            $customer_data = [
                'name' => $customer['name'],
                'npwp' => self::generateNPWP(),
                'nib' => self::generateNIB(),
                'created_by' => 1,
                'user_id' => $user_id,
                'status' => 'active'
            ];

            // Use model to create customer (which will generate code automatically)
            $customer_id = self::$customerModel->create($customer_data);
            if (!$customer_id) {
                throw new \Exception('Failed to create customer: ' . $customer['name']);
            }

            self::$customer_ids[] = $customer_id;
            self::$user_ids[$customer_id] = $user_id;
        }
    }

    /**
     * Generate branch data
     */
    private static function generateBranches() {
        $branch_types = ['cabang', 'pusat'];
        $postal_codes = ['12760', '13210', '14350', '15310', '16110'];
        $provinsi = [
            ['id' => 31, 'name' => 'DKI Jakarta'],
            ['id' => 32, 'name' => 'Jawa Barat'],
            ['id' => 33, 'name' => 'Jawa Tengah'],
            ['id' => 34, 'name' => 'DI Yogyakarta'],
            ['id' => 35, 'name' => 'Jawa Timur']
        ];
        
        $kota = [
            31 => [
                ['id' => 3171, 'name' => 'Jakarta Pusat'],
                ['id' => 3172, 'name' => 'Jakarta Utara'],
                ['id' => 3173, 'name' => 'Jakarta Barat'],
                ['id' => 3174, 'name' => 'Jakarta Selatan'],
                ['id' => 3175, 'name' => 'Jakarta Timur']
            ],
            32 => [
                ['id' => 3271, 'name' => 'Bogor'],
                ['id' => 3272, 'name' => 'Sukabumi'],
                ['id' => 3273, 'name' => 'Bandung'],
                ['id' => 3274, 'name' => 'Cirebon'],
                ['id' => 3275, 'name' => 'Bekasi']
            ]
        ];

        $domains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com'];
        
        foreach (self::$customer_ids as $customer_id) {
            // Each customer gets 2-3 branches
            $branch_count = rand(2, 3);
            
            // First branch is always pusat
            $selected_provinsi = $provinsi[array_rand($provinsi)];
            $selected_kota = isset($kota[$selected_provinsi['id']]) ? 
                $kota[$selected_provinsi['id']][array_rand($kota[$selected_provinsi['id']])] : 
                ['id' => null, 'name' => 'Unknown'];

            // Generate kantor pusat first
            $pusat_data = [
                'customer_id' => $customer_id,
                'name' => "Kantor Pusat " . $selected_kota['name'],
                'type' => 'pusat',
                'nitku' => sprintf("%013d", rand(1000000000000, 9999999999999)),
                'postal_code' => $postal_codes[array_rand($postal_codes)],
                'latitude' => rand(-6000000, -5000000) / 100000,
                'longitude' => rand(106000000, 107000000) / 100000,
                'address' => sprintf(
                    'Jl. %s No. %d, %s, %s', 
                    self::generateStreetName(),
                    rand(1, 200),
                    $selected_kota['name'],
                    $selected_provinsi['name']
                ),
                'phone' => sprintf(
                    '%s%s', 
                    rand(0, 1) ? '021-' : '022-',
                    rand(1000000, 9999999)
                ),
                'email' => sprintf(
                    'pusat.%s@%s',
                    strtolower(str_replace([' ', '.'], '', $selected_kota['name'])),
                    $domains[array_rand($domains)]
                ),
                'provinsi_id' => $selected_provinsi['id'],
                'regency_id' => $selected_kota['id'],
                'user_id' => self::$user_ids[$customer_id],
                'created_by' => self::$user_ids[$customer_id],
                'status' => 'active'
            ];

            $pusat_id = self::$branchModel->create($pusat_data);
            if (!$pusat_id) {
                throw new \Exception('Failed to create pusat for customer: ' . $customer_id);
            }
            self::$branch_ids[] = $pusat_id;

            // Generate remaining branches
            for ($i = 1; $i < $branch_count; $i++) {
                $selected_provinsi = $provinsi[array_rand($provinsi)];
                $selected_kota = isset($kota[$selected_provinsi['id']]) ? 
                    $kota[$selected_provinsi['id']][array_rand($kota[$selected_provinsi['id']])] : 
                    ['id' => null, 'name' => 'Unknown'];

                $branch_data = [
                    'customer_id' => $customer_id,
                    'name' => sprintf(
                        "Cabang %s %d",
                        $selected_kota['name'],
                        $i
                    ),
                    'type' => 'cabang',
                    'nitku' => sprintf("%013d", rand(1000000000000, 9999999999999)),
                    'postal_code' => $postal_codes[array_rand($postal_codes)],
                    'latitude' => rand(-6000000, -5000000) / 100000,
                    'longitude' => rand(106000000, 107000000) / 100000,
                    'address' => sprintf(
                        'Jl. %s No. %d RT %02d/RW %02d, %s, %s', 
                        self::generateStreetName(),
                        rand(1, 200),
                        rand(1, 12),
                        rand(1, 8),
                        $selected_kota['name'],
                        $selected_provinsi['name']
                    ),
                    'phone' => sprintf(
                        '%s%s', 
                        rand(0, 1) ? '021-' : '022-',
                        rand(1000000, 9999999)
                    ),
                    'email' => sprintf(
                        'cabang.%s%d@%s',
                        strtolower(str_replace([' ', '.'], '', $selected_kota['name'])),
                        $i,
                        $domains[array_rand($domains)]
                    ),
                    'provinsi_id' => $selected_provinsi['id'],
                    'regency_id' => $selected_kota['id'],
                    'user_id' => self::$user_ids[$customer_id],
                    'created_by' => self::$user_ids[$customer_id],
                    'status' => 'active'
                ];

                $branch_id = self::$branchModel->create($branch_data);
                if (!$branch_id) {
                    throw new \Exception('Failed to create branch for customer: ' . $customer_id);
                }
                self::$branch_ids[] = $branch_id;
            }
        }
    }

    private static function generateStreetName() {
        $prefixes = ['Jend.', 'Letjen.', 'Dr.', 'Ir.', 'Prof.'];
        $names = [
            'Sudirman', 'Thamrin', 'Gatot Subroto', 'Rasuna Said', 'Kuningan',
            'Asia Afrika', 'Diponegoro', 'Ahmad Yani', 'Imam Bonjol', 'Veteran',
            'Pemuda', 'Merdeka', 'Hayam Wuruk', 'Gajah Mada', 'Wahid Hasyim'
        ];
        $types = ['Raya', 'Besar', ''];
        
        return sprintf(
            '%s %s %s',
            $prefixes[array_rand($prefixes)],
            $names[array_rand($names)],
            $types[array_rand($types)]
        );
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
