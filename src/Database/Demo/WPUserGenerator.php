<?php
/**
 * WordPress User Generator for Demo Data
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Demo/WPUserGenerator.php
 *
 * Description: Handles demo user generation for WordPress.
 *              Includes functionality for:
 *              - Auto-incrementing user IDs starting from 2
 *              - Unique username generation
 *              - Role assignment
 *              - Display name management
 *              
 * Usage:
 * $user_id = WPUserGenerator::generateUser('John Doe', 'customer');
 *
 * Dependencies:
 * - WordPress User functions (wp_create_user, wp_update_user)
 * - WP_User class
 * - WordPress database ($wpdb)
 *
 * Security:
 * - Preserves admin user (ID 1)
 * - Uses standard WordPress password hashing
 * - Maintains unique usernames
 *
 * Changelog:
 * 1.0.0 - 2024-01-27
 * - Initial version
 * - Added user generation with ID tracking
 * - Added unique username handling
 * - Added role management
 */

namespace WPCustomer\Database\Demo;

defined('ABSPATH') || exit;

class WPUserGenerator {

    use CustomerDemoDataHelperTrait;

    private static $usedUsernames = [];
    
    public static $customer_users = [
        ['id' => 2, 'username' => 'budi_santoso', 'display_name' => 'Budi Santoso', 'role' => 'customer'],
        ['id' => 3, 'username' => 'dewi_kartika', 'display_name' => 'Dewi Kartika', 'role' => 'customer'],
        ['id' => 4, 'username' => 'ahmad_hidayat', 'display_name' => 'Ahmad Hidayat', 'role' => 'customer'],
        ['id' => 5, 'username' => 'siti_rahayu', 'display_name' => 'Siti Rahayu', 'role' => 'customer'],
        ['id' => 6, 'username' => 'rudi_hermawan', 'display_name' => 'Rudi Hermawan', 'role' => 'customer'],
        ['id' => 7, 'username' => 'nina_kusuma', 'display_name' => 'Nina Kusuma', 'role' => 'customer'],
        ['id' => 8, 'username' => 'eko_prasetyo', 'display_name' => 'Eko Prasetyo', 'role' => 'customer'],
        ['id' => 9, 'username' => 'maya_wijaya', 'display_name' => 'Maya Wijaya', 'role' => 'customer'],
        ['id' => 10, 'username' => 'dian_pertiwi', 'display_name' => 'Dian Pertiwi', 'role' => 'customer'],
        ['id' => 11, 'username' => 'agus_suryanto', 'display_name' => 'Agus Suryanto', 'role' => 'customer']
    ];

    // Data untuk branch admin users
    public static $branch_users = [
        1 => [  // PT Maju Bersama
            'pusat' => ['id' => 12, 'username' => 'maju_pusat', 'display_name' => 'Admin Pusat Maju Bersama'],
            'cabang1' => ['id' => 13, 'username' => 'maju_cabang1', 'display_name' => 'Admin Cabang 1 Maju Bersama'],
            'cabang2' => ['id' => 14, 'username' => 'maju_cabang2', 'display_name' => 'Admin Cabang 2 Maju Bersama']
        ],
        2 => [  // CV Teknologi Nusantara
            'pusat' => ['id' => 15, 'username' => 'teknologi_pusat', 'display_name' => 'Admin Pusat Teknologi Nusantara'],
            'cabang1' => ['id' => 16, 'username' => 'teknologi_cabang1', 'display_name' => 'Admin Cabang 1 Teknologi Nusantara'],
            'cabang2' => ['id' => 17, 'username' => 'teknologi_cabang2', 'display_name' => 'Admin Cabang 2 Teknologi Nusantara']
        ],
        3 => [  // PT Sinar Abadi
            'pusat' => ['id' => 18, 'username' => 'sinar_pusat', 'display_name' => 'Admin Pusat Sinar Abadi'],
            'cabang1' => ['id' => 19, 'username' => 'sinar_cabang1', 'display_name' => 'Admin Cabang 1 Sinar Abadi'],
            'cabang2' => ['id' => 20, 'username' => 'sinar_cabang2', 'display_name' => 'Admin Cabang 2 Sinar Abadi']
        ],
        4 => [  // PT Global Teknindo
            'pusat' => ['id' => 21, 'username' => 'global_pusat', 'display_name' => 'Admin Pusat Global Teknindo'],
            'cabang1' => ['id' => 22, 'username' => 'global_cabang1', 'display_name' => 'Admin Cabang 1 Global Teknindo'],
            'cabang2' => ['id' => 23, 'username' => 'global_cabang2', 'display_name' => 'Admin Cabang 2 Global Teknindo']
        ],
        5 => [  // CV Mitra Solusi
            'pusat' => ['id' => 24, 'username' => 'mitra_pusat', 'display_name' => 'Admin Pusat Mitra Solusi'],
            'cabang1' => ['id' => 25, 'username' => 'mitra_cabang1', 'display_name' => 'Admin Cabang 1 Mitra Solusi'],
            'cabang2' => ['id' => 26, 'username' => 'mitra_cabang2', 'display_name' => 'Admin Cabang 2 Mitra Solusi']
        ],
        6 => [  // PT Karya Digital
            'pusat' => ['id' => 27, 'username' => 'karya_pusat', 'display_name' => 'Admin Pusat Karya Digital'],
            'cabang1' => ['id' => 28, 'username' => 'karya_cabang1', 'display_name' => 'Admin Cabang 1 Karya Digital'],
            'cabang2' => ['id' => 29, 'username' => 'karya_cabang2', 'display_name' => 'Admin Cabang 2 Karya Digital']
        ],
        7 => [  // PT Bumi Perkasa
            'pusat' => ['id' => 30, 'username' => 'bumi_pusat', 'display_name' => 'Admin Pusat Bumi Perkasa'],
            'cabang1' => ['id' => 31, 'username' => 'bumi_cabang1', 'display_name' => 'Admin Cabang 1 Bumi Perkasa'],
            'cabang2' => ['id' => 32, 'username' => 'bumi_cabang2', 'display_name' => 'Admin Cabang 2 Bumi Perkasa']
        ],
        8 => [  // CV Cipta Kreasi
            'pusat' => ['id' => 33, 'username' => 'cipta_pusat', 'display_name' => 'Admin Pusat Cipta Kreasi'],
            'cabang1' => ['id' => 34, 'username' => 'cipta_cabang1', 'display_name' => 'Admin Cabang 1 Cipta Kreasi'],
            'cabang2' => ['id' => 35, 'username' => 'cipta_cabang2', 'display_name' => 'Admin Cabang 2 Cipta Kreasi']
        ],
        9 => [  // PT Meta Inovasi
            'pusat' => ['id' => 36, 'username' => 'meta_pusat', 'display_name' => 'Admin Pusat Meta Inovasi'],
            'cabang1' => ['id' => 37, 'username' => 'meta_cabang1', 'display_name' => 'Admin Cabang 1 Meta Inovasi'],
            'cabang2' => ['id' => 38, 'username' => 'meta_cabang2', 'display_name' => 'Admin Cabang 2 Meta Inovasi']
        ],
        10 => [  // PT Delta Sistem
            'pusat' => ['id' => 39, 'username' => 'delta_pusat', 'display_name' => 'Admin Pusat Delta Sistem'],
            'cabang1' => ['id' => 40, 'username' => 'delta_cabang1', 'display_name' => 'Admin Cabang 1 Delta Sistem'],
            'cabang2' => ['id' => 41, 'username' => 'delta_cabang2', 'display_name' => 'Admin Cabang 2 Delta Sistem']
        ]
    ];

    /**
     * Run the generator
     * 
     * @return bool True on success, false on failure
     * tidak digunakan agi
    public function run(): bool {
        try {
            if (!$this->validate()) {
                return false;
            }

            global $wpdb;
            // Start transaction
            $wpdb->query('START TRANSACTION');

            try {
                $this->generate();
                $wpdb->query('COMMIT');
                return true;
            } catch (\Exception $e) {
                $wpdb->query('ROLLBACK');
                throw $e;
            }
        } catch (\Exception $e) {
            $this->debug('Generation failed: ' . $e->getMessage());
            return false;
        }
    }
    */
    
    protected function validate(): bool {
        if (!current_user_can('create_users')) {
            $this->debug('Current user cannot create users');
            return false;
        }
        return true;
    }

    /*
     * tidak digunakan lagi
    protected function generate($user_type = null): void {
        try {
            $users_to_generate = [];
            
            switch ($user_type) {
                case 'branch':
                    $users_to_generate = self::$branch_users;
                    break;
                case 'customer':
                default:
                    $users_to_generate = self::$customer_users;
                    break;
            }

            foreach ($users_to_generate as $user) {
                $this->generateUser($user);
            }
            
        } catch (\Exception $e) {
            $this->debug('Generation failed: ' . $e->getMessage());
        }
    }
    */

    public function generateUser($data) {
        global $wpdb;
        
        // 1. Cek apakah user dengan ID tersebut sudah ada
        $existing_user = get_user_by('ID', $data['id']);
        if ($existing_user) {
            // Update display name jika berbeda
            if ($existing_user->display_name !== $data['display_name']) {
                wp_update_user([
                    'ID' => $data['id'],
                    'display_name' => $data['display_name']
                ]);
                $this->debug("Updated user display name: {$data['display_name']} with ID: {$data['id']}");
            }
            return $data['id'];
        }

        // 2. Jika user belum ada, gunakan username dari data atau generate baru
        $username = isset($data['username']) 
            ? $data['username'] 
            : $this->generateUniqueUsername($data['display_name']);
        
        // 3. Insert user baru ke database
        $result = $wpdb->insert(
            $wpdb->users,
            [
                'ID' => $data['id'],
                'user_login' => $username,
                'user_pass' => wp_hash_password('Demo_Data-2025'),
                'user_email' => $username . '@example.com',
                'display_name' => $data['display_name'],
                'user_registered' => current_time('mysql')
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s'
            ]
        );

        if ($result === false) {
            throw new \Exception($wpdb->last_error);
        }

        $user_id = $data['id'];

        // Insert user meta directly
        $wpdb->insert(
            $wpdb->usermeta,
            [
                'user_id' => $user_id,
                'meta_key' => 'wp_customer_demo_user',
                'meta_value' => '1'
            ],
            [
                '%d',
                '%s',
                '%s'
            ]
        );

        // Add role capability
        $wpdb->insert(
            $wpdb->usermeta,
            [
                'user_id' => $user_id,
                'meta_key' => $wpdb->prefix . 'capabilities',
                'meta_value' => serialize(array($data['role'] => true))
            ],
            [
                '%d',
                '%s',
                '%s'
            ]
        );

        // Update user level for backward compatibility
        $wpdb->insert(
            $wpdb->usermeta,
            [
                'user_id' => $user_id,
                'meta_key' => $wpdb->prefix . 'user_level',
                'meta_value' => '0'
            ],
            [
                '%d',
                '%s',
                '%s'
            ]
        );

        $this->debug("Created user: {$data['display_name']} with ID: {$user_id}");
        
        return $user_id;
    }

    private function generateUniqueUsername($display_name) {
        $base_username = strtolower(str_replace(' ', '_', $display_name));
        $username = $base_username;
        $suffix = 1;
        
        while (in_array($username, self::$usedUsernames) || username_exists($username)) {
            $username = $base_username . $suffix;
            $suffix++;
        }
        
        self::$usedUsernames[] = $username;
        return $username;
    }

    private function debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WP User Generator] ' . $message);
        }
    }
}
