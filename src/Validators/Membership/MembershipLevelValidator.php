<?php
/**
 * Membership Level Validator
 *
 * @package     WP_Customer
 * @subpackage  Validators/Membership
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Validators/Membership/MembershipLevelValidator.php
 *
 * Description: Validator untuk memvalidasi data membership level.
 *              Includes validasi untuk:
 *              - Basic level data (name, price, etc)
 *              - Capability structure
 *              - Business rules
 *              - Deletion rules
 *              
 * Dependencies:
 * - MembershipLevelModel untuk data checks
 * - MembershipFeatureModel untuk validasi capabilities
 *
 * Changelog:
 * 1.0.0 - 2024-02-11
 * - Initial version
 * - Added data validation
 * - Added deletion validation
 * - Added capability validation
 */
<?php
namespace WPCustomer\Validators\Membership;

class MembershipLevelValidator {
    private $model;

    public function __construct() {
        $this->model = new \WPCustomer\Models\Membership\MembershipLevelModel();
    }

    /**
     * Validate level data before save
     * 
     * @param array $data Level data
     * @param int|null $id Level ID if updating
     * @return array Array of error messages
     */
    public function validate_level($data, $id = null) {
        $errors = [];

        // Required fields
        if (empty($data['name'])) {
            $errors[] = __('Level name is required.', 'wp-customer');
        }

        // Name length
        if (strlen($data['name']) > 50) {
            $errors[] = __('Level name cannot exceed 50 characters.', 'wp-customer');
        }

        // Check slug uniqueness (except for current level when editing)
        if ($this->model->exists_by_slug($data['slug'], $id)) {
            $errors[] = __('A level with this name already exists.', 'wp-customer');
        }

        // Price validation
        if (!isset($data['price_per_month']) || $data['price_per_month'] < 0) {
            $errors[] = __('Price must be 0 or greater.', 'wp-customer');
        }

        // Trial period validation
        if (!empty($data['is_trial_available'])) {
            if (empty($data['trial_days']) || $data['trial_days'] < 0) {
                $errors[] = __('Trial days must be greater than 0 when trial is enabled.', 'wp-customer');
            }
        }

        // Grace period validation
        if (!isset($data['grace_period_days']) || $data['grace_period_days'] < 0) {
            $errors[] = __('Grace period days must be 0 or greater.', 'wp-customer');
        }

        // Validate capabilities structure
        if (!empty($data['capabilities'])) {
            $caps_errors = $this->validate_capabilities($data['capabilities']);
            $errors = array_merge($errors, $caps_errors);
        }

        return $errors;
    }

    /**
     * Validate capabilities data structure
     * 
     * @param array $capabilities
     * @return array Array of error messages
     */
    private function validate_capabilities($capabilities) {
        $errors = [];

        // Validate capabilities JSON structure
        $required_sections = ['features', 'limits', 'notifications'];
        
        // Check if it's a string (JSON) and decode
        if (is_string($capabilities)) {
            $capabilities = json_decode($capabilities, true);
        }

        if (!is_array($capabilities)) {
            $errors[] = __('Invalid capabilities format.', 'wp-customer');
            return $errors;
        }

        // Check required sections
        foreach ($required_sections as $section) {
            if (!isset($capabilities[$section]) || !is_array($capabilities[$section])) {
                $errors[] = sprintf(__('Missing or invalid %s section in capabilities.', 'wp-customer'), $section);
            }
        }

        // Validate limits
        if (isset($capabilities['limits'])) {
            foreach ($capabilities['limits'] as $key => $limit) {
                if (!isset($limit['value']) || !is_numeric($limit['value'])) {
                    $errors[] = sprintf(__('Invalid value for limit: %s', 'wp-customer'), $key);
                }
            }
        }

        // Validate features
        if (isset($capabilities['features'])) {
            foreach ($capabilities['features'] as $key => $feature) {
                if (!isset($feature['value']) || !is_bool($feature['value'])) {
                    $errors[] = sprintf(__('Invalid value for feature: %s', 'wp-customer'), $key);
                }
            }
        }

        return $errors;
    }

    /**
     * Validate level deletion
     * 
     * @param int $level_id
     * @return array Array of error messages
     */
    public function validate_delete($level_id) {
        $errors = [];

        // Check if level exists
        if (!$this->model->get_level($level_id)) {
            $errors[] = __('Level not found.', 'wp-customer');
        }

        // Check if level is in use
        if ($this->model->is_level_in_use($level_id)) {
            $errors[] = __('Cannot delete level because it is currently in use by one or more customers.', 'wp-customer');
        }

        return $errors;
    }
}
