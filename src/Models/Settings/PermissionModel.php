<?php
/**
 * Permission Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models/Settings
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Settings/PermissionModel.php
 *
 * Description: Model untuk mengelola hak akses plugin
 *
 * Changelog:
 * 1.1.0 - 2024-12-08
 * - Added view_own_customer capability
 * - Updated default role capabilities for editor and author roles
 * - Added documentation for view_own_customer permission
 *
 * 1.0.0 - 2024-11-28
 * - Initial release
 * - Basic permission management
 * - Default capabilities setup
 */
namespace WPCustomer\Models\Settings;

class PermissionModel {
    private $default_capabilities = [
        // Customer capabilities
        'view_customer_list' => 'Lihat Daftar Customer',
        'view_customer_detail' => 'Lihat Detail Customer',
        'view_own_customer' => 'Lihat Customer Sendiri',
        'add_customer' => 'Tambah Customer',
        'edit_all_customers' => 'Edit Semua Customer',
        'edit_own_customer' => 'Edit Customer Sendiri',
        'delete_customer' => 'Hapus Customer',

        // Branch capabilities
        'view_branch_list' => 'Lihat Daftar Cabang',
        'view_branch_detail' => 'Lihat Detail Cabang',
        'view_own_branch' => 'Lihat Cabang Sendiri',
        'add_branch' => 'Tambah Cabang',
        'edit_all_branches' => 'Edit Semua Cabang',
        'edit_own_branch' => 'Edit Cabang Sendiri',
        'delete_branch' => 'Hapus Cabang'
    ];

    private $default_role_caps = [
        'editor' => [
            'view_customer_list',
            'view_customer_detail',
            'view_own_customer',
            'edit_own_customer',
            'view_branch_list',
            'view_branch_detail',
            'view_own_branch',
            'edit_own_branch'
        ],
        'author' => [
            'view_customer_list',
            'view_customer_detail',
            'view_own_customer',
            'view_branch_list',
            'view_branch_detail',
            'view_own_branch'
        ],
        'contributor' => [
            'view_own_customer',
            'view_own_branch'
        ]
    ];

    public function getAllCapabilities(): array {
        return $this->default_capabilities;
    }

    public function roleHasCapability(string $role_name, string $capability): bool {
        $role = get_role($role_name);
        if (!$role) {
            error_log("Role not found: $role_name");
            return false;
        }

        $has_cap = $role->has_cap($capability);
        return $has_cap;
    }

    public function updateRoleCapabilities(string $role_name, array $capabilities): bool {
        if ($role_name === 'administrator') {
            return false;
        }

        $role = get_role($role_name);
        if (!$role) {
            return false;
        }

        // Reset existing capabilities
        foreach (array_keys($this->default_capabilities) as $cap) {
            $role->remove_cap($cap);
        }

        // Add new capabilities
        foreach ($this->default_capabilities as $cap => $label) {
            if (isset($capabilities[$cap]) && $capabilities[$cap]) {
                $role->add_cap($cap);
            }
        }

        return true;
    }

    public function addCapabilities(): void {
        // Set administrator capabilities
        $admin = get_role('administrator');
        if ($admin) {
            foreach (array_keys($this->default_capabilities) as $cap) {
                $admin->add_cap($cap);
            }
        }

        // Set default role capabilities
        foreach ($this->default_role_caps as $role_name => $caps) {
            $role = get_role($role_name);
            if ($role) {
                // Reset capabilities first
                foreach (array_keys($this->default_capabilities) as $cap) {
                    $role->remove_cap($cap);
                }
                // Add default capabilities
                foreach ($caps as $cap) {
                    $role->add_cap($cap);
                }
            }
        }
    }

    public function resetToDefault(): bool {
    try {
        // Reset semua role ke default
        foreach (get_editable_roles() as $role_name => $role_info) {
            $role = get_role($role_name);
            if (!$role) continue;

            // Hapus semua capability yang ada
            foreach (array_keys($this->default_capabilities) as $cap) {
                $role->remove_cap($cap);
            }

            // Jika administrator, berikan semua capability
            if ($role_name === 'administrator') {
                foreach (array_keys($this->default_capabilities) as $cap) {
                    $role->add_cap($cap);
                }
                continue;
            }

            // Untuk role lain, berikan sesuai default jika ada
            if (isset($this->default_role_caps[$role_name])) {
                foreach ($this->default_role_caps[$role_name] as $cap) {
                    $role->add_cap($cap);
                }
            }
        }

        return true;

    } catch (\Exception $e) {
        error_log('Error resetting permissions: ' . $e->getMessage());
        return false;
    }
}
}
