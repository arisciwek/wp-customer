<?php
/**
 * Invoice Payment Settings Model
 *
 * @package     WP_Customer
 * @subpackage  Models/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Settings/InvoicePaymentSettingsModel.php
 *
 * Description: Model untuk invoice & payment settings menggunakan AbstractSettingsModel
 *              Mengikuti standardized architecture pattern (TODO-2198)
 *
 * Changelog:
 * 1.0.0 - 2025-01-13
 * - Initial version following TODO-2198 pattern
 * - Extends AbstractSettingsModel from wp-app-core
 * - Settings: invoice (due days, prefix, format, currency, tax, email)
 * - Settings: payment (methods, confirmation, auto-approve, reminders)
 */

namespace WPCustomer\Models\Settings;

use WPAppCore\Models\Abstract\AbstractSettingsModel;
use WPCustomer\Cache\CustomerCacheManager;

defined('ABSPATH') || exit;

class InvoicePaymentSettingsModel extends AbstractSettingsModel {

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
        return 'wp_customer_invoice_payment_settings';
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
     * Get default settings values
     *
     * @return array
     */
    protected function getDefaultSettings(): array {
        return [
            // Invoice Settings
            'invoice_due_days' => 7,
            'invoice_prefix' => 'INV',
            'invoice_number_format' => 'YYYYMM',
            'invoice_currency' => 'Rp',
            'invoice_tax_percentage' => 11.0,
            'invoice_sender_email' => '',

            // Payment Settings
            'payment_methods' => ['transfer_bank', 'virtual_account'],
            'payment_confirmation_required' => true,
            'payment_auto_approve_threshold' => 0,
            'payment_reminder_days' => [7, 3, 1],
        ];
    }

    /**
     * Sanitize settings data
     *
     * @param array $input Raw input data
     * @return array Sanitized data
     */
    public function sanitizeSettings(array $input): array {
        $sanitized = [];

        // Invoice Settings
        // invoice_due_days: 1-365 days
        $sanitized['invoice_due_days'] = isset($input['invoice_due_days'])
            ? max(1, min(365, intval($input['invoice_due_days'])))
            : $this->getDefaults()['invoice_due_days'];

        // invoice_prefix: alphanumeric, max 10 chars
        $sanitized['invoice_prefix'] = isset($input['invoice_prefix'])
            ? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $input['invoice_prefix']), 0, 10))
            : $this->getDefaults()['invoice_prefix'];

        // invoice_number_format: allowed values only
        $allowed_formats = ['YYYYMM', 'YYYYMMDD', 'YYMM', 'YYMMDD'];
        $sanitized['invoice_number_format'] = isset($input['invoice_number_format']) && in_array($input['invoice_number_format'], $allowed_formats)
            ? $input['invoice_number_format']
            : $this->getDefaults()['invoice_number_format'];

        // invoice_currency: sanitize text, max 10 chars
        $sanitized['invoice_currency'] = isset($input['invoice_currency'])
            ? substr(sanitize_text_field($input['invoice_currency']), 0, 10)
            : $this->getDefaults()['invoice_currency'];

        // invoice_tax_percentage: 0-100%
        $sanitized['invoice_tax_percentage'] = isset($input['invoice_tax_percentage'])
            ? max(0, min(100, floatval($input['invoice_tax_percentage'])))
            : $this->getDefaults()['invoice_tax_percentage'];

        // invoice_sender_email: valid email or empty
        $sanitized['invoice_sender_email'] = isset($input['invoice_sender_email']) && !empty($input['invoice_sender_email'])
            ? sanitize_email($input['invoice_sender_email'])
            : '';

        // Payment Settings
        // payment_methods: array of allowed methods
        $allowed_methods = ['transfer_bank', 'virtual_account', 'kartu_kredit', 'e_wallet'];
        $sanitized['payment_methods'] = [];
        if (isset($input['payment_methods']) && is_array($input['payment_methods'])) {
            foreach ($input['payment_methods'] as $method) {
                if (in_array($method, $allowed_methods)) {
                    $sanitized['payment_methods'][] = $method;
                }
            }
        }
        // Fallback to default if empty
        if (empty($sanitized['payment_methods'])) {
            $sanitized['payment_methods'] = $this->getDefaults()['payment_methods'];
        }

        // payment_confirmation_required: boolean
        $sanitized['payment_confirmation_required'] = isset($input['payment_confirmation_required'])
            ? (bool) $input['payment_confirmation_required']
            : $this->getDefaults()['payment_confirmation_required'];

        // payment_auto_approve_threshold: non-negative number
        $sanitized['payment_auto_approve_threshold'] = isset($input['payment_auto_approve_threshold'])
            ? max(0, floatval($input['payment_auto_approve_threshold']))
            : $this->getDefaults()['payment_auto_approve_threshold'];

        // payment_reminder_days: array of 1-365, sorted descending
        $sanitized['payment_reminder_days'] = [];
        if (isset($input['payment_reminder_days']) && is_array($input['payment_reminder_days'])) {
            foreach ($input['payment_reminder_days'] as $day) {
                $day = intval($day);
                if ($day >= 1 && $day <= 365) {
                    $sanitized['payment_reminder_days'][] = $day;
                }
            }
            // Remove duplicates and sort descending
            $sanitized['payment_reminder_days'] = array_unique($sanitized['payment_reminder_days']);
            rsort($sanitized['payment_reminder_days']);
        }
        // Fallback to default if empty
        if (empty($sanitized['payment_reminder_days'])) {
            $sanitized['payment_reminder_days'] = $this->getDefaults()['payment_reminder_days'];
        }

        return $sanitized;
    }

    // ✅ getSettings() - inherited from AbstractSettingsModel
    // ✅ getSetting($key) - inherited from AbstractSettingsModel
    // ✅ saveSettings($settings) - inherited from AbstractSettingsModel
    // ✅ updateSetting($key, $value) - inherited from AbstractSettingsModel
    // ✅ resetToDefaults() - inherited from AbstractSettingsModel
    // ✅ getDefaults() - inherited from AbstractSettingsModel (public wrapper)
}
