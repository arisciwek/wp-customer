<?php
/**
 * Customer General Settings Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Settings/CustomerGeneralSettingsController.php
 *
 * Description: Controller untuk general customer settings.
 *              Extends AbstractSettingsController dari wp-app-core.
 *              Handles general plugin settings (display, cache, API, system).
 *
 * Based On: wp-app-core/src/Controllers/Settings/PlatformGeneralSettingsController.php
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (TODO-2198)
 * - Initial implementation extending AbstractSettingsController
 * - Implements doSave() and doReset() methods
 * - Registers notification messages via hook
 * - Auto-registers wpapp_save_* and wpapp_reset_* hooks
 */

namespace WPCustomer\Controllers\Settings;

use WPAppCore\Controllers\Abstract\AbstractSettingsController;
use WPAppCore\Models\Abstract\AbstractSettingsModel;
use WPAppCore\Validators\Abstract\AbstractSettingsValidator;
use WPCustomer\Models\Settings\CustomerGeneralSettingsModel;
use WPCustomer\Validators\Settings\CustomerGeneralSettingsValidator;

class CustomerGeneralSettingsController extends AbstractSettingsController {

    /**
     * Get plugin slug
     *
     * @return string
     */
    protected function getPluginSlug(): string {
        return 'wp-customer';
    }

    /**
     * Get plugin prefix for hooks and options
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
     * Get capability required to manage settings
     *
     * @return string
     */
    protected function getSettingsCapability(): string {
        return 'manage_options';
    }

    /**
     * Get default tabs
     * No tabs - this controller handles single tab content
     *
     * @return array
     */
    protected function getDefaultTabs(): array {
        return []; // Handled by orchestrator
    }

    /**
     * Get model instance
     *
     * @return AbstractSettingsModel
     */
    protected function getModel(): AbstractSettingsModel {
        return new CustomerGeneralSettingsModel();
    }

    /**
     * Get validator instance
     *
     * @return AbstractSettingsValidator
     */
    protected function getValidator(): AbstractSettingsValidator {
        return new CustomerGeneralSettingsValidator();
    }

    /**
     * Get controller slug for this controller
     *
     * @return string
     */
    protected function getControllerSlug(): string {
        return 'general';
    }

    /**
     * Initialize controller
     * Registers hooks including notification messages
     */
    public function init(): void {
        parent::init(); // Registers wpapp_save_* and wpapp_reset_* hooks

        // Register notification messages
        add_filter('wpc_settings_notification_messages', [$this, 'registerNotificationMessages']);
    }

    /**
     * Register notification messages for this controller
     *
     * @param array $messages Existing messages from other controllers
     * @return array Modified messages with this controller's messages added
     */
    public function registerNotificationMessages(array $messages): array {
        // Save message - SEPARATED from reset
        $messages['save_messages']['general'] = __('General settings have been saved successfully.', 'wp-customer');

        // Reset message - SEPARATED from save
        $messages['reset_messages']['general'] = __('General settings have been reset to default values successfully.', 'wp-customer');

        return $messages;
    }

    /**
     * Save settings (implementation of abstract method)
     * Called by central dispatcher via hook
     *
     * @param array $data POST data
     * @return bool True if saved successfully
     */
    protected function doSave(array $data): bool {
        // Extract settings from POST data
        $settings = $data['wp_customer_settings'] ?? [];

        // Save via model
        return $this->model->saveSettings($settings);
    }

    /**
     * Reset settings to defaults (implementation of abstract method)
     * Called by central dispatcher via hook
     *
     * @return array Default settings
     */
    protected function doReset(): array {
        return $this->model->getDefaults();
    }
}
