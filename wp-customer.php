<?php
/**
 * Plugin Name: WP Customer
 * Plugin URI: 
 * Description: Plugin untuk mengelola data Customer dan Cabangnya
 * Version: 1.0.0
 * Author: arisciwek
 * Author URI: 
 * License: GPL v2 or later
 * 
 * @package     WP_Customer
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: /wp-customer/wp-customer.php
 */

defined('ABSPATH') || exit;

// Define plugin constants first, before anything else
define('WP_CUSTOMER_VERSION', '1.0.0');
define('WP_CUSTOMER_FILE', __FILE__);
define('WP_CUSTOMER_PATH', plugin_dir_path(__FILE__));
define('WP_CUSTOMER_URL', plugin_dir_url(__FILE__));
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
        require_once WP_CUSTOMER_PATH . 'includes/class-dependencies.php';
        require_once WP_CUSTOMER_PATH . 'includes/class-init-hooks.php';
        require_once WP_CUSTOMER_PATH . 'includes/class-admin-bar-info.php';
        require_once WP_CUSTOMER_PATH . 'includes/class-app-core-integration.php';

        $this->loader = new WP_Customer_Loader();

        // Initialize Settings Controller
        new \WPCustomer\Controllers\SettingsController();
    }

    /**
     * Initialize hooks and controllers
     */
    private function initHooks() {
        // Register activation/deactivation hooks
        register_activation_hook(WP_CUSTOMER_FILE, array('WP_Customer_Activator', 'activate'));
        register_deactivation_hook(WP_CUSTOMER_FILE, array('WP_Customer_Deactivator', 'deactivate'));

        // Register non-persistent cache groups to avoid conflicts with object cache plugins
        // This ensures our cache is runtime-only and doesn't persist to Memcached/Redis
        wp_cache_add_non_persistent_groups(array('wp_customer'));

        // Initialize dependencies
        $dependencies = new WP_Customer_Dependencies($this->plugin_name, $this->version);

        // Register asset hooks
        $this->loader->add_action('admin_enqueue_scripts', $dependencies, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $dependencies, 'enqueue_scripts');

        // Initialize menu
        $menu_manager = new \WPCustomer\Controllers\MenuManager($this->plugin_name, $this->version);
        $this->loader->add_action('init', $menu_manager, 'init');

        // Initialize controllers
        $this->initControllers();

        // Initialize other hooks
        $init_hooks = new WP_Customer_Init_Hooks();
        $init_hooks->init();

        // Initialize admin bar info display after WordPress is loaded
        $this->loader->add_action('init', 'WP_Customer_Admin_Bar_Info', 'init');

        // Initialize WP App Core integration
        $this->loader->add_action('init', 'WP_Customer_App_Core_Integration', 'init');
    }

    /**
     * Initialize plugin controllers
     */
    private function initControllers() {
        // Customer Controller
        $this->customer_controller = new \WPCustomer\Controllers\CustomerController();

        // Employee Controller
        new \WPCustomer\Controllers\Employee\CustomerEmployeeController();

        // Branch Controller
        new \WPCustomer\Controllers\Branch\BranchController();

        // Company Controllers
        new \WPCustomer\Controllers\Company\CompanyMembershipController();
        new \WPCustomer\Controllers\Company\CompanyInvoiceController();

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
     * Run the plugin
     */
    public function run() {
        $this->loader->run();
    }
}

/**
 * Returns the main instance of WPCustomer
 */
function wp_customer() {
    return WPCustomer::getInstance();
}

// Initialize the plugin
wp_customer()->run();
