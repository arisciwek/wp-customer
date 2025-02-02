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
 */

namespace WPCustomer\Database\Demo;

use WPCustomer\Database\Demo\Data\CustomerUsersData;
use WPCustomer\Database\Demo\Data\BranchUsersData;

defined('ABSPATH') || exit;

class WPUserGenerator {
    use CustomerDemoDataHelperTrait;

    private static $usedUsernames = [];
    
    // Reference the data from separate files
    public static $customer_users;
    public static $branch_users;

    public function __construct() {
        // Initialize the static properties from the data files
        self::$customer_users = CustomerUsersData::$data;
        self::$branch_users = BranchUsersData::$data;
    }

    protected function validate(): bool {
        if (!current_user_can('create_users')) {
            $this->debug('Current user cannot create users');
            return false;
        }
        return true;
    }

    public function generateUser($data) {
        global $wpdb;
        
        // 1. Check if user with this ID already exists
        $existing_user = get_user_by('ID', $data['id']);
        if ($existing_user) {
            // Update display name if different
            if ($existing_user->display_name !== $data['display_name']) {
                wp_update_user([
                    'ID' => $data['id'],
                    'display_name' => $data['display_name']
                ]);
                $this->debug("Updated user display name: {$data['display_name']} with ID: {$data['id']}");
            }
            return $data['id'];
        }

        // 2. Use username from data or generate new one
        $username = isset($data['username']) 
            ? $data['username'] 
            : $this->generateUniqueUsername($data['display_name']);
        
        // 3. Insert new user into database
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
            error_log('[WPUserGenerator] ' . $message);
        }
    }
}
