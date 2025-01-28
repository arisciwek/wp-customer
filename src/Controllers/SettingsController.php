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

class SettingsController {
    public function init() {
        add_action('admin_init', [$this, 'register_settings']);
        $this->register_ajax_handlers();
    }

    // Add this to your SettingsController or appropriate controller class
    public function register_ajax_handlers() {
        add_action('wp_ajax_reset_permissions', [$this, 'handle_reset_permissions']);
        add_action('wp_ajax_generate_demo_data', [$this, 'handle_generate_demo_data']);      
    }

    public function handle_reset_permissions() {
        try {
            // Verify nonce
            check_ajax_referer('wp_customer_reset_permissions', 'nonce');

            // Check permissions
            if (!current_user_can('manage_options')) {
                throw new \Exception(__('You do not have permission to perform this action.', 'wp-customer'));
            }

            // Reset permissions using PermissionModel
            $permission_model = new \WPCustomer\Models\Settings\PermissionModel();
            $success = $permission_model->resetToDefault();

            if (!$success) {
                throw new \Exception(__('Failed to reset permissions.', 'wp-customer'));
            }

            wp_send_json_success([
                'message' => __('Permissions have been reset to default settings.', 'wp-customer')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function register_settings() {
        // General Settings
        register_setting(
            'wp_customer_settings',
            'wp_customer_settings',
            array(
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => array(
                    'datatables_page_length' => 25,
                    'enable_cache' => 0,
                    'cache_duration' => 3600,
                    'enable_debug' => 0,
                    'enable_pusher' => 0,
                    'pusher_app_key' => '',
                    'pusher_app_secret' => '',
                    'pusher_cluster' => 'ap1'
                )
            )
        );

        // Membership Settings
        register_setting(
            'wp_customer_membership_settings',
            'wp_customer_membership_settings',
            array(
                'sanitize_callback' => [$this, 'sanitize_membership_settings'],
                'default' => array(
                    'regular_max_staff' => 2,
                    'priority_max_staff' => 5,
                    'utama_max_staff' => -1,
                    'regular_capabilities' => array(
                        'can_add_staff' => true,
                        'max_departments' => 1
                    ),
                    'priority_capabilities' => array(
                        'can_add_staff' => true,
                        'can_export' => true,
                        'max_departments' => 3
                    ),
                    'utama_capabilities' => array(
                        'can_add_staff' => true,
                        'can_export' => true,
                        'can_bulk_import' => true,
                        'max_departments' => -1
                    )
                )
            )
        );

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

    public function sanitize_membership_settings($input) {
        $sanitized = array();
        
        // Sanitize staff limits
        $levels = ['regular', 'priority', 'utama'];
        foreach ($levels as $level) {
            $max_staff_key = "{$level}_max_staff";
            $sanitized[$max_staff_key] = intval($input[$max_staff_key]);
            
            // Validate max staff value
            if ($sanitized[$max_staff_key] != -1 && $sanitized[$max_staff_key] < 1) {
                $sanitized[$max_staff_key] = 1;
            }
            
            // Sanitize capabilities
            $capabilities_key = "{$level}_capabilities";
            $sanitized[$capabilities_key] = array();
            
            if (isset($input[$capabilities_key]) && is_array($input[$capabilities_key])) {
                foreach ($input[$capabilities_key] as $cap => $value) {
                    $sanitized[$capabilities_key][$cap] = (bool) $value;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Get the appropriate generator class based on data type
     *
     * @param string $type The type of data to generate
     * @return AbstractDemoData Generator instance
     * @throws \Exception If invalid type specified
     */
    private function getGeneratorClass($type) {
        switch ($type) {
            case 'users':
                return new \WPCustomer\Database\Demo\WPUserGenerator();
            case 'customer':
                return new \WPCustomer\Database\Demo\CustomerDemoData();
            case 'branch':
                return new \WPCustomer\Database\Demo\BranchDemoData();
            case 'employee':
                return new \WPCustomer\Database\Demo\EmployeeDemoData();
            case 'membership':
                return new \WPCustomer\Database\Demo\MembershipDemoData();
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
                    'type' => 'dev_mode_off'  // Menandakan error karena development mode off
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
                    'type' => 'error'  // Menandakan error teknis
                ]);
            }

        } catch (\Exception $e) {
            $this->debug('Demo data generation failed: ' . $e->getMessage());
            wp_send_json_error([
                'message' => 'Failed to generate demo data.',
                'type' => 'error'
            ]);
        }
    }

    public function renderPage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Anda tidak memiliki izin untuk mengakses halaman ini.', 'wp-customer'));
        }

        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        
        require_once WP_CUSTOMER_PATH . 'src/Views/templates/settings/settings_page.php';
        $this->loadTabView($current_tab);
    }

    private function loadTabView($tab) {
        // Define allowed tabs and their templates
        $allowed_tabs = [
            'general' => 'tab-general.php',
            'permissions' => 'tab-permissions.php',
            'membership' => 'tab-membership.php',
            'demo-data' => 'tab-demo-data.php'
        ];
        
        // Validate tab exists
        if (!isset($allowed_tabs[$tab])) {
            $tab = 'general';
        }
        
        $tab_file = WP_CUSTOMER_PATH . 'src/Views/templates/settings/' . $allowed_tabs[$tab];
        
        if (file_exists($tab_file)) {
            require_once $tab_file;
        } else {
            echo sprintf(
                __('Tab file tidak ditemukan: %s', 'wp-customer'),
                esc_html($tab_file)
            );
        }
    }

    // Render functions for membership fields
    public function render_membership_section() {
        echo '<p>' . __('Konfigurasi level keanggotaan dan batasan untuk setiap level.', 'wp-customer') . '</p>';
    }

    public function render_max_staff_field($level) {
        $options = get_option('wp_customer_membership_settings');
        $field_name = "{$level}_max_staff";
        $value = isset($options[$field_name]) ? $options[$field_name] : 2;
        ?>
        <input type="number" 
               name="wp_customer_membership_settings[<?php echo esc_attr($field_name); ?>]"
               value="<?php echo esc_attr($value); ?>"
               min="-1"
               class="small-text">
        <p class="description">
            <?php _e('-1 untuk unlimited', 'wp-customer'); ?>
        </p>
        <?php
    }

    public function render_capabilities_field($level) {
        $options = get_option('wp_customer_membership_settings');
        $field_name = "{$level}_capabilities";
        $capabilities = isset($options[$field_name]) ? $options[$field_name] : array();
        
        $available_caps = array(
            'can_add_staff' => __('Dapat menambah staff', 'wp-customer'),
            'can_export' => __('Dapat export data', 'wp-customer'),
            'can_bulk_import' => __('Dapat bulk import', 'wp-customer')
        );

        foreach ($available_caps as $cap => $label) {
            $checked = isset($capabilities[$cap]) ? $capabilities[$cap] : false;
            ?>
            <label>
                <input type="checkbox" 
                       name="wp_customer_membership_settings[<?php echo esc_attr($field_name); ?>][<?php echo esc_attr($cap); ?>]"
                       value="1"
                       <?php checked($checked, true); ?>>
                <?php echo esc_html($label); ?>
            </label><br>
            <?php
        }
    }
}
