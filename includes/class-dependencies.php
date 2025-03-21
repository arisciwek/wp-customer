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
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'leaflet_enqueue_scripts']); // Add this line

    }

public function enqueue_frontend_assets() {
    // Ignore admin and ajax requests
    if (is_admin() || wp_doing_ajax()) {
        return;
    }

    if (get_query_var('wp_customer_register') !== '') {
        error_log('Enqueuing registration assets...');

        // Register page specific style
        wp_enqueue_style(
            'wp-customer-register',
            WP_CUSTOMER_URL . 'assets/css/auth/register.css',
            [],
            $this->version
        );


        // Enqueue styles
        wp_enqueue_style(
            'wp-customer-form',
            WP_CUSTOMER_URL . 'assets/css/customer-form.css',
            [],
            $this->version
        );

        wp_enqueue_style(
            'wp-customer-toast',
            WP_CUSTOMER_URL . 'assets/css/customer/toast.css',
            [],
            $this->version
        );

        // Core scripts
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'jquery-validate',
            'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js',
            ['jquery'],
            '1.19.5',
            true
        );

        // Toast component
        wp_enqueue_script(
            'wp-customer-toast',
            WP_CUSTOMER_URL . 'assets/js/customer/toast.js',
            ['jquery'],
            $this->version,
            true
        );

        // Registration form handler
        wp_enqueue_script(
            'wp-customer-register',
            WP_CUSTOMER_URL . 'assets/js/auth/register.js',
            ['jquery', 'jquery-validate', 'wp-customer-toast'],
            $this->version,
            true
        );

        // Localize script
        wp_localize_script(
            'wp-customer-register',
            'wpCustomerData',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_customer_register'),
                'i18n' => [
                    'registering' => __('Mendaftar...', 'wp-customer'),
                    'register' => __('Daftar', 'wp-customer'),
                    'error' => __('Terjadi kesalahan. Silakan coba lagi.', 'wp-customer')
                ]
            ]
        );
        error_log('Registration assets enqueued');
    }
}



    public function enqueue_styles() {

        $screen = get_current_screen();
        if (!$screen) return;
        // Check if we're on the registration page
        if (get_query_var('wp_customer_register')) {
            // Enqueue registration-specific styles
            wp_enqueue_style('wp-customer-register', WP_CUSTOMER_URL . 'assets/css/auth/register.css', [], $this->version);
            wp_enqueue_style('wp-customer-toast', WP_CUSTOMER_URL . 'assets/css/customer/toast.css', [], $this->version);
            return;
        }


        // Settings page styles// Settings page styles
        if ($screen->id === 'wp-customer_page_wp-customer-settings') {
           // Common styles for settings page
           wp_enqueue_style('wp-customer-common', WP_CUSTOMER_URL . 'assets/css/settings/common-style.css', [], $this->version);
           wp_enqueue_style('wp-customer-settings', WP_CUSTOMER_URL . 'assets/css/settings/settings-style.css', ['wp-customer-common'], $this->version);
           wp_enqueue_style('wp-customer-modal', WP_CUSTOMER_URL . 'assets/css/customer/confirmation-modal.css', [], $this->version);

           // Get current tab and permission tab
           $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
           $permission_tab = isset($_GET['permission_tab']) ? sanitize_key($_GET['permission_tab']) : '';

           switch ($current_tab) {
               case 'permissions':
                   wp_enqueue_style(
                       'wp-customer-permissions-tab',
                       WP_CUSTOMER_URL . 'assets/css/settings/permissions-tab-style.css',
                       ['wp-customer-settings'],
                       $this->version
                   );
                   break;

               case 'general':
                   wp_enqueue_style(
                       'wp-customer-general-tab', 
                       WP_CUSTOMER_URL . 'assets/css/settings/general-tab-style.css',
                       ['wp-customer-settings'],
                       $this->version
                   );
                   break;

               case 'membership-levels':
                   wp_enqueue_style(
                       'wp-customer-membership-levels-tab',
                       WP_CUSTOMER_URL . 'assets/css/settings/customer-membership-levels-tab-style.css',
                       ['wp-customer-settings'],
                       $this->version
                   );
                   break;

                case 'membership-features':
                    wp_enqueue_style(
                        'wp-customer-membership-features-tab',
                        WP_CUSTOMER_URL . 'assets/css/settings/membership-features-tab-style.css',
                        ['wp-customer-settings'],
                        $this->version
                    );
                    break;

               case 'demo-data':
                   wp_enqueue_style(
                       'wp-customer-demo-data-tab',
                       WP_CUSTOMER_URL . 'assets/css/settings/demo-data-tab-style.css',
                       ['wp-customer-settings'],
                       $this->version
                   );
                   break;
           }
        }

        // Customer and Branch pages styles
        if ($screen->id === 'toplevel_page_wp-customer') {
            // Core styles
            wp_enqueue_style('wp-customer-toast', WP_CUSTOMER_URL . 'assets/css/customer/toast.css', [], $this->version);
            wp_enqueue_style('wp-customer-modal', WP_CUSTOMER_URL . 'assets/css/customer/confirmation-modal.css', [], $this->version);
            // Branch toast - terpisah
            wp_enqueue_style('branch-toast', WP_CUSTOMER_URL . 'assets/css/branch/branch-toast.css', [], $this->version);

            // DataTables
            wp_enqueue_style('datatables', 'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css', [], '1.13.7');

            // Customer styles
            wp_enqueue_style('wp-customer-customer', WP_CUSTOMER_URL . 'assets/css/customer/customer-style.css', [], $this->version);
            wp_enqueue_style('wp-customer-membership-levels-tab', WP_CUSTOMER_URL . 'assets/css/customer/customer-membership-tab-style.css', [], $this->version);

            wp_enqueue_style('wp-customer-customer-form', WP_CUSTOMER_URL . 'assets/css/customer/customer-form.css', [], $this->version);

            // Branch styles
            wp_enqueue_style('wp-customer-branch', WP_CUSTOMER_URL . 'assets/css/branch/branch-style.css', [], $this->version);

            // Tambahkan Employee styles
            wp_enqueue_style('wp-customer-employee', WP_CUSTOMER_URL . 'assets/css/employee/employee-style.css', [], $this->version);
            wp_enqueue_style('employee-toast', WP_CUSTOMER_URL . 'assets/css/employee/employee-toast.css', [], $this->version);
        }

        // Style section di method enqueue_styles()
        if ($screen->id === 'toplevel_page_perusahaan') {
            // Core styles
            wp_enqueue_style('wp-customer-toast', WP_CUSTOMER_URL . 'assets/css/customer/toast.css', [], $this->version);
            
            // DataTables
            wp_enqueue_style('datatables', 'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css', [], '1.13.7');

            // Company styles
            wp_enqueue_style('wp-company', WP_CUSTOMER_URL . 'assets/css/company/company-style.css', [], $this->version);

           // Membership styles
            wp_enqueue_style('wp-company-membership', WP_CUSTOMER_URL . 'assets/css/company/company-membership-style.css', [], $this->version);

        }

    }

    public function enqueue_scripts() {
        $screen = get_current_screen();
        if (!$screen) return;

        // Check if we're on the registration page
        if (get_query_var('wp_customer_register')) {
            // Enqueue registration-specific scripts
            wp_enqueue_script('jquery-validate', 'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js', ['jquery'], '1.19.5', true);
            wp_enqueue_script('wp-customer-toast', WP_CUSTOMER_URL . 'assets/js/customer/toast.js', ['jquery'], $this->version, true);
            wp_enqueue_script('wp-customer-register', WP_CUSTOMER_URL . 'assets/js/auth/register.js', ['jquery', 'jquery-validate', 'wp-customer-toast'], $this->version, true);
            
            // Localize script
            wp_localize_script('wp-customer-register', 'wpCustomerData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_customer_register')
            ]);
            return;
        }

        // Settings page scripts
        if ($screen->id === 'wp-customer_page_wp-customer-settings') {
            // Common scripts for settings page
            wp_enqueue_script('wp-customer-toast', WP_CUSTOMER_URL . 'assets/js/customer/customer-toast.js', ['jquery'], $this->version, true);
            wp_enqueue_script('confirmation-modal', WP_CUSTOMER_URL . 'assets/js/customer/confirmation-modal.js', ['jquery'], $this->version, true);
            wp_enqueue_script('wp-customer-settings', WP_CUSTOMER_URL . 'assets/js/settings/settings-script.js', ['jquery', 'wp-customer-toast'], $this->version, true);
            
            // Get current tab and permission tab
            $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
            $permission_tab = isset($_GET['permission_tab']) ? sanitize_key($_GET['permission_tab']) : '';

            switch ($current_tab) {
                case 'permissions':
                    wp_enqueue_script(
                        'wp-customer-permissions-tab',
                        WP_CUSTOMER_URL . 'assets/js/settings/permissions-tab-script.js',
                        ['jquery', 'wp-customer-settings'],
                        $this->version,
                        true
                    );
                               
                    // Add localize script here
                    wp_localize_script('wp-customer-permissions-tab', 'wpCustomerData', [
                        'ajaxUrl' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce('wp_customer_reset_permissions'),
                        'i18n' => [
                            'resetConfirmTitle' => __('Reset Permissions?', 'wp-customer'),
                            'resetConfirmMessage' => __('This will restore all permissions to their default settings. This action cannot be undone.', 'wp-customer'),
                            'resetConfirmButton' => __('Reset Permissions', 'wp-customer'),
                            'resetting' => __('Resetting...', 'wp-customer'),
                            'cancelButton' => __('Cancel', 'wp-customer')
                        ]
                    ]);
                    break;
                case 'general':
                        // Modal components
                        wp_enqueue_script(
                            'confirmation-modal',
                            WP_CUSTOMER_URL . 'assets/js/customer/confirmation-modal.js',
                            ['jquery'],
                            $this->version,
                            true
                        );

                        // Localize script
                        wp_localize_script('wp-customer-settings', 'wpCustomerData', [
                            'ajaxUrl' => admin_url('admin-ajax.php'),
                            'clearCacheNonce' => wp_create_nonce('wp_customer_clear_cache')
                        ]);
                    break;
                case 'membership-features':
                    wp_enqueue_script(
                        'wp-customer-membership-features-tab',
                        WP_CUSTOMER_URL . 'assets/js/settings/customer-membership-features-tab-script.js',
                        ['jquery', 'wp-customer-settings'],
                        $this->version,
                        true
                    );
                    wp_localize_script(
                        'wp-customer-membership-features-tab',
                        'wpCustomerSettings',
                        [
                            'ajaxUrl' => admin_url('admin-ajax.php'),
                            'nonce' => wp_create_nonce('wp_customer_nonce'),
                            'i18n' => [
                                'addFeature' => __('Add New Feature', 'wp-customer'),
                                'editFeature' => __('Edit Feature', 'wp-customer'),
                                'deleteConfirm' => __('Are you sure you want to delete this feature?', 'wp-customer'),
                                'loadError' => __('Failed to load feature data', 'wp-customer'),
                                'saveError' => __('Failed to save feature', 'wp-customer'),
                                'deleteError' => __('Failed to delete feature', 'wp-customer'),
                                'saving' => __('Saving...', 'wp-customer'),
                                'loading' => __('Loading...', 'wp-customer')
                            ]
                        ]
                    );
                    break;
                case 'membership-levels':
                    wp_enqueue_script(
                        'wp-membership-levels',
                        WP_CUSTOMER_URL . 'assets/js/settings/customer-membership-levels-tab-script.js',
                        ['jquery', 'wp-customer-settings'],
                        WP_CUSTOMER_VERSION,
                        true
                    );

                    wp_localize_script('wp-membership-levels', 'wpCustomerData', [
                        'ajaxUrl' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce('wp_customer_nonce'),
                        'i18n' => [
                            'confirmDelete' => __('Are you sure you want to delete this membership level?', 'wp-customer'),
                            'saveSuccess' => __('Membership level saved successfully.', 'wp-customer'),
                            'saveError' => __('Failed to save membership level.', 'wp-customer'),
                            'deleteSuccess' => __('Membership level deleted successfully.', 'wp-customer'),
                            'deleteError' => __('Failed to delete membership level.', 'wp-customer'),
                            'loadError' => __('Failed to load membership level data.', 'wp-customer'),
                            'required' => __('This field is required.', 'wp-customer'),
                            'invalidNumber' => __('Please enter a valid number.', 'wp-customer')
                        ]
                    ]);

                    break;
                case 'demo-data':
                    wp_enqueue_script(
                        'wp-customer-demo-data-tab',
                        WP_CUSTOMER_URL . 'assets/js/settings/customer-demo-data-tab-script.js',
                        ['jquery', 'wp-customer-settings'],
                        $this->version,
                        true
                    );

                    wp_localize_script('wp-customer-demo-data-tab', 'wpCustomerDemoData', [
                        'i18n' => [
                            'errorMessage' => __('An error occurred while generating demo data.', 'wp-customer'),
                            'generating' => __('Generating...', 'wp-customer')
                        ]
                    ]);
                    break;
            }

        }

        // Customer and Branch pages scripts
        if ($screen->id === 'toplevel_page_wp-customer') {
            // Core dependencies
            wp_enqueue_script('jquery-validate', 'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js', ['jquery'], '1.19.5', true);
            wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js', ['jquery'], '1.13.7', true);
            wp_enqueue_script('jquery-inputmask', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/jquery.inputmask.min.js', array('jquery'), null, true);
            // Components
            wp_enqueue_script('customer-toast', WP_CUSTOMER_URL . 'assets/js/customer/customer-toast.js', ['jquery'], $this->version, true);
            wp_enqueue_script('confirmation-modal', WP_CUSTOMER_URL . 'assets/js/customer/confirmation-modal.js', ['jquery'], $this->version, true);
            // Branch toast
            wp_enqueue_script('branch-toast', WP_CUSTOMER_URL . 'assets/js/branch/branch-toast.js', ['jquery'], $this->version, true);
        
            // Tambah handler untuk wilayah
            $this->enqueue_wilayah_handler();


            // Customer scripts - path fixed according to tree.md
            wp_enqueue_script('customer-datatable', WP_CUSTOMER_URL . 'assets/js/customer/customer-datatable.js', ['jquery', 'datatables', 'customer-toast'], $this->version, true);
            wp_enqueue_script('create-customer-form', WP_CUSTOMER_URL . 'assets/js/customer/create-customer-form.js', ['jquery', 'jquery-validate', 'customer-toast'], $this->version, true);
            wp_enqueue_script('edit-customer-form', WP_CUSTOMER_URL . 'assets/js/customer/edit-customer-form.js', ['jquery', 'jquery-validate', 'customer-toast'], $this->version, true);

            wp_enqueue_script('customer',
                WP_CUSTOMER_URL . 'assets/js/customer/customer-script.js',
                [
                    'jquery',
                    'customer-toast',
                    'customer-datatable',
                    'create-customer-form',
                    'edit-customer-form'
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

            // Employee scripts - mengikuti pola branch yang sudah berhasil
            wp_enqueue_script('employee-datatable', WP_CUSTOMER_URL . 'assets/js/employee/employee-datatable.js', ['jquery', 'datatables', 'customer-toast', 'customer'], $this->version, true);
            wp_enqueue_script('employee-toast', WP_CUSTOMER_URL . 'assets/js/employee/employee-toast.js', ['jquery'], $this->version, true);
            wp_enqueue_script('create-employee-form', WP_CUSTOMER_URL . 'assets/js/employee/create-employee-form.js', ['jquery', 'jquery-validate', 'employee-toast', 'employee-datatable'], $this->version, true);
            wp_enqueue_script('edit-employee-form', WP_CUSTOMER_URL . 'assets/js/employee/edit-employee-form.js', ['jquery', 'jquery-validate', 'employee-toast', 'employee-datatable'], $this->version, true);
            wp_enqueue_script(
                'wp-customer-membership',
                WP_CUSTOMER_URL . 'assets/js/customer/customer-membership.js',
                ['jquery', 'wp-customer', 'customer-toast'],
                WP_CUSTOMER_VERSION,
                true
            );

            // Gunakan wpCustomerData untuk semua
            $customer_nonce = wp_create_nonce('wp_customer_nonce');
            wp_localize_script('customer', 'wpCustomerData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => $customer_nonce,
                'debug' => true
            ]);
        }

        // Script section di method enqueue_scripts()
        if ($screen->id === 'toplevel_page_perusahaan') {
            // Core dependencies
            wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js', ['jquery'], '1.13.7', true);
            
            // Components
            wp_enqueue_script('customer-toast', WP_CUSTOMER_URL . 'assets/js/customer/customer-toast.js', ['jquery'], $this->version, true);

            // Company scripts
            wp_enqueue_script('company-datatable', WP_CUSTOMER_URL . 'assets/js/company/company-datatable.js', ['jquery', 'datatables', 'customer-toast'], $this->version, true);
            wp_enqueue_script('company-script', WP_CUSTOMER_URL . 'assets/js/company/company-script.js', ['jquery', 'company-datatable', 'customer-toast'], $this->version, true);

            wp_enqueue_script('company-membership', WP_CUSTOMER_URL . 'assets/js/company/company-membership.js', ['jquery'], $this->version, true);

            // Localize script
            wp_localize_script('company-script', 'wpCustomerData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_customer_nonce'),
                'debug' => true
            ]);
        }  
    }

    private function enqueue_wilayah_handler() {
        // Use direct constant check first
        if (!defined('WILAYAH_INDONESIA_URL')) {
            error_log('Wilayah Indonesia plugin is not installed');
            return;
        }

        // Cek apakah sudah di-enqueue sebelumnya
        if (wp_script_is('wilayah-select-handler-core', 'enqueued')) {
            return;
        }

        // Enqueue core handler dari plugin wilayah-indonesia
        wp_enqueue_script(
            'wilayah-select-handler-core',
            WILAYAH_INDONESIA_URL . 'assets/js/components/select-handler-core.js',
            ['jquery'],
            defined('WILAYAH_INDONESIA_VERSION') ? WILAYAH_INDONESIA_VERSION : '1.0.0',
            true
        );

        // Enqueue UI handler dari plugin wilayah-indonesia
        wp_enqueue_script(
            'wilayah-select-handler-ui',
            WILAYAH_INDONESIA_URL . 'assets/js/components/select-handler-ui.js',
            ['jquery', 'wilayah-select-handler-core'],
            defined('WILAYAH_INDONESIA_VERSION') ? WILAYAH_INDONESIA_VERSION : '1.0.0',
            true
        );

        // Localize script data
        wp_localize_script('wilayah-select-handler-core', 'wilayahData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wilayah_select_nonce'),
            'debug' => (defined('WP_DEBUG') && WP_DEBUG),
            'texts' => [
                'select_provinsi' => __('Pilih Provinsi', 'wp-customer'),
                'select_regency' => __('Pilih Kabupaten/Kota', 'wp-customer'),
                'loading' => __('Memuat...', 'wp-customer'),
                'error' => __('Gagal memuat data', 'wp-customer')
            ]
        ]);
    }

    public function leaflet_enqueue_scripts() {
        $screen = get_current_screen();
        if (!$screen) return;
        
        if ($screen->id === 'toplevel_page_wp-customer') {
            // Leaflet CSS & JS
            wp_enqueue_style(
                'leaflet',
                'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
                [],
                '1.9.4'
            );
            
            wp_enqueue_script(
                'leaflet',
                'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
                [],
                '1.9.4',
                true
            );

            // Custom map picker
            wp_enqueue_script(
                'wp-customer-map-picker',
                WP_CUSTOMER_URL . 'assets/js/branch/map-picker.js',
                ['jquery', 'leaflet'],
                $this->version,
                true
            );

            // Localize script dengan settings
            wp_localize_script(
                'wp-customer-map-picker',
                'wpCustomerMapSettings',
                [
                    'defaultLat' => get_option('wp_customer_settings')['map_default_lat'] ?? -6.200000,
                    'defaultLng' => get_option('wp_customer_settings')['map_default_lng'] ?? 106.816666,
                    'defaultZoom' => get_option('wp_customer_settings')['map_default_zoom'] ?? 12
                ]
            );
        }
    }

}
