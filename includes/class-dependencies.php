<?php
/**
 * Dependencies Handler Class
 *
 * @package     WP_Customer
 * @subpackage  Includes
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/includes/class-dependencies.php
 *
 * Description: Menangani dependencies plugin seperti CSS, JavaScript,
 *              dan library eksternal
 *
 * Changelog:
 * 1.1.0 - 2024-12-10
 * - Added branch management dependencies
 * - Added branch CSS and JS files
 * - Updated screen checks for branch assets
 * - Fixed path inconsistencies
 * - Added common-style.css
 *
 * 1.0.0 - 2024-11-23
 * - Initial creation
 * - Added asset enqueuing methods
 * - Added CDN dependencies
 */
class WP_Customer_Dependencies {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        $screen = get_current_screen();
        if (!$screen) return;

        // Settings page styles
        if ($screen->id === 'wp-customer_page_wp-customer-settings') {
            wp_enqueue_style('wp-customer-common', WP_CUSTOMER_URL . 'assets/css/settings/common-style.css', [], $this->version);
            wp_enqueue_style('wp-customer-settings', WP_CUSTOMER_URL . 'assets/css/settings/settings-style.css', ['wp-customer-common'], $this->version);
            wp_enqueue_style('wp-customer-modal', WP_CUSTOMER_URL . 'assets/css/components/confirmation-modal.css', [], $this->version);

            $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
            switch ($current_tab) {
                case 'permission':
                    wp_enqueue_style('wp-customer-permission-tab', WP_CUSTOMER_URL . 'assets/css/settings/permission-tab-style.css', [], $this->version);
                    break;
                case 'general':
                    wp_enqueue_style('wp-customer-general-tab', WP_CUSTOMER_URL . 'assets/css/settings/general-tab-style.css', [], $this->version);
                    break;
            }
            return;
        }

        // Customer and Branch pages styles
        if ($screen->id === 'toplevel_page_wp-customer') {
            // Core styles
            wp_enqueue_style('wp-customer-toast', WP_CUSTOMER_URL . 'assets/css/components/toast.css', [], $this->version);
            wp_enqueue_style('wp-customer-modal', WP_CUSTOMER_URL . 'assets/css/components/confirmation-modal.css', [], $this->version);
            // Branch toast - terpisah
            wp_enqueue_style('branch-toast', WP_CUSTOMER_URL . 'assets/css/branch/branch-toast.css', [], $this->version);

            // DataTables
            wp_enqueue_style('datatables', 'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css', [], '1.13.7');

            // Customer styles
            wp_enqueue_style('wp-customer-customer', WP_CUSTOMER_URL . 'assets/css/customer.css', [], $this->version);
            wp_enqueue_style('wp-customer-customer-form', WP_CUSTOMER_URL . 'assets/css/customer-form.css', [], $this->version);

            // Branch styles
            wp_enqueue_style('wp-customer-branch', WP_CUSTOMER_URL . 'assets/css/branch/branch.css', [], $this->version);
        }
    }

    public function enqueue_scripts() {
        $screen = get_current_screen();
        if (!$screen) return;

        // Settings page scripts
        if ($screen->id === 'wp-customer_page_wp-customer-settings') {
            wp_enqueue_script('wp-customer-toast', WP_CUSTOMER_URL . 'assets/js/components/toast.js', ['jquery'], $this->version, true);
            wp_enqueue_script('confirmation-modal', WP_CUSTOMER_URL . 'assets/js/components/confirmation-modal.js', ['jquery'], $this->version, true);
            wp_enqueue_script('wp-customer-settings', WP_CUSTOMER_URL . 'assets/js/settings/settings-script.js', ['jquery', 'wp-customer-toast'], $this->version, true);

            if (isset($_GET['tab']) && $_GET['tab'] === 'permission') {
                wp_enqueue_script('wp-customer-permissions', WP_CUSTOMER_URL . 'assets/js/settings/permissions-script.js', ['jquery', 'wp-customer-toast'], $this->version, true);
            }
            return;
        }

        // Customer and Branch pages scripts
        if ($screen->id === 'toplevel_page_wp-customer') {
            // Core dependencies
            wp_enqueue_script('jquery-validate', 'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js', ['jquery'], '1.19.5', true);
            wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js', ['jquery'], '1.13.7', true);

            // Components
            wp_enqueue_script('customer-toast', WP_CUSTOMER_URL . 'assets/js/components/customer-toast.js', ['jquery'], $this->version, true);
            wp_enqueue_script('confirmation-modal', WP_CUSTOMER_URL . 'assets/js/components/confirmation-modal.js', ['jquery'], $this->version, true);
            // Branch toast
            wp_enqueue_script('branch-toast', WP_CUSTOMER_URL . 'assets/js/branch/branch-toast.js', ['jquery'], $this->version, true);

            // Customer scripts - path fixed according to tree.md
            wp_enqueue_script('customer-datatable', WP_CUSTOMER_URL . 'assets/js/components/customer-datatable.js', ['jquery', 'datatables', 'customer-toast'], $this->version, true);
            wp_enqueue_script('create-customer-form', WP_CUSTOMER_URL . 'assets/js/components/create-customer-form.js', ['jquery', 'jquery-validate', 'customer-toast'], $this->version, true);
            wp_enqueue_script('edit-customer-form', WP_CUSTOMER_URL . 'assets/js/components/edit-customer-form.js', ['jquery', 'jquery-validate', 'customer-toast'], $this->version, true);

            wp_enqueue_script('wp-customer-dashboard',
                WP_CUSTOMER_URL . 'assets/js/dashboard.js',
                ['jquery'],
                $this->version,
                true
            );

            wp_enqueue_script('customer',
                WP_CUSTOMER_URL . 'assets/js/customer.js',
                [
                    'jquery',
                    'customer-toast',
                    'customer-datatable',
                    'create-customer-form',
                    'edit-customer-form',
                    'wp-customer-dashboard' // Tambahkan dependency
                ],
                $this->version,
                true
            );

            // Branch scripts
            wp_enqueue_script('branch-datatable', WP_CUSTOMER_URL . 'assets/js/branch/branch-datatable.js', ['jquery', 'datatables', 'customer-toast', 'customer'], $this->version, true);
            wp_enqueue_script('branch-toast', WP_CUSTOMER_URL . 'assets/js/branch/branch-toast.js', ['jquery'], $this->version, true);
            // Update dependencies untuk form
            wp_enqueue_script('create-branch-form', WP_CUSTOMER_URL . 'assets/js/branch/create-branch-form.js', ['jquery', 'jquery-validate', 'branch-toast', 'branch-datatable'], $this->version, true);
            wp_enqueue_script('edit-branch-form', WP_CUSTOMER_URL . 'assets/js/branch/edit-branch-form.js', ['jquery', 'jquery-validate', 'branch-toast', 'branch-datatable'], $this->version, true);
            // Localize script
            wp_localize_script('customer', 'wpCustomerData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_customer_nonce')
            ]);

        }
    }

    public function enqueue_select_handler() {
        // Cek apakah sudah di-enqueue sebelumnya
        if (wp_script_is('wp-customer-select-handler', 'enqueued')) {
            return;
        }

        wp_enqueue_script('wp-customer-select-handler', 
            WP_CUSTOMER_URL . 'assets/js/components/select-handler.js', 
            ['jquery'], 
            $this->version, 
            true
        );

        wp_localize_script('wp-customer-select-handler', 'wpCustomerSelectData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_customer_nonce'),
            'texts' => [
                'select_customer' => __('Pilih Customer', 'wp-customer'),
                'select_branch' => __('Pilih Kabupaten/Kota', 'wp-customer'),
                'loading' => __('Memuat...', 'wp-customer')
            ]
        ]);
    }

}
