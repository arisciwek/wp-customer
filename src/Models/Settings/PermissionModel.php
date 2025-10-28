<?php
/**
 * Permission Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models/Settings
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Settings/PermissionModel.php
 *
 * Description: Model untuk mengelola hak akses plugin
 *
 * Changelog:
 * 1.2.0 - 2025-01-16
 * - Added default capabilities for customer_admin role
 * - Added default capabilities for customer_branch_admin role
 * - Added default capabilities for customer_employee role
 * - Updated resetToDefault() to handle all customer roles
 * - Implemented hierarchical permission system (admin > customer_admin > branch_admin > employee)
 *
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
        // WP Agency Plugin - View Access (required for cross-plugin integration)
        // Customer employees need view access to agencies they work with
        'view_agency_list' => 'Lihat Daftar Agency',
        'view_agency_detail' => 'Lihat Detail Agency',
        'view_division_list' => 'Lihat Daftar Unit Kerja',
        'view_division_detail' => 'Lihat Detail Unit Kerja',
        'view_employee_list' => 'Lihat Daftar Pegawai Agency',
        'view_employee_detail' => 'Lihat Detail Pegawai Agency',

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
        'delete_customer_employee' => 'Hapus Karyawan',

        // Membership Invoice capabilities
        'view_customer_membership_invoice_list' => 'Lihat Daftar Invoice Membership',
        'view_customer_membership_invoice_detail' => 'Lihat Detail Invoice Membership',
        'view_own_customer_membership_invoice' => 'Lihat Invoice Membership Sendiri',
        'create_customer_membership_invoice' => 'Buat Invoice Membership',
        'edit_all_customer_membership_invoices' => 'Edit Semua Invoice Membership',
        'edit_own_customer_membership_invoice' => 'Edit Invoice Membership Sendiri',
        'delete_customer_membership_invoice' => 'Hapus Invoice Membership',
        'approve_customer_membership_invoice' => 'Approve Invoice Membership',

        // Membership Invoice Payment capabilities
        'pay_all_customer_membership_invoices' => 'Bayar Semua Invoice Membership Customer',
        'pay_own_customer_membership_invoices' => 'Bayar Invoice Membership Customer Sendiri',
        'pay_own_branch_membership_invoices' => 'Bayar Invoice Membership Cabang Sendiri'
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
        ],
        'membership_invoice' => [
            'title' => 'Membership Invoice Permissions',
            'caps' => [
                'view_customer_membership_invoice_list',
                'view_customer_membership_invoice_detail',
                'view_own_customer_membership_invoice',
                'create_customer_membership_invoice',
                'edit_all_customer_membership_invoices',
                'edit_own_customer_membership_invoice',
                'delete_customer_membership_invoice',
                'approve_customer_membership_invoice'
            ]
        ],
        'membership_invoice_payment' => [
            'title' => 'Membership Invoice Payment Permissions',
            'caps' => [
                'pay_all_customer_membership_invoices',
                'pay_own_customer_membership_invoices',
                'pay_own_branch_membership_invoices'
            ]
        ]
    ];

    private function getDisplayedCapabiities(): array{
       return array_merge(
            $this->displayed_capabilities_in_tabs['customer']['caps'],
            $this->displayed_capabilities_in_tabs['branch']['caps'],
            $this->displayed_capabilities_in_tabs['employee']['caps'],
            $this->displayed_capabilities_in_tabs['membership_invoice']['caps'],
            $this->displayed_capabilities_in_tabs['membership_invoice_payment']['caps']
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
            ///$customer->add_cap('read');

            $default_capabiities = [
                'read' => true
                /*
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
                'delete_customer_employee' => false,

                // Membership Invoice capabilities
                'view_customer_membership_invoice_list' => true,
                'view_customer_membership_invoice_detail' => true,
                'view_own_customer_membership_invoice' => true,
                'create_customer_membership_invoice' => false,
                'edit_all_customer_membership_invoices' => false,
                'edit_own_customer_membership_invoice' => false,
                'delete_customer_membership_invoice' => false,
                'approve_customer_membership_invoice' => false
                */
            ];

            foreach ($default_capabiities as $cap => $enabled) {
                if ($enabled) {
                    $customer->add_cap($cap);
                } else {
                    $customer->remove_cap($cap);
                }
            }
        }

        // Set customer_admin role capabilities
        // Customer Admin adalah owner dari customer, manages semua yang ada di bawah customer mereka
        // Note: 'read' capability inherited from base 'customer' role (dual-role pattern)
        $customer_admin = get_role('customer_admin');
        if ($customer_admin) {
            $default_capabilities = [
                // WP Agency Plugin - View Access (filtered by related agencies)
                'view_agency_list' => true,        // Can see agencies related to their branches
                'view_agency_detail' => true,      // Can see agency details (filtered)
                'view_division_list' => true,      // Can see divisions
                'view_division_detail' => true,    // Can see division details
                'view_employee_list' => true,      // Can see agency employees
                'view_employee_detail' => true,    // Can see employee details

                // Customer capabilities - owner manages their customer
                'view_customer_list' => true,
                'view_customer_detail' => true,
                'view_own_customer' => true,
                'add_customer' => false,           // Cannot create new customers
                'edit_own_customer' => true,       // Can edit their own customer
                'edit_all_customers' => false,     // Cannot edit other customers
                'delete_customer' => false,        // Cannot delete customers

                // Branch capabilities - full access to their branches
                'view_customer_branch_list' => true,
                'view_customer_branch_detail' => true,
                'view_own_customer_branch' => true,
                'add_customer_branch' => true,     // Can create branches under their customer
                'edit_all_customer_branches' => true,   // Can edit all branches under their customer
                'edit_own_customer_branch' => true,
                'delete_customer_branch' => false,  // Cannot delete branches under their customer

                // Employee capabilities - full access to their employees
                'view_customer_employee_list' => true,
                'view_customer_employee_detail' => true,
                'view_own_customer_employee' => true,
                'add_customer_employee' => true,   // Can hire employees
                'edit_all_customer_employees' => true,  // Can edit all employees under their customer
                'edit_own_customer_employee' => true,
                'delete_customer_employee' => false, // Cannot remove employees

                // Membership Invoice capabilities - full access for their branches
                'view_customer_membership_invoice_list' => true,
                'view_customer_membership_invoice_detail' => true,
                'view_own_customer_membership_invoice' => true,
                'create_customer_membership_invoice' => true,   // Can create invoices
                'edit_all_customer_membership_invoices' => true, // Can edit all invoices under their customer
                'edit_own_customer_membership_invoice' => true,
                'delete_customer_membership_invoice' => false,   // Cannot delete invoices
                'approve_customer_membership_invoice' => false,  // Cannot approve (needs higher authority)

                // Membership Invoice Payment capabilities - can pay all invoices under their customer
                'pay_all_customer_membership_invoices' => false,            // Cannot pay invoices from other customers
                'pay_own_customer_membership_invoices' => true,             // Can pay all invoices under their customer (all branches)
                'pay_own_branch_membership_invoices' => false               // This is for branch admin
            ];

            foreach ($default_capabilities as $cap => $enabled) {
                if ($enabled) {
                    $customer_admin->add_cap($cap);
                } else {
                    $customer_admin->remove_cap($cap);
                }
            }
        }

        // Set customer_branch_admin role capabilities
        // Branch Admin manages satu branch dan employee di branch tersebut
        // Note: 'read' capability inherited from base 'customer' role (dual-role pattern)
        $customer_branch_admin = get_role('customer_branch_admin');
        if ($customer_branch_admin) {
            $default_capabilities = [
                // WP Agency Plugin - View Access (filtered by related agencies)
                'view_agency_list' => true,        // Can see agencies related to their branch
                'view_agency_detail' => true,      // Can see agency details (filtered)
                'view_division_list' => true,      // Can see divisions
                'view_division_detail' => true,    // Can see division details
                'view_employee_list' => true,      // Can see agency employees
                'view_employee_detail' => true,    // Can see employee details

                // Customer capabilities - can view parent customer
                'view_customer_list' => true,      // Can see customer list (filtered to their customer)
                'view_customer_detail' => true,    // Can see their customer details
                'view_own_customer' => true,
                'add_customer' => false,           // Cannot create customers
                'edit_own_customer' => false,      // Cannot edit customer
                'edit_all_customers' => false,
                'delete_customer' => false,

                // Branch capabilities - manages only their branch
                'view_customer_branch_list' => true,
                'view_customer_branch_detail' => true,
                'view_own_customer_branch' => true,
                'add_customer_branch' => false,    // Cannot create new branches
                'edit_all_customer_branches' => false, // Cannot edit other branches
                'edit_own_customer_branch' => true,    // Can edit their own branch
                'delete_customer_branch' => false,     // Cannot delete branches

                // Employee capabilities - manages employees in their branch
                'view_customer_employee_list' => true,
                'view_customer_employee_detail' => true,
                'view_own_customer_employee' => true,
                'add_customer_employee' => true,       // Can hire employees for their branch
                'edit_all_customer_employees' => false, // Cannot edit all employees
                'edit_own_customer_employee' => true,   // Can edit employees in their branch
                'delete_customer_employee' => true,     // Can remove employees from their branch

                // Membership Invoice capabilities - limited to their branch
                'view_customer_membership_invoice_list' => true,
                'view_customer_membership_invoice_detail' => true,
                'view_own_customer_membership_invoice' => true,
                'create_customer_membership_invoice' => true,   // Can create invoices
                'edit_all_customer_membership_invoices' => false, // Cannot edit all invoices
                'edit_own_customer_membership_invoice' => true,   // Can edit invoices for their branch
                'delete_customer_membership_invoice' => false,    // Cannot delete invoices
                'approve_customer_membership_invoice' => false,   // Cannot approve invoices

                // Membership Invoice Payment capabilities - can pay only for their branch
                'pay_all_customer_membership_invoices' => false,             // Cannot pay all customer invoices
                'pay_own_customer_membership_invoices' => false,             // Cannot pay all branch invoices under customer
                'pay_own_branch_membership_invoices' => true                 // Can pay only invoices for their branch
            ];

            foreach ($default_capabilities as $cap => $enabled) {
                if ($enabled) {
                    $customer_branch_admin->add_cap($cap);
                } else {
                    $customer_branch_admin->remove_cap($cap);
                }
            }
        }

        // Set customer_employee role capabilities
        // Employee hanya bisa melihat informasi yang relevan dengan pekerjaan mereka
        // Note: 'read' capability inherited from base 'customer' role (dual-role pattern)
        $customer_employee = get_role('customer_employee');
        if ($customer_employee) {
            $default_capabilities = [
                // WP Agency Plugin - View Access (filtered by related agencies)
                'view_agency_list' => true,        // Can see agencies related to their branch
                'view_agency_detail' => true,      // Can see agency details (filtered)
                'view_division_list' => true,      // Can see divisions
                'view_division_detail' => true,    // Can see division details
                'view_employee_list' => true,      // Can see agency employees
                'view_employee_detail' => true,    // Can see employee details

                // Customer capabilities - view only
                'view_customer_list' => true,      // Can see customer (their employer)
                'view_customer_detail' => true,
                'view_own_customer' => true,
                'add_customer' => false,
                'edit_own_customer' => false,
                'edit_all_customers' => false,
                'delete_customer' => false,

                // Branch capabilities - view only their branch
                'view_customer_branch_list' => true,
                'view_customer_branch_detail' => true,
                'view_own_customer_branch' => true,
                'add_customer_branch' => false,
                'edit_all_customer_branches' => false,
                'edit_own_customer_branch' => false,   // Cannot edit branch
                'delete_customer_branch' => false,

                // Employee capabilities - view only
                'view_customer_employee_list' => true,
                'view_customer_employee_detail' => true,
                'view_own_customer_employee' => true,
                'add_customer_employee' => false,
                'edit_all_customer_employees' => false,
                'edit_own_customer_employee' => false,  // Cannot edit employees
                'delete_customer_employee' => false,

                // Membership Invoice capabilities - view only
                'view_customer_membership_invoice_list' => true,
                'view_customer_membership_invoice_detail' => true,
                'view_own_customer_membership_invoice' => true,
                'create_customer_membership_invoice' => false,
                'edit_all_customer_membership_invoices' => false,
                'edit_own_customer_membership_invoice' => false,  // Cannot edit invoices
                'delete_customer_membership_invoice' => false,
                'approve_customer_membership_invoice' => false,

                // Membership Invoice Payment capabilities - cannot pay
                'pay_all_customer_membership_invoices' => false,             // Cannot pay invoices
                'pay_own_customer_membership_invoices' => false,             // Cannot pay invoices
                'pay_own_branch_membership_invoices' => false                // Cannot pay invoices
            ];

            foreach ($default_capabilities as $cap => $enabled) {
                if ($enabled) {
                    $customer_employee->add_cap($cap);
                } else {
                    $customer_employee->remove_cap($cap);
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

                // Customer roles get their specific default capabilities
                if (in_array($role_name, ['customer', 'customer_admin', 'customer_branch_admin', 'customer_employee'])) {
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
