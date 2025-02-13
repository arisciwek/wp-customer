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

class MembershipLevelController {
    private $model;
    private $feature_model;
    private $cache_manager;
    private $validator;
    
    public function __construct() {

        error_log('MembershipLevelController initialized');
        $this->model = new MembershipLevelModel();
        $this->feature_model = new MembershipFeatureModel();
        $this->cache_manager = new CustomerCacheManager();
        $this->validator = new MembershipLevelValidator();
        
    }

    public function handle_get_level() {
        try {

            $this->verify_request('get_membership_level');

            $level_id = intval($_POST['id']);
            $level = $this->model->get_level($level_id);

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

    public function handle_save_level() {
        try {
            $this->verify_request('save_membership_level');
    
            error_log('SAVE LEVEL REQUEST: ' . print_r($_POST, true));

            // Get and sanitize input data
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $data = $this->sanitize_level_data($_POST);

            // Validate data
            $validation_errors = $this->validator->validate_level($data, $id);
            if (!empty($validation_errors)) {
                throw new \Exception(implode(' ', $validation_errors));
            }

            // Save via model
            $result = $this->model->save_level($id, $data);

            if (!$result) {
                throw new \Exception(__('Failed to save membership level.', 'wp-customer'));
            }

            // Clear cache
            $this->clear_level_cache($id ?: $result);

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

    public function handle_delete_level() {
        try {
            $this->verify_request('delete_membership_level');

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
            $this->clear_level_cache($level_id);

            wp_send_json_success([
                'message' => __('Level deleted successfully.', 'wp-customer')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    private function verify_request($action) {
        if (!current_user_can('manage_options')) {
            throw new \Exception(__('You do not have permission to perform this action.', 'wp-customer'));
        }

        check_ajax_referer('wp_customer_nonce', 'nonce');
    }

    private function sanitize_level_data($post_data) {
        $data = [
            'name' => sanitize_text_field($post_data['name']),
            'slug' => sanitize_title($post_data['name']),
            'description' => sanitize_textarea_field($post_data['description']),
            'price_per_month' => floatval($post_data['price_per_month']),
            'is_trial_available' => isset($post_data['is_trial_available']) ? 1 : 0,
            'trial_days' => intval($post_data['trial_days']),
            'grace_period_days' => intval($post_data['grace_period_days']),
            'sort_order' => intval($post_data['sort_order'])
        ];

        // Ambil daftar fitur yang tersedia dari model
        $feature_model = new MembershipFeatureModel();
        $available_features = $feature_model->get_all_features_by_group();

        $capabilities = [];
        
        // Proses setiap grup capabilities
        foreach ($available_features as $group => $features) {
            if (!empty($post_data[$group])) {
                $capabilities[$group] = [];
                foreach ($post_data[$group] as $field => $value) {
                    // Cari fitur yang sesuai
                    $feature = array_filter($features, function($f) use ($field) {
                        return $f['field_name'] === $field;
                    });
                    
                    if (!empty($feature)) {
                        $feature = reset($feature);
                        $capabilities[$group][$field] = [
                            'field' => $field,
                            'group' => $group,
                            'label' => $feature['metadata']['label'],
                            'value' => $group === 'limits' ? 
                                intval($value) : 
                                (bool)$value
                        ];
                    }
                }
            }
        }

        $data['capabilities'] = json_encode($capabilities);
        return $data;
    }

    private function process_capabilities_data($capabilities) {
        if (empty($capabilities)) {
            return json_encode([
                'features' => [],
                'limits' => [],
                'notifications' => [
                    'email' => ['value' => true],
                    'dashboard' => ['value' => true]
                ]
            ]);
        }

        return json_encode($capabilities);
    }

    private function clear_level_cache($level_id) {
        $this->cache_manager->delete('membership_level', $level_id);
        $this->cache_manager->delete('membership_level_list');
    }
}