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
        //$this->register();
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_settings() {
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
                    // Pusher defaults
                    'enable_pusher' => 0,
                    'pusher_app_key' => '',
                    'pusher_app_secret' => '',
                    'pusher_cluster' => 'ap1'
                )
            )
        );
    }

    public function sanitize_settings($input) {
        $sanitized = array();

        // Existing sanitization
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

    public function render_general_section() {
        echo '<p>' . __('Konfigurasi pengaturan umum untuk plugin WP Customer.', 'wp-customer') . '</p>';
    }

    public function render_datatable_field() {
        $options = get_option('wp_customer_settings');
        $value = isset($options['datatables_page_length']) ? $options['datatables_page_length'] : 25;
        ?>
        <select name="wp_customer_settings[datatables_page_length]">
            <option value="10" <?php selected($value, 10); ?>>10</option>
            <option value="25" <?php selected($value, 25); ?>>25</option>
            <option value="50" <?php selected($value, 50); ?>>50</option>
            <option value="100" <?php selected($value, 100); ?>>100</option>
        </select>
        <p class="description">
            <?php _e('Jumlah data yang ditampilkan per halaman dalam tabel', 'wp-customer'); ?>
        </p>
        <?php
    }

    public function render_cache_field() {
        $options = get_option('wp_customer_settings');
        $enable_cache = isset($options['enable_cache']) ? $options['enable_cache'] : 0;
        $cache_duration = isset($options['cache_duration']) ? $options['cache_duration'] : 3600;
        ?>
        <label>
            <input type="checkbox" name="wp_customer_settings[enable_cache]" 
                   value="1" <?php checked($enable_cache, 1); ?>>
            <?php _e('Aktifkan caching', 'wp-customer'); ?>
        </label>
        
        <div class="cache-options" style="margin-top: 10px;">
            <label>
                <?php _e('Durasi Cache (detik):', 'wp-customer'); ?>
                <input type="number" name="wp_customer_settings[cache_duration]" 
                       value="<?php echo esc_attr($cache_duration); ?>" 
                       min="60" step="60">
            </label>
        </div>
        <?php
    }

    public function render_debug_field() {
        $options = get_option('wp_customer_settings');
        $enable_debug = isset($options['enable_debug']) ? $options['enable_debug'] : 0;
        ?>
        <label>
            <input type="checkbox" name="wp_customer_settings[enable_debug]" 
                   value="1" <?php checked($enable_debug, 1); ?>>
            <?php _e('Aktifkan mode debug', 'wp-customer'); ?>
        </label>
        <p class="description">
            <?php _e('Menampilkan informasi debugging tambahan di console', 'wp-customer'); ?>
        </p>
        <?php
    }

    public function renderPage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Anda tidak memiliki izin untuk mengakses halaman ini.', 'wp-customer'));
        }

        require_once WP_CUSTOMER_PATH . 'src/Views/templates/settings/settings_page.php';
        //require_once WP_CUSTOMER_PATH . 'src/Views/templates/settings/tab-permissions.php';
        //require_once WP_CUSTOMER_PATH . 'src/Views/templates/settings/tab-general.php';

        // Load tab content
        $this->loadTabView($current_tab);
    }    



private function loadTabView($tab) {
    // Define allowed tabs and their templates
    $allowed_tabs = [
        'general' => 'tab-general.php',
        'permissions' => 'tab-permissions.php'
    ];
    
    // Validate tab exists
    if (!isset($allowed_tabs[$tab])) {
        $tab = 'general'; // Default to general if invalid tab
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

/*
    private function loadTabView($tab) {
        $tab_file = WP_CUSTOMER_PATH . 'src/Views/templates/settings/tab-' . $tab . '.php';
        
        if (file_exists($tab_file)) {
            require_once $tab_file;
        } else {
            echo sprintf(
                __('Tab file tidak ditemukan: %s', 'wp-customer'),
                esc_html($tab_file)
            );
        }
    }
*/

}
