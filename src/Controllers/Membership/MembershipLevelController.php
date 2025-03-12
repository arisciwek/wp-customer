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

        add_action('wp_ajax_save_customer_membership_level', [$this, 'handle_save_membership_level']);
        
    }

    public function getMembershipLevel() {
        error_log('Received nonce: ' . $_POST['nonce']);
        error_log('Expected nonce for wp_customer_nonce: ' . wp_create_nonce('wp_customer_nonce'));
        
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');
            
            $level_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            error_log('Getting level data for ID: ' . $level_id);

            if (!$level_id) {
                throw new \Exception('Invalid level ID');
            }

            // Get level data
            $level = $this->model->getLevel($level_id);
            error_log('Raw level data: ' . print_r($level, true));

            if (!$level) {
                throw new \Exception('Level not found');
            }

            // Jika $level adalah objek, dan nilai max_staff dan max_departments 
            // seharusnya diambil dari capabilities
            $capabilities = is_string($level->capabilities) 
                ? json_decode($level->capabilities, true) 
                : $level->capabilities;

            $max_staff = null;
            $max_departments = null;

            if (is_array($capabilities) && isset($capabilities['resources'])) {
                if (isset($capabilities['resources']['max_staff'])) {
                    $max_staff = $capabilities['resources']['max_staff']['value'] ?? null;
                }
                
                if (isset($capabilities['resources']['max_departments'])) {
                    $max_departments = $capabilities['resources']['max_departments']['value'] ?? null;
                }
            }

            // Format response data
            $response = [
                'id' => $level->id,
                'name' => $level->name,
                'description' => $level->description,
                'price_per_month' => $level->price_per_month,
                'max_staff' => $level->max_staff,
                'max_departments' => $level->max_departments,
                'is_trial_available' => $level->is_trial_available,
                'trial_days' => $level->trial_days,
                'grace_period_days' => $level->grace_period_days,
                'capabilities' => is_string($level->capabilities) ? $level->capabilities : json_encode($level->capabilities)
            ];
            
            error_log('Formatted response: ' . print_r($response, true));
            wp_send_json_success($response);

        } catch (\Exception $e) {
            error_log('Error in get_customer_membership_level: ' . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }

    }

    public function handle_save_membership_level() {
        try {
            $this->verify_request('save_membership_level');
        
            error_log('=== START SAVE LEVEL ===');
            error_log('Raw POST data: ' . print_r($_POST, true));
            
            // Get and sanitize input data
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            
            // SISIPKAN DI SINI - Sebelum memanggil sanitize_level_data
            try {
                $data = $this->sanitize_level_data($_POST);
            } catch (\Exception $e) {
                error_log("Sanitization error: " . $e->getMessage());
                error_log("Sanitization error trace: " . $e->getTraceAsString());
                throw new \Exception(__('Failed to process membership data.', 'wp-customer'));
            }

            error_log('Sanitized data: ' . print_r($data, true));

            // Decode capabilities untuk validasi
            $capabilities = json_decode($data['capabilities'], true);
            error_log('Decoded capabilities: ' . print_r($capabilities, true));
            
            // Validate capabilities structure
            if (!$this->validateCapabilitiesStructure($capabilities)) {
                throw new \Exception(__('Invalid capabilities structure.', 'wp-customer'));
            }

            // Save via model
            $result = $this->model->save_level($id, $data);
            error_log('Save result: ' . ($result ? 'success' : 'failed'));

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
            error_log('Error saving level: ' . $e->getMessage());
            error_log('Error trace: ' . $e->getTraceAsString());
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

    /**
     * Helper function untuk mendapatkan default features berdasarkan grup
     */
    private function getDefaultFeaturesForGroup($group_slug) {
        // Ambil semua features dari model
        $all_features = $this->feature_model->get_all_features_by_group();
        
        // Return features untuk grup yang diminta
        return $all_features[$group_slug] ?? [];
    }

    public function handle_get_form_data() {
        try {
            $this->verify_request('get_membership_form');
            
            $level_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            
            // Ambil data level
            $level = $this->model->get_level($level_id);
            if (!$level) {
                throw new \Exception(__('Level tidak ditemukan.', 'wp-customer'));
            }

            // Ambil struktur features dari model
            $feature_model = new MembershipFeatureModel();
            $available_features = $feature_model->get_all_features_by_group();
            
            // Parse capabilities yang ada
            $existing_capabilities = json_decode($level['capabilities'], true);

            // Format data untuk form dengan capabilities yang sesuai struktur dari model
            $form_data = [
                'id' => $level['id'],
                'name' => $level['name'],
                'slug' => $level['slug'],
                'description' => $level['description'],
                'price_per_month' => floatval($level['price_per_month']),
                'is_trial_available' => (bool)$level['is_trial_available'],
                'trial_days' => intval($level['trial_days']),
                'grace_period_days' => intval($level['grace_period_days']),
                'sort_order' => intval($level['sort_order']),
                'capabilities' => $existing_capabilities
            ];
    
            error_log('Form Data: ' . json_encode($form_data, true));

            wp_send_json_success($form_data);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    // Modifikasi pada fungsi sanitize_level_data
    private function sanitize_level_data($post_data) {
        error_log('Data sebelum sanitize: ' . print_r($post_data, true));
        // Basic level data
        $data = [
            'name' => sanitize_text_field($post_data['name']),
            'slug' => sanitize_title($post_data['name']),
            'description' => sanitize_textarea_field($post_data['description']),
            'price_per_month' => floatval($post_data['price_per_month']),
            'is_trial_available' => isset($post_data['is_trial_available']) ? 1 : 0,
            'trial_days' => isset($post_data['trial_days']) ? intval($post_data['trial_days']) : 0,
            'grace_period_days' => isset($post_data['grace_period_days']) ? intval($post_data['grace_period_days']) : 0,
            'sort_order' => intval($post_data['sort_order'])
        ];

        // Dapatkan semua features yang tersedia dari model
        $available_features = $this->feature_model->get_all_features_by_group();
        
        // Inisialisasi capabilities dengan struktur lengkap
        $capabilities = [];
        
        // Iterasi per grup feature
        foreach ($available_features as $group_slug => $features) {
            $capabilities[$group_slug] = [];
            
            foreach ($features as $feature) {
                $field_name = $feature['field_name'];
                $metadata = $feature['metadata'];
                
                // Set nilai awal dari metadata
                $feature_data = [
                    'type' => $metadata['type'],
                    'field' => $field_name,
                    'group' => $group_slug,
                    'label' => $metadata['label'],
                    'description' => $metadata['description'],
                    'is_required' => $metadata['is_required'] ?? false,
                    'ui_settings' => $metadata['ui_settings'] ?? [],
                    'default_value' => $metadata['default_value'],

                ];

                // Tentukan value berdasarkan tipe field
                if ($metadata['type'] === 'checkbox') {
                    // Untuk checkbox: false jika tidak dicentang
                    $feature_data['value'] = isset($post_data['capabilities'][$group_slug][$field_name]['value']) &&
                        ($post_data['capabilities'][$group_slug][$field_name]['value'] === 'on' ||
                         $post_data['capabilities'][$group_slug][$field_name]['value'] === '1' ||
                         $post_data['capabilities'][$group_slug][$field_name]['value'] === true ||
                         $post_data['capabilities'][$group_slug][$field_name]['value'] === 'true');
                }
                else if ($metadata['type'] === 'number') {
                    if (isset($post_data['capabilities'][$group_slug][$field_name]['value'])) {
                        $feature_data['value'] = intval($post_data['capabilities'][$group_slug][$field_name]['value']);
                    } else {
                        $feature_data['value'] = $metadata['default_value'] ?? 0;
                    }
                    
                    // Validasi min/max jika ada
                    if (isset($metadata['ui_settings']['min']) && $feature_data['value'] < $metadata['ui_settings']['min']) {
                        $feature_data['value'] = $metadata['ui_settings']['min'];
                    }
                    if (isset($metadata['ui_settings']['max']) && $feature_data['value'] > $metadata['ui_settings']['max']) {
                        $feature_data['value'] = $metadata['ui_settings']['max'];
                    }
                }
                else {
                    // Untuk tipe lain: gunakan default value jika tidak diisi
                    $feature_data['value'] = isset($post_data['capabilities'][$group_slug][$field_name]) ?
                        $post_data['capabilities'][$group_slug][$field_name] :
                        ($metadata['default_value'] ?? null);
                }

                // Pastikan settings selalu ada
                $feature_data['settings'] = [];

                // Simpan ke capabilities
                $capabilities[$group_slug][$field_name] = $feature_data;
            }
        }

        // Encode capabilities ke JSON
        $data['capabilities'] = json_encode($capabilities);
        error_log('Data setelah sanitize: ' . print_r($data, true));
        
        return $data;
    }


    private function validateCapabilitiesStructure($capabilities) {
        // Dapatkan daftar grup valid dari model
        $available_features = $this->feature_model->get_all_features_by_group();
        $valid_groups = array_keys($available_features);
        
        // Validasi struktur dasar
        if (!is_array($capabilities)) {
            throw new \Exception("Invalid capabilities format");
        }

        // Validasi setiap grup
        foreach ($capabilities as $group => $features) {
            if (!in_array($group, $valid_groups)) {
                throw new \Exception("Invalid capability group: {$group}");
            }

            if (!is_array($features)) {
                throw new \Exception("Invalid features structure for group: {$group}");
            }

            // Validasi setiap fitur dalam grup
            foreach ($features as $field_name => $feature) {
                if (!isset($feature['field']) || !isset($feature['value'])) {
                    throw new \Exception("Invalid feature structure for {$field_name} in {$group}");
                }

                // Validasi tipe data sesuai metadata
                $metadata = $this->findFeatureMetadata($available_features[$group], $field_name);
                if ($metadata && $metadata['type'] === 'number' && !is_numeric($feature['value'])) {
                    throw new \Exception("Value must be numeric for {$field_name}");
                }
            }
        }

        return true;
    }

    private function findFeatureMetadata($group_features, $field_name) {
        foreach ($group_features as $feature) {
            if ($feature['field_name'] === $field_name) {
                return $feature['metadata'];
            }
        }
        return null;
    }

    private function process_feature_value($value, $type, $group_slug) {
        if ($type === 'number') {
            return intval($value);
        }
        
        if ($type === 'checkbox') {
            return $value === 'on' || $value === '1' || $value === true;
        }
        
        return $value;
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
