<?php
namespace CustomerManagement\Core;

class Enqueue {
    public function register() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function enqueue_admin_scripts($hook) {
        // Proper hook checking to avoid null parameter
        if (!$hook || !is_string($hook) || strpos($hook, 'customer-management') === false) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'customer-management-admin',
            WP_CUSTOMER_URL . 'assets/css/admin.css',
            [],
            WP_CUSTOMER_VERSION
        );

        wp_enqueue_style(
            'customer-management-dashboard',
            WP_CUSTOMER_URL . 'assets/css/dashboard.css',
            [],
            WP_CUSTOMER_VERSION
        );

        wp_enqueue_style(
            'customer-management-core',
            WP_CUSTOMER_URL . 'assets/css/customer-management.css',
            [],
            WP_CUSTOMER_VERSION
        );

        // DataTables CSS
        wp_enqueue_style(
            'datatables',
            '//cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css',
            [],
            '1.11.5'
        );

        // JavaScript dependencies
        wp_enqueue_script(
            'datatables',
            '//cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js',
            ['jquery'],
            '1.11.5',
            true
        );

        // Core admin script
        wp_enqueue_script(
            'customer-management-admin',
            WP_CUSTOMER_URL . 'assets/js/admin.js',
            ['jquery', 'datatables'],
            WP_CUSTOMER_VERSION,
            true
        );

        // Customer management specific script
        wp_enqueue_script(
            'customer-management-core',
            WP_CUSTOMER_URL . 'assets/js/customer-management.js',
            ['jquery', 'datatables', 'customer-management-admin'],
            WP_CUSTOMER_VERSION,
            true
        );

        // Dashboard script
        wp_enqueue_script(
            'customer-management-dashboard',
            WP_CUSTOMER_URL . 'assets/js/dashboard.js',
            ['jquery', 'datatables', 'customer-management-admin'],
            WP_CUSTOMER_VERSION,
            true
        );

        // Localize script for all JS files
        $localize_data = array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('customer_management_nonce'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'can_edit' => current_user_can('edit_customers'),
            'can_delete' => current_user_can('delete_customers')
        );

        wp_localize_script('customer-management-admin', 'customerManagement', $localize_data);
    }
}
