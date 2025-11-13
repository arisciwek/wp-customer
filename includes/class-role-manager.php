<?php
/**
 * Role Manager Class
 *
 * @package     WP_Customer
 * @subpackage  Includes
 * @version     1.0.12
 * @author      arisciwek
 *
 * Path: /wp-customer/includes/class-role-manager.php
 *
 * Description: Centralized role management for WP Customer plugin.
 *              Single source of truth untuk role definitions.
 *              Accessible untuk plugin lain dan internal components.
 *
 * Role Structure:
 * - customer: Base role for all customer plugin users
 * - customer_admin: Customer company administrator
 * - customer_branch_admin: Branch administrator
 * - customer_employee: Employee role
 *
 * Multi-Role Assignment Pattern:
 *
 * 1. Create Customer:
 *    $user->set_role('customer');           // Primary role
 *    $user->add_role('customer_admin');     // Admin privileges
 *    $user->add_role('customer_employee');  // Employee privileges
 *
 * 2. Create Branch:
 *    Base role 'customer' is already set in wp_insert_user()
 *    $user->add_role('customer_branch_admin');  // Branch admin privileges
 *    $user->add_role('customer_employee');      // Employee privileges
 *
 * Usage:
 * - Get all roles: WP_Customer_Role_Manager::getRoles()
 * - Get role slugs: WP_Customer_Role_Manager::getRoleSlugs()
 * - Check if role exists: WP_Customer_Role_Manager::roleExists($slug)
 *
 * Changelog:
 * 1.0.12 - 2025-11-02
 * - Added multi-role assignment pattern documentation
 * 1.0.0 - 2024-01-14
 * - Initial creation
 * - Moved role definitions from WP_Customer_Activator
 * - Made accessible for all components
 */

defined('ABSPATH') || exit;

class WP_Customer_Role_Manager {
    /**
     * Get all available roles with their display names
     * Single source of truth for roles in the plugin
     *
     * @return array Array of role_slug => role_name pairs
     */
    public static function getRoles(): array {
        return [
            'customer' => __('Customer', 'wp-customer'),
            'customer_admin' => __('Customer Admin', 'wp-customer'),
            'customer_branch_admin' => __('Customer Branch Admin', 'wp-customer'),
            'customer_employee' => __('Customer Employee', 'wp-customer'),
        ];
    }

    /**
     * Get only role slugs
     *
     * @return array Array of role slugs
     */
    public static function getRoleSlugs(): array {
        return array_keys(self::getRoles());
    }

    /**
     * Check if a role is managed by this plugin
     *
     * @param string $role_slug Role slug to check
     * @return bool True if role is managed by this plugin
     */
    public static function isPluginRole(string $role_slug): bool {
        return array_key_exists($role_slug, self::getRoles());
    }

    /**
     * Check if a WordPress role exists
     *
     * @param string $role_slug Role slug to check
     * @return bool True if role exists in WordPress
     */
    public static function roleExists(string $role_slug): bool {
        return get_role($role_slug) !== null;
    }

    /**
     * Get display name for a role
     *
     * @param string $role_slug Role slug
     * @return string|null Role display name or null if not found
     */
    public static function getRoleName(string $role_slug): ?string {
        $roles = self::getRoles();
        return $roles[$role_slug] ?? null;
    }

    /**
     * Get user access type based on WordPress roles
     * Returns the highest priority role for the user
     *
     * Priority order:
     * 1. administrator -> 'admin'
     * 2. customer_admin -> 'customer_admin'
     * 3. customer_branch_admin -> 'customer_branch_admin'
     * 4. customer_employee -> 'customer_employee'
     * 5. none -> 'none'
     *
     * @param int $user_id User ID (0 for current user)
     * @return string Access type
     */
    public static function getUserAccessType(int $user_id = 0): string {
        if ($user_id === 0) {
            $user_id = get_current_user_id();
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return 'none';
        }

        // Check roles in priority order
        if (in_array('administrator', $user->roles)) {
            return 'admin';
        }

        if (in_array('customer_admin', $user->roles)) {
            return 'customer_admin';
        }

        if (in_array('customer_branch_admin', $user->roles)) {
            return 'customer_branch_admin';
        }

        if (in_array('customer_employee', $user->roles)) {
            return 'customer_employee';
        }

        return 'none';
    }

    /**
     * Check if user has specific role
     *
     * @param string $role_slug Role slug to check
     * @param int $user_id User ID (0 for current user)
     * @return bool True if user has the role
     */
    public static function userHasRole(string $role_slug, int $user_id = 0): bool {
        if ($user_id === 0) {
            $user_id = get_current_user_id();
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        return in_array($role_slug, $user->roles);
    }
}
