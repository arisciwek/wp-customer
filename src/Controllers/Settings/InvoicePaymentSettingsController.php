<?php
/**
 * Invoice Payment Settings Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Settings/InvoicePaymentSettingsController.php
 *
 * Description: Controller untuk invoice & payment settings tab
 *              Mengikuti standardized architecture pattern (TODO-2198)
 *              Auto-registers hooks untuk save & reset
 *
 * Changelog:
 * 1.0.0 - 2025-01-13
 * - Initial version following TODO-2198 pattern
 * - Extends AbstractSettingsController from wp-app-core
 * - Handles save/reset via hook system
 * - 3 lines doSave(), 1 line doReset()
 */

namespace WPCustomer\Controllers\Settings;

use WPAppCore\Controllers\Abstract\AbstractSettingsController;
use WPAppCore\Models\Abstract\AbstractSettingsModel;
use WPAppCore\Validators\Abstract\AbstractSettingsValidator;
use WPCustomer\Models\Settings\InvoicePaymentSettingsModel;
use WPCustomer\Validators\Settings\InvoicePaymentSettingsValidator;

defined('ABSPATH') || exit;

class InvoicePaymentSettingsController extends AbstractSettingsController {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        // Register notification messages via hook
        add_filter('wpc_settings_notification_messages', [$this, 'registerNotificationMessages']);
    }

    /**
     * Get plugin slug
     *
     * @return string
     */
    protected function getPluginSlug(): string {
        return 'wp-customer';
    }

    /**
     * Get plugin prefix for hooks
     *
     * @return string
     */
    protected function getPluginPrefix(): string {
        return 'wpc';
    }

    /**
     * Get settings page slug
     *
     * @return string
     */
    protected function getSettingsPageSlug(): string {
        return 'wp-customer-settings';
    }

    /**
     * Get capability required
     *
     * @return string
     */
    protected function getSettingsCapability(): string {
        return 'manage_options';
    }

    /**
     * Get default tabs
     *
     * @return array
     */
    protected function getDefaultTabs(): array {
        return [
            'invoice-payment' => __('Invoice & Payment', 'wp-customer'),
        ];
    }

    /**
     * Get model instance
     *
     * @return AbstractSettingsModel
     */
    protected function getModel(): AbstractSettingsModel {
        return new InvoicePaymentSettingsModel();
    }

    /**
     * Get validator instance
     *
     * @return AbstractSettingsValidator
     */
    protected function getValidator(): AbstractSettingsValidator {
        return new InvoicePaymentSettingsValidator();
    }

    /**
     * Get controller slug
     *
     * @return string
     */
    protected function getControllerSlug(): string {
        return 'invoice-payment';
    }

    /**
     * Save settings (3 lines)
     *
     * @param array $data POST data
     * @return bool
     */
    protected function doSave(array $data): bool {
        $option_name = $this->getOptionName();
        $settings_data = $data[$option_name] ?? [];
        return $this->model->saveSettings($settings_data);
    }

    /**
     * Reset settings (1 line)
     *
     * @return array
     */
    protected function doReset(): array {
        return $this->model->resetToDefaults() ? $this->model->getDefaults() : [];
    }

    /**
     * Register custom notification messages
     *
     * @param array $messages Existing messages
     * @return array Modified messages
     */
    public function registerNotificationMessages(array $messages): array {
        $messages['save_messages']['invoice-payment'] = __('Pengaturan invoice & payment berhasil disimpan.', 'wp-customer');
        $messages['reset_messages']['invoice-payment'] = __('Pengaturan invoice & payment berhasil direset ke nilai default.', 'wp-customer');

        return $messages;
    }

    /**
     * Prepare view data for template
     *
     * @param string $tab Current tab
     * @return array
     */
    public function prepareViewData(string $tab): array {
        if ($tab !== 'invoice-payment') {
            return [];
        }

        return [
            'settings' => $this->model->getSettings(),
        ];
    }
}
