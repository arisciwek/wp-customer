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


        // Register autoloader first
        spl_autoload_register(function ($class) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                //error_log("Autoloader attempting to load: " . $class);
            }

            $prefix = 'WPCustomer\\';
            $base_dir = plugin_dir_path(__FILE__) . 'src/';
            
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }
            
            $relative_class = substr($class, $len);
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                //error_log("Looking for file: " . $file);
                //error_log("File exists: " . (file_exists($file) ? 'yes' : 'no'));
            }

            if (file_exists($file)) {
                require $file;
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    //error_log("Successfully loaded: " . $file);
                    return true;
                }
            }
        });

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
