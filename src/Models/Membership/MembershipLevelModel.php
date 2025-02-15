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

    // Dapatkan semua group yang aktif dari features
    public function getActiveGroups() {
        global $wpdb;
        $table = $wpdb->prefix . 'app_customer_membership_features';
        
        // Mengambil group yang unik dari metadata
        $groups = $wpdb->get_col("
            SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.group'))
            FROM {$table}
            WHERE status = 'active'
            ORDER BY sort_order ASC
        ");
        
        return $groups;
    }

    // Dapatkan mapping group ke capabilities
    public function getGroupMapping() {
        // Ini bisa jadi setting di database juga
        $default_mapping = [
            'staff' => 'features',
            'data' => 'features',
            'resources' => 'limits',
            'communication' => 'notifications'
        ];

        // Ambil custom mapping dari database/options jika ada
        $custom_mapping = get_option('wp_customer_group_mapping', []);
        
        return array_merge($default_mapping, $custom_mapping);
    }
    
    /**
     * Get a single level by ID
     */
    public function get_level($id) {
        // Get the raw data first
        $level = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d AND status = 'active'",
            $id
        ), ARRAY_A);  // Gunakan ARRAY_A agar konsisten dengan get_all_levels()

        if (!$level) {
            return null;
        }

        // Format numeric values
        $level['price_per_month'] = floatval($level['price_per_month']);
        $level['trial_days'] = intval($level['trial_days']);
        $level['grace_period_days'] = intval($level['grace_period_days']);
        $level['is_trial_available'] = intval($level['is_trial_available']);

        // Process capabilities
        if (!empty($level['capabilities'])) {
            $capabilities = json_decode($level['capabilities'], true);
            if ($capabilities) {
                // Strukturkan capabilities ke dalam group yang sesuai
                foreach ($capabilities as $group => $items) {
                    $level[$group] = $items;
                }
            }
        }

        return $level;
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
        // Get complete level data from database with ARRAY_A
        $levels = $this->wpdb->get_results(
            "SELECT * FROM {$this->table} 
             WHERE status = 'active' 
             ORDER BY sort_order ASC, id ASC",
            ARRAY_A  // Menggunakan array associative
        );

        // Validate and process each level
        if (!empty($levels)) {
            foreach ($levels as &$level) {
                // Format numeric values
                $level['price_per_month'] = floatval($level['price_per_month']);
                $level['trial_days'] = intval($level['trial_days']);
                $level['grace_period_days'] = intval($level['grace_period_days']);
                $level['is_trial_available'] = intval($level['is_trial_available']);

                // Process capabilities
                if (!empty($level['capabilities'])) {
                    $capabilities = json_decode($level['capabilities'], true);
                    if ($capabilities) {
                        // Strukturkan capabilities ke dalam group yang sesuai
                        foreach ($capabilities as $group => $items) {
                            $level[$group] = $items;
                        }
                    }
                }
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

        // Dapatkan semua grup yang unik dari metadata
        $groups = [];
        foreach ($features as $feature) {
            $metadata = json_decode($feature->metadata, true);
            $groups[$metadata['group']] = true;
        }

        // Kelompokkan fitur berdasarkan grup
        $grouped_features = array_fill_keys(array_keys($groups), []);
        
        foreach ($features as $feature) {
            $metadata = json_decode($feature->metadata, true);
            $group = $metadata['group'];
            
            $grouped_features[$group][] = [
                'field_name' => $feature->field_name,
                'metadata' => $metadata
            ];
        }

        return $grouped_features;
    }
}
