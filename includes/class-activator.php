<?php
/**
 * File: class-activator.php
 * Path: /wp-customer/includes/class-activator.php
 * Description: Handles plugin activation and database installation
 * 
 * @package     WP_Customer
 * @subpackage  Includes
 * @version     1.0.12
 * @author      arisciwek
 * 
 * Description: Menangani proses aktivasi plugin dan instalasi database.
 *              Termasuk di dalamnya:
 *              - Instalasi tabel database melalui Database\Installer
 *              - Menambahkan versi plugin ke options table
 *              - Setup permission dan capabilities
 * 
 * Dependencies:
 * - WPCustomer\Database\Installer untuk instalasi database
 * - WPCustomer\Models\Settings\PermissionModel untuk setup capabilities
 * - WordPress Options API
 * 
 * Changelog:
 * 1.0.12 - 2025-11-02
 * - Added user/role cache clearing after adding capabilities
 * - Ensures capabilities load immediately without re-login required
 * - Fixes issue where users need to reset permissions manually after activation
 *
 * 1.0.1 - 2024-01-07
 * - Refactored database installation to use Database\Installer
 * - Enhanced error handling
 * - Added dependency management
 * 
 * 1.0.0 - 2024-11-23
 * - Initial creation
 * - Added activation handling
 * - Added version management
 * - Added permissions setup
 */
use WPCustomer\Models\Settings\PermissionModel;
use WPCustomer\Database\Installer;

// Load RoleManager
require_once WP_CUSTOMER_PATH . 'includes/class-role-manager.php';

class WP_Customer_Activator {
    private static function logError($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("WP_Customer_Activator Error: {$message}");
        }
    }

    public static function activate() {
        try {
            // Load textdomain first
            load_textdomain('wp-customer', WP_CUSTOMER_PATH . 'languages/wp-customer-id_ID.mo');

            // 1. Run database installation first
            $installer = new Installer();
            if (!$installer->run()) {
                self::logError('Failed to install database tables');
                return;
            }

            // 2. Create roles if they don't exist
            $all_roles = WP_Customer_Role_Manager::getRoles();

            foreach ($all_roles as $role_slug => $role_name) {
                if (!get_role($role_slug)) {
                    add_role(
                        $role_slug,
                        $role_name,
                        [] // Start with empty capabilities
                    );
                }
            }

            // 3. Now initialize permission model and add capabilities
            try {
                $permission_model = new PermissionModel();
                $permission_model->addCapabilities(); // This will add caps to both admin and customer roles

                // 3.1 Clear WordPress user/role caches to ensure capabilities load immediately
                // This ensures logged-in users see new capabilities without re-login
                global $wpdb;

                // Clear all user meta cache (forces WordPress to reload capabilities)
                wp_cache_delete('alloptions', 'options');

                // Clear user meta cache for all users
                $user_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->users}");
                foreach ($user_ids as $user_id) {
                    clean_user_cache($user_id);
                    wp_cache_delete($user_id, 'users');
                    wp_cache_delete($user_id, 'user_meta');
                }

            } catch (\Exception $e) {
                self::logError('Error adding capabilities: ' . $e->getMessage());
            }

            // 4. Continue with rest of activation (demo data, version, etc)
            self::addVersion();
            self::setupMembershipDefaults();

            // Add rewrite rules
            add_rewrite_rule(
                'customer-register/?$',
                'index.php?wp_customer_register=1',
                'top'
            );

            // Flush rewrite rules
            flush_rewrite_rules();

        } catch (\Exception $e) {
            self::logError('Critical error during activation: ' . $e->getMessage());
            throw $e;
        }
    }

    // Tambahkan metode baru ini
    private static function setupMembershipDefaults() {
        try {
            // Periksa apakah settings sudah ada
            if (!get_option('wp_customer_membership_settings')) {
                $default_settings = [
                    'regular_max_staff' => 2,
                    'regular_can_add_staff' => true,
                    'regular_can_export' => false,
                    'regular_can_bulk_import' => false,
                    
                    'priority_max_staff' => 5,
                    'priority_can_add_staff' => true,
                    'priority_can_export' => true,
                    'priority_can_bulk_import' => false,
                    
                    'utama_max_staff' => -1,
                    'utama_can_add_staff' => true,
                    'utama_can_export' => true,
                    'utama_can_bulk_import' => true,
                    
                    'default_level' => 'regular'
                ];

                add_option('wp_customer_membership_settings', $default_settings);
            }
        } catch (\Exception $e) {
            self::logError('Error setting up membership defaults: ' . $e->getMessage());
        }
    }

    private static function addVersion() {
        add_option('wp_customer_version', WP_CUSTOMER_VERSION);
    }

    /**
     * Get all available roles with their display names
     * DEPRECATED: Use WP_Customer_Role_Manager::getRoles() instead
     *
     * @deprecated 1.0.2 Use WP_Customer_Role_Manager::getRoles()
     * @return array Array of role_slug => role_name pairs
     */
    public static function getRoles(): array {
        return WP_Customer_Role_Manager::getRoles();
    }
}
