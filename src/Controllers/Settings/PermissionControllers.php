<?php
/**
 * Permission Controller Class
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Settings/PermissionController.php
 *
 * Description: Controller untuk mengelola permission updates via AJAX
 */

namespace WPCustomer\Controllers\Settings;

use WPCustomer\Models\Settings\PermissionModel;

class PermissionController {
    private $permission_model;
    private $log_file;

    public function __construct() {
        $this->permission_model = new PermissionModel();
        
        // Register AJAX handlers
        add_action('wp_ajax_update_wp_customer_permissions', [$this, 'handlePermissionUpdate']);
    }

    public function handlePermissionUpdate() {
        try {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('=== Permission Update Request ===');
                error_log(print_r($_POST, true));
            }

            // Verify nonce
            check_ajax_referer('wp_customer_permissions', 'security');

            // Check admin capability
            if (!current_user_can('manage_options')) {
                throw new \Exception(__('Insufficient permissions', 'wp-customer'));
            }

            $current_tab = isset($_POST['current_tab']) ? sanitize_key($_POST['current_tab']) : 'customer';
            $capability_groups = $this->permission_model->getCapabilityGroups();

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Current Tab: ' . $current_tab);
                error_log('Capability Groups:');
                error_log(print_r($capability_groups, true));
            }

            // Only process capabilities from current tab
            $current_tab_caps = isset($capability_groups[$current_tab]['caps']) ? 
                              $capability_groups[$current_tab]['caps'] : 
                              [];

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Current Tab Capabilities:');
                error_log(print_r($current_tab_caps, true));
            }

            // Handle reset request
            if (isset($_POST['reset_permissions']) && $_POST['reset_permissions']) {
                $this->handleReset($current_tab);
                return;
            }

            // Handle regular update
            $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
            $roles = wp_roles();

            foreach ($roles->role_names as $role_name => $role_display_name) {
                if ($role_name === 'administrator') {
                    continue;
                }

                $role = get_role($role_name);
                if (!$role) continue;

                // Only update capabilities from current tab
                foreach ($current_tab_caps as $cap) {
                    $has_cap = isset($permissions[$role_name][$cap]);
                    
                    // Only update if there's a change
                    if ($role->has_cap($cap) !== $has_cap) {
                        if ($has_cap) {
                            $role->add_cap($cap);
                        } else {
                            $role->remove_cap($cap);
                        }
                    }
                }
            }

            wp_send_json_success([
                'message' => sprintf(
                    __('Hak akses %s berhasil diperbarui', 'wp-customer'),
                    $capability_groups[$current_tab]['title']
                )
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    private function handleReset($current_tab) {
        try {
            $capability_groups = $this->permission_model->getCapabilityGroups();
            
            if (!isset($capability_groups[$current_tab])) {
                throw new \Exception(__('Invalid tab specified', 'wp-customer'));
            }

            // Get default capabilities for current tab
            $default_caps = $capability_groups[$current_tab]['caps'];
            
            // Reset capabilities for all roles except administrator
            $roles = wp_roles();
            foreach ($roles->role_names as $role_name => $role_display_name) {
                if ($role_name === 'administrator') continue;
                
                $role = get_role($role_name);
                if (!$role) continue;

                // Remove all capabilities from current tab
                foreach ($default_caps as $cap) {
                    $role->remove_cap($cap);
                }

                // Add back default capabilities if this is the default role for this tab
                if ($role_name === $current_tab) {
                    foreach ($default_caps as $cap) {
                        $role->add_cap($cap);
                    }
                }
            }

            wp_send_json_success([
                'message' => sprintf(
                    __('Hak akses %s berhasil direset ke default', 'wp-customer'),
                    $capability_groups[$current_tab]['title']
                )
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}
