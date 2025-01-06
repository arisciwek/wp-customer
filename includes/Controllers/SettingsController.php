<?php
/**
 * Settings Controller
 *
 * @package     CustomerManagement
 * @subpackage  Core/Controllers
 * @version     1.0.0
 * 
 * Description:
 * Handles registration and management of plugin settings including:
 * - Settings registration
 * - Settings sections 
 * - Settings fields
 * - Settings validation
 * - Settings saving
 * 
 * Path: includes/Controllers/SettingsController.php
 * Timestamp: 2024-01-06 11:00:00
 * 
 * Required Capabilities:
 * - manage_customer_settings
 * 
 * Dependencies:
 * - WordPress Settings API
 * - Customer Management Settings System
 * 
 * Changelog:
 * 1.0.0 - 2024-01-06
 * - Initial release
 * - Added settings registration
 * - Added settings validation
 * - Added settings fields handling
 */

namespace CustomerManagement\Controllers;

class SettingsController extends BaseController {
    
    protected function register_ajax_handlers() {
        // Register any AJAX handlers for settings if needed
        // For now, we don't have any AJAX actions for settings
    }
    
    public function register() {
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_settings() {
        register_setting(
            'wp_customer_settings', // Option group
            'wp_customer_settings', // Option name
            [$this, 'sanitize_settings'] // Sanitize callback
        );

        // General Section
        add_settings_section(
            'wp_customer_general_section',
            __('General Settings', 'customer-management'),
            [$this, 'render_general_section'],
            'wp_customer_settings'
        );

        // Add settings fields
        $this->add_settings_fields();
    }

    public function add_settings_fields() {
        // DataTables Settings
        add_settings_field(
            'datatables_page_length',
            __('DataTables Page Length', 'customer-management'),
            [$this, 'render_datatable_field'],
            'wp_customer_settings',
            'wp_customer_general_section'
        );

        // Cache Settings
        add_settings_field(
            'cache_settings',
            __('Cache Settings', 'customer-management'),
            [$this, 'render_cache_field'],
            'wp_customer_settings',
            'wp_customer_general_section'
        );

        // Debug Mode
        add_settings_field(
            'debug_mode',
            __('Debug Mode', 'customer-management'),
            [$this, 'render_debug_field'],
            'wp_customer_settings',
            'wp_customer_general_section'
        );
    }

    public function sanitize_settings($input) {
        $sanitized = array();

        // Sanitize datatables page length
        if (isset($input['datatables_page_length'])) {
            $sanitized['datatables_page_length'] = absint($input['datatables_page_length']);
        }

        // Sanitize cache settings
        $sanitized['enable_cache'] = isset($input['enable_cache']) ? 1 : 0;
        if (isset($input['cache_duration'])) {
            $sanitized['cache_duration'] = absint($input['cache_duration']);
        }

        // Sanitize debug mode
        $sanitized['enable_debug'] = isset($input['enable_debug']) ? 1 : 0;

        return $sanitized;
    }

    public function render_general_section() {
        echo '<p>' . __('Configure general settings for the Customer Management plugin.', 'customer-management') . '</p>';
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
            <?php _e('Number of rows displayed per page in tables', 'customer-management'); ?>
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
            <?php _e('Enable caching', 'customer-management'); ?>
        </label>
        
        <div class="cache-options" style="margin-top: 10px;">
            <label>
                <?php _e('Cache Duration (seconds):', 'customer-management'); ?>
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
            <?php _e('Enable debug mode', 'customer-management'); ?>
        </label>
        <p class="description">
            <?php _e('Shows additional debugging information in console', 'customer-management'); ?>
        </p>
        <?php
    }
}