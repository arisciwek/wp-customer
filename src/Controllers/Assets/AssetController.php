<?php
/**
 * Asset Controller Class
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Assets
 * @version     1.6.0
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
 * 1.7.0 - 2025-12-25
 * - Added: Map picker integration for company dashboard
 * - Added: Leaflet.js (1.9.4) enqueuing for map functionality
 * - Added: wpapp-map-picker.js (global from wp-app-core)
 * - Added: wpapp-map-adapter.js (global from wp-app-core)
 * - Added: company-map-adapter.js for company-specific map integration
 * - Support: Location picker in company edit forms
 *
 * 1.6.0 - 2025-11-14 (Task-2205)
 * - Added: Membership Groups Modal assets enqueuing
 * - Added: customer-membership-groups-modal-script.js
 * - Added: customer-membership-groups-modal-style.css
 * - Localized: wpCustomerGroupsModal with AJAX & i18n data
 * - Dependencies: wp-modal plugin for modal display
 *
 * 1.5.1 - 2025-11-09 (TODO-2196)
 * - Added: company-invoice-style.css enqueuing
 * - CSS includes invoice info grid styles (moved from PHP template)
 * - Clean separation: no inline CSS/JS in PHP templates
 *
 * 1.5.0 - 2025-11-09 (TODO-2196)
 * - Added: company-invoice-datatable.js enqueuing
 * - Added: enqueue_company_invoice_dashboard_assets() method
 * - Screen ID: toplevel_page_company-invoices
 * - Compatible with wp-datatable panel manager
 *
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
        if ($screen->id === 'toplevel_page_wp-customer') {
            $this->enqueue_customer_dashboard_assets();
        }

        // Company Dashboard (main page)
        if ($screen->id === 'toplevel_page_perusahaan') {
            $this->enqueue_company_dashboard_assets();
        }

        // Company Invoice Dashboard (main page - TODO-2196)
        if ($screen->id === 'toplevel_page_company-invoices') {
            $this->enqueue_company_invoice_dashboard_assets();
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
        // Enqueue Leaflet CSS (for branch map picker in edit forms)
        wp_enqueue_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            [],
            '1.9.4'
        );

        // Enqueue Leaflet JS (for branch map picker in edit forms)
        wp_enqueue_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            [],
            '1.9.4',
            false
        );

        // Enqueue global MapPicker from wp-app-core
        wp_enqueue_script(
            'wpapp-map-picker',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/map/wpapp-map-picker.js',
            ['jquery', 'leaflet'],
            WP_APP_CORE_VERSION,
            false
        );

        // Enqueue CSS for forms (create/edit customer modals)
        wp_enqueue_style(
            'customer-forms',
            WP_CUSTOMER_URL . 'assets/css/customer/customer-forms.css',
            [],
            $this->version
        );

        // Enqueue CSS for audit log / history tab
        wp_enqueue_style(
            'audit-log-styles',
            WP_CUSTOMER_URL . 'assets/css/audit-log/audit-log.css',
            [],
            $this->version
        );

        // Enqueue minimal JS for DataTable initialization
        // Dependency: jquery only (datatables will be loaded by wp-datatable BaseAssets)
        wp_enqueue_script(
            'customer-datatable',
            WP_CUSTOMER_URL . 'assets/js/customer/customer-datatable.js',
            ['jquery'],
            $this->version,
            false  // Load in header instead of footer
        );

        // Enqueue customer modal handler for CRUD operations (edit/delete buttons)
        // Listens to wpdt:action-edit and wpdt:action-delete events from wp-datatable
        wp_enqueue_script(
            'customer-modal-handler',
            WP_CUSTOMER_URL . 'assets/js/customer/customer-modal-handler.js',
            ['jquery', 'wp-modal', 'customer-datatable'],
            $this->version,
            true  // Load in footer
        );

        // Enqueue branches DataTable (for branches tab)
        wp_enqueue_script(
            'branches-datatable',
            WP_CUSTOMER_URL . 'assets/js/customer/branches-datatable.js',
            ['jquery', 'customer-datatable'],
            $this->version,
            false
        );

        // Enqueue employees DataTable (for employees tab)
        wp_enqueue_script(
            'employees-datatable',
            WP_CUSTOMER_URL . 'assets/js/customer/employees-datatable.js',
            ['jquery', 'customer-datatable'],
            $this->version,
            false
        );

        // Enqueue audit log DataTable (for history tab)
        wp_enqueue_script(
            'audit-log-datatable',
            WP_CUSTOMER_URL . 'assets/js/audit-log/audit-log.js',
            ['jquery', 'customer-datatable'],
            $this->version,
            false
        );

        // Enqueue customer branch map adapter for location picker in branch edit forms
        // Integrates global MapPicker with branch modal forms
        // Dependencies: wpapp-map-picker from wp-app-core and wp-modal
        wp_enqueue_script(
            'customer-branch-map',
            WP_CUSTOMER_URL . 'assets/js/customer/customer-branch-map.js',
            ['jquery', 'wp-modal', 'wpapp-map-picker', 'customer-datatable'],
            $this->version,
            true  // Load in footer
        );

        // Localize audit log script with nonce and i18n
        wp_localize_script('audit-log-datatable', 'wpCustomerAuditLog', [
            'nonce' => wp_create_nonce('wp_customer_ajax_nonce'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'i18n' => [
                'processing' => __('Loading...', 'wp-customer'),
                'search' => __('Search:', 'wp-customer'),
                'lengthMenu' => __('Show _MENU_ entries', 'wp-customer'),
                'info' => __('Showing _START_ to _END_ of _TOTAL_ entries', 'wp-customer'),
                'infoEmpty' => __('No entries available', 'wp-customer'),
                'infoFiltered' => __('(filtered from _MAX_ total entries)', 'wp-customer'),
                'zeroRecords' => __('No matching records found', 'wp-customer'),
                'emptyTable' => __('No audit logs available', 'wp-customer'),
                'paginate' => [
                    'first' => __('First', 'wp-customer'),
                    'previous' => __('Previous', 'wp-customer'),
                    'next' => __('Next', 'wp-customer'),
                    'last' => __('Last', 'wp-customer')
                ],
                'field' => __('Field', 'wp-customer'),
                'oldValue' => __('Old Value', 'wp-customer'),
                'newValue' => __('New Value', 'wp-customer'),
                'detailTitle' => __('Audit Log Details', 'wp-customer'),
                'close' => __('Close', 'wp-customer'),
                'modalLibraryNotLoaded' => __('Modal library not loaded', 'wp-customer')
            ]
        ]);

        // Localize script with nonce (shared by all DataTables and modal handler)
        // Used by: customer-datatable.js, customer-modal-handler.js
        wp_localize_script('customer-datatable', 'wpCustomerConfig', [
            'nonce' => wp_create_nonce('wpdt_nonce'),
            'ajaxUrl' => admin_url('admin-ajax.php')
        ]);
    }

    /**
     * Enqueue company dashboard assets
     *
     * @return void
     */
    private function enqueue_company_dashboard_assets(): void {
        // Enqueue Leaflet CSS (for map picker)
        wp_enqueue_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            [],
            '1.9.4'
        );

        // Enqueue Leaflet JS (for map picker)
        wp_enqueue_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            [],
            '1.9.4',
            false
        );

        // Enqueue global MapPicker from wp-app-core
        wp_enqueue_script(
            'wpapp-map-picker',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/map/wpapp-map-picker.js',
            ['jquery', 'leaflet'],
            WP_APP_CORE_VERSION,
            false
        );

        // Enqueue global Map Adapter from wp-app-core
        wp_enqueue_script(
            'wpapp-map-adapter',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/map/wpapp-map-adapter.js',
            ['jquery', 'leaflet', 'wpapp-map-picker'],
            WP_APP_CORE_VERSION,
            false
        );

        // Enqueue CSS for forms (edit company modals)
        wp_enqueue_style(
            'company-forms',
            WP_CUSTOMER_URL . 'assets/css/company/company-forms.css',
            [],
            $this->version
        );

        // Enqueue minimal JS for DataTable initialization
        // Dependency: jquery only (datatables will be loaded by wp-datatable BaseAssets)
        wp_enqueue_script(
            'company-datatable',
            WP_CUSTOMER_URL . 'assets/js/company/company-datatable.js',
            ['jquery'],
            $this->version,
            false  // Load in header instead of footer
        );

        // Enqueue employees DataTable (for staff tab)
        wp_enqueue_script(
            'company-employees-datatable',
            WP_CUSTOMER_URL . 'assets/js/company/company-employees-datatable.js',
            ['jquery', 'company-datatable'],
            $this->version,
            false
        );

        // Enqueue company modal handler for CRUD operations (edit/delete buttons)
        // Handles company-edit-btn and company-delete-btn clicks
        // Uses WPModal from wp-modal plugin
        wp_enqueue_script(
            'company-modal-handler',
            WP_CUSTOMER_URL . 'assets/js/company/company-modal-handler.js',
            ['jquery', 'wp-modal', 'company-datatable'],
            $this->version,
            true  // Load in footer
        );

        // Enqueue company map adapter for location picker in company forms
        // Integrates global MapPicker with company modal forms
        // Dependencies: wpapp-map-picker and wpapp-map-adapter from wp-app-core
        wp_enqueue_script(
            'company-map-adapter',
            WP_CUSTOMER_URL . 'assets/js/company/company-map-adapter.js',
            ['jquery', 'wp-modal', 'wpapp-map-adapter', 'company-datatable'],
            $this->version,
            true  // Load in footer
        );

        // Localize script with nonce (shared by all DataTables and modal)
        wp_localize_script('company-datatable', 'wpCompanyConfig', [
            'nonce' => wp_create_nonce('wpdt_nonce'),
            'ajaxUrl' => admin_url('admin-ajax.php')
        ]);

        // Localize script for company modal (wpCustomerData is expected by company-modal-handler.js)
        wp_localize_script('company-modal-handler', 'wpCustomerData', [
            'nonce' => wp_create_nonce('wpdt_nonce'),
            'ajaxUrl' => admin_url('admin-ajax.php')
        ]);
    }

    /**
     * Enqueue company invoice dashboard assets
     *
     * @return void
     */
    private function enqueue_company_invoice_dashboard_assets(): void {
        // Enqueue styles
        wp_enqueue_style(
            'company-invoice-style',
            WP_CUSTOMER_URL . 'assets/css/company/company-invoice-style.css',
            [],
            $this->version
        );

        // Enqueue minimal JS for DataTable initialization
        // Dependency: jquery only (datatables will be loaded by wp-datatable BaseAssets)
        wp_enqueue_script(
            'company-invoice-datatable',
            WP_CUSTOMER_URL . 'assets/js/company-invoice/company-invoice-datatable.js',
            ['jquery'],
            $this->version,
            false  // Load in header instead of footer
        );

        // Localize script with nonce
        wp_localize_script('company-invoice-datatable', 'wpCompanyInvoiceConfig', [
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
        // SHARED: Load base settings styles from wp-app-core
        wp_enqueue_style(
            'wpapp-settings-base',
            WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/wpapp-settings-style.css',
            [],
            WP_APP_CORE_VERSION
        );

        // CUSTOM: Load wp-customer-specific customizations
        wp_enqueue_style(
            'wp-customer-settings-style',
            WP_CUSTOMER_URL . 'assets/css/settings/customer-settings-style.css',
            ['wpapp-settings-base'], // Depends on shared base
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

        // SHARED: Load base settings script from wp-app-core
        // Handles Save button
        wp_enqueue_script(
            'wpapp-settings-base',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/wpapp-settings-script.js',
            ['jquery', 'wp-modal'],
            WP_APP_CORE_VERSION,
            true
        );

        // SHARED: Load reset script from wp-app-core (GLOBAL for ALL plugins)
        // Handles Reset button (#wpapp-settings-reset) with WPModal confirmation
        // File: wpapp-settings-reset-script.js (renamed from settings-reset-helper-post.js)
        // Used by: wp-customer, wp-agency, wp-disnaker, and all wp-app-* plugins
        wp_enqueue_script(
            'wpapp-settings-reset-script',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/wpapp-settings-reset-script.js',
            ['jquery', 'wp-modal', 'wpapp-settings-base'],
            WP_APP_CORE_VERSION,
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
            // REMOVED: 'permissions' case - now handled by CustomerPermissionsController
            // via AbstractPermissionsController->enqueueAssets() which loads shared assets from wp-app-core

            case 'general':
                wp_enqueue_style(
                    'wp-customer-general-tab',
                    WP_CUSTOMER_URL . 'assets/css/settings/general-tab-style.css',
                    ['wp-customer-settings-style'],  // Fixed: correct handle
                    $this->version
                );
                break;

            case 'membership-levels':
                wp_enqueue_style(
                    'wp-customer-membership-levels-tab',
                    WP_CUSTOMER_URL . 'assets/css/settings/customer-membership-levels-tab-style.css',
                    ['wp-customer-settings-style'],  // Fixed: correct handle
                    $this->version
                );
                break;

            case 'membership-features':
                wp_enqueue_style(
                    'wp-customer-membership-features-tab',
                    WP_CUSTOMER_URL . 'assets/css/settings/membership-features-tab-style.css',
                    ['wp-customer-settings-style'],  // Fixed: correct handle
                    $this->version
                );

                // Groups modal style (Task-2205)
                wp_enqueue_style(
                    'wp-customer-membership-groups-modal',
                    WP_CUSTOMER_URL . 'assets/css/settings/customer-membership-groups-modal-style.css',
                    ['wp-customer-membership-features-tab', 'wp-modal'],
                    $this->version
                );
                break;

            case 'demo-data':
                // SHARED: Load base demo-data styles from wp-app-core (TODO-2201)
                wp_enqueue_style(
                    'wpapp-demo-data',
                    WP_APP_CORE_PLUGIN_URL . 'assets/css/demo-data/wpapp-demo-data.css',
                    ['wpapp-settings-base'],  // Correct dependency handle
                    WP_APP_CORE_VERSION
                );

                // CUSTOM: Load wp-customer-specific customizations (if any)
                // Uncomment if needed for custom styles not in shared CSS
                // wp_enqueue_style(
                //     'wp-customer-demo-data-custom',
                //     WP_CUSTOMER_URL . 'assets/css/settings/customer-demo-data-custom.css',
                //     ['wpapp-demo-data'],
                //     $this->version
                // );
                break;

            case 'invoice-payment':
                wp_enqueue_style(
                    'wp-customer-invoice-payment-tab',
                    WP_CUSTOMER_URL . 'assets/css/settings/invoice-payment-style.css',
                    ['wp-customer-settings-style'],  // Fixed: correct handle
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
            // REMOVED: 'permissions' case - now handled by CustomerPermissionsController
            // via AbstractPermissionsController->enqueueAssets() which loads:
            // - shared JS from wp-app-core (permission-matrix.js)
            // - localized script data (wpappPermissions object)

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
                    ['jquery', 'wp-customer-toast'],  // Fixed: removed non-existent 'wp-customer-settings' dependency
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

                // Groups modal script (Task-2205)
                wp_enqueue_script(
                    'wp-customer-membership-groups-modal',
                    WP_CUSTOMER_URL . 'assets/js/settings/customer-membership-groups-modal-script.js',
                    ['jquery', 'wp-customer-toast', 'wp-modal'],  // depends on WP-Modal plugin
                    $this->version,
                    true
                );

                wp_localize_script(
                    'wp-customer-membership-groups-modal',
                    'wpCustomerGroupsModal',
                    [
                        'ajaxUrl' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce('wp_customer_nonce'),
                        'i18n' => [
                            'modalTitle' => __('Manage Feature Groups', 'wp-customer'),
                            'addGroup' => __('Add New Group', 'wp-customer'),
                            'editGroup' => __('Edit Group', 'wp-customer'),
                            'deleteConfirm' => __('Are you sure you want to delete this group? This action cannot be undone.', 'wp-customer'),
                            'closeModal' => __('Close', 'wp-customer'),
                            'loadError' => __('Failed to load group data', 'wp-customer'),
                            'saveError' => __('Failed to save group', 'wp-customer'),
                            'deleteError' => __('Failed to delete group', 'wp-customer'),
                            'reloadError' => __('Failed to reload groups list', 'wp-customer')
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
                // SHARED: Load demo-data script from wp-app-core (TODO-2201)
                wp_enqueue_script(
                    'wpapp-demo-data',
                    WP_APP_CORE_PLUGIN_URL . 'assets/js/demo-data/wpapp-demo-data.js',
                    ['jquery', 'wpapp-settings-base', 'wp-modal'], // Correct dependency handle
                    WP_APP_CORE_VERSION,
                    true
                );

                // Localize with wp-customer specific data
                wp_localize_script('wpapp-demo-data', 'wpCustomerSettings', [
                    'pluginPrefix' => 'customer',
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonces' => [
                        'generate' => wp_create_nonce('wp_customer_generate_demo'),
                        'delete' => wp_create_nonce('wp_customer_delete_demo'),
                    ],
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
