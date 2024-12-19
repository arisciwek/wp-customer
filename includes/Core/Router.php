<?php
namespace CustomerManagement\Core;

class Router {
    public function register() {
        add_action('admin_menu', [$this, 'add_menu_pages']);
    }

    public function add_menu_pages() {
        add_menu_page(
            'Customer Management',
            'Customers',
            'manage_options',
            'customer-management',
            [$this, 'render_main_page'],
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'customer-management',
            'Staff Management',
            'Staff',
            'manage_options',
            'customer-staff',
            [$this, 'render_staff_page']
        );

        add_submenu_page(
            'customer-management',
            'Branch Management',
            'Branches',
            'manage_options',
            'customer-branches',
            [$this, 'render_branch_page']
        );

        add_submenu_page(
            'customer-management',
            'Settings',
            'Settings',
            'manage_options',
            'customer-settings',
            [$this, 'render_settings_page']
        );
    }

    public function render_main_page() {
        require_once WP_CUSTOMER_PATH . 'includes/Views/customers/customer-page.php';
    }

    public function render_staff_page() {
        require_once WP_CUSTOMER_PATH . 'includes/Views/staff/staff_page.php';
    }

    public function render_branch_page() {
        require_once WP_CUSTOMER_PATH . 'includes/Views/branches/branch_page.php';
    }

    public function render_settings_page() {
        require_once WP_CUSTOMER_PATH . 'includes/Views/settings/settings_page.php';
    }
}
