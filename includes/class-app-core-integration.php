<?php
/**
 * WP App Core Integration Class
 *
 * @package     WP_Customer
 * @subpackage  Includes
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/includes/class-app-core-integration.php
 *
 * Description: Integration layer untuk menghubungkan wp-customer dengan wp-app-core.
 *              Menyediakan user info untuk admin bar dan fitur shared lainnya.
 *
 * Usage:
 * - Dipanggil otomatis saat wp-app-core aktif
 * - Menyediakan callback untuk mendapatkan user info customer
 * - Role names disediakan langsung via $result['role_names'] (no filters needed)
 *
 * Changelog:
 * 1.1.0 - 2025-01-18
 * - REFACTOR: Moved getUserInfo() query to CustomerEmployeeModel for reusability
 * - Added: Cache support in CustomerEmployeeModel::getUserInfo()
 * - Improved: get_user_info() now delegates to Model layer for employee data
 * - Benefits: Cleaner separation of concerns, cacheable, reusable across codebase
 * - Added: Comprehensive debug logging for all query paths
 * - Added: Fallback handling for users with roles but no entity link
 *
 * 1.0.0 - 2025-01-18
 * - Initial creation
 * - Integration dengan wp-app-core admin bar
 * - Support untuk cache manager
 */

defined('ABSPATH') || exit;

class WP_Customer_App_Core_Integration {

    /**
     * Initialize integration
     */
    public static function init() {
        // Check if wp-app-core is active
        if (!class_exists('WP_App_Core_Admin_Bar_Info')) {
            return;
        }

        // Register customer plugin with app core
        add_action('wp_app_core_register_admin_bar_plugins', [__CLASS__, 'register_with_app_core']);

        // Add filter for role names
        add_filter('wp_app_core_role_name_customer', [__CLASS__, 'get_role_name']);
        add_filter('wp_app_core_role_name_customer_admin', [__CLASS__, 'get_role_name']);
        add_filter('wp_app_core_role_name_customer_branch_admin', [__CLASS__, 'get_role_name']);
        add_filter('wp_app_core_role_name_customer_employee', [__CLASS__, 'get_role_name']);
    }

    /**
     * Register customer plugin with app core
     */
    public static function register_with_app_core() {
        if (!class_exists('WP_App_Core_Admin_Bar_Info')) {
            return;
        }

        WP_App_Core_Admin_Bar_Info::register_plugin('customer', [
            'roles' => WP_Customer_Role_Manager::getRoleSlugs(),
            'get_user_info' => [__CLASS__, 'get_user_info'],
        ]);
    }

    /**
     * Get user information for admin bar
     *
     * @param int $user_id
     * @return array|null
     */
    public static function get_user_info($user_id) {
        // DEBUG: Log function call
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("=== WP_Customer get_user_info START for user_id: {$user_id} ===");
        }

        $result = null;

        // First, try to get employee data using CustomerEmployeeModel
        // This provides caching and makes the query reusable
        $employee_model = new \WPCustomer\Models\Employee\CustomerEmployeeModel();
        $result = $employee_model->getUserInfo($user_id);

        // DEBUG: Log result from model
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($result) {
                error_log("USER DATA FROM MODEL (employee): " . print_r($result, true));
            } else {
                error_log("No employee data found in model");
            }
        }

        // If not an employee, check if user is a customer (owner)
        if (!$result) {
            global $wpdb;

            $customer = $wpdb->get_row($wpdb->prepare(
                "SELECT c.id, c.name as customer_name, c.code as customer_code
                 FROM {$wpdb->prefix}app_customers c
                 WHERE c.user_id = %d",
                $user_id
            ));

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
                        'entity_name' => $customer->customer_name,
                        'entity_code' => $customer->customer_code,
                        'relation_type' => 'owner',
                        'icon' => '🏢'
                    ];

                    // DEBUG: Log customer owner result
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("USER DATA (customer owner): " . print_r($result, true));
                    }
                }
            }
        }

        // Check if user is a customer branch admin (only if not already found)
        if (!$result) {
            global $wpdb;

            $customer_branch_admin = $wpdb->get_row($wpdb->prepare(
                "SELECT b.id, b.name, b.type, b.customer_id,
                        c.name as customer_name, c.code as customer_code
                 FROM {$wpdb->prefix}app_customer_branches b
                 LEFT JOIN {$wpdb->prefix}app_customers c ON b.customer_id = c.id
                 WHERE b.user_id = %d",
                $user_id
            ));

            if ($customer_branch_admin) {
                $result = [
                    'branch_id' => $customer_branch_admin->id,
                    'branch_name' => $customer_branch_admin->name,
                    'branch_type' => $customer_branch_admin->type,
                    'entity_name' => $customer_branch_admin->customer_name,
                    'entity_code' => $customer_branch_admin->customer_code,
                    'relation_type' => 'branch_admin',
                    'icon' => '🏢'
                ];

                // DEBUG: Log branch admin result
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("USER DATA (customer branch admin): " . print_r($result, true));
                }
            }
        }

        // Fallback: If user has customer role but no entity link, show role-based info
        if (!$result) {
            $user = get_user_by('ID', $user_id);
            if ($user) {
                $customer_roles = WP_Customer_Role_Manager::getRoleSlugs();
                $user_roles = (array) $user->roles;

                // DEBUG: Log user roles
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("User Roles: " . print_r($user_roles, true));
                    error_log("Customer Roles (available): " . print_r($customer_roles, true));
                }

                // Check if user has any customer role
                $has_customer_role = false;
                foreach ($customer_roles as $role_slug) {
                    if (in_array($role_slug, $user_roles)) {
                        $has_customer_role = true;
                        break;
                    }
                }

                // DEBUG: Log role check result
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Has Customer Role: " . ($has_customer_role ? 'YES' : 'NO'));
                }

                if ($has_customer_role) {
                    // Get first customer role for display
                    $first_customer_role = null;
                    foreach ($customer_roles as $role_slug) {
                        if (in_array($role_slug, $user_roles)) {
                            $first_customer_role = $role_slug;
                            break;
                        }
                    }

                    $role_name = WP_Customer_Role_Manager::getRoleName($first_customer_role);

                    // For users without entity link, show role-based info
                    // Use role name as branch name
                    $result = [
                        'entity_name' => 'Customer System',
                        'entity_code' => 'CUSTOMER',
                        'branch_id' => null,
                        'branch_name' => $role_name ?? 'Staff',
                        'branch_type' => 'admin',
                        'relation_type' => 'role_only',
                        'icon' => '🏢'
                    ];

                    // DEBUG: Log fallback result
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("FALLBACK RESULT: " . print_r($result, true));
                    }
                }
            }
        }

        // DEBUG: Log final result
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("=== WP_Customer get_user_info END - Final Result: " . print_r($result ?? null, true) . " ===");
        }

        return $result ?? null;
    }

    /**
     * Get role display name
     *
     * @param string|null $default
     * @return string|null
     */
    public static function get_role_name($default) {
        $role_slug = str_replace('wp_app_core_role_name_', '', current_filter());
        return WP_Customer_Role_Manager::getRoleName($role_slug) ?? $default;
    }
}
