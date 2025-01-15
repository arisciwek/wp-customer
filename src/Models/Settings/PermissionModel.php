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
        'delete_branch' => 'Hapus Cabang',

        // Employee capabilities
        'view_employee_list' => 'Lihat Daftar Karyawan',
        'view_employee_detail' => 'Lihat Detail Karyawan', 
        'view_own_employee' => 'Lihat Karyawan Sendiri',
        'add_employee' => 'Tambah Karyawan',
        'edit_all_employees' => 'Edit Karyawan',
        'edit_own_employee' => 'Edit Karyawan Sendiri',
        'delete_employee' => 'Hapus Karyawan'        
    ];

    // Add default role capabilities array
    private $default_role_caps = [
        'customer' => [
            'title' => 'Customer Permissions',
            'caps' => [
                'view_customer_list',
                'view_own_customer',
                'add_customer',
                'edit_all_customers',
                'edit_own_customer'
            ]
        ],
        'branch' => [
            'title' => 'Branch Permissions',
            'caps' => [
                'view_branch_list',
                'view_branch_detail',
                'view_own_branch',
                'add_branch',
                'edit_all_branches',
                'edit_own_branch',
                'delete_branch'
            ]
        ],
        'employee' => [
            'title' => 'Employee Permissions',
            'caps' => [
                'view_employee_list',
                'view_employee_detail',
                'view_own_employee',
                'add_employee',
                'edit_all_employees',
                'edit_own_employee',
                'delete_employee'
            ]
        ]
    ];

    public function getAllCapabilities(): array {
        return $this->default_capabilities;
    }

    public function getCapabilityGroups(): array {
        return $this->default_role_caps;
    }

    public function roleHasCapability(string $role_name, string $capability): bool {
        $role = get_role($role_name);
        if (!$role) {
            error_log("Role not found: $role_name");
            return false;
        }
        return $role->has_cap($capability);
    }

    public function addCapabilities(): void {
        // Set administrator capabilities
        $admin = get_role('administrator');
        if ($admin) {
            foreach (array_keys($this->default_capabilities) as $cap) {
                $admin->add_cap($cap);
            }
        }

        // Set customer role capabilities
        $customer = get_role('customer');
        if ($customer) {
            foreach (array_keys($this->default_capabilities) as $cap) {
                $customer->remove_cap($cap);
            }
            foreach ($this->default_role_caps['customer']['caps'] as $cap) {
                $customer->add_cap($cap);
            }
        }
    }

    public function resetToDefault(): bool {
        try {
            // Reset all roles to default
            foreach (get_editable_roles() as $role_name => $role_info) {
                $role = get_role($role_name);
                if (!$role) continue;

                // Remove all existing capabilities
                foreach (array_keys($this->default_capabilities) as $cap) {
                    $role->remove_cap($cap);
                }

                // Administrator gets all capabilities
                if ($role_name === 'administrator') {
                    foreach (array_keys($this->default_capabilities) as $cap) {
                        $role->add_cap($cap);
                    }
                    continue;
                }

                // Customer role gets its specific capabilities
                if ($role_name === 'customer' && isset($this->default_role_caps['customer'])) {
                    foreach ($this->default_role_caps['customer']['caps'] as $cap) {
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
        foreach ($capabilities as $cap => $enabled) {
            if ($enabled && isset($this->default_capabilities[$cap])) {
                $role->add_cap($cap);
            }
        }

        return true;
    }
}
