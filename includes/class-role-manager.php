<?php
/**
 * Role Manager Class
 *
 * @package     WP_Customer
 * @subpackage  Includes
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-customer/includes/class-role-manager.php
 *
 * Description: Centralized role management for WP Customer plugin.
 *              Single source of truth untuk role definitions.
 *              Accessible untuk plugin lain dan internal components.
 *
 * Usage:
 * - Get all roles: WP_Customer_Role_Manager::getRoles()
 * - Get role slugs: WP_Customer_Role_Manager::getRoleSlugs()
 * - Check if role exists: WP_Customer_Role_Manager::roleExists($slug)
 *
 * Changelog:
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
}
