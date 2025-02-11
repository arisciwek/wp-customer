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

namespace WPCustomer\Validators\Membership;

use WPCustomer\Models\Membership\MembershipLevelModel;
use WPCustomer\Models\Membership\MembershipFeatureModel;

defined('ABSPATH') || exit;

class MembershipLevelValidator {
    private $level_model;
    private $feature_model;

    public function __construct() {
        $this->level_model = new MembershipLevelModel();
        $this->feature_model = new MembershipFeatureModel();
    }

    /**
     * Validate level data for save/update
     */
    public function validate_level($data, $id = null) {
        $errors = [];

        // Basic required fields
        if (empty($data['name'])) {
            $errors[] = __('Level name is required.', 'wp-customer');
        } else {
            // Check name length
            if (mb_strlen($data['name']) > 50) {
                $errors[] = __('Level name cannot exceed 50 characters.', 'wp-customer');
            }
            
            // Check slug uniqueness
            $slug = sanitize_title($data['name']);
            if ($this->level_model->exists_by_slug($slug, $id)) {
                $errors[] = __('A level with this name already exists.', 'wp-customer');
            }
        }

        // Price validation
        if (!isset($data['price_per_month'])) {
            $errors[] = __('Price per month is required.', 'wp-customer');
        } else {
            $price = floatval($data['price_per_month']);
            if ($price < 0) {
                $errors[] = __('Price cannot be negative.', 'wp-customer');
            }
        }

        // Trial period validation
        if (!empty($data['is_trial_available'])) {
            if (empty($data['trial_days']) || intval($data['trial_days']) < 1) {
                $errors[] = __('Trial days must be at least 1 when trial is available.', 'wp-customer');
            }
        }

        // Grace period validation
        if (!isset($data['grace_period_days'])) {
            $errors[] = __('Grace period days is required.', 'wp-customer');
        } else {
            $grace_days = intval($data['grace_period_days']);
            if ($grace_days < 0) {
                $errors[] = __('Grace period days cannot be negative.', 'wp-customer');
            }
        }

        // Sort order validation
        if (isset($data['sort_order'])) {
            $sort_order = intval($data['sort_order']);
            if ($sort_order < 0) {
                $errors[] = __('Sort order cannot be negative.', 'wp-customer');
            }
        }

        return $errors;
    }

    /**
     * Validate level deletion
     */
    public function validate_delete($level_id) {
        $errors = [];

        // Check if level exists
        $level = $this->level_model->get_level($level_id);
        if (!$level) {
            $errors[] = __('Level not found.', 'wp-customer');
            return $errors;
        }

        // Check if level is in use by active customers
        $active_customers = $this->level_model->get_active_customers_count($level_id);
        if ($active_customers > 0) {
            $errors[] = sprintf(
                __('Cannot delete level: %d active customers are using this level.', 'wp-customer'),
                $active_customers
            );
        }

        return $errors;
    }

    /**
     * Validate capability structure
     */
    public function validate_capabilities($capabilities) {
        $errors = [];

        if (!is_array($capabilities)) {
            $errors[] = __('Invalid capabilities structure.', 'wp-customer');
            return $errors;
        }

        // Validate required sections
        $required_sections = ['features', 'limits', 'notifications'];
        foreach ($required_sections as $section) {
            if (!isset($capabilities[$section])) {
                $errors[] = sprintf(
                    __('Missing required capabilities section: %s.', 'wp-customer'),
                    $section
                );
            }
        }

        // Validate feature values against master features
        if (isset($capabilities['features'])) {
            $master_features = $this->feature_model->get_all_features();
            foreach ($capabilities['features'] as $key => $feature) {
                $found = false;
                foreach ($master_features as $master) {
                    if ($master->field_name === $key) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $errors[] = sprintf(
                        __('Invalid feature found: %s.', 'wp-customer'),
                        $key
                    );
                }
            }
        }

        // Validate limits values
        if (isset($capabilities['limits'])) {
            foreach ($capabilities['limits'] as $key => $value) {
                if (!is_numeric($value) || ($value < -1)) {
                    $errors[] = sprintf(
                        __('Invalid limit value for %s. Must be -1 or greater.', 'wp-customer'),
                        $key
                    );
                }
            }
        }

        return $errors;
    }

    /**
     * Validate period values
     */
    private function validate_periods($trial_days, $grace_days) {
        $errors = [];

        // Trial days validation
        if ($trial_days !== null) {
            if (!is_numeric($trial_days) || $trial_days < 0) {
                $errors[] = __('Trial days must be 0 or greater.', 'wp-customer');
            }
        }

        // Grace period validation
        if ($grace_days !== null) {
            if (!is_numeric($grace_days) || $grace_days < 1) {
                $errors[] = __('Grace period must be at least 1 day.', 'wp-customer');
            }
        }

        return $errors;
    }
}

