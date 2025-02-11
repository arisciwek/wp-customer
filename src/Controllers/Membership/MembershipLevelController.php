<?php
/**
 * Membership Level Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Membership
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Membership/MembershipLevelController.php
 *
 * Description: Controller untuk mengelola level membership.
 *              Handles CRUD operations untuk membership levels.
 *              Includes fitur management dan capability assignment.
 *              
 * Dependencies:
 * - MembershipLevelModel untuk operasi data
 * - MembershipFeatureModel untuk data fitur
 * - CustomerCacheManager untuk caching
 * - WordPress AJAX API
 *
 * Changelog:
 * 1.0.0 - 2024-02-11
 * - Initial version
 * - Added CRUD operations
 * - Added feature integration
 * - Added cache management
 */

namespace WPCustomer\Controllers\Membership;

use WPCustomer\Models\Membership\MembershipLevelModel;
use WPCustomer\Models\Membership\MembershipFeatureModel;
use WPCustomer\Cache\CustomerCacheManager;
use WPCustomer\Validators\Membership\MembershipLevelValidator;

defined('ABSPATH') || exit;

class MembershipLevelController {
    private $model;
    private $feature_model;
    private $cache_manager;
    private $validator;
    
    public function __construct() {
        $this->model = new MembershipLevelModel();
        $this->feature_model = new MembershipFeatureModel();
        $this->cache_manager = new CustomerCacheManager();
        $this->validator = new MembershipLevelValidator();
        
        $this->register_hooks();
    }

    public function register_hooks() {
        // Register AJAX handlers
        add_action('wp_ajax_get_membership_level', [$this, 'handle_get_level']);
        add_action('wp_ajax_save_membership_level', [$this, 'handle_save_level']);
        add_action('wp_ajax_delete_membership_level', [$this, 'handle_delete_level']);
    }

    /**
     * Handle getting a single level with capabilities
     */
    public function handle_get_level() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new \Exception(__('You do not have permission to perform this action.', 'wp-customer'));
            }

            $level_id = intval($_POST['id']);
            
            // Try to get from cache first
            $level = $this->cache_manager->get('membership_level', $level_id);

            if ($level === null) {
                // Not in cache, get from model
                $level = $this->model->get_level($level_id);
                
                if ($level) {
                    // Decode capabilities JSON
                    $level->capabilities = json_decode($level->capabilities, true);
                    
                    // Store in cache for future requests
                    $this->cache_manager->set('membership_level', $level, null, $level_id);
                }
            }

            if (!$level) {
                throw new \Exception(__('Level not found.', 'wp-customer'));
            }

            wp_send_json_success($level);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle saving/updating a level
     */
    public function handle_save_level() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new \Exception(__('You do not have permission to perform this action.', 'wp-customer'));
            }

            // Get and sanitize input data
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $data = $this->sanitize_level_data($_POST);

            // Validate data
            $validation_errors = $this->validator->validate_level($data, $id);
            if (!empty($validation_errors)) {
                throw new \Exception(implode(' ', $validation_errors));
            }

            // Process capabilities
            $data['capabilities'] = $this->process_capabilities_data($_POST);

            // Save via model
            $result = $this->model->save_level($id, $data);

            if (!$result) {
                throw new \Exception(__('Failed to save membership level.', 'wp-customer'));
            }

            // Clear cache
            if ($id > 0) {
                $this->cache_manager->delete('membership_level', $id);
            }
            $this->cache_manager->delete('membership_level_list');

            wp_send_json_success([
                'message' => $id ? 
                    __('Level updated successfully.', 'wp-customer') : 
                    __('Level added successfully.', 'wp-customer'),
                'id' => $result
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle deleting a level
     */
    public function handle_delete_level() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new \Exception(__('You do not have permission to perform this action.', 'wp-customer'));
            }

            $level_id = intval($_POST['id']);

            // Validate deletion
            $validation_errors = $this->validator->validate_delete($level_id);
            if (!empty($validation_errors)) {
                throw new \Exception(implode(' ', $validation_errors));
            }

            // Delete via model
            $result = $this->model->delete_level($level_id);

            if (!$result) {
                throw new \Exception(__('Failed to delete membership level.', 'wp-customer'));
            }

            // Clear cache
            $this->cache_manager->delete('membership_level', $level_id);
            $this->cache_manager->delete('membership_level_list');

            wp_send_json_success([
                'message' => __('Level deleted successfully.', 'wp-customer')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process and structure capabilities data from form
     */
    private function process_capabilities_data($post_data) {
        $capabilities = [
            'features' => [],
            'limits' => [],
            'notifications' => []
        ];

        // Process features
        if (!empty($post_data['features'])) {
            foreach ($post_data['features'] as $key => $value) {
                $capabilities['features'][sanitize_key($key)] = [
                    'value' => rest_sanitize_boolean($value)
                ];
            }
        }

        // Process limits
        if (!empty($post_data['limits'])) {
            foreach ($post_data['limits'] as $key => $value) {
                $capabilities['limits'][sanitize_key($key)] = intval($value);
            }
        }

        // Process notifications
        if (!empty($post_data['notifications'])) {
            foreach ($post_data['notifications'] as $key => $value) {
                $capabilities['notifications'][sanitize_key($key)] = 
                    rest_sanitize_boolean($value);
            }
        }

        return json_encode($capabilities);
    }

    /**
     * Sanitize level input data
     */
    private function sanitize_level_data($post_data) {
        return [
            'name' => sanitize_text_field($post_data['name']),
            'slug' => sanitize_title($post_data['name']),
            'description' => sanitize_textarea_field($post_data['description']),
            'price_per_month' => floatval($post_data['price_per_month']),
            'is_trial_available' => isset($post_data['is_trial_available']) ? 1 : 0,
            'trial_days' => intval($post_data['trial_days']),
            'grace_period_days' => intval($post_data['grace_period_days']),
            'sort_order' => intval($post_data['sort_order']),
            'created_by' => get_current_user_id()
        ];
    }
}

