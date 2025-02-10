<?php
/**
 * Membership Feature Model
 *
 * @package     WP_Customer
 * @subpackage  Models/Membership
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Membership/MembershipFeatureModel.php
 *
 * Description: Model untuk mengelola data fitur membership.
 *              Handles database operations for membership features.
 *              Includes data validation and sanitization.
 *              
 * Dependencies:
 * - WordPress $wpdb
 * - CustomerCacheManager for caching
 *
 * Changelog:
 * 1.0.0 - 2024-02-10
 * - Initial version
 * - Added CRUD operations
 * - Added validation methods
 */

namespace WPCustomer\Models\Membership;

use WPCustomer\Cache\CustomerCacheManager;

defined('ABSPATH') || exit;

class MembershipFeatureModel {
    private $wpdb;
    private $table;
    private $cache_manager;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'app_customer_membership_features';
        $this->cache_manager = new CustomerCacheManager();
    }

    /**
     * Get a single feature by ID
     */
    public function get_feature($id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d AND status = 'active'",
            $id
        ));
    }

    /**
     * Save or update a feature
     */
    public function save_feature($id, $data) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Validating feature data...');
        }

        // Pass ID to validation
        if (!$this->validate_feature_data($data, $id)) {
            error_log('Feature data validation failed');
            return false;
        }

        try {
            if ($id > 0) {
                // Update
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Updating existing feature ID: ' . $id);
                }
                $result = $this->wpdb->update(
                    $this->table,
                    $data,
                    ['id' => $id]
                );
                return $result !== false ? $id : false;
            } else {
                // Insert
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Inserting new feature');
                }
                $result = $this->wpdb->insert(
                    $this->table,
                    $data
                );
                return $result ? $this->wpdb->insert_id : false;
            }
        } catch (\Exception $e) {
            error_log('Database operation failed: ' . $e->getMessage());
            error_log('SQL Error: ' . $this->wpdb->last_error);
            return false;
        }
    }

    private function validate_feature_data($data) {
        // Required fields
        $required = ['field_group', 'field_name', 'field_label', 'field_type'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Validation failed: Missing required field '{$field}'");
                }
                return false;
            }
        }

        return true;
    }

    /**
     * Soft delete a feature
     */
    public function delete_feature($id) {
        return $this->wpdb->update(
            $this->table,
            ['status' => 'inactive'],
            ['id' => $id]
        );
    }

    /**
     * Get all active features
     */
    public function get_all_features() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->table} 
             WHERE status = 'active' 
             ORDER BY field_group, sort_order ASC"
        );
    }

}
