<?php
/**
 * File: SettingsController.php 
 * Path: /wp-customer/src/Controllers/Settings/SettingsController.php
 * Description: Controller untuk mengelola halaman pengaturan plugin termasuk matrix permission
 * Version: 3.0.0
 * Last modified: 2024-11-28 08:45:00
 * 
 * Changelog:
 * v3.0.0 - 2024-11-28
 * - Perbaikan handling permission matrix
 * - Penambahan validasi dan error handling
 * - Optimasi performa loading data
 * - Penambahan logging aktivitas
 * 
 * v2.0.0 - 2024-11-27
 * - Integrasi dengan WordPress Roles API
 * 
 * Dependencies:
 * - PermissionModel
 * - SettingsModel 
 * - WordPress admin functions
 */

namespace WPCustomer\Controllers;

use WPCustomer\Controllers\Settings\MembershipFeaturesController;
use WPCustomer\Models\Membership\MembershipLevelModel;
use WPCustomer\Models\Membership\MembershipFeatureModel;
use WPCustomer\Cache\CustomerCacheManager;

class SettingsController {
    public function init() {
        add_action('admin_init', [$this, 'register_settings']);
        $this->register_ajax_handlers();
    }

    // Add this to your SettingsController or appropriate controller class
    public function register_ajax_handlers() {
        // DEPRECATED: Moved to CustomerDemoDataController (TODO-2201)
        // add_action('wp_ajax_customer_generate_demo_data', [$this, 'handle_generate_demo_data']);
        // add_action('wp_ajax_customer_check_demo_data', [$this, 'handle_check_demo_data']);

        // DEPRECATED: Replaced by AbstractPermissionsController (TODO-2200)
        // Uses different nonce: 'customer_reset_permissions' instead of 'wp_customer_reset_permissions'
        // add_action('wp_ajax_reset_customer_permissions', [$this, 'handle_reset_customer_permissions']);

        // DEPRECATED: Moved to MembershipLevelController
        // add_action('wp_ajax_get_customer_membership_level', [$this, 'handle_get_customer_membership_level']);

        // DEPRECATED: Already registered in MembershipLevelController (line 49)
        // add_action('wp_ajax_save_customer_membership_level', [$this, 'handle_save_customer_membership_level']);

        // TODO: This controller will be deleted after all handlers are migrated
        return;
    }

    // DEPRECATED: Moved to MembershipLevelController
    // public function handle_get_customer_membership_level() {
    //     check_ajax_referer('wp_customer_nonce', 'nonce');
    //     $membership_level_controller = new \WPCustomer\Controllers\Membership\MembershipLevelController();
    //     $membership_level_controller->getMembershipLevel();
    // }

    // DEPRECATED: Moved to MembershipLevelController
    // public function handle_save_customer_membership_level() {
    //     check_ajax_referer('wp_customer_nonce', 'nonce');
    //     $membership_level_controller = new \WPCustomer\Controllers\Membership\MembershipLevelController();
    //     $membership_level_controller->saveMembershipLevel();
    // }

    // DEPRECATED: Replaced by AbstractPermissionsController::handleResetPermissions()
    // Uses different nonce: 'customer_reset_permissions' instead of 'wp_customer_reset_permissions'
    /**
    public function handle_reset_customer_permissions() {
        // CRITICAL: Start output buffering to prevent contamination from plugin hooks
        // resetToDefault() triggers WordPress hooks that may init other plugins
        // which can output to buffer and cause 500 errors
        ob_start();

        error_log('=== WP-CUSTOMER RESET PERMISSIONS START ===');
        error_log('Received nonce: ' . ($_POST['nonce'] ?? 'NOT SET'));
        error_log('Expected nonce: ' . wp_create_nonce('wp_customer_reset_permissions'));
        error_log('Current user ID: ' . get_current_user_id());
        error_log('User can manage_options: ' . (current_user_can('manage_options') ? 'YES' : 'NO'));

        try {
            // Verify nonce
            check_ajax_referer('wp_customer_reset_permissions', 'nonce');
            error_log('Nonce verified successfully');

            // Check permissions
            if (!current_user_can('manage_options')) {
                error_log('ERROR: User does not have manage_options capability');
                error_log('=== WP-CUSTOMER RESET PERMISSIONS END (Permission Denied) ===');
                ob_end_clean(); // Clean buffer before sending JSON
                wp_send_json_error([
                    'message' => __('You do not have permission to perform this action.', 'wp-customer')
                ]);
                die(); // Ensure no code runs after wp_send_json
            }

            // Reset permissions using PermissionModel
            error_log('Creating PermissionModel instance...');
            $permission_model = new \WPCustomer\Models\Settings\PermissionModel();

            error_log('Calling resetToDefault()...');
            $success = $permission_model->resetToDefault();
            error_log('resetToDefault() returned: ' . ($success ? 'TRUE' : 'FALSE'));

            // CRITICAL: Clean output buffer before sending JSON response
            // This removes any output from plugin hooks triggered during reset
            ob_end_clean();

            if (!$success) {
                error_log('ERROR: resetToDefault() returned false');
                error_log('=== WP-CUSTOMER RESET PERMISSIONS END (Failed) ===');
                wp_send_json_error([
                    'message' => __('Failed to reset permissions.', 'wp-customer')
                ]);
                die(); // Ensure no code runs after wp_send_json
            }

            error_log('SUCCESS: Permissions reset successfully');
            error_log('=== WP-CUSTOMER RESET PERMISSIONS END (Success) ===');
            wp_send_json_success([
                'message' => __('Permissions have been reset to default settings.', 'wp-customer')
            ]);
            die(); // Ensure no code runs after wp_send_json

        } catch (\Exception $e) {
            error_log('EXCEPTION caught: ' . $e->getMessage());
            error_log('Exception trace: ' . $e->getTraceAsString());
            error_log('=== WP-CUSTOMER RESET PERMISSIONS END (Exception) ===');
            ob_end_clean(); // Clean buffer before sending JSON
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
            die(); // Ensure no code runs after wp_send_json
        }
    }
    */

    public function register_settings() {
        // NOTE: wp_customer_settings registration moved to CustomerGeneralSettingsController (TODO-2198)
        // This controller now only handles legacy AJAX and other settings

        // Development Settings
        register_setting(
            'wp_customer_development_settings',
            'wp_customer_development_settings',
            array(
                'sanitize_callback' => [$this, 'sanitize_development_settings'],
                'default' => array(
                    'enable_development' => 0,
                    'clear_data_on_deactivate' => 0
                )
            )
        );
    }

    public function sanitize_development_settings($input) {
        $sanitized = array();
        $sanitized['enable_development'] = isset($input['enable_development']) ? 1 : 0;
        $sanitized['clear_data_on_deactivate'] = isset($input['clear_data_on_deactivate']) ? 1 : 0;
        return $sanitized;
    }

    public function sanitize_settings($input) {
        $sanitized = array();

        // General settings sanitization
        $sanitized['datatables_page_length'] = absint($input['datatables_page_length']);
        $sanitized['enable_cache'] = isset($input['enable_cache']) ? 1 : 0;
        $sanitized['cache_duration'] = absint($input['cache_duration']);
        $sanitized['enable_debug'] = isset($input['enable_debug']) ? 1 : 0;

        // Pusher sanitization
        $sanitized['enable_pusher'] = isset($input['enable_pusher']) ? 1 : 0;
        $sanitized['pusher_app_key'] = sanitize_text_field($input['pusher_app_key']);
        $sanitized['pusher_app_secret'] = sanitize_text_field($input['pusher_app_secret']);
        $sanitized['pusher_cluster'] = sanitize_text_field($input['pusher_cluster']);

        return $sanitized;
    }

    // DEPRECATED: Moved to CustomerDemoDataController (TODO-2201)
    /**
     * Get the appropriate generator class based on data type
     *
     * @param string $type The type of data to generate
     * @return AbstractDemoData Generator instance
     * @throws \Exception If invalid type specified
     *
    private function getGeneratorClass($type) {
        error_log('=== Start WP Customer getGeneratorClass ===');
        error_log('Received type: ' . $type);
        error_log('getGeneratorClass received type: [' . $type . ']');
        error_log('Type length: ' . strlen($type));
        error_log('Type character codes: ' . json_encode(array_map('ord', str_split($type))));

        switch ($type) {
            case 'users':
                return new \WPCustomer\Database\Demo\WPUserGenerator();
            case 'customer':
                return new \WPCustomer\Database\Demo\CustomerDemoData();
            case 'branch':
                return new \WPCustomer\Database\Demo\BranchDemoData();
            case 'employee':
                return new \WPCustomer\Database\Demo\CustomerEmployeeDemoData();
            case 'membership-groups':
                return new \WPCustomer\Database\Demo\MembershipGroupsDemoData();
            case 'membership-features':
                return new \WPCustomer\Database\Demo\MembershipFeaturesDemoData();
            case 'membership-level':
                return new \WPCustomer\Database\Demo\MembershipLevelsDemoData();
            case 'memberships':
                return new \WPCustomer\Database\Demo\MembershipDemoData();
            case 'company-invoices':
                return new \WPCustomer\Database\Demo\CompanyInvoiceDemoData();
            default:
                throw new \Exception('Invalid demo data type: ' . $type);
        }
    }

    public function handle_generate_demo_data() {
        try {
            // Validate nonce and permissions first
            if (!current_user_can('manage_options')) {
                throw new \Exception('Permission denied');
            }

            $type = sanitize_text_field($_POST['type']);
            $nonce = sanitize_text_field($_POST['nonce']);

            if (!wp_verify_nonce($nonce, "generate_demo_{$type}")) {
                throw new \Exception('Invalid security token');
            }

            // Get the generator class based on type
            $generator = $this->getGeneratorClass($type);

            // Check if development mode is enabled before proceeding
            if (!$generator->isDevelopmentMode()) {
                wp_send_json_error([
                    'message' => 'Cannot generate demo data - Development mode is not enabled. Please enable it in settings first.',
                    'type' => 'dev_mode_off'
                ]);
                return;
            }

            // If development mode is on, proceed with generation
            if ($generator->run()) {
                wp_send_json_success([
                    'message' => ucfirst($type) . ' data generated successfully.',
                    'type' => 'success'
                ]);
            } else {
                wp_send_json_error([
                    'message' => 'Failed to generate demo data.',
                    'type' => 'error'
                ]);
            }

        } catch (\Exception $e) {
            error_log('Demo data generation failed: ' . $e->getMessage());
            wp_send_json_error([
                'message' => 'Failed to generate demo data.',
                'type' => 'error'
            ]);
        }
    }
    */

    public function renderPage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Anda tidak memiliki izin untuk mengakses halaman ini.', 'wp-customer'));
        }

        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        
        require_once WP_CUSTOMER_PATH . 'src/Views/templates/settings/settings_page.php';
        $this->loadTabView($current_tab);
    }

    /**
     * Mengambil feature groups dan features yang aktif dengan caching
     */
    private function getActiveGroupsAndFeatures() {
        $featureModel = new MembershipFeatureModel();
        return $featureModel->getActiveGroupsAndFeatures();
    }
    
    private function loadTabView($tab) {
        $allowed_tabs = [
            'general' => 'tab-general.php',
            'invoice-payment' => 'tab-invoice-payment.php',
            'permissions' => 'tab-permissions.php',
            'membership-levels' => 'tab-membership-levels.php',
            'membership-features' => 'tab-membership-features.php',
            'demo-data' => 'tab-demo-data.php'
        ];

        $tab = isset($allowed_tabs[$tab]) ? $tab : 'general';

        if ($tab === 'membership-features') {
            $membership_controller = new MembershipFeaturesController();
            $view_data = [
                'grouped_features' => $membership_controller->getAllFeatures(),
                'field_groups' => $membership_controller->getFeatureGroups(),
                'field_types' => ['checkbox', 'number', 'text'],
                'field_subtypes' => ['integer', 'float', 'text']
            ];
        } else if ($tab === 'membership-levels') {
            $membership_level_model = new MembershipLevelModel();
            $membership_feature_model = new MembershipFeatureModel();
            
            // Ambil data level dengan capabilities
            $levels = $membership_level_model->get_all_levels();
            $grouped_features = $membership_feature_model->get_all_features_by_group();
            
            // Ambil group dan feature data dari cache/database
            $groups_and_features = $this->getActiveGroupsAndFeatures();
            
            // Tambahkan group mapping
            $group_mapping = $membership_feature_model->getGroupMapping();
            
            // Tambahkan default capabilities structure
            $default_capabilities = [
                'features' => [],
                'limits' => [],
                'notifications' => []
            ];

            $view_data = [
                'levels' => $levels,
                'grouped_features' => $grouped_features,
                'groups_and_features' => $groups_and_features,
                'group_mapping' => $group_mapping,              // Baru
                'default_capabilities' => $default_capabilities // Baru
            ];
        }
            
        $tab_file = WP_CUSTOMER_PATH . 'src/Views/templates/settings/' . $allowed_tabs[$tab];
        
        if (file_exists($tab_file)) {
            if (isset($view_data)) {
                extract($view_data);
            }
            require_once $tab_file;
        } else {
            echo sprintf(
                __('Tab file tidak ditemukan: %s', 'wp-customer'),
                esc_html($tab_file)
            );
        }
    }

    // Render functions for membership fields
    // DEPRECATED: Not used anymore
    // public function render_membership_section() {
    //     echo '<p>' . __('Konfigurasi level keanggotaan dan batasan untuk setiap level.', 'wp-customer') . '</p>';
    // }

    // DEPRECATED: Moved to CustomerDemoDataController (TODO-2201)
    /**
    public function handle_check_demo_data() {
        try {
            if (!current_user_can('manage_options')) {
                throw new \Exception('Permission denied');
            }

            $type = sanitize_text_field($_POST['type']);
            $nonce = sanitize_text_field($_POST['nonce']);

            if (!wp_verify_nonce($nonce, 'customer_check_demo_data')) {
                throw new \Exception('Invalid security token');
            }

            global $wpdb;
            $has_data = false;
            $count = 0;

            switch($type) {
                case 'branch':
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_branches");
                    $has_data = ($count > 0);
                    break;
                case 'customer':
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}app_customers");
                    $has_data = ($count > 0);
                    break;
                case 'membership-groups':
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_membership_feature_groups");
                    $has_data = ($count > 0);
                    break;
                case 'membership-features':
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_membership_features");
                    $has_data = ($count > 0);
                    break;
                case 'memberships':
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_memberships");
                    $has_data = ($count > 0);
                    break;
                default:
                    throw new \Exception('Invalid data type');
            }

            wp_send_json_success([
                'has_data' => $has_data,
                'count' => $count
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    */

}
