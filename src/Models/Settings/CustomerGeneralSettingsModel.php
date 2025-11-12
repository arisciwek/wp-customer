<?php
/**
 * Customer General Settings Model
 *
 * @package     WP_Customer
 * @subpackage  Models/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Settings/CustomerGeneralSettingsModel.php
 *
 * Description: Model untuk mengelola pengaturan umum plugin customer.
 *              Extends AbstractSettingsModel dari wp-app-core.
 *              Menggunakan CustomerCacheManager untuk cache management.
 *
 * Based On: wp-app-core/src/Models/Settings/PlatformSettingsModel.php
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (TODO-2198)
 * - Initial implementation extending AbstractSettingsModel
 * - General plugin settings (records per page, cache, language, etc)
 * - Integrated with CustomerCacheManager
 */

namespace WPCustomer\Models\Settings;

use WPAppCore\Models\Abstract\AbstractSettingsModel;
use WPCustomer\Cache\CustomerCacheManager;

class CustomerGeneralSettingsModel extends AbstractSettingsModel {

    private CustomerCacheManager $cacheManager;

    public function __construct() {
        $this->cacheManager = new CustomerCacheManager();
        parent::__construct(); // Register cache invalidation hooks
    }

    /**
     * Get option name for these settings
     *
     * @return string
     */
    protected function getOptionName(): string {
        return 'wp_customer_settings';
    }

    /**
     * Get cache manager instance
     *
     * @return CustomerCacheManager
     */
    protected function getCacheManager() {
        return $this->cacheManager;
    }

    /**
     * Get default settings
     *
     * @return array
     */
    protected function getDefaultSettings(): array {
        return [
            // Display Settings
            'records_per_page' => 15,
            'datatables_language' => 'id',
            'display_format' => 'hierarchical',

            // Cache Settings
            'enable_caching' => true,
            'cache_duration' => 43200, // 12 hours in seconds

            // API Settings
            'enable_api' => false,
            'api_key' => '',

            // System Settings
            'log_enabled' => false,
            'enable_hard_delete_branch' => false,  // Production: soft delete, Demo: hard delete
        ];
    }

    // ✅ getSettings() - inherited from AbstractSettingsModel
    // ✅ getSetting($key) - inherited from AbstractSettingsModel
    // ✅ saveSettings($settings) - inherited from AbstractSettingsModel
    // ✅ updateSetting($key, $value) - inherited from AbstractSettingsModel
    // ✅ getDefaults() - inherited from AbstractSettingsModel

    /**
     * Custom sanitization for customer general settings
     * Called by WordPress Settings API
     *
     * @param array $input Raw input from form
     * @return array Sanitized settings
     */
    public function sanitizeSettings(array $input): array {
        $sanitized = [];

        // Records per page: 5-100
        if (isset($input['records_per_page'])) {
            $sanitized['records_per_page'] = max(5, min(100, intval($input['records_per_page'])));
        }

        // Datatables language
        if (isset($input['datatables_language'])) {
            $sanitized['datatables_language'] = sanitize_text_field($input['datatables_language']);
        }

        // Display format
        if (isset($input['display_format'])) {
            $allowed_formats = ['hierarchical', 'flat'];
            $sanitized['display_format'] = in_array($input['display_format'], $allowed_formats)
                ? $input['display_format']
                : 'hierarchical';
        }

        // Cache settings
        $sanitized['enable_caching'] = isset($input['enable_caching']) && $input['enable_caching'];

        if (isset($input['cache_duration'])) {
            $allowed_durations = [3600, 7200, 21600, 43200, 86400];
            $sanitized['cache_duration'] = in_array(intval($input['cache_duration']), $allowed_durations)
                ? intval($input['cache_duration'])
                : 43200;
        }

        // API settings
        $sanitized['enable_api'] = isset($input['enable_api']) && $input['enable_api'];

        if (isset($input['api_key'])) {
            $sanitized['api_key'] = sanitize_text_field($input['api_key']);
        }

        // System settings
        $sanitized['log_enabled'] = isset($input['log_enabled']) && $input['log_enabled'];
        $sanitized['enable_hard_delete_branch'] = isset($input['enable_hard_delete_branch']) && $input['enable_hard_delete_branch'];

        return $sanitized;
    }
}
