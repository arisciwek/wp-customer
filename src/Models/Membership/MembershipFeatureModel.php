<?php
/**
 * Membership Feature Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models/Membership
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Membership/MembershipFeatureModel.php
 *
 * Description: Model untuk mengelola data fitur membership.
 *              Features:
 *              - CRUD operations untuk fitur membership
 *              - Validasi data fitur
 *              - Pengaturan metadata
 */

namespace WPCustomer\Models\Membership;

use WPCustomer\Cache\CustomerCacheManager;

class MembershipFeatureModel {
    /**
     * Table name
     * @var string
     */
    private $wpdb;
    private $cache_manager;
    private $table;  // Tambahkan ini
    private $table_groups;
    private $table_features;
    
    private const GROUP_MAPPING = [
        'staff' => 'features',
        'data' => 'features',
        'resources' => 'limits',
        'communication' => 'notifications'
    ];

    public function getGroupMapping() {
        return self::GROUP_MAPPING;
    }

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->cache_manager = new CustomerCacheManager();
        $this->table = $wpdb->prefix . 'app_customer_membership_features';  // Inisialisasi table
        $this->table_groups = $wpdb->prefix . 'app_customer_membership_feature_groups';
        $this->table_features = $wpdb->prefix . 'app_customer_membership_features';
    }

    /**
     * Get feature group by ID
     */
    public function get_feature_group($group_id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}app_customer_membership_feature_groups 
             WHERE id = %d AND status = 'active'",
            $group_id
        ));
    }

    /**
     * Get all active features
     *
     * @return array|null Array of feature objects or null on error
     */
    public function get_active_features() {
        // Tidak perlu global $wpdb lagi karena sudah ada di property
        try {
            return $this->wpdb->get_results("
                SELECT * FROM {$this->table} 
                WHERE status = 'active'
                ORDER BY sort_order ASC
            ");
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Error getting active features: ' . $e->getMessage());
            }
            return null;
        }
    }

    public function getActiveGroupsAndFeatures() {
        // Coba ambil dari cache dulu
        $cached_data = $this->cache_manager->get('membership_groups_features');
        if ($cached_data !== null) {
            error_log('Data diambil dari cache');
            return $cached_data;
        }
        
        error_log('Cache miss - mengambil dari database');
        
        // Jika tidak ada cache, ambil dari database
        $data = [];
        
        // Ambil active groups
        $active_groups = $this->wpdb->get_results("
            SELECT * FROM {$this->table_groups} 
            WHERE status = 'active' 
            ORDER BY sort_order ASC
        ", ARRAY_A);
        
        // Untuk setiap group, ambil features-nya
        foreach ($active_groups as $group) {
            $query = $this->wpdb->prepare("
                SELECT * FROM {$this->table_features} 
                WHERE group_id = %d AND status = 'active'
                ORDER BY sort_order ASC
            ", $group['id']);
            
            $group_features = $this->wpdb->get_results($query, ARRAY_A);
            
            $data[] = [
                'group' => $group,
                'features' => $group_features
            ];
        }
        
        // Simpan ke cache selama 1 jam
        $this->cache_manager->set('membership_groups_features', $data, HOUR_IN_SECONDS);
        
        return $data;
    }

    /**
     * Check if group exists by slug
     */
    public function group_exists_by_slug($slug, $exclude_id = null) {
        $query = "SELECT COUNT(*) FROM {$this->wpdb->prefix}app_customer_membership_feature_groups 
                  WHERE slug = %s AND status = 'active'";
        $params = [$slug];

        if ($exclude_id) {
            $query .= " AND id != %d";
            $params[] = $exclude_id;
        }

        return (bool) $this->wpdb->get_var($this->wpdb->prepare($query, $params));
    }

    /**
     * Save or update feature group
     */
    public function save_feature_group($id, $data) {
        if ($id > 0) {
            return $this->wpdb->update(
                $this->wpdb->prefix . 'app_customer_membership_feature_groups',
                $data,
                ['id' => $id]
            ) !== false ? $id : false;
        } else {
            $result = $this->wpdb->insert(
                $this->wpdb->prefix . 'app_customer_membership_feature_groups',
                $data
            );
            return $result ? $this->wpdb->insert_id : false;
        }
    }

    /**
     * Check if group has any features
     */
    public function group_has_features($group_id) {
        return (bool) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}app_customer_membership_features 
             WHERE group_id = %d AND status = 'active'",
            $group_id
        ));
    }

    /**
     * Delete feature group (soft delete)
     */
    public function delete_feature_group($group_id) {
        return $this->wpdb->update(
            $this->wpdb->prefix . 'app_customer_membership_feature_groups',
            ['status' => 'inactive'],
            ['id' => $group_id]
        ) !== false;
    }

    /**
     * Get feature by ID
     *
     * @param int $id Feature ID
     * @return object|null Feature object or null if not found
     */
    public function get_feature($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$this->table} WHERE id = %d
        ", $id));
    }

    /**
     * Save feature (create or update)
     *
     * @param int $id Feature ID (0 for new feature)
     * @param array $data Feature data
     * @return bool|int Feature ID on success, false on failure
     */
    public function save_feature($id, $data) {
        global $wpdb;
        
        // Prepare metadata
        $metadata = [
            'type' => $data['field_type'],
            'group' => $data['field_group'],
            'label' => $data['field_label'],
            'description' => '',
            'is_required' => !empty($data['is_required']),
            'ui_settings' => [
                'css_class' => $data['css_class'] ?? '',
                'css_id' => $data['css_id'] ?? ''
            ]
        ];

        if (!empty($data['field_subtype'])) {
            $metadata['subtype'] = $data['field_subtype'];
        }

        $save_data = [
            'field_name' => $data['field_name'],
            'metadata' => wp_json_encode($metadata),
            'sort_order' => $data['sort_order'],
            'created_by' => $data['created_by']
        ];

        if ($id > 0) {
            // Update
            $result = $wpdb->update(
                $this->table,
                $save_data,
                ['id' => $id]
            );
            return $result !== false ? $id : false;
        } else {
            // Insert
            $result = $wpdb->insert($this->table, $save_data);
            return $result ? $wpdb->insert_id : false;
        }
    }

    /**
     * Delete feature (soft delete)
     *
     * @param int $id Feature ID
     * @return bool Success status
     */
    public function delete_feature($id) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table,
            ['status' => 'inactive'],
            ['id' => $id]
        ) !== false;
    }
    
    /**
     * Get all features grouped by their group type
     *
     * @return array Features grouped by group type
     */
    public function get_all_features_by_group() {
        try {
            // Get raw features data
            $features = $this->get_active_features();
            
            if (!$features) {
                return [];
            }

            // Initialize result array
            $result = [];

            // Group features by their group type from metadata
            foreach ($features as $feature) {
                $metadata = json_decode($feature->metadata, true);
                $group = $metadata['group'] ?? 'ungrouped';

                // Transform feature data into a more usable format
                $feature_data = [
                    'field_name' => $feature->field_name,
                    'metadata' => $metadata
                ];

                // Add to appropriate group
                if (!isset($result[$group])) {
                    $result[$group] = [];
                }
                $result[$group][] = $feature_data;
            }

            return $result;

        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Error in get_all_features_by_group: ' . $e->getMessage());
            }
            return [];
        }
    }

    /**
     * Get mapping between groups and capability sections
     */
    public function get_group_capability_mapping() {
        global $wpdb;
        
        // Coba ambil dari cache dulu
        $mapping = wp_cache_get('membership_group_capability_mapping');
        
        if ($mapping === false) {
            $results = $wpdb->get_results("
                SELECT slug, capability_group 
                FROM {$wpdb->prefix}app_customer_membership_feature_groups 
                WHERE status = 'active'
            ", ARRAY_A);

            $mapping = [];
            foreach ($results as $row) {
                $mapping[$row['slug']] = $row['capability_group'];
            }
            
            // Simpan ke cache
            wp_cache_set('membership_group_capability_mapping', $mapping, '', HOUR_IN_SECONDS);
        }
        
        return $mapping;
    }

    
}