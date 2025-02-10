<?php
/**
 * Membership Levels Demo Data Generator
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo
 * @version     1.1.0
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
 * - WPCustomer\Models\Customer\CustomerMembershipLevelModel
 * - WordPress database ($wpdb)
 * - WordPress Options API
 * - CustomerDemoDataHelperTrait
 *
 * Order of Operations:
 * 1. Check development mode
 * 2. Clean existing membership data if in development mode
 * 3. Setup membership defaults
 * 4. Insert membership levels
 *
 * Changelog:
 * 1.1.0 - 2024-02-08
 * - Added CustomerDemoDataHelperTrait integration
 * - Added development mode check before data cleanup
 * - Improved error handling and logging
 * 
 * 1.0.0 - 2024-01-27
 * - Initial version
 * - Added membership levels setup
 * - Added data cleaning
 */

namespace WPCustomer\Database\Demo;

use WPCustomer\Models\Membership\CustomerMembershipLevelModel;
use WPCustomer\Database\Tables\CustomerMembershipLevelsDB;

class MembershipLevelsDemoData extends AbstractDemoData {
    use CustomerDemoDataHelperTrait;

    private $customerMembershipLevelModel;
    
    public function __construct() {
        parent::__construct();
        $this->customerMembershipLevelModel = new CustomerMembershipLevelModel();
    }

    protected function validate(): bool {
        try {
            if (!$this->isDevelopmentMode()) {
                $this->debug('Development mode is not enabled');
                return false;
            }

            $table_exists = $this->wpdb->get_var(
                "SHOW TABLES LIKE '{$this->wpdb->prefix}app_customer_membership_levels'"
            );
            
            if (!$table_exists) {
                throw new \Exception('Membership levels table does not exist');
            }

            return true;
        } catch (\Exception $e) {
            $this->debug('Validation failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function generate(): bool {
        try {
            if ($this->shouldClearData()) {
                $this->clearExistingData();
            } else {
                $this->debug('Skipping data cleanup - not enabled in settings');
            }
            
            // Use CustomerMembershipLevelsDB to insert defaults
            CustomerMembershipLevelsDB::insert_defaults();
            $this->debug('Default membership levels inserted');

            return true;
        } catch (\Exception $e) {
            $this->debug('Generation failed: ' . $e->getMessage());
            return false;
        }
    }

    private function clearExistingData(): void {
        try {
            $this->wpdb->query("START TRANSACTION");
            
            // Delete from child tables first
            $this->wpdb->query("DELETE FROM {$this->wpdb->prefix}app_customer_memberships");
            $this->wpdb->query("DELETE FROM {$this->wpdb->prefix}app_customer_membership_levels");
            
            delete_option('wp_customer_membership_settings');
            
            $this->wpdb->query("COMMIT");
            
            $this->debug('Existing membership data cleared');
        } catch (\Exception $e) {
            $this->wpdb->query("ROLLBACK");
            $this->debug('Error clearing existing data: ' . $e->getMessage());
            throw $e;
        }
    }
}
