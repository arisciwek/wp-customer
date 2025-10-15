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

        error_log("[WPUserGenerator] === generateUser called ===");
        error_log("[WPUserGenerator] Input data: " . json_encode($data));

        // 1. Check if user with this ID already exists using DIRECT DATABASE QUERY
        // IMPORTANT: Don't use get_user_by() because it can return cached/filtered objects
        // that don't actually exist in database
        $existing_user_row = $wpdb->get_row($wpdb->prepare(
            "SELECT ID, user_login, display_name FROM {$wpdb->users} WHERE ID = %d",
            $data['id']
        ));

        error_log("[WPUserGenerator] Checking existing user with ID {$data['id']} via direct DB query: " . ($existing_user_row ? 'EXISTS' : 'NOT FOUND'));

        if ($existing_user_row) {
            error_log("[WPUserGenerator] User exists in database - Display Name: {$existing_user_row->display_name}");
            // Update display name if different
            if ($existing_user_row->display_name !== $data['display_name']) {
                wp_update_user([
                    'ID' => $data['id'],
                    'display_name' => $data['display_name']
                ]);
                error_log("[WPUserGenerator] Updated user display name: {$data['display_name']} with ID: {$data['id']}");
                $this->debug("Updated user display name: {$data['display_name']} with ID: {$data['id']}");
            }
            error_log("[WPUserGenerator] Returning existing user ID: {$data['id']}");
            return $data['id'];
        }

        // 2. Use username from data or generate new one
        $username = isset($data['username'])
            ? $data['username']
            : $this->generateUniqueUsername($data['display_name']);

        error_log("[WPUserGenerator] Username to use: {$username}");

        // Check if username already exists
        $username_exists = username_exists($username);
        error_log("[WPUserGenerator] Username '{$username}' exists check: " . ($username_exists ? "YES (ID: {$username_exists})" : 'NO'));

        // 3. Insert new user into database
        $user_data_to_insert = [
            'ID' => $data['id'],
            'user_login' => $username,
            'user_pass' => wp_hash_password('Demo_Data-2025'),
            'user_email' => $username . '@example.com',
            'display_name' => $data['display_name'],
            'user_registered' => current_time('mysql')
        ];
        error_log("[WPUserGenerator] Attempting to insert user into {$wpdb->users}: " . json_encode([
            'ID' => $user_data_to_insert['ID'],
            'user_login' => $user_data_to_insert['user_login'],
            'user_email' => $user_data_to_insert['user_email'],
            'display_name' => $user_data_to_insert['display_name']
        ]));

        $result = $wpdb->insert(
            $wpdb->users,
            $user_data_to_insert,
            [
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s'
            ]
        );

        error_log("[WPUserGenerator] wpdb->insert result: " . ($result === false ? 'FALSE' : $result));
        if ($result === false) {
            error_log("[WPUserGenerator] ERROR: wpdb->last_error: " . $wpdb->last_error);
            throw new \Exception($wpdb->last_error);
        }

        $user_id = $data['id'];
        error_log("[WPUserGenerator] User inserted successfully with ID: {$user_id}");

        // Insert user meta directly
        error_log("[WPUserGenerator] Inserting user meta 'wp_customer_demo_user'");
        $meta_result_1 = $wpdb->insert(
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
        error_log("[WPUserGenerator] Meta insert result (demo_user): " . ($meta_result_1 === false ? 'FALSE - ' . $wpdb->last_error : $meta_result_1));

        // Add role capability
        $capabilities = serialize(array($data['role'] => true));
        error_log("[WPUserGenerator] Inserting capabilities: {$capabilities}");
        $meta_result_2 = $wpdb->insert(
            $wpdb->usermeta,
            [
                'user_id' => $user_id,
                'meta_key' => $wpdb->prefix . 'capabilities',
                'meta_value' => $capabilities
            ],
            [
                '%d',
                '%s',
                '%s'
            ]
        );
        error_log("[WPUserGenerator] Meta insert result (capabilities): " . ($meta_result_2 === false ? 'FALSE - ' . $wpdb->last_error : $meta_result_2));

        // Update user level for backward compatibility
        error_log("[WPUserGenerator] Inserting user_level");
        $meta_result_3 = $wpdb->insert(
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
        error_log("[WPUserGenerator] Meta insert result (user_level): " . ($meta_result_3 === false ? 'FALSE - ' . $wpdb->last_error : $meta_result_3));

        error_log("[WPUserGenerator] === User creation completed successfully ===");
        error_log("[WPUserGenerator] Created user: {$data['display_name']} with ID: {$user_id}");
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

    /**
     * Delete demo users by IDs
     *
     * @param array $user_ids Array of user IDs to delete
     * @param bool $force_delete Force delete without demo user check (for development)
     * @return int Number of users deleted
     */
    public function deleteUsers(array $user_ids, bool $force_delete = false): int {
        if (empty($user_ids)) {
            return 0;
        }

        error_log("[WPUserGenerator] === Deleting demo users ===");
        error_log("[WPUserGenerator] User IDs to delete: " . json_encode($user_ids));
        error_log("[WPUserGenerator] Force delete mode: " . ($force_delete ? 'YES' : 'NO'));

        $deleted_count = 0;

        foreach ($user_ids as $user_id) {
            // Check if user exists
            $existing_user = get_user_by('ID', $user_id);

            if (!$existing_user) {
                error_log("[WPUserGenerator] User ID {$user_id} not found, skipping");
                continue;
            }

            // Skip ID 1 (main admin) for safety even in force mode
            if ($user_id == 1) {
                error_log("[WPUserGenerator] User ID 1 is main admin, skipping for safety");
                continue;
            }

            // Check if this is a demo user (unless force delete is enabled)
            if (!$force_delete) {
                $is_demo = get_user_meta($user_id, 'wp_customer_demo_user', true);

                if ($is_demo !== '1') {
                    error_log("[WPUserGenerator] User ID {$user_id} is not a demo user, skipping for safety");
                    continue;
                }
            } else {
                error_log("[WPUserGenerator] Force deleting user ID {$user_id} ({$existing_user->user_login})");
            }

            // Use WordPress function to delete user
            // This will also delete all user meta automatically
            require_once(ABSPATH . 'wp-admin/includes/user.php');

            $result = wp_delete_user($user_id);

            if ($result) {
                $deleted_count++;
                error_log("[WPUserGenerator] Deleted user ID {$user_id} ({$existing_user->user_login})");
            } else {
                error_log("[WPUserGenerator] Failed to delete user ID {$user_id}");
            }
        }

        error_log("[WPUserGenerator] Deleted {$deleted_count} users");
        $this->debug("Deleted {$deleted_count} users");

        return $deleted_count;
    }

    private function debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WPUserGenerator] ' . $message);
        }
    }
}
