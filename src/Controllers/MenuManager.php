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
use WPCustomer\Controllers\Company\CompanyInvoiceController;
use WPCustomer\Controllers\Companies\CompaniesController;

class MenuManager {
    private $plugin_name;
    private $version;
    private $settings_controller;
    private $customer_controller;
    private $customer_dashboard_controller;  // NEW: CustomerDashboardController for centralized DataTable
    private $company_controller;  // Tambah property untuk CompanyController
    private $company_invoice_controller;  // Tambah property untuk CompanyInvoiceController
    private $companies_controller;  // Tambah property untuk CompaniesController

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->settings_controller = new SettingsController();
        $this->customer_controller = new CustomerController();
        $this->customer_dashboard_controller = new CustomerDashboardController();  // NEW: Inisialisasi CustomerDashboardController
        $this->company_controller = new CompanyController();  // Inisialisasi CompanyController
        $this->company_invoice_controller = new CompanyInvoiceController();  // Inisialisasi CompanyInvoiceController
        $this->companies_controller = new CompaniesController();  // Inisialisasi CompaniesController
    }

    public function init() {
        add_action('admin_menu', [$this, 'registerMenus']);
        $this->settings_controller->init();
    }

    public function registerMenus() {
        // Menu WP Customer (OLD)
        add_menu_page(
            __('WP Customer', 'wp-customer'),
            __('WP Customer', 'wp-customer'),
            'view_customer_list',
            'wp-customer',
            [$this->customer_controller, 'renderMainPage'],
            'dashicons-businessperson',
            30
        );

        // Menu WP Customer V2 (NEW - wp-datatable DualPanel)
        add_menu_page(
            __('Customer V2', 'wp-customer'),
            __('Customer V2', 'wp-customer'),
            'view_customer_list',
            'wp-customer-v2',
            [$this->customer_dashboard_controller, 'render'],
            'dashicons-businessperson',
            30.5
        );

        // Menu WP Perusahaan
        add_menu_page(
            __('WP Perusahaan', 'wp-customer'),
            __('WP Perusahaan', 'wp-customer'),
            'view_customer_branch_list',
            'perusahaan',  // Unique menu slug untuk perusahaan
            [$this->company_controller, 'renderMainPage'],
            'dashicons-building',
            31
        );

        // Menu WP Invoice Membership
        add_menu_page(
            __('Invoice Membership', 'wp-customer'),
            __('Invoice Membership', 'wp-customer'),
            'view_customer_membership_invoice_list',
            'invoice_perusahaan',  // Unique menu slug untuk invoice perusahaan
            [$this->company_invoice_controller, 'render_page'],
            'dashicons-media-spreadsheet',
            32
        );

        // Menu Companies (Perusahaan-2)
        add_menu_page(
            __('Perusahaan-2', 'wp-customer'),
            __('🏢 Perusahaan-2', 'wp-customer'),
            'view_customer_branch_list',
            'wp-customer-companies',
            [$this->companies_controller, 'render_page'],
            'dashicons-store',
            33
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
