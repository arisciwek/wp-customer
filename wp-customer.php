<?php
/**
 * Plugin Name: WP Customer
 * Plugin URI:
 * Description: Plugin untuk mengelola data Customer dan Cabangnya
 *   
 * @package     WPCustomer
 * @version     1.0.0
 * @author      arisciwek
 * 
 * License: GPL v2 or later
 */

defined('ABSPATH') || exit;
define('WP_CUSTOMER_VERSION', '1.0.0');

class WPCustomer {
    private static $instance = null;
    private $loader;
    private $plugin_name;
    private $version;
    private $customer_controller;
    private $dashboard_controller;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function defineConstants() {
        define('WP_CUSTOMER_FILE', __FILE__);
        define('WP_CUSTOMER_PATH', plugin_dir_path(__FILE__));
        define('WP_CUSTOMER_URL', plugin_dir_url(__FILE__));
    }

    private function __construct() {
        $this->plugin_name = 'wp-customer';
        $this->version = WP_CUSTOMER_VERSION;

        $this->defineConstants();
        $this->includeDependencies();
        $this->initHooks();
    }

    private function includeDependencies() {
        require_once WP_CUSTOMER_PATH . 'includes/class-loader.php';
        require_once WP_CUSTOMER_PATH . 'includes/class-activator.php';
        require_once WP_CUSTOMER_PATH . 'includes/class-deactivator.php';
        require_once WP_CUSTOMER_PATH . 'includes/class-dependencies.php';
        require_once WP_CUSTOMER_PATH . 'includes/class-init-hooks.php';

        require_once WP_CUSTOMER_PATH . 'src/Database/Installer.php';

        require_once WP_CUSTOMER_PATH . 'src/Controllers/Auth/CustomerRegistrationHandler.php';
        require_once WP_CUSTOMER_PATH . 'src/Controllers/SettingsController.php';
        require_once WP_CUSTOMER_PATH . 'src/Controllers/MenuManager.php';


        require_once WP_CUSTOMER_PATH . 'src/Models/Settings/SettingsModel.php';
        require_once WP_CUSTOMER_PATH . 'src/Models/Settings/PermissionModel.php';

        require_once WP_CUSTOMER_PATH . 'src/Controllers/CustomerController.php';
        require_once WP_CUSTOMER_PATH . 'src/Models/CustomerModel.php';

        require_once WP_CUSTOMER_PATH . 'src/Validators/CustomerValidator.php';
        require_once WP_CUSTOMER_PATH . 'src/Cache/CacheManager.php';

        require_once WP_CUSTOMER_PATH . 'src/Views/components/confirmation-modal.php';

        // Branch Related
        require_once WP_CUSTOMER_PATH . 'src/Controllers/branch/BranchController.php';
        require_once WP_CUSTOMER_PATH . 'src/Models/Branch/BranchModel.php';
        require_once WP_CUSTOMER_PATH . 'src/Validators/Branch/BranchValidator.php';

        // Employee
        require_once WP_CUSTOMER_PATH . 'src/Controllers/Employee/CustomerEmployeeController.php';
        require_once WP_CUSTOMER_PATH . 'src/Models/Employee/CustomerEmployeeModel.php';
        require_once WP_CUSTOMER_PATH . 'src/Validators/Employee/CustomerEmployeeValidator.php';

        // Add autoloader
        /*
        spl_autoload_register(function ($class) {
            $prefix = 'WPCustomer\\';
            $base_dir = __DIR__ . '/src/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }
            $relative_class = substr($class, $len);
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

            if (file_exists($file)) {
                require $file;
            }
        });
        */

        $this->loader = new WP_Customer_Loader();

        new \WPCustomer\Controllers\SettingsController();

    }

    private function initHooks() {
        register_activation_hook(__FILE__, array('WP_Customer_Activator', 'activate'));
        register_deactivation_hook(__FILE__, array('WP_Customer_Deactivator', 'deactivate'));

        // Inisialisasi dependencies
        $dependencies = new WP_Customer_Dependencies($this->plugin_name, $this->version);

        // Register hooks
        $this->loader->add_action('admin_enqueue_scripts', $dependencies, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $dependencies, 'enqueue_scripts');

        // Inisialisasi menu
        $menu_manager = new \WPCustomer\Controllers\MenuManager($this->plugin_name, $this->version);
        $this->loader->add_action('init', $menu_manager, 'init');

        register_activation_hook(__FILE__, array('WP_Customer_Activator', 'activate'));
        register_deactivation_hook(__FILE__, array('WP_Customer_Deactivator', 'deactivate'));
        
        // Set auto increment untuk user ID
        register_activation_hook(__FILE__, function() {
            global $wpdb;
            $wpdb->query("ALTER TABLE {$wpdb->prefix}users AUTO_INCREMENT = 11");
        });

        $this->initControllers(); 

          new \WPCustomer\Controllers\Branch\BranchController();

        $init_hooks = new WP_Customer_Init_Hooks();
        $init_hooks->init();          
    }

    private function initControllers() {
        // Inisialisasi controllers
        $this->customer_controller = new \WPCustomer\Controllers\CustomerController();

        // Inisialisasi Employee Controller
        new \WPCustomer\Controllers\Employee\CustomerEmployeeController();

        // Register AJAX hooks SEBELUM init

        // Tambahkan handler untuk stats
        add_action('wp_ajax_get_customer_stats', [$this->customer_controller, 'getStats']);

        add_action('wp_ajax_handle_customer_datatable', [$this->customer_controller, 'handleDataTableRequest']);
        add_action('wp_ajax_get_customer', [$this->customer_controller, 'show']);
    }


    public function run() {
        $this->loader->run();
    }
}

// Initialize plugin
function wp_customer() {
    return WPCustomer::getInstance();
}

// Start the plugin
wp_customer()->run();
