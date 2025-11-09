<?php
/**
 * File: MenuManager.php
 * Path: /wp-customer/src/Controllers/MenuManager.php
 *
 * @package     WP_Customer
 * @subpackage  Admin/Controllers
 * @version     1.0.11
 * @author      arisciwek
 */

namespace WPCustomer\Controllers;

use WPCustomer\Controllers\SettingsController;
use WPCustomer\Controllers\Customer\CustomerController;
use WPCustomer\Controllers\Customer\CustomerDashboardController;
use WPCustomer\Controllers\Company\CompanyController;
use WPCustomer\Controllers\Company\CompanyDashboardController;
use WPCustomer\Controllers\Company\CompanyInvoiceController;
use WPCustomer\Controllers\Company\CompanyInvoiceDashboardController;

class MenuManager {
    private $plugin_name;
    private $version;
    private $settings_controller;
    private $customer_controller;
    private $customer_dashboard_controller;  // CustomerDashboardController for DualPanel
    private $company_controller;  // CompanyController (OLD - will be deprecated)
    private $company_dashboard_controller;  // CompanyDashboardController for DualPanel
    // private $company_invoice_controller;  // CompanyInvoiceController (instantiated in wp-customer.php, not here)
    private $company_invoice_dashboard_controller;  // CompanyInvoiceDashboardController for DualPanel (NEW)

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->settings_controller = new SettingsController();
        $this->customer_controller = new CustomerController();
        $this->customer_dashboard_controller = new CustomerDashboardController();
        $this->company_controller = new CompanyController();  // OLD - keeping for reference
        $this->company_dashboard_controller = new CompanyDashboardController();  // NEW: DualPanel
        // $this->company_invoice_controller = new CompanyInvoiceController();  // Already instantiated in wp-customer.php
        $this->company_invoice_dashboard_controller = new CompanyInvoiceDashboardController();  // NEW: DualPanel (TODO-2196)
    }

    public function init() {
        add_action('admin_menu', [$this, 'registerMenus']);
        $this->settings_controller->init();
    }

    public function registerMenus() {
        // Menu WP Customer (wp-datatable DualPanel - refactored from V2)
        add_menu_page(
            __('WP Customer', 'wp-customer'),
            __('WP Customer', 'wp-customer'),
            'view_customer_list',
            'wp-customer',
            [$this->customer_dashboard_controller, 'render'],
            'dashicons-businessperson',
            30
        );

        // Menu WP Perusahaan (wp-datatable DualPanel - refactored)
        add_menu_page(
            __('WP Perusahaan', 'wp-customer'),
            __('WP Perusahaan', 'wp-customer'),
            'view_customer_branch_list',
            'perusahaan',
            [$this->company_dashboard_controller, 'render'],
            'dashicons-building',
            31
        );

        // Menu WP Invoice Membership (wp-datatable DualPanel - TODO-2196)
        add_menu_page(
            __('Invoice Membership', 'wp-customer'),
            __('Invoice Membership', 'wp-customer'),
            'view_customer_membership_invoice_list',  // Invoice-specific capability
            'company-invoices',  // NEW: Changed slug for DualPanel
            [$this->company_invoice_dashboard_controller, 'render'],
            'dashicons-media-spreadsheet',
            32
        );

        // Submenu Settings
        add_submenu_page(
            'wp-customer',
            __('Pengaturan', 'wp-customer'),
            __('Pengaturan', 'wp-customer'),
            'manage_options',
            'wp-customer-settings',
            [$this->settings_controller, 'renderPage']
        );
    }
}
