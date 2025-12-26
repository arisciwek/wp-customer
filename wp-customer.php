<?php
/**
 * Plugin Name: WP Customer
 * Plugin URI:
 * Description: Plugin untuk mengelola data Customer dan Cabangnya
 * Version: 1.0.15
 * Author: arisciwek
 * Author URI:
 * License: GPL v2 or later
 *
 * @package     WP_Customer
 * @version     1.0.15
 * @author      arisciwek
 *
 * Path: /wp-customer/wp-customer.php
 *
 * Changelog:
 * 1.0.15 - 2025-11-02 (TODO-2190 Fully Global Architecture)
 * - Removed: customer-branch-map.js (plugin-specific adapter)
 * - Now uses: wpapp-map-adapter.js (global adapter from wp-app-core)
 * - Zero plugin-specific code needed for map integration
 * - All map functionality handled by wp-app-core global scope
 * - Cleaner, simpler, no duplication across plugins
 *
 * 1.0.14 - 2025-11-02 (TODO-2190 Global Scope Migration)
 * - Changed: Map picker migrated to wp-app-core as global shared component
 * - File: wpapp-map-picker.js now in wp-app-core/assets/js/map/
 * - Prevents code duplication across wp-app-* plugins
 * - wp-customer now only loads adapter (customer-branch-map.js)
 * - Leaflet.js now loaded globally from wp-app-core
 *
 * 1.0.13 - 2025-11-02 (TODO-2190)
 * - Added: Branch CRUD with wpAppModal integration
 * - Added: Map picker (Leaflet) for branch coordinates
 * - Added: customer-branch-map.js adapter for modal lifecycle
 * - Fixed: Nonce security issues in form submission
 * - Fixed: Table alias conflicts (cb/ce)
 * - Fixed: Customer context preservation in modals
 *
 * 1.0.12 - 2025-10-31 (TODO-2183)
 * - Added: AgencyAccessFilter instantiation for cross-plugin integration
 * - Fixed: customer_admin now can see Disnaker list (Task-2176)
 */

defined('ABSPATH') || exit;

// Define plugin constants first, before anything else
define('WP_CUSTOMER_VERSION', '1.0.15');
define('WP_CUSTOMER_FILE', __FILE__);
define('WP_CUSTOMER_PATH', plugin_dir_path(__FILE__));
define('WP_CUSTOMER_URL', plugin_dir_url(__FILE__));

// Load critical hook classes early
require_once WP_CUSTOMER_PATH . 'src/Hooks/CustomerDeleteHooks.php';
define('WP_CUSTOMER_DEVELOPMENT', false);


/**
 * Main plugin class
 */
class WPCustomer {
    /**
     * Single instance of the class
     */
    private static $instance = null;

    private $loader;
    private $plugin_name;
    private $version;
    private $customer_controller;
    private $dashboard_controller;

    /**
     * Get single instance of WPCustomer
     */
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->plugin_name = 'wp-customer';
        $this->version = WP_CUSTOMER_VERSION;

        // Register autoloader first
        require_once WP_CUSTOMER_PATH . 'includes/class-autoloader.php';
        $autoloader = new WPCustomerAutoloader('WPCustomer\\', WP_CUSTOMER_PATH);
        $autoloader->register();

        // Load textdomain immediately before including dependencies
        load_textdomain('wp-customer', WP_CUSTOMER_PATH . 'languages/wp-customer-id_ID.mo');

        $this->includeDependencies();

        $this->initHooks();
    }

    /**
     * Include required dependencies
     */
    private function includeDependencies() {
        require_once WP_CUSTOMER_PATH . 'includes/class-loader.php';
        require_once WP_CUSTOMER_PATH . 'includes/class-role-manager.php';
        require_once WP_CUSTOMER_PATH . 'includes/class-activator.php';
        require_once WP_CUSTOMER_PATH . 'includes/class-deactivator.php';
        // Note: class-dependencies.php replaced by AssetController
        require_once WP_CUSTOMER_PATH . 'includes/class-init-hooks.php';
        require_once WP_CUSTOMER_PATH . 'includes/class-upgrade.php';

        $this->loader = new WP_Customer_Loader();

        // Initialize AssetController
        $asset_controller = \WPCustomer\Controllers\Assets\AssetController::get_instance();
        $asset_controller->init();

        // Note: Settings controllers initialized in MenuManager
        // - OLD SettingsController: Legacy AJAX handlers
        // - NEW CustomerSettingsPageController: Standardized settings (TODO-2198)
    }

    /**
     * Initialize hooks and controllers
     */
    private function initHooks() {
        // Register activation/deactivation hooks
        register_activation_hook(WP_CUSTOMER_FILE, array('WP_Customer_Activator', 'activate'));
        register_deactivation_hook(WP_CUSTOMER_FILE, array('WP_Customer_Deactivator', 'deactivate'));

        // Register non-persistent cache groups to avoid conflicts with object cache plugins
        // This ensures our cache is runtime-only and doesn't persist to Memcached/Redis/W3TC
        wp_cache_add_non_persistent_groups(array(
            'wp_customer',
            'wp_customer_entity_relations',
            'wp_customer_branch_relations',
            'wp_customer_company_relations',
            'wp_customer_employee_relations'
        ));

        // Run upgrade check on admin_init (fixes duplicate wp_capabilities, etc.)
        $this->loader->add_action('admin_init', 'WP_Customer_Upgrade', 'check_and_upgrade');

        // Note: Asset loading now handled by AssetController (initialized in includeDependencies)

        // Initialize menu - call init() directly instead of via loader
        // to ensure admin_menu hook is registered in time
        $menu_manager = new \WPCustomer\Controllers\MenuManager($this->plugin_name, $this->version);
        $menu_manager->init();

        // Initialize controllers
        $this->initControllers();

        // Initialize delete hooks for cascade operations
        \WPCustomer\Hooks\CustomerDeleteHooks::init();

        // Initialize other hooks
        $init_hooks = new WP_Customer_Init_Hooks();
        $init_hooks->init();

        // NEW: Simplified WP App Core integration (v2.0)
        // wp-app-core handles ALL WordPress queries (user, role, permission)
        // wp-customer ONLY provides entity data from its tables
        add_filter('wp_app_core_user_entity_data', [$this, 'provide_entity_data'], 10, 3);

        // Custom role names for wp-customer roles
        add_filter('wp_app_core_role_display_name', [$this, 'get_role_display_name'], 10, 2);

        // Task-2165: Auto entity creation hooks
        $auto_entity_creator = new \WPCustomer\Handlers\AutoEntityCreator();
        add_action('wp_customer_customer_created', [$auto_entity_creator, 'handleCustomerCreated'], 10, 2);
        add_action('wp_customer_branch_created', [$auto_entity_creator, 'handleBranchCreated'], 10, 2);

        // Task-2167 Review-01: Branch deletion cleanup hooks
        $branch_cleanup_handler = new \WPCustomer\Handlers\BranchCleanupHandler();
        add_action('wp_customer_branch_before_delete', [$branch_cleanup_handler, 'handleBeforeDelete'], 10, 2);
        add_action('wp_customer_branch_deleted', [$branch_cleanup_handler, 'handleAfterDelete'], 10, 3);

        // Task-2168: Customer deletion cleanup hooks
        $customer_cleanup_handler = new \WPCustomer\Handlers\CustomerCleanupHandler();
        add_action('wp_customer_customer_before_delete', [$customer_cleanup_handler, 'handleBeforeDelete'], 10, 2);
        add_action('wp_customer_customer_deleted', [$customer_cleanup_handler, 'handleAfterDelete'], 10, 3);

        // Customer role-based filter (Review-01 from TODO-2187)
        // Filter customer DataTable based on user's customer_employees association
        $customer_role_filter = new \WPCustomer\Integrations\CustomerRoleFilter();

        // Agency customer filter - Province-based filtering for agency users
        // Agency users only see customers with branches in their province
        $agency_customer_filter = new \WPCustomer\Integrations\AgencyCustomerFilter();

        // NOTE: Agency company filter removed - now handled by EntityRelationModel
        // EntityRelationModel.get_accessible_entity_ids() handles agency users for 'company' entity
        // Returns company IDs based on province, integrated with DataTableAccessFilter

        // TODO-2183: Agency access filter integration (cross-plugin with wp-agency)
        $agency_access_filter = new \WPCustomer\Integrations\AgencyAccessFilter();

        // TODO-2183 Follow-up: Agency employee access filter integration
        $employee_access_filter = new \WPCustomer\Integrations\EmployeeAccessFilter();

        // TODO-2183 Follow-up: Branch access filter integration (new companies tab)
        $branch_access_filter = new \WPCustomer\Integrations\BranchAccessFilter();

        // Task-2170: Employee lifecycle hooks (created, updated, before_delete, deleted)
        $employee_cleanup_handler = new \WPCustomer\Handlers\EmployeeCleanupHandler();
        add_action('wp_customer_employee_before_delete', [$employee_cleanup_handler, 'handleBeforeDelete'], 10, 2);
        add_action('wp_customer_employee_deleted', [$employee_cleanup_handler, 'handleAfterDelete'], 10, 3);

        // Simple Integration: AgencyTabController
        // Injects customer statistics into wp-agency info tab via generic hook
        // MVC proper: Controller → Model → View
        $agency_tab_controller = new \WPCustomer\Controllers\Integration\AgencyTabController();
        $agency_tab_controller->init();

        // Initialize DataTableAccessFilter for access control
        // Filters agency datatable based on user role and access
        new \WPCustomer\Controllers\Integration\DataTableAccessFilter();

        // WP Admin Bar Integration - Add customer/company info to admin bar
        add_filter('wp_admin_bar_user_data', 'wp_customer_add_admin_bar_user_data', 10, 3);

        // DEBUG: Log all database queries related to agencies
        add_filter('query', function($query) {
            if (strpos($query, 'app_agencies') !== false) {
                error_log('=== FINAL SQL QUERY (agencies) ===');
                error_log($query);
                error_log('=== END SQL QUERY ===');
            }
            return $query;
        });

        // OLD CODE - Commented out (replaced by generic framework)
        // TODO-2071: Cross-plugin integration with wp-agency
        // Filter agencies based on customer's branches
        // Only initialize if wp-agency is active
        // if (class_exists('WPAgency\Models\Agency\AgencyDataTableModel')) {
        //     new \WPCustomer\Integrations\AgencyAccessFilter();
        // }
    }

    /**
     * Initialize plugin controllers
     */
    private function initControllers() {
        // Customer Controller
        $this->customer_controller = new \WPCustomer\Controllers\Customer\CustomerController();

        // Employee Controller
        new \WPCustomer\Controllers\Employee\CustomerEmployeeController();

        // Branch Controller
        new \WPCustomer\Controllers\Branch\BranchController();

        // Companies Controller (Branch Management with Hook-based System)
        new \WPCustomer\Controllers\Companies\CompaniesController();

        // Company Controllers
        new \WPCustomer\Controllers\Company\CompanyMembershipController();
        new \WPCustomer\Controllers\Company\CompanyInvoiceController(); // CRUD operations only

        // Integration Controllers (Hook-based Cross-Plugin Integration)
        // OLD CODE - Commented out (replaced by generic framework TODO-2179)
        // Task-2177: Agency Integration - Injects customer statistics into wp-agency dashboard
        // new \WPCustomer\Controllers\Integration\AgencyIntegrationController();

        // Register AJAX handlers
        add_action('wp_ajax_get_customer_stats', [$this->customer_controller, 'getStats']);
        add_action('wp_ajax_handle_customer_datatable', [$this->customer_controller, 'handleDataTableRequest']);
        add_action('wp_ajax_get_customer', [$this->customer_controller, 'show']);

        add_action('admin_menu', function() {
            $user = wp_get_current_user();
            if (in_array('customer', (array) $user->roles)) {
                // hapus menu yang tidak perlu
                remove_menu_page('edit.php'); // Posts
                remove_menu_page('edit-comments.php'); // Comments
                remove_menu_page('tools.php'); // Tools
            }
        });

    }

    /**
     * Provide entity data for wp-app-core admin bar (v2.0 simplified integration)
     *
     * wp-app-core queries WordPress user, roles, and permissions
     * wp-customer only provides customer/branch entity data
     *
     * @param array|null $entity_data Existing entity data (from other plugins)
     * @param int $user_id WordPress user ID
     * @param WP_User $user WordPress user object
     * @return array|null Entity data or null if not found
     */
    public function provide_entity_data($entity_data, $user_id, $user) {
        // Skip if another plugin already provided data
        if ($entity_data) {
            return $entity_data;
        }

        // Query customer entity data from Model
        $employee_model = new \WPCustomer\Models\Employee\CustomerEmployeeModel();
        $user_info = $employee_model->getUserInfo($user_id);

        if (!$user_info) {
            return null;
        }

        // Return ONLY entity data (customer/branch info)
        // wp-app-core will merge this with WordPress user/role/permission data
        return [
            'entity_name' => $user_info['customer_name'] ?? '',
            'entity_code' => $user_info['customer_code'] ?? '',
            'branch_name' => $user_info['branch_name'] ?? '',
            'branch_type' => $user_info['branch_type'] ?? '',
            'department' => $user_info['department'] ?? '',
            'position' => $user_info['position'] ?? '',
            'icon' => '🏢',
            'relation_type' => 'customer'
        ];
    }

    /**
     * Get custom role display name for wp-customer roles
     *
     * @param string $name Current display name
     * @param string $slug Role slug
     * @return string Role display name
     */
    public function get_role_display_name($name, $slug) {
        return WP_Customer_Role_Manager::getRoleName($slug) ?? $name;
    }

    /**
     * Run the plugin
     */
    public function run() {
        $this->loader->run();

        /**
         * Action: wp_customer_init
         *
         * Fires after wp-customer core is initialized.
         * Used by integration framework for bootstrapping.
         *
         * @since 1.0.12
         */
        do_action('wp_customer_init');
    }
}

/**
 * Returns the main instance of WPCustomer
 */
function wp_customer() {
    return WPCustomer::getInstance();
}

/**
 * Add customer/company data to WP Admin Bar
 *
 * Hook into: wp_admin_bar_user_data
 *
 * Displays:
 * - Customer/Company name
 * - Branch name & code
 * - Branch type (Pusat/Cabang)
 * - Employee name (if available)
 *
 * @param array $data Current user data
 * @param int $user_id WordPress user ID
 * @param WP_User $user WordPress user object
 * @return array Enhanced user data
 */
function wp_customer_add_admin_bar_user_data($data, $user_id, $user) {
    global $wpdb;

    // Check if user is customer employee
    $employee = $wpdb->get_row($wpdb->prepare(
        "SELECT ce.name as employee_name,
                c.name as customer_name,
                cb.name as branch_name,
                cb.code as branch_code,
                cb.type as branch_type
         FROM {$wpdb->prefix}app_customer_employees ce
         LEFT JOIN {$wpdb->prefix}app_customers c ON ce.customer_id = c.id
         LEFT JOIN {$wpdb->prefix}app_customer_branches cb ON ce.branch_id = cb.id
         WHERE ce.user_id = %d
         LIMIT 1",
        $user_id
    ));

    if (!$employee) {
        return $data; // Not a customer employee
    }

    // Prepare enhanced data
    $enhanced_data = [
        'entity_name' => $employee->customer_name,
        'entity_icon' => '🏢',
        'branch_name' => $employee->branch_name,
    ];

    // Add branch type as custom field if available
    if ($employee->branch_type) {
        $branch_type_label = $employee->branch_type === 'pusat' ? 'Pusat' : 'Cabang';
        $enhanced_data['custom_fields']['Tipe Cabang'] = $branch_type_label;
    }

    return array_merge($data, $enhanced_data);
}

// Initialize the plugin
wp_customer()->run();
