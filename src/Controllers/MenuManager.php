<?php
/**
 * File: MenuManager.php
 * Path: /wp-customer/src/Controllers/MenuManager.php
 * 
 * @package     WP_Customer
 * @subpackage  Admin/Controllers
 * @version     1.0.1
 * @author      arisciwek
 */

namespace WPCustomer\Controllers;

use WPCustomer\Controllers\SettingsController;
use WPCustomer\Controllers\Company\CompanyController;

class MenuManager {
    private $plugin_name;
    private $version;
    private $settings_controller;
    private $customer_controller;
    private $company_controller;  // Tambah property untuk CompanyController

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->settings_controller = new SettingsController();
        $this->customer_controller = new CustomerController();
        $this->company_controller = new CompanyController();  // Inisialisasi CompanyController
    }

    public function init() {
        add_action('admin_menu', [$this, 'registerMenus']);
        $this->settings_controller->init();
    }

    public function registerMenus() {
        // Menu WP Customer
        add_menu_page(
            __('WP Customer', 'wp-customer'),
            __('WP Customer', 'wp-customer'),
            'manage_options',
            'wp-customer',
            [$this->customer_controller, 'renderMainPage'],
            'dashicons-businessperson',
            30
        );

        // Menu WP Perusahaan 
        add_menu_page(
            __('WP Perusahaan', 'wp-customer'),
            __('WP Perusahaan', 'wp-customer'),
            'manage_options',
            'perusahaan',  // Unique menu slug untuk perusahaan
            [$this->company_controller, 'renderMainPage'],
            'dashicons-building',
            31
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