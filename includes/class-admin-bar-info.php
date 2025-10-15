<?php
/**
 * Admin Bar Info Class
 *
 * @package     WP_Customer
 * @subpackage  Includes
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/includes/class-admin-bar-info.php
 *
 * Description: Display user information in WordPress admin bar.
 *              Shows branch name and roles for customer-related users.
 *              Helps with debugging capabilities and user assignments.
 *
 * Changelog:
 * 1.0.0 - 2025-01-15
 * - Initial creation
 * - Display branch name for users
 * - Display all user roles
 */

defined('ABSPATH') || exit;

use WPCustomer\Cache\CustomerCacheManager;

class WP_Customer_Admin_Bar_Info {

    /**
     * Cache manager instance
     * @var CustomerCacheManager
     */
    private static $cache_manager = null;

    /**
     * Initialize the admin bar info display
     */
    public static function init() {
        // Only add for logged in users
        if (!is_user_logged_in()) {
            return;
        }

        // Check if user has any customer-related role
        $user = wp_get_current_user();
        $has_customer_role = false;

        foreach (WP_Customer_Role_Manager::getRoleSlugs() as $role_slug) {
            if (in_array($role_slug, (array) $user->roles)) {
                $has_customer_role = true;
                break;
            }
        }

        // If user has customer role, add admin bar info
        if ($has_customer_role) {
            add_action('admin_bar_menu', [__CLASS__, 'add_admin_bar_info'], 100);
            // Styles are now loaded via class-dependencies.php
        }
    }

    /**
     * Add user info to admin bar
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    public static function add_admin_bar_info($wp_admin_bar) {
        $user = wp_get_current_user();
        $user_id = $user->ID;

        // Get branch information
        $branch_info = self::get_user_branch_info($user_id);
        $branch_text = $branch_info ? esc_html($branch_info['branch_name']) : 'No Branch Assigned';
        $customer_text = $branch_info ? esc_html($branch_info['customer_name']) : '';

        // Get user roles
        $role_names = [];
        foreach ((array) $user->roles as $role_slug) {
            // Get role display name
            $role_obj = get_role($role_slug);
            if ($role_obj) {
                // Try to get from our role manager first
                $role_name = WP_Customer_Role_Manager::getRoleName($role_slug);
                if (!$role_name) {
                    // Fallback to WordPress role name
                    $wp_roles = wp_roles();
                    $role_name = isset($wp_roles->role_names[$role_slug])
                        ? translate_user_role($wp_roles->role_names[$role_slug])
                        : $role_slug;
                }
                $role_names[] = $role_name;
            }
        }
        $roles_text = !empty($role_names) ? implode(', ', $role_names) : 'No Roles';

        // Build the display text
        $info_html = '<span class="wp-customer-admin-bar-info">';

        // Customer & Branch Info
        if ($customer_text && $branch_text !== 'No Branch Assigned') {
            $info_html .= '<span class="wp-customer-info">';
            $info_html .= '🏢 ' . $branch_text;
            $info_html .= '</span>';
        } else {
            $info_html .= '<span class="wp-customer-branch-info">';
            $info_html .= '🏢 ' . $branch_text;
            $info_html .= '</span>';
        }

        $info_html .= '<span class="wp-customer-separator"> | </span>';

        // Roles Info
        $info_html .= '<span class="wp-customer-roles-info">';
        $info_html .= '👤 ' . $roles_text;
        $info_html .= '</span>';

        $info_html .= '</span>';

        // Add to admin bar (parent: top-secondary for right side)
        $wp_admin_bar->add_node([
            'id'    => 'wp-customer-user-info',
            'parent' => 'top-secondary',
            'title' => $info_html,
            'meta'  => [
                'class' => 'wp-customer-admin-bar-item',
                'title' => 'WP Customer User Information'
            ]
        ]);

        // Add submenu with detailed info
        $wp_admin_bar->add_node([
            'parent' => 'wp-customer-user-info',
            'id'     => 'wp-customer-user-details',
            'title'  => self::get_detailed_info_html($user_id, $branch_info),
            'meta'   => [
                'class' => 'wp-customer-user-details'
            ]
        ]);
    }

    /**
     * Get cache manager instance
     *
     * @return CustomerCacheManager
     */
    private static function get_cache_manager() {
        if (self::$cache_manager === null) {
            self::$cache_manager = new CustomerCacheManager();
        }
        return self::$cache_manager;
    }

    /**
     * Get user's branch information from database with caching
     *
     * @param int $user_id
     * @return array|null
     */
    private static function get_user_branch_info($user_id) {
        // Try to get from cache first
        $cache_manager = self::get_cache_manager();
        $cache_key = 'user_branch_info';
        $cached_data = $cache_manager->get($cache_key, $user_id);

        if ($cached_data !== null) {
            return $cached_data;
        }

        // If not in cache, query database
        global $wpdb;

        // First check if user is a customer (owner)
        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT c.id, c.name as customer_name, c.code as customer_code
             FROM {$wpdb->prefix}app_customers c
             WHERE c.user_id = %d",
            $user_id
        ));

        $result = null;

        if ($customer) {
            // User is a customer owner, get their main branch
            $branch = $wpdb->get_row($wpdb->prepare(
                "SELECT b.id, b.name, b.type
                 FROM {$wpdb->prefix}app_customer_branches b
                 WHERE b.customer_id = %d
                 AND b.type = 'pusat'
                 ORDER BY b.id ASC
                 LIMIT 1",
                $customer->id
            ));

            if ($branch) {
                $result = [
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'branch_type' => $branch->type,
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->customer_name,
                    'customer_code' => $customer->customer_code,
                    'relation_type' => 'owner'
                ];
            }
        }

        // Check if user is a branch admin (only if not already found as customer owner)
        if (!$result) {
            $branch_admin = $wpdb->get_row($wpdb->prepare(
                "SELECT b.id, b.name, b.type, b.customer_id,
                        c.name as customer_name, c.code as customer_code
                 FROM {$wpdb->prefix}app_customer_branches b
                 LEFT JOIN {$wpdb->prefix}app_customers c ON b.customer_id = c.id
                 WHERE b.user_id = %d",
                $user_id
            ));

            if ($branch_admin) {
                $result = [
                    'branch_id' => $branch_admin->id,
                    'branch_name' => $branch_admin->name,
                    'branch_type' => $branch_admin->type,
                    'customer_id' => $branch_admin->customer_id,
                    'customer_name' => $branch_admin->customer_name,
                    'customer_code' => $branch_admin->customer_code,
                    'relation_type' => 'branch_admin'
                ];
            }
        }

        // Check if user is an employee (only if not already found)
        if (!$result) {
            $employee = $wpdb->get_row($wpdb->prepare(
                "SELECT e.id, e.user_id, e.branch_id, e.position,
                        e.finance, e.operation, e.legal, e.purchase,
                        b.name as branch_name, b.type as branch_type, b.customer_id, b.status as branch_status,
                        c.name as customer_name, c.code as customer_code
                 FROM {$wpdb->prefix}app_customer_employees e
                 LEFT JOIN {$wpdb->prefix}app_customer_branches b ON e.branch_id = b.id AND b.status = 'active'
                 LEFT JOIN {$wpdb->prefix}app_customers c ON b.customer_id = c.id
                 WHERE e.user_id = %d
                 AND e.status = 'active'",
                $user_id
            ));

            // Debug log
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Employee query for user_id $user_id: " . print_r($employee, true));
            }

            if ($employee) {
                // Build department string from boolean flags
                $departments = [];
                if ($employee->finance) $departments[] = 'Finance';
                if ($employee->operation) $departments[] = 'Operation';
                if ($employee->legal) $departments[] = 'Legal';
                if ($employee->purchase) $departments[] = 'Purchase';
                $department_string = !empty($departments) ? implode(', ', $departments) : null;

                // Check if branch data exists (might be null if branch was deleted)
                if ($employee->branch_id && $employee->branch_name) {
                    $result = [
                        'branch_id' => $employee->branch_id,
                        'branch_name' => $employee->branch_name,
                        'branch_type' => $employee->branch_type,
                        'customer_id' => $employee->customer_id,
                        'customer_name' => $employee->customer_name,
                        'customer_code' => $employee->customer_code,
                        'position' => $employee->position,
                        'department' => $department_string,
                        'relation_type' => 'employee'
                    ];
                } else {
                    // Employee exists but branch data missing (orphaned employee)
                    error_log("Employee (ID: {$employee->id}, user_id: {$employee->user_id}) has branch_id {$employee->branch_id} but branch not found or inactive");
                }
            }
        }

        // Store result in cache before returning (even if null)
        $cache_manager->set($cache_key, $result ?? null, 5 * MINUTE_IN_SECONDS, $user_id);

        return $result ?? null;
    }

    /**
     * Get detailed info HTML for dropdown
     *
     * @param int $user_id
     * @param array|null $branch_info
     * @return string
     */
    private static function get_detailed_info_html($user_id, $branch_info) {
        $user = get_user_by('ID', $user_id);

        $html = '<div class="wp-customer-detailed-info">';

        // User Info Section
        $html .= '<div class="info-section">';
        $html .= '<strong>User Information:</strong><br>';
        $html .= 'ID: ' . $user_id . '<br>';
        $html .= 'Username: ' . esc_html($user->user_login) . '<br>';
        $html .= 'Email: ' . esc_html($user->user_email) . '<br>';
        $html .= '</div>';

        // Customer/Branch Info Section
        if ($branch_info) {
            $html .= '<div class="info-section">';
            $html .= '<strong>Customer/Branch:</strong><br>';
            $html .= 'Customer: ' . esc_html($branch_info['customer_name']) . ' (' . esc_html($branch_info['customer_code']) . ')<br>';
            $html .= 'Branch: ' . esc_html($branch_info['branch_name']) . '<br>';
            $html .= 'Type: ' . ucfirst($branch_info['branch_type']) . '<br>';
            $html .= 'Relation: ' . ucfirst(str_replace('_', ' ', $branch_info['relation_type'])) . '<br>';

            if (isset($branch_info['position'])) {
                $html .= 'Position: ' . esc_html($branch_info['position']) . '<br>';
            }
            if (isset($branch_info['department'])) {
                $html .= 'Department: ' . esc_html($branch_info['department']) . '<br>';
            }
            $html .= '</div>';
        }

        // Roles Section
        $html .= '<div class="info-section">';
        $html .= '<strong>Roles:</strong><br>';
        foreach ((array) $user->roles as $role) {
            $html .= '• ' . esc_html($role) . '<br>';
        }
        $html .= '</div>';

        // Capabilities Section (showing customer-related capabilities)
        $html .= '<div class="info-section">';
        $html .= '<strong>Key Capabilities:</strong><br>';

        $key_caps = [
            'view_customer_list',
            'view_customer_branch_list',
            'view_customer_employee_list',
            'edit_all_customers',
            'edit_own_customer',
            'manage_options'
        ];

        foreach ($key_caps as $cap) {
            if (user_can($user_id, $cap)) {
                $html .= '✓ ' . $cap . '<br>';
            }
        }
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

}