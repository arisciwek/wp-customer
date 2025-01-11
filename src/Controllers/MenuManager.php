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

class MenuManager {
    private $plugin_name;
    private $version;
    private $settings_controller;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->settings_controller = new SettingsController();
    }

    public function init() {
        add_action('admin_menu', [$this, 'registerMenus']);
        $this->settings_controller->init();
    }

    public function registerMenus() {
        add_menu_page(
            __('WP Customer', 'wp-customer'),
            __('WP Customer', 'wp-customer'),
            'view_customer_detail',
            'wp-customer',
            [$this, 'renderMainPage'],
            'dashicons-businessperson',
            30
        );

        add_submenu_page(
            'wp-customer',
            __('Pengaturan', 'wp-customer'),
            __('Pengaturan', 'wp-customer'),
            'manage_options',
            'wp-customer-settings',
            [$this->settings_controller, 'renderPage']
        );
    }

    public function renderMainPage() {

        // Cek multiple capabilities di sini
        if (!current_user_can('view_customer_list') || !current_user_can('view_customer_detail')) {
            wp_die(__('Anda tidak memiliki izin untuk mengakses halaman ini.', 'wp-customer'));
        }

        require_once WP_CUSTOMER_PATH . 'src/Views/templates/customer-dashboard.php';
    }
}
