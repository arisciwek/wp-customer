<?php
/**
 * Membership Features Demo Data Generator
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Demo/CustomerMembershipFeaturesDemoData.php
 *
 * Description: Generate demo data for membership features.
 *              Uses CustomerMembershipFeaturesDB default definitions.
 *              Must run before MembershipLevelsDemoData.
 *              
 * Dependencies:
 * - AbstractDemoData base class
 * - CustomerDemoDataHelperTrait
 * - CustomerMembershipFeaturesDB for feature definitions
 */

namespace WPCustomer\Database\Demo;

use WPCustomer\Database\Tables\CustomerMembershipFeaturesDB;

defined('ABSPATH') || exit;

class CustomerMembershipFeaturesDemoData extends AbstractDemoData {
    use CustomerDemoDataHelperTrait;

    // Track generated feature IDs for reference
    private $feature_ids = [];

    protected function validate(): bool {
        try {
            if (!$this->isDevelopmentMode()) {
                $this->debug('Development mode is not enabled');
                return false;
            }

            // Check if features table exists
            $table_exists = $this->wpdb->get_var(
                "SHOW TABLES LIKE '{$this->wpdb->prefix}app_customer_membership_features'"
            );
            
            if (!$table_exists) {
                throw new \Exception('Membership features table does not exist');
            }

            return true;

        } catch (\Exception $e) {
            $this->debug('Validation failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function generate(): void {
        try {
            if ($this->shouldClearData()) {
                $this->clearExistingData();
            }

            // Use insert_defaults from CustomerMembershipFeaturesDB
            CustomerMembershipFeaturesDB::insert_defaults();
            
            // Get and store the generated feature IDs
            $this->feature_ids = $this->wpdb->get_col(
                "SELECT id FROM {$this->wpdb->prefix}app_customer_membership_features 
                 WHERE status = 'active' 
                 ORDER BY field_group, sort_order"
            );

            $this->debug("Generated " . count($this->feature_ids) . " membership features");

        } catch (\Exception $e) {
            $this->debug("Error in feature generation: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Clear existing membership features
     */
    private function clearExistingData(): void {
        $this->wpdb->query("DELETE FROM {$this->wpdb->prefix}app_customer_membership_features WHERE id > 0");
        $this->wpdb->query(
            "ALTER TABLE {$this->wpdb->prefix}app_customer_membership_features AUTO_INCREMENT = 1"
        );
        $this->debug('Cleared existing membership features');
    }

    /**
     * Get array of generated feature IDs
     */
    public function getFeatureIds(): array {
        return $this->feature_ids;
    }
}

