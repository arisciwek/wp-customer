<?php
/**
 * Permission Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models/Settings
 * @version     1.0.13
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Settings/PermissionModel.php
 *
 * Description: Model untuk mengelola hak akses plugin
 *
 * Changelog:
 * 1.0.13 - 2025-10-30
 * - CRITICAL FIX: Adopted clean reset pattern from wp-agency and wp-app-core
 * - Added: getDefaultCapabilitiesForRole() method for role-specific defaults
 * - Improved: resetToDefault() using RoleManager::isPluginRole() check
 * - Fixed: Reset now only affects customer roles + administrator
 * - Fixed: No longer touches agency/platform/other plugin roles
 * - Changed: Use WP_Customer_Role_Manager for all role operations
 * - Security: Prevents accidental permission removal from other plugins
 *
 * 1.0.12 - 2025-10-29
 * - Added 'wp_agency' tab to permission matrix for cross-plugin integration
 * - WP Agency view access capabilities now manageable via UI
 * - Updated getDisplayedCapabiities() to include wp_agency capabilities
 *
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
        'pay_all_customer_membership_invoices' => 'Bayar Semua Invoice Membership Customer termasuk Customer Lain',
        'pay_own_customer_membership_invoices' => 'Bayar Invoice Membership Customer Sendiri',
        'pay_own_branch_membership_invoices' => 'Bayar Invoice Membership Cabang Sendiri'
    ];

    // Define base capabilities untuk setiap role beserta nilai default-nya
    private $displayed_capabilities_in_tabs = [
        'wp_agency' => [
            'title' => 'WP Agency',
            'description' => 'WP Agency - View Access Permissions',
            'caps' => [
                // WP Agency Plugin - View Access (required for cross-plugin integration)
                'view_agency_list',
                'view_agency_detail',
                'view_division_list',
                'view_division_detail',
                'view_employee_list',
                'view_employee_detail'
            ]
        ],
        'customer' => [
            'title' => 'Customer',
            'description' => 'Customer Permissions',
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
            'title' => 'Branch',
            'description' => 'Branch Permissions',
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
            'title' => 'Employee',
            'description' => 'Employee Permissions',
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
            'title' => 'Membership Invoice',
            'description' => 'Membership Invoice Permissions',
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
            'title' => 'Invoice Payment',
            'description' => 'Membership Invoice Payment Permissions',
            'caps' => [
                'pay_all_customer_membership_invoices',
                'pay_own_customer_membership_invoices',
                'pay_own_branch_membership_invoices'
            ]
        ]
    ];

    private function getDisplayedCapabiities(): array{
       return array_merge(
            $this->displayed_capabilities_in_tabs['wp_agency']['caps'],
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

    /**
     * Get capability descriptions for tooltips/help text
     *
     * @return array Associative array of capability => description
     */
    public function getCapabilityDescriptions(): array {
        return [
            // WP Agency Plugin - View Access
            'view_agency_list' => __('Memungkinkan melihat daftar semua agency', 'wp-customer'),
            'view_agency_detail' => __('Memungkinkan melihat detail informasi agency', 'wp-customer'),
            'view_division_list' => __('Memungkinkan melihat daftar semua unit kerja', 'wp-customer'),
            'view_division_detail' => __('Memungkinkan melihat detail informasi unit kerja', 'wp-customer'),
            'view_employee_list' => __('Memungkinkan melihat daftar semua pegawai agency', 'wp-customer'),
            'view_employee_detail' => __('Memungkinkan melihat detail informasi pegawai agency', 'wp-customer'),

            // Customer capabilities
            'view_customer_list' => __('Memungkinkan melihat daftar semua customer dalam format tabel', 'wp-customer'),
            'view_customer_detail' => __('Memungkinkan melihat detail informasi customer', 'wp-customer'),
            'view_own_customer' => __('Memungkinkan melihat customer yang ditugaskan ke pengguna', 'wp-customer'),
            'add_customer' => __('Memungkinkan menambahkan data customer baru', 'wp-customer'),
            'edit_all_customers' => __('Memungkinkan mengedit semua data customer', 'wp-customer'),
            'edit_own_customer' => __('Memungkinkan mengedit hanya customer yang ditugaskan', 'wp-customer'),
            'delete_customer' => __('Memungkinkan menghapus data customer', 'wp-customer'),

            // Branch capabilities
            'view_customer_branch_list' => __('Memungkinkan melihat daftar semua cabang', 'wp-customer'),
            'view_customer_branch_detail' => __('Memungkinkan melihat detail informasi cabang', 'wp-customer'),
            'view_own_customer_branch' => __('Memungkinkan melihat cabang yang ditugaskan', 'wp-customer'),
            'add_customer_branch' => __('Memungkinkan menambahkan data cabang baru', 'wp-customer'),
            'edit_all_customer_branches' => __('Memungkinkan mengedit semua data cabang', 'wp-customer'),
            'edit_own_customer_branch' => __('Memungkinkan mengedit hanya cabang yang ditugaskan', 'wp-customer'),
            'delete_customer_branch' => __('Memungkinkan menghapus data cabang', 'wp-customer'),

            // Employee capabilities
            'view_customer_employee_list' => __('Memungkinkan melihat daftar semua karyawan', 'wp-customer'),
            'view_customer_employee_detail' => __('Memungkinkan melihat detail informasi karyawan', 'wp-customer'),
            'view_own_customer_employee' => __('Memungkinkan melihat karyawan yang ditugaskan', 'wp-customer'),
            'add_customer_employee' => __('Memungkinkan menambahkan data karyawan baru', 'wp-customer'),
            'edit_all_customer_employees' => __('Memungkinkan mengedit semua data karyawan', 'wp-customer'),
            'edit_own_customer_employee' => __('Memungkinkan mengedit hanya karyawan yang ditugaskan', 'wp-customer'),
            'delete_customer_employee' => __('Memungkinkan menghapus data karyawan', 'wp-customer'),

            // Membership Invoice capabilities
            'view_customer_membership_invoice_list' => __('Memungkinkan melihat daftar semua invoice membership', 'wp-customer'),
            'view_customer_membership_invoice_detail' => __('Memungkinkan melihat detail informasi invoice membership', 'wp-customer'),
            'view_own_customer_membership_invoice' => __('Memungkinkan melihat invoice membership yang ditugaskan', 'wp-customer'),
            'create_customer_membership_invoice' => __('Memungkinkan membuat invoice membership baru', 'wp-customer'),
            'edit_all_customer_membership_invoices' => __('Memungkinkan mengedit semua invoice membership', 'wp-customer'),
            'edit_own_customer_membership_invoice' => __('Memungkinkan mengedit invoice membership yang ditugaskan', 'wp-customer'),
            'delete_customer_membership_invoice' => __('Memungkinkan menghapus invoice membership', 'wp-customer'),
            'approve_customer_membership_invoice' => __('Memungkinkan menyetujui invoice membership', 'wp-customer'),

            // Membership Invoice Payment capabilities
            'pay_all_customer_membership_invoices' => __('Memungkinkan membayar semua invoice membership customer termasuk customer lain', 'wp-customer'),
            'pay_own_customer_membership_invoices' => __('Memungkinkan membayar invoice membership customer sendiri', 'wp-customer'),
            'pay_own_branch_membership_invoices' => __('Memungkinkan membayar invoice membership cabang sendiri', 'wp-customer'),
        ];
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

        // Add capabilities to platform roles from wp-app-core
        // Platform roles should have full view/manage access to customer data
        $platform_roles = ['platform_support', 'platform_admin', 'platform_super_admin', 'platform_analyst'];

        foreach ($platform_roles as $role_slug) {
            $role = get_role($role_slug);
            if ($role) {
                // Platform Support - View access to all customer data
                if ($role_slug === 'platform_support') {
                    $platform_caps = [
                        'view_customer_list' => true,
                        'view_customer_detail' => true,
                        'view_customer_branch_list' => true,
                        'view_customer_branch_detail' => true,
                        'view_customer_employee_list' => true,
                        'view_customer_employee_detail' => true,
                        'view_customer_membership_invoice_list' => true,
                        'view_customer_membership_invoice_detail' => true,
                    ];

                    foreach ($platform_caps as $cap => $enabled) {
                        if ($enabled) {
                            $role->add_cap($cap);
                        }
                    }
                }

                // Platform Admin - Full access except delete
                if ($role_slug === 'platform_admin') {
                    foreach (array_keys($this->available_capabilities) as $cap) {
                        // Skip delete capabilities
                        if (strpos($cap, 'delete_') === false) {
                            $role->add_cap($cap);
                        }
                    }
                }

                // Platform Super Admin - Full access to everything
                if ($role_slug === 'platform_super_admin') {
                    foreach (array_keys($this->available_capabilities) as $cap) {
                        $role->add_cap($cap);
                    }
                }

                // Platform Analyst - View only access
                if ($role_slug === 'platform_analyst') {
                    foreach (array_keys($this->available_capabilities) as $cap) {
                        if (strpos($cap, 'view_') === 0) {
                            $role->add_cap($cap);
                        }
                    }
                }
            }
        }
    }

    public function resetToDefault(): bool {
        global $wpdb;

        try {
            error_log('[CustomerPermissionModel] resetToDefault() START - Using direct DB manipulation');

            // CRITICAL: Increase execution limits
            $old_time_limit = ini_get('max_execution_time');
            @set_time_limit(120);
            error_log('[CustomerPermissionModel] Time limit set to 120 seconds');

            // Require Role Manager
            require_once WP_CUSTOMER_PATH . 'includes/class-role-manager.php';

            // Get WordPress roles option from database
            $wp_user_roles = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = '{$wpdb->prefix}user_roles'");
            $roles = maybe_unserialize($wp_user_roles);
            error_log('[CustomerPermissionModel] Retrieved ' . count($roles) . ' roles from database');

            $modified = false;

            foreach ($roles as $role_name => $role_data) {
                error_log('[CustomerPermissionModel] Processing role: ' . $role_name);

                // Only process customer roles + administrator
                $is_customer_role = \WP_Customer_Role_Manager::isPluginRole($role_name);
                $is_admin = $role_name === 'administrator';

                if (!$is_customer_role && !$is_admin) {
                    error_log('[CustomerPermissionModel] Skipping ' . $role_name);
                    continue;
                }

                // Remove all customer capabilities
                error_log('[CustomerPermissionModel] Removing customer capabilities from ' . $role_name);
                foreach (array_keys($this->available_capabilities) as $cap) {
                    if (isset($roles[$role_name]['capabilities'][$cap])) {
                        unset($roles[$role_name]['capabilities'][$cap]);
                        $modified = true;
                    }
                }

                // Add capabilities back
                if ($role_name === 'administrator') {
                    error_log('[CustomerPermissionModel] Adding all capabilities to administrator');
                    foreach (array_keys($this->available_capabilities) as $cap) {
                        $roles[$role_name]['capabilities'][$cap] = true;
                        $modified = true;
                    }
                } else if ($is_customer_role) {
                    error_log('[CustomerPermissionModel] Adding default capabilities to ' . $role_name);
                    // Add read capability
                    $roles[$role_name]['capabilities']['read'] = true;

                    // Add default capabilities
                    $default_caps = $this->getDefaultCapabilitiesForRole($role_name);
                    foreach ($default_caps as $cap => $enabled) {
                        if ($enabled && isset($this->available_capabilities[$cap])) {
                            $roles[$role_name]['capabilities'][$cap] = true;
                            $modified = true;
                        }
                    }
                }
                error_log('[CustomerPermissionModel] Completed processing ' . $role_name);
            }

            // Save back to database if modified
            if ($modified) {
                error_log('[CustomerPermissionModel] Saving modified roles to database');
                $updated = update_option($wpdb->prefix . 'user_roles', $roles);
                error_log('[CustomerPermissionModel] Database update result: ' . ($updated ? 'SUCCESS' : 'NO CHANGE'));
            }

            error_log('[CustomerPermissionModel] All roles processed successfully');
            error_log('[CustomerPermissionModel] resetToDefault() END - returning TRUE');

            // Restore time limit
            @set_time_limit($old_time_limit);

            return true;

        } catch (\Exception $e) {
            error_log('[CustomerPermissionModel] EXCEPTION in resetToDefault(): ' . $e->getMessage());
            error_log('[CustomerPermissionModel] Stack trace: ' . $e->getTraceAsString());

            // Restore time limit
            if (isset($old_time_limit)) {
                @set_time_limit($old_time_limit);
            }

            error_log('[CustomerPermissionModel] resetToDefault() END - returning FALSE');
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

    /**
     * Get default capabilities for a specific customer role
     *
     * @param string $role_slug Role slug
     * @return array Array of capability => bool pairs
     */
    private function getDefaultCapabilitiesForRole(string $role_slug): array {
        $defaults = [
            'customer' => [
                'read' => true
                // Customer base role has no customer management capabilities by default
                // Only has 'read' for wp-admin access
            ],
            'customer_admin' => [
                'read' => true,
                // WP Agency Plugin - View Access (filtered by related agencies)
                'view_agency_list' => true,
                'view_agency_detail' => true,
                'view_division_list' => true,
                'view_division_detail' => true,
                'view_employee_list' => true,
                'view_employee_detail' => true,

                // Customer capabilities - owner manages their customer
                'view_customer_list' => true,
                'view_customer_detail' => true,
                'view_own_customer' => true,
                'add_customer' => false,
                'edit_own_customer' => true,
                'edit_all_customers' => false,
                'delete_customer' => false,

                // Branch capabilities - full access to their branches
                'view_customer_branch_list' => true,
                'view_customer_branch_detail' => true,
                'view_own_customer_branch' => true,
                'add_customer_branch' => true,
                'edit_all_customer_branches' => true,
                'edit_own_customer_branch' => true,
                'delete_customer_branch' => false,

                // Employee capabilities - full access to their employees
                'view_customer_employee_list' => true,
                'view_customer_employee_detail' => true,
                'view_own_customer_employee' => true,
                'add_customer_employee' => true,
                'edit_all_customer_employees' => true,
                'edit_own_customer_employee' => true,
                'delete_customer_employee' => false,

                // Membership Invoice capabilities - full access for their branches
                'view_customer_membership_invoice_list' => true,
                'view_customer_membership_invoice_detail' => true,
                'view_own_customer_membership_invoice' => true,
                'create_customer_membership_invoice' => true,
                'edit_all_customer_membership_invoices' => true,
                'edit_own_customer_membership_invoice' => true,
                'delete_customer_membership_invoice' => false,
                'approve_customer_membership_invoice' => false,

                // Membership Invoice Payment capabilities
                'pay_all_customer_membership_invoices' => false,
                'pay_own_customer_membership_invoices' => true,
                'pay_own_branch_membership_invoices' => false
            ],
            'customer_branch_admin' => [
                'read' => true,
                // WP Agency Plugin - View Access (filtered by related agencies)
                'view_agency_list' => true,
                'view_agency_detail' => true,
                'view_division_list' => true,
                'view_division_detail' => true,
                'view_employee_list' => true,
                'view_employee_detail' => true,

                // Customer capabilities - can view parent customer
                'view_customer_list' => true,
                'view_customer_detail' => true,
                'view_own_customer' => true,
                'add_customer' => false,
                'edit_own_customer' => false,
                'edit_all_customers' => false,
                'delete_customer' => false,

                // Branch capabilities - manages only their branch
                'view_customer_branch_list' => true,
                'view_customer_branch_detail' => true,
                'view_own_customer_branch' => true,
                'add_customer_branch' => false,
                'edit_all_customer_branches' => false,
                'edit_own_customer_branch' => true,
                'delete_customer_branch' => false,

                // Employee capabilities - manages employees in their branch
                'view_customer_employee_list' => true,
                'view_customer_employee_detail' => true,
                'view_own_customer_employee' => true,
                'add_customer_employee' => true,
                'edit_all_customer_employees' => false,
                'edit_own_customer_employee' => true,
                'delete_customer_employee' => true,

                // Membership Invoice capabilities - limited to their branch
                'view_customer_membership_invoice_list' => true,
                'view_customer_membership_invoice_detail' => true,
                'view_own_customer_membership_invoice' => true,
                'create_customer_membership_invoice' => true,
                'edit_all_customer_membership_invoices' => false,
                'edit_own_customer_membership_invoice' => true,
                'delete_customer_membership_invoice' => false,
                'approve_customer_membership_invoice' => false,

                // Membership Invoice Payment capabilities
                'pay_all_customer_membership_invoices' => false,
                'pay_own_customer_membership_invoices' => false,
                'pay_own_branch_membership_invoices' => true
            ],
            'customer_employee' => [
                'read' => true,
                // WP Agency Plugin - View Access (filtered by related agencies)
                'view_agency_list' => true,
                'view_agency_detail' => true,
                'view_division_list' => true,
                'view_division_detail' => true,
                'view_employee_list' => true,
                'view_employee_detail' => true,

                // Customer capabilities - view only
                'view_customer_list' => true,
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
                'edit_own_customer_branch' => false,
                'delete_customer_branch' => false,

                // Employee capabilities - view only
                'view_customer_employee_list' => true,
                'view_customer_employee_detail' => true,
                'view_own_customer_employee' => true,
                'add_customer_employee' => false,
                'edit_all_customer_employees' => false,
                'edit_own_customer_employee' => false,
                'delete_customer_employee' => false,

                // Membership Invoice capabilities - view only
                'view_customer_membership_invoice_list' => true,
                'view_customer_membership_invoice_detail' => true,
                'view_own_customer_membership_invoice' => true,
                'create_customer_membership_invoice' => false,
                'edit_all_customer_membership_invoices' => false,
                'edit_own_customer_membership_invoice' => false,
                'delete_customer_membership_invoice' => false,
                'approve_customer_membership_invoice' => false,

                // Membership Invoice Payment capabilities - cannot pay
                'pay_all_customer_membership_invoices' => false,
                'pay_own_customer_membership_invoices' => false,
                'pay_own_branch_membership_invoices' => false
            ],
        ];

        return $defaults[$role_slug] ?? [];
    }
}
