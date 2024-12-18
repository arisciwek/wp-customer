<?php
namespace CustomerManagement\Core;

class Enqueue {
    public function register() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'customer-') === false) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'customer-management-admin',
            CUSTOMER_PLUGIN_URL . 'assets/css/admin.css',
            [],
            CUSTOMER_PLUGIN_VERSION
        );

        wp_enqueue_style(
            'customer-management-dashboard',
            CUSTOMER_PLUGIN_URL . 'assets/css/dashboard.css',
            [],
            CUSTOMER_PLUGIN_VERSION
        );

        // DataTables CSS
        wp_enqueue_style(
            'datatables',
            '//cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css',
            [],
            '1.11.5'
        );

        // JavaScript
        wp_enqueue_script(
            'datatables',
            '//cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js',
            ['jquery'],
            '1.11.5',
            true
        );
/*
        wp_enqueue_script(
            'customer-management-admin',
            CUSTOMER_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'datatables'],
            CUSTOMER_PLUGIN_VERSION,
            true
        );
*/
        wp_enqueue_script(
            'customer-management-admin',
            CUSTOMER_PLUGIN_URL . 'assets/js/customer-management.js',
            ['jquery', 'datatables'],
            CUSTOMER_PLUGIN_VERSION,
            true
        );


        wp_enqueue_script(
            'customer-management-dashboard',
            CUSTOMER_PLUGIN_URL . 'assets/js/dashboard.js',
            ['jquery', 'datatables'],
            CUSTOMER_PLUGIN_VERSION,
            true
        );


        // Localize script
        wp_localize_script('customer-management-admin', 'customerManagement', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('customer_management_nonce')
        ]);
    }
}
