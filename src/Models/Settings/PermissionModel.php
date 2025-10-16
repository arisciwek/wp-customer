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
    private $available_capabilities = [
        // Customer capabilities
        'view_customer_list' => 'Lihat Daftar Customer',
        'view_customer_detail' => 'Lihat Detail Customer',
        'view_own_customer' => 'Lihat Customer Sendiri',
        'add_customer' => 'Tambah Customer',
        'edit_all_customers' => 'Edit Semua Customer',
        'edit_own_customer' => 'Edit Customer Sendiri',
        'delete_customer' => 'Hapus Customer',

        // Branch capabilities
        'view_customer_branch_list' => 'Lihat Daftar Cabang',
        'view_customer_branch_detail' => 'Lihat Detail Cabang',
        'view_own_customer_branch' => 'Lihat Cabang Sendiri',
        'add_customer_branch' => 'Tambah Cabang',
        'edit_all_customer_branches' => 'Edit Semua Cabang',
        'edit_own_customer_branch' => 'Edit Cabang Sendiri',
        'delete_customer_branch' => 'Hapus Cabang',

        // Employee capabilities
        'view_customer_employee_list' => 'Lihat Daftar Karyawan',
        'view_customer_employee_detail' => 'Lihat Detail Karyawan',
        'view_own_customer_employee' => 'Lihat Karyawan Sendiri',
        'add_customer_employee' => 'Tambah Karyawan',
        'edit_all_customer_employees' => 'Edit Karyawan',
        'edit_own_customer_employee' => 'Edit Karyawan Sendiri',
        'delete_customer_employee' => 'Hapus Karyawan'
    ];

    // Define base capabilities untuk setiap role beserta nilai default-nya
    private $displayed_capabilities_in_tabs = [
        'customer' => [
            'title' => 'Customer Permissions',
            'caps' => [
                // Customer capabilities
                'view_customer_list',
                'view_own_customer', 
                'add_customer',
                'edit_own_customer',
                'edit_all_customers'
            ]
        ],
        'branch' => [
            'title' => 'Branch Permissions',
            'caps' => [
                'view_customer_branch_list',
                'view_customer_branch_detail',
                'view_own_customer_branch',
                'add_customer_branch',
                'edit_all_customer_branches',
                'edit_own_customer_branch',
                'delete_customer_branch'
            ]
        ],
        'employee' => [
            'title' => 'Employee Permissions',
            'caps' => [
                'view_customer_employee_list',
                'view_customer_employee_detail',
                'view_own_customer_employee',
                'add_customer_employee',
                'edit_all_customer_employees',
                'edit_own_customer_employee',
                'delete_customer_employee'
            ]
        ]
    ];

    private function getDisplayedCapabiities(): array{
       return array_merge(
            $this->displayed_capabilities_in_tabs['customer']['caps'],
            $this->displayed_capabilities_in_tabs['branch']['caps'],
            $this->displayed_capabilities_in_tabs['employee']['caps']
        );
    } 


    public function getAllCapabilities(): array {
        return $this->available_capabilities;
    }

    public function getCapabilityGroups(): array {
        return $this->displayed_capabilities_in_tabs;
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
            foreach (array_keys($this->available_capabilities) as $cap) {
                $admin->add_cap($cap);
            }
        }

        // Set customer role capabilities
        $customer = get_role('customer');
        if ($customer) {
            // Add 'read' capability - required for wp-admin access
            $customer->add_cap('read');

            $default_capabiities = [
                // Customer capabilities
                'view_customer_list' => true,
                'view_customer_detail' => true,
                'add_customer' => false,
                'view_own_customer' => true,
                'edit_own_customer' => false,
                'view_own_customer' => true,
                'delete_customer' => false,

                // Branch capabilities
                'add_customer_branch' => true,
                'view_customer_branch_list' => true,
                'view_customer_branch_detail' => true,
                'view_own_customer_branch' => true,
                'edit_own_customer_branch' => true,
                'delete_customer_branch' => false,

                // Employee capabilities
                'add_customer_employee' => true,
                'view_customer_employee_list' => true,
                'view_customer_employee_detail' => true,
                'view_own_customer_employee' => true,
                'edit_own_customer_employee' => true,
                'delete_customer_employee' => false
            ];

            foreach ($default_capabiities as $cap => $enabled) {
                if ($enabled) {
                    $customer->add_cap($cap);
                } else {
                    $customer->remove_cap($cap);
                }
            }
        }
    }

    public function resetToDefault(): bool {
        try {
            // Reset all roles to default
            foreach (get_editable_roles() as $role_name => $role_info) {
                $role = get_role($role_name);
                if (!$role) continue;

                // Remove all existing capabilities first
                foreach (array_keys($this->available_capabilities) as $cap) {
                    $role->remove_cap($cap);
                }

                // Administrator gets all capabilities
                if ($role_name === 'administrator') {
                    foreach (array_keys($this->available_capabilities) as $cap) {
                        $role->add_cap($cap);
                    }
                    continue;
                }

                // Customer role gets its specific default capabilities
                if ($role_name === 'customer') {
                    $this->addCapabilities(); // Gunakan method yang sudah ada
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

        // Get default caps for customer role
        $default_customer_caps = [];
        if ($role_name === 'customer') {
            $default_customer_caps = $this->displayed_capabilities_in_tabs['customer']['caps'];
        }

        // Reset existing capabilities while respecting defaults for customer
        foreach (array_keys($this->available_capabilities) as $cap) {
            if ($role_name === 'customer' && isset($default_customer_caps[$cap])) {
                // For customer role, keep default value
                if ($default_customer_caps[$cap]) {
                    $role->add_cap($cap);
                } else {
                    $role->remove_cap($cap);
                }
                continue;
            }
            $role->remove_cap($cap);
        }

        // Add new capabilities (only for non-customer roles or non-default capabilities)
        foreach ($capabilities as $cap => $enabled) {
            if ($enabled && isset($this->available_capabilities[$cap])) {
                if ($role_name !== 'customer' || !isset($default_customer_caps[$cap])) {
                    $role->add_cap($cap);
                }
            }
        }

        return true;
    }
}
