<?php
/**
 * Customer General Settings Validator
 *
 * @package     WP_Customer
 * @subpackage  Validators/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Validators/Settings/CustomerGeneralSettingsValidator.php
 *
 * Description: Validator untuk customer general settings.
 *              Extends AbstractSettingsValidator dari wp-app-core.
 *              Provides validation rules untuk general plugin settings.
 *
 * Based On: wp-app-core/src/Validators/Settings/PlatformSettingsValidator.php
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (TODO-2198)
 * - Initial implementation extending AbstractSettingsValidator
 * - Validation rules for display settings
 * - Validation rules for cache settings
 * - Validation rules for API settings
 * - Validation rules for system settings
 */

namespace WPCustomer\Validators\Settings;

use WPAppCore\Validators\Abstract\AbstractSettingsValidator;

defined('ABSPATH') || exit;

class CustomerGeneralSettingsValidator extends AbstractSettingsValidator {

    /**
     * Get text domain for translations
     *
     * @return string
     */
    protected function getTextDomain(): string {
        return 'wp-customer';
    }

    /**
     * Get validation rules
     *
     * @return array
     */
    protected function getRules(): array {
        return [
            // Display Settings
            'records_per_page' => ['required', 'numeric', 'min:5', 'max:100'],
            'datatables_language' => ['required', 'max:10'],
            'display_format' => ['required', 'in:hierarchical,flat'],

            // Cache Settings
            'enable_caching' => ['boolean'],
            'cache_duration' => ['numeric', 'min:3600', 'max:86400'],

            // API Settings
            'enable_api' => ['boolean'],
            'api_key' => ['max:255'],

            // System Settings
            'log_enabled' => ['boolean'],
            'enable_hard_delete_branch' => ['boolean'],
        ];
    }

    /**
     * Get custom error messages
     *
     * @return array
     */
    protected function getMessages(): array {
        return [
            'records_per_page.required' => __('Records per page is required.', 'wp-customer'),
            'records_per_page.min' => __('Records per page must be at least 5.', 'wp-customer'),
            'records_per_page.max' => __('Records per page cannot exceed 100.', 'wp-customer'),
            'datatables_language.required' => __('Language is required.', 'wp-customer'),
            'display_format.required' => __('Display format is required.', 'wp-customer'),
            'display_format.in' => __('Display format must be either hierarchical or flat.', 'wp-customer'),
            'cache_duration.min' => __('Cache duration must be at least 1 hour (3600 seconds).', 'wp-customer'),
            'cache_duration.max' => __('Cache duration cannot exceed 24 hours (86400 seconds).', 'wp-customer'),
        ];
    }

    // ✅ validate($data) - inherited from AbstractSettingsValidator
    // ✅ getErrors() - inherited from AbstractSettingsValidator
    // ✅ hasErrors() - inherited from AbstractSettingsValidator
}
