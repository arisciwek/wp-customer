<?php
/**
 * Membership Levels Demo Data Generator
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Demo/MembershipLevelsDemoData.php
 *
 * Description: Generates demo data for membership levels.
 *              First component to run in demo data generation.
 *              Sets up base configuration for:
 *              - Regular membership (2 staff max)
 *              - Priority membership (5 staff max)
 *              - Utama membership (unlimited staff)
 *              
 * Dependencies:
 * - WPCustomer\Models\Customer\CustomerMembershipModel
 * - WordPress database ($wpdb)
 * - WordPress Options API
 *
 * Order of Operations:
 * 1. Clean existing membership data
 * 2. Setup membership defaults
 * 3. Insert membership levels
 *
 * Changelog:
 * 1.0.0 - 2024-01-27
 * - Initial version
 * - Added membership levels setup
 * - Added data cleaning
 */

namespace WPCustomer\Database\Demo;

use WPCustomer\Models\Customer\CustomerMembershipModel;

defined('ABSPATH') || exit;

class MembershipLevelsDemoData extends AbstractDemoData {
    /**
     * Validate before generating data
     */
    protected function validate(): bool {
        try {
            // Check if table exists
            $table_exists = $this->wpdb->get_var(
                "SHOW TABLES LIKE '{$this->wpdb->prefix}app_customer_membership_levels'"
            );
            
            if (!$table_exists) {
                throw new \Exception('Membership levels table does not exist');
            }

            // Check if settings key exists in options
            if (!get_option('wp_customer_membership_settings')) {
                $this->debug('Membership settings not found - will be created');
            }

            return true;
        } catch (\Exception $e) {
            $this->debug('Validation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate membership levels data
     */
    protected function generate(): bool {
        try {
            // Clear existing data
            $this->clearExistingData();
            
            // Setup membership defaults
            if (!$this->customerMembershipModel->setupMembershipDefaults()) {
                throw new \Exception('Failed to setup membership defaults');
            }
            $this->debug('Membership defaults setup complete');

            // Insert default levels
            if (!$this->customerMembershipModel->insertDefaultLevels()) {
                throw new \Exception('Failed to insert default membership levels');
            }
            $this->debug('Default membership levels inserted');

            return true;
        } catch (\Exception $e) {
            $this->debug('Generation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear existing membership data
     */
    private function clearExistingData(): void {
        // Clear membership levels table
        $this->wpdb->query("TRUNCATE TABLE {$this->wpdb->prefix}app_customer_membership_levels");
        
        // Delete membership settings
        delete_option('wp_customer_membership_settings');
        
        $this->debug('Existing membership data cleared');
    }
}
