<?php
/**
 * Membership Features Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Membership
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Membership/MembershipFeaturesController.php
 *
 * Description: Controller untuk mengelola fitur-fitur membership.
 *              Handles CRUD operations for membership features.
 *              Includes caching integration and security checks.
 *              
 * Dependencies:
 * - MembershipFeatureModel for data operations
 * - CustomerCacheManager for caching
 * - WordPress AJAX API
 * - WordPress Capability System
 *
 * Changelog:
 * 1.0.0 - 2024-02-10
 * - Initial version
 * - Added CRUD operations
 * - Added cache integration
 * - Added security checks
 */

namespace WPCustomer\Controllers\Membership;

use WPCustomer\Cache\CustomerCacheManager;
use WPCustomer\Models\Membership\MembershipFeatureModel;

defined('ABSPATH') || exit;

class MembershipFeaturesController {
    private $model;
    private $cache_manager;
    
    public function __construct() {
        $this->model = new MembershipFeatureModel();
        $this->cache_manager = new CustomerCacheManager();
    }

    /**
     * Handle getting a single feature
     */
    public function handle_get_feature() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new \Exception(__('You do not have permission to perform this action.', 'wp-customer'));
            }

            $feature_id = intval($_POST['id']);
            
            // Debug
            error_log('Getting feature ID: ' . $feature_id);
            
            // Try to get from cache first
            $feature = $this->cache_manager->get('feature', $feature_id);

            if ($feature === null) {
                // Not in cache, get from model
                $feature = $this->model->get_feature($feature_id);
                
                if ($feature) {
                    // Store in cache for future requests
                    $this->cache_manager->set('feature', $feature, null, $feature_id);
                }
            }

            if (!$feature) {
                throw new \Exception(__('Feature not found.', 'wp-customer'));
            }

            // Debug
            error_log('Feature data: ' . print_r($feature, true));

            wp_send_json_success($feature);

        } catch (\Exception $e) {
            error_log('Error in handle_get_feature: ' . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle saving/updating a feature
     */
	public function handle_save_feature() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new \Exception(__('You do not have permission to perform this action.', 'wp-customer'));
            }

            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            
            $data = [
                'field_group' => sanitize_text_field($_POST['field_group']),
                'field_name' => sanitize_text_field($_POST['field_name']),
                'field_label' => sanitize_text_field($_POST['field_label']),
                'field_type' => sanitize_text_field($_POST['field_type']),
                'field_subtype' => !empty($_POST['field_subtype']) ? sanitize_text_field($_POST['field_subtype']) : null,
                'is_required' => isset($_POST['is_required']) ? 1 : 0,
                'css_class' => !empty($_POST['css_class']) ? sanitize_text_field($_POST['css_class']) : null,
                'css_id' => !empty($_POST['css_id']) ? sanitize_text_field($_POST['css_id']) : null,
                'sort_order' => intval($_POST['sort_order']),
                'created_by' => get_current_user_id()
            ];

            // Use model instance
            $result = $this->model->save_feature($id, $data);

            if (!$result) {
                throw new \Exception(__('Failed to save feature.', 'wp-customer'));
            }

            // Use cache manager instance
            if ($id > 0) {
                $this->cache_manager->delete('feature', $id);
            }
            $this->cache_manager->delete('feature_list');

            $message = $id ? __('Feature updated successfully.', 'wp-customer') 
                         : __('Feature added successfully.', 'wp-customer');

            wp_send_json_success([
                'message' => $message
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
	}

    /**
     * Handle deleting a feature (soft delete)
     */
    public function handle_delete_feature() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new \Exception(__('You do not have permission to perform this action.', 'wp-customer'));
            }

            $feature_id = intval($_POST['id']);
            
            // Soft delete via model
            $result = $this->model->delete_feature($feature_id);

            if (!$result) {
                throw new \Exception(__('Failed to delete feature.', 'wp-customer'));
            }

            // Clear related caches
            $this->cache_manager->delete('feature', $feature_id);
            $this->cache_manager->delete('feature_list');

            wp_send_json_success([
                'message' => __('Feature deleted successfully.', 'wp-customer')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get all active features
     */
    public function getAllFeatures() {
        // Coba ambil dari cache dulu
        $features = $this->cache_manager->get('membership_feature_list');
        
        if ($features === null) {
            $features = $this->model->get_active_features();
            
            // Kelompokkan features berdasarkan group
            $grouped_features = [];
            if($features) {
                foreach ($features as $feature) {
                    $metadata = json_decode($feature->metadata);
                    $group = $metadata->group;
                    if (!isset($grouped_features[$group])) {
                        $grouped_features[$group] = [];
                    }
                    $grouped_features[$group][] = $feature;
                }
            }

            // Simpan ke cache
            $this->cache_manager->set('membership_feature_list', $grouped_features);
            
            return $grouped_features;
        }

        return $features;
    }


    /**
     * Get all unique groups from active features
     */
    public function getFeatureGroups() {
        // Coba ambil dari cache dulu
        $groups = $this->cache_manager->get('membership_feature_groups');
        
        if ($groups === null) {
            global $wpdb;
            // Ambil semua group unik dari metadata JSON
            $results = $wpdb->get_results("
                SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.group')) as group_name
                FROM {$wpdb->prefix}app_customer_membership_features
                WHERE status = 'active'
                ORDER BY group_name ASC
            ");
            
            $groups = array_map(function($row) {
                return $row->group_name;
            }, $results);

            // Simpan ke cache
            $this->cache_manager->set('membership_feature_groups', $groups);
        }

        return $groups;
    }

}
