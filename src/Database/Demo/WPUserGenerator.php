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
    
    /**
     * Run the generator
     * 
     * @return bool True on success, false on failure
     */
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
    
    protected function validate(): bool {
        if (!current_user_can('create_users')) {
            $this->debug('Current user cannot create users');
            return false;
        }
        return true;
    }

    protected function generate(): void {
        $users_to_generate = [
            ['id' => 2, 'display_name' => 'Budi Santoso', 'role' => 'customer'],
            ['id' => 3, 'display_name' => 'Dewi Kartika', 'role' => 'customer'], 
            ['id' => 4, 'display_name' => 'Ahmad Hidayat', 'role' => 'customer'],
            ['id' => 5, 'display_name' => 'Siti Rahayu', 'role' => 'customer'],
            ['id' => 6, 'display_name' => 'Rudi Hermawan', 'role' => 'customer'],
            ['id' => 7, 'display_name' => 'Nina Kusuma', 'role' => 'customer'],
            ['id' => 8, 'display_name' => 'Eko Prasetyo', 'role' => 'customer'],
            ['id' => 9, 'display_name' => 'Maya Wijaya', 'role' => 'customer'],
            ['id' => 10, 'display_name' => 'Dian Pertiwi', 'role' => 'customer'],
            ['id' => 11, 'display_name' => 'Agus Suryanto', 'role' => 'customer']
        ];

        foreach ($users_to_generate as $user) {
            $this->generateUser($user);
        }
    }

    public function generateUser($data) {
        global $wpdb;
        
        $username = $this->generateUniqueUsername($data['display_name']);
        
        // Insert directly into wp_users table
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
