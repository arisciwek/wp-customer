<?php
/**
 * Membership Feature Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models/Membership
 * @version     1.0.0
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

class MembershipFeatureModel {
    /**
     * Table name
     * @var string
     */
    private $table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'app_customer_membership_features';
    }

    /**
     * Get all active features
     *
     * @return array|null Array of feature objects or null on error
     */
    public function get_active_features() {
        global $wpdb;
        
        try {
            return $wpdb->get_results("
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
}