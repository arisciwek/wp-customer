<?php
/**
 * Asset Controller Class
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Assets
 * @version     1.4.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Assets/AssetController.php
 *
 * Description: Mengelola asset loading untuk plugin wp-customer.
 *              Menggantikan class-dependencies.php dengan pattern
 *              yang lebih modular menggunakan Singleton pattern.
 *              Inspired by wp-datatable dan wp-modal AssetController.
 *
 * Changelog:
 * 1.4.0 - 2025-11-09
 * - Added: customer-datatable.js enqueuing (minimal, no conflict)
 * - NO inline scripts in PHP views (per user request)
 * - Standalone JS file handles DataTable init + click events
 * - Compatible with wp-datatable panel manager
 *
 * 1.3.0 - 2025-11-09 (REVERTED - inline scripts not preferred)
 * - Pattern: Inline scripts in views + wp-datatable panel manager
 *
 * 1.2.0 - 2025-11-09 (REVERTED)
 * - Added: Customer dashboard asset enqueuing (caused conflicts)
 * - Fixes: Modal system (wpAppModal → WPModal migration)
 *
 * 1.1.0 - 2025-11-09
 * - Added: Settings page asset enqueuing
 * - Added: Tab-specific styles and scripts for 6 settings tabs
 * - Added: Localization for settings scripts
 * - Ported from class-dependencies.php.txt
 *
 * 1.0.0 - 2025-11-09
 * - Initial creation
 * - Minimal structure - assets will be added as needed
 * - Singleton pattern implementation
 */

namespace WPCustomer\Controllers\Assets;

class AssetController {
    /**
     * Singleton instance
     *
     * @var AssetController|null
     */
    private static $instance = null;

    /**
     * Plugin name
     *
     * @var string
     */
    private $plugin_name;

    /**
     * Plugin version
     *
     * @var string
     */
    private $version;

    /**
     * Get singleton instance
     *
     * @return AssetController
     */
    public static function get_instance(): AssetController {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor (Singleton pattern)
     */
    private function __construct() {
        $this->plugin_name = 'wp-customer';
        $this->version = defined('WP_CUSTOMER_VERSION') ? WP_CUSTOMER_VERSION : '1.0.0';

        // Register hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Initialize AssetController
     * Called from main plugin file
     *
     * @return void
     */
    public function init(): void {
        // Hook for extensions to register additional assets
        do_action('wpc_register_assets', $this);
    }

    /**
     * Enqueue frontend assets
     *
     * @return void
     */
    public function enqueue_frontend_assets(): void {
        // Ignore admin and ajax requests
        if (is_admin() || wp_doing_ajax()) {
            return;
        }

        // Assets will be added as needed
    }

    /**
     * Enqueue admin assets (styles and scripts)
     *
     * @return void
     */
    public function enqueue_admin_assets(): void {
        $screen = get_current_screen();
        if (!$screen) {
            return;
        }

        // Customer Dashboard (main page)
        if ($screen->id === 'toplevel_page_wp-customer-v2') {
            $this->enqueue_customer_dashboard_assets();
        }

        // Settings page assets
        if ($screen->id === 'wp-customer_page_wp-customer-settings') {
            $this->enqueue_settings_assets();
        }
    }

    /**
     * Enqueue customer dashboard assets
     *
     * @return void
     */
    private function enqueue_customer_dashboard_assets(): void {
        // Enqueue minimal JS for DataTable initialization
        // Dependency: jquery only (datatables will be loaded by wp-datatable BaseAssets)
        wp_enqueue_script(
            'customer-datatable',
            WP_CUSTOMER_URL . 'assets/js/customer/customer-datatable.js',
            ['jquery'],
            $this->version,
            false  // Load in header instead of footer
        );

        // Enqueue branches DataTable (for branches tab)
        wp_enqueue_script(
            'branches-datatable',
            WP_CUSTOMER_URL . 'assets/js/customer/branches-datatable.js',
            ['jquery', 'customer-datatable'],
            $this->version,
            false
        );

        // Localize script with nonce (shared by all DataTables)
        wp_localize_script('customer-datatable', 'wpCustomerConfig', [
            'nonce' => wp_create_nonce('wpdt_nonce'),
            'ajaxUrl' => admin_url('admin-ajax.php')
        ]);
    }

    /**
     * Enqueue settings page assets (styles and scripts)
     *
     * @return void
     */
    private function enqueue_settings_assets(): void {
        // Main settings styles
        wp_enqueue_style(
            'wp-customer-settings',
            WP_CUSTOMER_URL . 'assets/css/settings/settings-style.css',
            [],
            $this->version
        );

        wp_enqueue_style(
            'wp-customer-modal',
            WP_CUSTOMER_URL . 'assets/css/customer/confirmation-modal.css',
            [],
            $this->version
        );

        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

        // Tab-specific styles
        $this->enqueue_settings_tab_styles($current_tab);

        // Common scripts for settings page
        wp_enqueue_script(
            'wp-customer-toast',
            WP_CUSTOMER_URL . 'assets/js/customer/customer-toast.js',
            ['jquery'],
            $this->version,
            true
        );

        wp_enqueue_script(
            'confirmation-modal',
            WP_CUSTOMER_URL . 'assets/js/customer/confirmation-modal.js',
            ['jquery'],
            $this->version,
            true
        );

        wp_enqueue_script(
            'wp-customer-settings',
            WP_CUSTOMER_URL . 'assets/js/settings/settings-script.js',
            ['jquery', 'wp-customer-toast'],
            $this->version,
            true
        );

        // Tab-specific scripts
        $this->enqueue_settings_tab_scripts($current_tab);
    }

    /**
     * Enqueue settings tab-specific styles
     *
     * @param string $current_tab Current tab ID
     * @return void
     */
    private function enqueue_settings_tab_styles(string $current_tab): void {
        switch ($current_tab) {
            case 'permissions':
                wp_enqueue_style(
                    'wp-customer-permissions-tab',
                    WP_CUSTOMER_URL . 'assets/css/settings/permissions-tab-style.css',
                    ['wp-customer-settings'],
                    $this->version
                );
                break;

            case 'general':
                wp_enqueue_style(
                    'wp-customer-general-tab',
                    WP_CUSTOMER_URL . 'assets/css/settings/general-tab-style.css',
                    ['wp-customer-settings'],
                    $this->version
                );
                break;

            case 'membership-levels':
                wp_enqueue_style(
                    'wp-customer-membership-levels-tab',
                    WP_CUSTOMER_URL . 'assets/css/settings/customer-membership-levels-tab-style.css',
                    ['wp-customer-settings'],
                    $this->version
                );
                break;

            case 'membership-features':
                wp_enqueue_style(
                    'wp-customer-membership-features-tab',
                    WP_CUSTOMER_URL . 'assets/css/settings/membership-features-tab-style.css',
                    ['wp-customer-settings'],
                    $this->version
                );
                break;

            case 'demo-data':
                wp_enqueue_style(
                    'wp-customer-demo-data-tab',
                    WP_CUSTOMER_URL . 'assets/css/settings/demo-data-tab-style.css',
                    ['wp-customer-settings'],
                    $this->version
                );
                break;

            case 'invoice-payment':
                wp_enqueue_style(
                    'wp-customer-invoice-payment-tab',
                    WP_CUSTOMER_URL . 'assets/css/settings/invoice-payment-style.css',
                    ['wp-customer-settings'],
                    $this->version
                );
                break;
        }
    }

    /**
     * Enqueue settings tab-specific scripts
     *
     * @param string $current_tab Current tab ID
     * @return void
     */
    private function enqueue_settings_tab_scripts(string $current_tab): void {
        switch ($current_tab) {
            case 'permissions':
                wp_enqueue_script(
                    'wp-customer-permissions-tab',
                    WP_CUSTOMER_URL . 'assets/js/settings/customer-permissions-tab-script.js',
                    ['jquery', 'wp-customer-settings', 'wp-customer-toast'],
                    $this->version,
                    true
                );

                wp_localize_script('wp-customer-permissions-tab', 'wpCustomerData', [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wp_customer_reset_permissions'),
                    'i18n' => [
                        'resetConfirmTitle' => __('Reset Permissions?', 'wp-customer'),
                        'resetConfirmMessage' => __('This will restore all permissions to their default settings. This action cannot be undone.', 'wp-customer'),
                        'resetConfirmButton' => __('Reset Permissions', 'wp-customer'),
                        'resetting' => __('Resetting...', 'wp-customer'),
                        'cancelButton' => __('Cancel', 'wp-customer')
                    ]
                ]);
                break;

            case 'general':
                wp_localize_script('wp-customer-settings', 'wpCustomerData', [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'clearCacheNonce' => wp_create_nonce('wp_customer_clear_cache')
                ]);
                break;

            case 'membership-features':
                wp_enqueue_script(
                    'wp-customer-membership-features-tab',
                    WP_CUSTOMER_URL . 'assets/js/settings/customer-membership-features-tab-script.js',
                    ['jquery', 'wp-customer-settings'],
                    $this->version,
                    true
                );

                wp_localize_script(
                    'wp-customer-membership-features-tab',
                    'wpCustomerSettings',
                    [
                        'ajaxUrl' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce('wp_customer_nonce'),
                        'i18n' => [
                            'addFeature' => __('Add New Feature', 'wp-customer'),
                            'editFeature' => __('Edit Feature', 'wp-customer'),
                            'deleteConfirm' => __('Are you sure you want to delete this feature?', 'wp-customer'),
                            'loadError' => __('Failed to load feature data', 'wp-customer'),
                            'saveError' => __('Failed to save feature', 'wp-customer'),
                            'deleteError' => __('Failed to delete feature', 'wp-customer'),
                            'saving' => __('Saving...', 'wp-customer'),
                            'loading' => __('Loading...', 'wp-customer')
                        ]
                    ]
                );
                break;

            case 'membership-levels':
                wp_enqueue_script(
                    'wp-membership-levels',
                    WP_CUSTOMER_URL . 'assets/js/settings/customer-membership-levels-tab-script.js',
                    ['jquery', 'wp-customer-settings'],
                    $this->version,
                    true
                );

                wp_localize_script('wp-membership-levels', 'wpCustomerData', [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wp_customer_nonce'),
                    'i18n' => [
                        'confirmDelete' => __('Are you sure you want to delete this membership level?', 'wp-customer'),
                        'saveSuccess' => __('Membership level saved successfully.', 'wp-customer'),
                        'saveError' => __('Failed to save membership level.', 'wp-customer'),
                        'deleteSuccess' => __('Membership level deleted successfully.', 'wp-customer'),
                        'deleteError' => __('Failed to delete membership level.', 'wp-customer'),
                        'loadError' => __('Failed to load membership level data.', 'wp-customer'),
                        'required' => __('This field is required.', 'wp-customer'),
                        'invalidNumber' => __('Please enter a valid number.', 'wp-customer')
                    ]
                ]);
                break;

            case 'demo-data':
                wp_enqueue_script(
                    'wp-customer-demo-data-tab',
                    WP_CUSTOMER_URL . 'assets/js/settings/customer-demo-data-tab-script.js',
                    ['jquery', 'wp-customer-settings'],
                    $this->version,
                    true
                );

                wp_localize_script('wp-customer-demo-data-tab', 'wpCustomerDemoData', [
                    'i18n' => [
                        'errorMessage' => __('An error occurred while generating demo data.', 'wp-customer'),
                        'generating' => __('Generating...', 'wp-customer')
                    ]
                ]);
                break;

            case 'invoice-payment':
                wp_enqueue_script(
                    'wp-customer-invoice-payment-tab',
                    WP_CUSTOMER_URL . 'assets/js/settings/invoice-payment-script.js',
                    ['jquery', 'wp-customer-settings'],
                    $this->version,
                    true
                );
                break;
        }
    }
}
