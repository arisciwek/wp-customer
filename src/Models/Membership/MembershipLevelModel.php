<?php
/**
 * Membership Level Model
 *
 * @package     WP_Customer
 * @subpackage  Models/Membership
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Membership/MembershipLevelModel.php
 *
 * Description: Model untuk mengelola data level membership.
 *              Handles database operations untuk membership levels.
 *              Includes capability management dan data validation.
 *              
 * Dependencies:
 * - WordPress $wpdb
 * - CustomerCacheManager for caching
 *
 * Changelog:
 * 1.0.0 - 2024-02-11
 * - Initial version
 * - Added CRUD operations
 * - Added capability management
 */

namespace WPCustomer\Models\Membership;

use WPCustomer\Cache\CustomerCacheManager;

defined('ABSPATH') || exit;

class MembershipLevelModel {
    private $wpdb;
    private $table;
    private $cache_manager;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'app_customer_membership_levels';
        $this->cache_manager = new CustomerCacheManager();
    }

    /**
     * Get a single level by ID
     */
    public function get_level($id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d AND status = 'active'",
            $id
        ));
    }

    /**
     * Save or update a level
     */
    public function save_level($id, $data) {
        try {
            if ($id > 0) {
                // Update
                $result = $this->wpdb->update(
                    $this->table,
                    $data,
                    ['id' => $id]
                );
                return $result !== false ? $id : false;
            } else {
                // Insert
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

    /**
     * Soft delete a level
     */
    public function delete_level($id) {
        return $this->wpdb->update(
            $this->table,
            ['status' => 'inactive'],
            ['id' => $id]
        );
    }

    /**
     * Check if level exists by slug
     */
    public function exists_by_slug($slug, $exclude_id = null) {
        $query = "SELECT COUNT(*) FROM {$this->table} 
                 WHERE slug = %s AND status = 'active'";
        $params = [$slug];

        if ($exclude_id) {
            $query .= " AND id != %d";
            $params[] = $exclude_id;
        }

        return (bool) $this->wpdb->get_var(
            $this->wpdb->prepare($query, $params)
        );
    }

    /**
     * Check if level is in use by any customers
     */
    public function is_level_in_use($level_id) {
        $customers_table = $this->wpdb->prefix . 'app_customers';
        
        return (bool) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$customers_table} 
             WHERE membership_level_id = %d AND status = 'active'",
            $level_id
        ));
    }

    /**
     * Get level's current capabilities
     */
    public function get_level_capabilities($level_id) {
        $level = $this->get_level($level_id);
        if (!$level) return null;
        
        return json_decode($level->capabilities, true);
    }

    /**
     * Update level's capabilities
     */
    public function update_capabilities($level_id, $capabilities) {
        return $this->wpdb->update(
            $this->table,
            ['capabilities' => json_encode($capabilities)],
            ['id' => $level_id]
        );
    }

    /**
     * Get total active customers for a level
     */
    public function get_active_customers_count($level_id) {
        $customers_table = $this->wpdb->prefix . 'app_customers';
        
        return (int) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$customers_table} 
             WHERE membership_level_id = %d AND status = 'active'",
            $level_id
        ));
    }

	public function get_all_levels() {
	    // Try to get from cache first
	    $levels = $this->cache_manager->get('membership_level_list');
	    
	    if ($levels === null) {
	        $levels = $this->wpdb->get_results(
	            "SELECT * FROM {$this->table} 
	             WHERE status = 'active' 
	             ORDER BY sort_order ASC, id ASC"
	        );

	        // Simpan di cache tanpa decode
	        if ($levels) {
	            $this->cache_manager->set('membership_level_list', $levels);
	        }
	    }
	    
	    return $levels;
	}
	
	public function get_all_features_by_group() {
	    $features = $this->wpdb->get_results("
	        SELECT * FROM {$this->table} 
	        WHERE status = 'active'
	        ORDER BY sort_order ASC"
	    );

	    // Group features berdasarkan metadata group
	    $grouped_features = [];
	    foreach ($features as $feature) {
	        $metadata = json_decode($feature->metadata, true);
	        $group = $metadata['group'];
	        
	        if (!isset($grouped_features[$group])) {
	            $grouped_features[$group] = [];
	        }
	        
	        $grouped_features[$group][] = [
	            'field_name' => $feature->field_name,
	            'metadata' => $metadata
	        ];
	    }

	    return $grouped_features;
	}
}
