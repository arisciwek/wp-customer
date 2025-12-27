<?php
/**
 * Customer Settings Page Controller (Orchestrator)
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Settings/CustomerSettingsPageController.php
 *
 * Description: Main orchestrator untuk customer settings page.
 *              Follows standardized pattern from wp-app-core (TODO-1205).
 *              Delegates to specialized controllers for each settings area.
 *
 * Based On: wp-app-core/src/Controllers/Settings/PlatformSettingsPageController.php
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (TODO-2198)
 * - Initial implementation following wp-app-core pattern
 * - Central dispatcher for save & reset operations
 * - Hook-based architecture for tab controllers
 * - Single Responsibility: Page rendering & tab coordination
 */

namespace WPCustomer\Controllers\Settings;

use WPCustomer\Controllers\Settings\CustomerGeneralSettingsController;
use WPCustomer\Controllers\Settings\InvoicePaymentSettingsController;
use WPCustomer\Controllers\Settings\CustomerPermissionsController;
use WPCustomer\Controllers\Settings\CustomerDemoDataController;

defined('ABSPATH') || exit;

class CustomerSettingsPageController {

    private array $controllers = [];

    public function __construct() {
        // Initialize specialized controllers
        $this->controllers = [
            'general' => new CustomerGeneralSettingsController(),
            'invoice-payment' => new InvoicePaymentSettingsController(),
            'permissions' => new CustomerPermissionsController(),
            'demo-data' => new CustomerDemoDataController(),
            'membership-features' => new \WPCustomer\Controllers\Settings\MembershipFeaturesController(),  // Added: Register AJAX hooks
            'membership-groups' => new \WPCustomer\Controllers\Settings\MembershipGroupsController(),      // Added: Register AJAX hooks
            // 'membership' => new CustomerMembershipSettingsController(), // TODO: Create this controller
            // Add more tabs here as needed
        ];
    }

    /**
     * Initialize controller
     */
    public function init(): void {
        // Initialize all specialized controllers FIRST
        // This registers their hooks (wpapp_save_*, wpapp_reset_*)
        foreach ($this->controllers as $tab => $controller) {
            // Only call init() if method exists (some controllers like CRUD controllers don't need it)
            if (method_exists($controller, 'init')) {
                $controller->init();
            }
        }

        // CRITICAL: Central dispatcher - handle save/reset BEFORE WordPress processes form
        add_action('admin_init', [$this, 'handleFormSubmission'], 1); // Priority 1 - very early

        // Register settings
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Central dispatcher for save & reset
     * Priority 1 - runs BEFORE WordPress Settings API processes form
     */
    public function handleFormSubmission(): void {
        // Only handle POST requests with option_page
        if (empty($_POST) || !isset($_POST['option_page'])) {
            return;
        }

        $option_page = $_POST['option_page'] ?? '';

        // Only handle our settings
        $our_settings = [
            'wp_customer_settings',
            'wp_customer_invoice_payment_settings',
            'wp_customer_membership_settings',
            // Add more option pages here as needed
        ];

        if (!in_array($option_page, $our_settings)) {
            return;
        }

        // Verify nonce
        check_admin_referer($option_page . '-options');

        // DISPATCH: Reset request?
        if (isset($_POST['reset_to_defaults']) && $_POST['reset_to_defaults'] === '1') {
            $this->dispatchReset($option_page);
            return; // exit handled in dispatchReset
        }

        // DISPATCH: Save request
        $this->dispatchSave($option_page);
        // Let WordPress continue to handle redirect
    }

    /**
     * Dispatch reset request via hook
     *
     * @param string $option_page Option page being reset
     */
    private function dispatchReset(string $option_page): void {
        // Trigger hook - controller yang match option_page akan respond
        $defaults = apply_filters("wpapp_reset_{$option_page}", [], $option_page);

        if (empty($defaults)) {
            // No controller handled this
            wp_die(__('Invalid reset request - no controller responded.', 'wp-customer'));
        }

        // Update option with defaults
        update_option($option_page, $defaults);

        // Build redirect URL
        $current_tab = $_POST['current_tab'] ?? '';
        $redirect_url = add_query_arg([
            'page' => 'wp-customer-settings',
            'tab' => $current_tab,
            'reset' => 'success',
            'reset_tab' => $current_tab
        ], admin_url('admin.php'));

        // CRITICAL: Redirect and exit to prevent WordPress from processing form
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Dispatch save request via hook
     *
     * @param string $option_page Option page being saved
     */
    private function dispatchSave(string $option_page): void {
        // Trigger hook - controller yang match option_page akan respond
        $saved = apply_filters("wpapp_save_{$option_page}", false, $_POST);

        if (!$saved) {
            // Validation failed or no controller handled this
            add_settings_error(
                $option_page,
                'save_failed',
                __('Failed to save settings. Please check your input.', 'wp-customer')
            );
        }

        // Let WordPress continue to process form and redirect
        // This allows WordPress Settings API to handle redirect with settings-updated parameter
    }

    /**
     * Register settings for WordPress Settings API
     */
    public function registerSettings(): void {
        // Each settings controller handles its own registration via AbstractSettingsController
        // This method remains for backward compatibility and future global settings

        // Add redirect after settings saved to prevent form resubmission
        add_filter('wp_redirect', [$this, 'addSettingsSavedMessage'], 10, 2);

        // Register development settings
        register_setting(
            'wp_customer_development_settings',  // Option group (matches settings_fields())
            'wp_customer_development_settings',  // Option name
            [
                'type' => 'array',
                'description' => 'WP Customer development settings',
                'sanitize_callback' => [$this, 'sanitizeDevelopmentSettings'],
                'default' => [
                    'enable_development' => 0,
                    'clear_data_on_deactivate' => 0
                ]
            ]
        );
    }

    /**
     * Get notification messages from all controllers via hook
     *
     * Hook pattern: Each controller registers their messages
     * This creates an abstraction layer for tab notifications
     *
     * @return array ['save_messages' => [...], 'reset_messages' => [...]]
     */
    public function getNotificationMessages(): array {
        $messages = [
            'save_messages' => [],
            'reset_messages' => []
        ];

        /**
         * Hook: wpc_settings_notification_messages
         *
         * Allows each controller to register their notification messages
         *
         * @param array $messages Array with 'save_messages' and 'reset_messages'
         * @return array Modified messages array
         *
         * @example
         * add_filter('wpc_settings_notification_messages', function($messages) {
         *     $messages['save_messages']['general'] = __('General settings saved', 'wp-customer');
         *     $messages['reset_messages']['general'] = __('General settings reset', 'wp-customer');
         *     return $messages;
         * });
         */
        $messages = apply_filters('wpc_settings_notification_messages', $messages);

        return $messages;
    }

    /**
     * Render settings page
     */
    public function renderPage(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'wp-customer'));
        }

        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

        // Make controller accessible in template
        $controller = $this;

        // Load settings page template
        // TODO: Create template file at src/Views/templates/settings/settings-page.php
        $template_path = WP_CUSTOMER_PATH . 'src/Views/templates/settings/settings-page.php';

        if (file_exists($template_path)) {
            require_once $template_path;
        } else {
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Customer Settings', 'wp-customer') . '</h1>';
            echo '<p>' . esc_html__('Settings page template not found. Please create:', 'wp-customer') . ' ' . esc_html($template_path) . '</p>';
            echo '</div>';
        }
    }

    /**
     * Load tab view
     */
    public function loadTabView(string $tab): void {
        $allowed_tabs = [
            'general' => 'tab-general.php',
            'invoice-payment' => 'tab-invoice-payment.php',
            'permissions' => 'tab-permissions.php',
            'membership' => 'tab-membership.php',
            'membership-levels' => 'tab-membership-levels.php',
            'membership-features' => 'tab-membership-features.php',
            'demo-data' => 'tab-demo-data.php',
            // Add more tabs here as needed
        ];

        $tab = isset($allowed_tabs[$tab]) ? $tab : 'general';

        // Prepare view data based on tab
        $view_data = $this->prepareViewData($tab);

        $tab_file = WP_CUSTOMER_PATH . 'src/Views/templates/settings/' . $allowed_tabs[$tab];

        if (file_exists($tab_file)) {
            if (!empty($view_data)) {
                extract($view_data);
            }
            require_once $tab_file;
        } else {
            echo sprintf(
                __('Tab file not found: %s', 'wp-customer'),
                esc_html($tab_file)
            );
        }
    }

    /**
     * Prepare view data for tabs
     */
    private function prepareViewData(string $tab): array {
        $data = [];

        switch ($tab) {
            case 'general':
                if (isset($this->controllers['general'])) {
                    $controller = $this->controllers['general'];
                    $data['settings'] = $controller->getModelInstance()->getSettings();
                }
                break;

            case 'invoice-payment':
                if (isset($this->controllers['invoice-payment'])) {
                    $controller = $this->controllers['invoice-payment'];
                    $data['settings'] = $controller->getModelInstance()->getSettings();
                }
                break;

            case 'membership':
                if (isset($this->controllers['membership'])) {
                    $controller = $this->controllers['membership'];
                    $data['settings'] = $controller->getModelInstance()->getSettings();
                }
                break;

            case 'membership-features':
                // Use already-instantiated controllers (AJAX hooks registered in __construct)
                if (isset($this->controllers['membership-features']) && isset($this->controllers['membership-groups'])) {
                    $data['grouped_features'] = $this->controllers['membership-features']->getAllFeatures();
                    $data['field_groups'] = $this->controllers['membership-groups']->getAllGroups();
                }
                break;

            // Add more tabs here as needed
        }

        return $data;
    }

    /**
     * Get tabs for navigation
     */
    public function getTabs(): array {
        return [
            'general' => __('General', 'wp-customer'),
            'invoice-payment' => __('Invoice & Payment', 'wp-customer'),
            'permissions' => __('Permissions', 'wp-customer'),
            'membership' => __('Membership', 'wp-customer'),
            // Add more tabs here as needed
        ];
    }

    /**
     * Add settings saved message to redirect URL
     * Prevents form resubmission issue
     *
     * @param string $location Redirect location
     * @param int $status HTTP status code
     * @return string Modified redirect location
     */
    public function addSettingsSavedMessage(string $location, int $status): string {
        // Only handle redirects from options.php for our settings
        if (strpos($location, 'page=wp-customer-settings') === false) {
            return $location;
        }

        // SKIP if this is a RESET request (not a save request)
        if (isset($_POST['reset_to_defaults']) && $_POST['reset_to_defaults'] === '1') {
            // Reset is handled by dispatchReset() - already redirected with reset=success
            return $location;
        }

        // Check if this is a settings save redirect
        if (isset($_POST['option_page'])) {
            $option_page = $_POST['option_page'];

            // Only for our settings pages
            $our_settings = [
                'wp_customer_settings',
                'wp_customer_membership_settings',
                // Add more option pages here as needed
            ];

            if (in_array($option_page, $our_settings)) {
                // IMPORTANT: Remove reset parameters first (prevent duplicate notifications)
                $location = remove_query_arg(['reset', 'reset_tab', 'message'], $location);

                // Add settings-updated parameter if not already present
                if (strpos($location, 'settings-updated=true') === false) {
                    $location = add_query_arg('settings-updated', 'true', $location);
                }

                // Add saved_tab parameter to show tab-specific success message
                if (isset($_POST['saved_tab'])) {
                    $saved_tab = sanitize_key($_POST['saved_tab']);
                    $location = add_query_arg('saved_tab', $saved_tab, $location);
                }
            }
        }

        return $location;
    }

    /**
     * Sanitize development settings
     *
     * @param array $input Raw input from form
     * @return array Sanitized settings
     */
    public function sanitizeDevelopmentSettings($input): array {
        $sanitized = [];

        // Sanitize enable_development (checkbox)
        $sanitized['enable_development'] = isset($input['enable_development']) && $input['enable_development'] == '1' ? 1 : 0;

        // Sanitize clear_data_on_deactivate (checkbox)
        $sanitized['clear_data_on_deactivate'] = isset($input['clear_data_on_deactivate']) && $input['clear_data_on_deactivate'] == '1' ? 1 : 0;

        return $sanitized;
    }
}
