<?php
/**
 * File: SettingsModel.php
 * Path: /wp-customer/src/Models/Settings/SettingsModel.php
 * Description: Model untuk mengelola pengaturan umum plugin
 * Version: 1.2.1
 * Last modified: 2024-12-03
 * 
 * Changelog:
 * v1.2.1 - 2024-12-03
 * - Changed sanitizeOptions visibility to public
 * - Added proper documentation blocks
 * 
 * v1.2.0 - 2024-11-28
 * - Mengganti semua constants menjadi properti class
 * - Perbaikan penggunaan properti di seluruh method
 */

namespace WPCustomer\Models\Settings;

class SettingsModel {
    private $option_group = 'wp_customer_settings';
    private $general_options = 'wp_customer_general_options';
    
    private $default_options = [
        'records_per_page' => 15,
        'enable_caching' => true,
        'cache_duration' => 43200, // 12 hours in seconds
        'datatables_language' => 'id',
        'display_format' => 'hierarchical',
        'enable_api' => false,
        'api_key' => '',
        'log_enabled' => false
    ];

    /**
     * Get all settings termasuk default values
     *
     * @return array
     */
    public function getSettings(): array {
        return [
            'general' => $this->getGeneralOptions(),
            'api' => $this->getApiSettings(),
            'display' => $this->getDisplaySettings(),
            'system' => $this->getSystemSettings()
        ];
    }

    /**
     * Register semua settings ke WordPress
     */
    public function registerSettings() {
        // Register setting di settings group
        register_setting(
            'wp_customer_options', // Option group
            'wp_customer_settings', // Option name
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitizeOptions'],
                'default' => $this->default_options
            ]
        );

        // General Section
        add_settings_section(
            'wp_customer_general_section',
            __('Pengaturan Umum', 'wp-customer'),
            [$this, 'renderGeneralSection'],
            'wp_customer'
        );

        // Add Fields
        add_settings_field(
            'records_per_page',
            __('Data Per Halaman', 'wp-customer'),
            [$this, 'renderNumberField'],
            'wp_customer',
            'wp_customer_general_section',
            [
                'label_for' => 'records_per_page',
                'field_id' => 'records_per_page',
                'desc' => __('Jumlah data yang ditampilkan per halaman (5-100)', 'wp-customer')
            ]
        );

        add_settings_field(
            'enable_caching',
            __('Aktifkan Cache', 'wp-customer'),
            [$this, 'renderCheckboxField'],
            'wp_customer',
            'wp_customer_general_section',
            [
                'label_for' => 'enable_caching',
                'field_id' => 'enable_caching',
                'desc' => __('Aktifkan caching untuk performa lebih baik', 'wp-customer')
            ]
        );

        add_settings_field(
            'cache_duration',
            __('Durasi Cache', 'wp-customer'),
            [$this, 'renderSelectField'],
            'wp_customer',
            'wp_customer_general_section',
            [
                'label_for' => 'cache_duration',
                'field_id' => 'cache_duration',
                'desc' => __('Berapa lama cache disimpan', 'wp-customer'),
                'options' => [
                    3600 => __('1 jam', 'wp-customer'),
                    7200 => __('2 jam', 'wp-customer'),
                    21600 => __('6 jam', 'wp-customer'),
                    43200 => __('12 jam', 'wp-customer'),
                    86400 => __('24 jam', 'wp-customer')
                ]
            ]
        );
    }
    
    /**
     * Get general options dengan default values
     *
     * @return array
     */
    public function getGeneralOptions(): array {
        $cache_key = 'wp_customer_general_options';
        $cache_group = 'wp_customer';
        
        // Try to get from cache first
        $options = wp_cache_get($cache_key, $cache_group);
        
        if (false === $options) {
            // Not in cache, get from database
            $options = get_option($this->general_options, []);
            
            // Parse with defaults
            $options = wp_parse_args($options, $this->default_options);
            
            // Store in cache for next time
            wp_cache_set($cache_key, $options, $cache_group);
        }
        
        return $options;
    }

    /**
     * Get API related settings
     *
     * @return array
     */
    private function getApiSettings(): array {
        $options = $this->getGeneralOptions();
        return [
            'enable_api' => $options['enable_api'],
            'api_key' => $options['api_key']
        ];
    }

    /**
     * Get display related settings
     *
     * @return array
     */
    private function getDisplaySettings(): array {
        $options = $this->getGeneralOptions();
        return [
            'display_format' => $options['display_format'],
            'datatables_language' => $options['datatables_language']
        ];
    }

    /**
     * Get system related settings
     *
     * @return array
     */
    private function getSystemSettings(): array {
        $options = $this->getGeneralOptions();
        return [
            'enable_caching' => $options['enable_caching'],
            'cache_duration' => $options['cache_duration'],
            'log_enabled' => $options['log_enabled']
        ];
    }
    
    /**
     * Save general settings dengan validasi
     *
     * @param array $input
     * @return bool
     */
    public function saveGeneralSettings(array $input): bool {
        if (empty($input)) {
            return false;
        }

        // Clear cache first
        wp_cache_delete('wp_customer_general_options', 'wp_customer');

        // Sanitize input
        $sanitized = $this->sanitizeOptions($input);
        
        // Only update if we have valid data
        if (!empty($sanitized)) {
            $result = update_option($this->general_options, $sanitized);
            
            // Re-cache the new values if update successful
            if ($result) {
                wp_cache_set(
                    'wp_customer_general_options',
                    $sanitized,
                    'wp_customer'
                );
            }
            
            return $result;
        }
        
        return false;
    }

    /**
     * Update general options
     *
     * @param array $new_options
     * @return bool
     */
    public function updateGeneralOptions(array $new_options): bool {
        $options = $this->sanitizeOptions($new_options);
        
        if (empty($options)) {
            return false;
        }

        return update_option($this->general_options, $options);
    }

    /**
     * Sanitize all option values
     * 
     * @param array $options
     * @return array
     *//**
 * Sanitize all option values
 * 
 * @param array|null $options
 * @return array
 */
    public function sanitizeOptions(?array $options = []): array {
        // If options is null, use empty array
        if ($options === null) {
            $options = [];
        }
        
        $sanitized = [];
        
        // Sanitize records per page
        if (isset($options['records_per_page'])) {
            $sanitized['records_per_page'] = absint($options['records_per_page']);
            if ($sanitized['records_per_page'] < 5) {
                $sanitized['records_per_page'] = 5;
            }
        }

        // Sanitize enable caching
        if (isset($options['enable_caching'])) {
            $sanitized['enable_caching'] = (bool) $options['enable_caching'];
        }

        // Sanitize cache duration
        if (isset($options['cache_duration'])) {
            $sanitized['cache_duration'] = absint($options['cache_duration']);
            if ($sanitized['cache_duration'] < 3600) { // Minimum 1 hour
                $sanitized['cache_duration'] = 3600;
            }
        }

        // Sanitize datatables language
        if (isset($options['datatables_language'])) {
            $sanitized['datatables_language'] = sanitize_key($options['datatables_language']);
        }

        // Sanitize display format
        if (isset($options['display_format'])) {
            $sanitized['display_format'] = in_array($options['display_format'], ['hierarchical', 'flat']) 
                ? $options['display_format'] 
                : 'hierarchical';
        }

        // Sanitize API settings
        if (isset($options['enable_api'])) {
            $sanitized['enable_api'] = (bool) $options['enable_api'];
        }

        if (isset($options['api_key'])) {
            $sanitized['api_key'] = sanitize_key($options['api_key']);
        }

        // Sanitize logging
        if (isset($options['log_enabled'])) {
            $sanitized['log_enabled'] = (bool) $options['log_enabled'];
        }

        // Merge with default options to ensure all required keys exist
        return wp_parse_args($sanitized, $this->default_options);
    }

    /**
     * Delete all plugin options
     *
     * @return bool
     */
    public function deleteOptions(): bool {
        return delete_option($this->general_options);
    }

    /**
     * Render general section description
     */
    public function renderGeneralSection() {
        echo '<p>' . __('Pengaturan umum untuk plugin WP Customer.', 'wp-customer') . '</p>';
    }

    /**
     * Render number field
     * 
     * @param array $args
     */
    public function renderNumberField($args) {
        $options = $this->getGeneralOptions();
        $value = $options[$args['field_id']] ?? '';
        
        printf(
            '<input type="number" id="%1$s" name="wp_customer_general_options[%1$s]" value="%2$s" class="regular-text">',
            esc_attr($args['field_id']),
            esc_attr($value)
        );
        
        if (isset($args['desc'])) {
            printf('<p class="description">%s</p>', esc_html($args['desc']));
        }
    }

    /**
     * Render checkbox field
     *
     * @param array $args
     */
    public function renderCheckboxField($args) {
        $options = $this->getGeneralOptions();
        $checked = isset($options[$args['field_id']]) ? checked($options[$args['field_id']], true, false) : '';
        
        printf(
            '<input type="checkbox" id="%1$s" name="wp_customer_general_options[%1$s]" value="1" %2$s>',
            esc_attr($args['field_id']),
            $checked
        );
        
        if (isset($args['desc'])) {
            printf('<p class="description">%s</p>', esc_html($args['desc']));
        }
    }

    /**
     * Render select field
     *
     * @param array $args
     */
    public function renderSelectField($args) {
        $options = $this->getGeneralOptions();
        $value = $options[$args['field_id']] ?? '';
        
        printf('<select id="%s" name="wp_customer_general_options[%s]">', 
            esc_attr($args['field_id']),
            esc_attr($args['field_id'])
        );
        
        foreach ($args['options'] as $key => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($key),
                selected($value, $key, false),
                esc_html($label)
            );
        }
        
        echo '</select>';
        
        if (isset($args['desc'])) {
            printf('<p class="description">%s</p>', esc_html($args['desc']));
        }
    }
}
