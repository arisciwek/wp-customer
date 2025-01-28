<?php
/**
 * Abstract Base Class for Demo Data Generation
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Demo/AbstractDemoData.php
 *
 * Description: Base abstract class for demo data generation.
 *              Provides common functionality and structure for:
 *              - Membership levels data generation
 *              - Customer data generation
 *              - Branch data generation
 *              - Employee data generation
 *              
 * Order of Execution:
 * 1. Membership Levels (base config)
 * 2. Customers (with WP Users)
 * 3. Branches 
 * 4. Employees
 *
 * Dependencies:
 * - WPUserGenerator for WordPress user creation
 * - WordPress database ($wpdb)
 * - WPCustomer Models:
 *   * CustomerMembershipModel
 *   * CustomerModel
 *   * BranchModel
 *
 * Changelog:
 * 1.0.0 - 2024-01-27
 * - Initial version
 * - Added base abstract structure
 * - Added model dependencies
 */

namespace WPCustomer\Database\Demo;

defined('ABSPATH') || exit;

abstract class AbstractDemoData {
    protected $wpdb;
    protected $customerMembershipModel;
    protected $customerModel;
    protected $branchModel;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Initialize models after plugins are loaded to prevent memory issues
        add_action('plugins_loaded', [$this, 'initModels'], 20);
    }

    public function initModels() {
        // Only initialize if not already done
        if (!isset($this->customerModel)) {
            if (class_exists('\WPCustomer\Models\Customer\CustomerModel')) {
                $this->customerModel = new \WPCustomer\Models\Customer\CustomerModel();
            }
        }
        
        if (!isset($this->branchModel)) {
            if (class_exists('\WPCustomer\Models\Branch\BranchModel')) {
                $this->branchModel = new \WPCustomer\Models\Branch\BranchModel();
            }
        }
        
        if (!isset($this->customerMembershipModel)) {
            if (class_exists('\WPCustomer\Models\Customer\CustomerMembershipModel')) {
                $this->customerMembershipModel = new \WPCustomer\Models\Customer\CustomerMembershipModel();
            }
        }
    }

    abstract protected function generate();
    abstract protected function validate();

    public function run() {
        try {
            // Ensure models are initialized
            $this->initModels();
            
            // Increase memory limit for demo data generation
            wp_raise_memory_limit('admin');
            
            $this->wpdb->query('START TRANSACTION');
            
            if (!$this->validate()) {
                throw new \Exception("Validation failed in " . get_class($this));
            }

            $this->generate();

            $this->wpdb->query('COMMIT');
            return true;

        } catch (\Exception $e) {
            $this->wpdb->query('ROLLBACK');
            $this->debug("Demo data generation failed: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Check if development mode is enabled
     * This can be enabled via settings or WP_CUSTOMER_DEVELOPMENT constant
     * 
     * @return bool True if development mode is enabled
     */
    public function isDevelopmentMode(): bool {
        $dev_settings = get_option('wp_customer_development_settings');
        return (isset($dev_settings['enable_development']) && $dev_settings['enable_development']) 
               || (defined('WP_CUSTOMER_DEVELOPMENT') && WP_CUSTOMER_DEVELOPMENT);
    }

    public function shouldClearData(): bool {
        $dev_settings = get_option('wp_customer_development_settings');
        return isset($dev_settings['clear_data_on_deactivate']) && 
               $dev_settings['clear_data_on_deactivate'];
    }

    protected function debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[" . get_class($this) . "] {$message}");
        }
    }
}
