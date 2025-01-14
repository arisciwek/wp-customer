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
    global $wpdb;
    $current_user_id = get_current_user_id();

    error_log('--- Debug MenuManager renderMainPage ---');
    error_log('User ID: ' . $current_user_id);

    // Dapatkan customer_id dari query parameter

    $customer_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    error_log('Query param customer_id: ' . $customer_id);

    // Cek relasi sebagai employee dengan customer spesifik
    $customer_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT customer_id 
         FROM {$wpdb->prefix}app_customer_employees 
         WHERE user_id = %d",
        $current_user_id
    ));
    
    error_log('Employee customer IDs: ' . print_r($customer_ids, true));


    // Cek akses ke plugin
    error_log('Checking basic plugin access:');
    error_log('Can view_customer_list: ' . (current_user_can('view_customer_list') ? 'yes' : 'no'));

    if (!current_user_can('view_customer_list')) {
        error_log('Basic access check failed - user cannot view customer list');
        wp_die(__('Anda tidak memiliki izin untuk mengakses halaman ini.', 'wp-customer'));
    }

    // Admin check
    error_log('Checking admin access:');
    error_log('Can edit_all_customers: ' . (current_user_can('edit_all_customers') ? 'yes' : 'no'));

    if (current_user_can('edit_all_customers')) {
        error_log('Admin access granted - showing full dashboard');
        require_once WP_CUSTOMER_PATH . 'src/Views/templates/customer-dashboard.php';
        return;
    }

    // Cek relasi sebagai owner
    $has_customer = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}app_customers WHERE user_id = %d",
        $current_user_id
    ));
    error_log('Checking owner relationship:');
    error_log('Has customer records: ' . ($has_customer > 0 ? 'yes' : 'no'));

    // Jika user adalah owner, berikan akses
    if ($has_customer > 0 && current_user_can('view_own_customer')) {
        error_log('Owner access granted - showing customer dashboard');
        require_once WP_CUSTOMER_PATH . 'src/Views/templates/customer-dashboard.php';
        return;
    }

    // Jika customer_id dari query parameter ada dalam daftar customer_ids employee
    if (!empty($customer_ids) && ($customer_id === 0 || in_array($customer_id, $customer_ids))) {
        error_log('Employee access granted for customer_id: ' . $customer_id);
        require_once WP_CUSTOMER_PATH . 'src/Views/templates/customer-dashboard.php';
        return;
    }

    error_log('All checks failed - showing no access template');
    error_log('--- End Debug MenuManager renderMainPage ---');
    require_once WP_CUSTOMER_PATH . 'src/Views/templates/customer-no-access.php';
}

}
