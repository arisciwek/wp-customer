<?php
/**
 * Companies Validator
 *
 * Validates data and checks permissions using HOOK-based system
 * Replaces old access_type pattern with filters
 *
 * @package WPCustomer
 * @subpackage Validators\Companies
 * @since 1.1.0
 * @author arisciwek
 */

namespace WPCustomer\Validators\Companies;

defined('ABSPATH') || exit;

/**
 * CompaniesValidator class
 *
 * Handles validation and permission checks for companies
 * Uses WordPress filters for extensibility
 *
 * @since 1.1.0
 */
class CompaniesValidator {

    /**
     * Check if user can access companies page
     *
     * Uses filter: wp_customer_can_access_companies_page
     *
     * @return bool True if user can access, false otherwise
     */
    public function can_access_page() {
        // Default permission - use existing branch capability
        $can_access = current_user_can('view_customer_branch_list');

        /**
         * Filter: wp_customer_can_access_companies_page
         *
         * Allows other plugins (like wp-agency) to grant/deny access
         *
         * @param bool $can_access Default access permission
         * @param array $context Context data
         *
         * @since 1.1.0
         */
        return apply_filters('wp_customer_can_access_companies_page', $can_access, [
            'user_id' => get_current_user_id(),
            'is_admin' => is_admin()
        ]);
    }

    /**
     * Check if user can view specific company
     *
     * Uses filter: wp_customer_can_view_company
     *
     * @param int $company_id Company ID
     * @return bool True if user can view, false otherwise
     */
    public function can_view_company($company_id = null) {
        // Default permission - use existing branch capability
        $can_view = current_user_can('view_customer_branch_detail');

        /**
         * Filter: wp_customer_can_view_company
         *
         * Example use cases:
         * - Agency employee can only view assigned companies
         * - Inspector can only view assigned companies
         * - Platform admin can view all companies
         *
         * @param bool $can_view Default view permission
         * @param int|null $company_id Company ID
         *
         * @since 1.1.0
         */
        return apply_filters('wp_customer_can_view_company', $can_view, $company_id);
    }

    /**
     * Check if user can create company
     *
     * Uses filter: wp_customer_can_create_company
     *
     * @return bool True if user can create, false otherwise
     */
    public function can_create_company() {
        // Default permission - use existing branch capability
        $can_create = current_user_can('add_customer_branch');

        /**
         * Filter: wp_customer_can_create_company
         *
         * Example use cases:
         * - Limit number of companies per customer
         * - Agency-specific creation rules
         * - Role-based creation limits
         *
         * @param bool $can_create Default create permission
         * @param array $context Context data
         *
         * @since 1.1.0
         */
        return apply_filters('wp_customer_can_create_company', $can_create, [
            'user_id' => get_current_user_id()
        ]);
    }

    /**
     * Check if user can edit specific company
     *
     * Uses filter: wp_customer_can_edit_company
     *
     * @param int $company_id Company ID
     * @return bool True if user can edit, false otherwise
     */
    public function can_edit_company($company_id) {
        // Default permission - use existing branch capability
        $can_edit = current_user_can('edit_all_customer_branches');

        /**
         * Filter: wp_customer_can_edit_company
         *
         * Example use cases:
         * - Agency employee can only edit assigned companies
         * - Prevent editing of certain company types
         * - Time-based edit restrictions
         *
         * @param bool $can_edit Default edit permission
         * @param int $company_id Company ID
         *
         * @since 1.1.0
         */
        return apply_filters('wp_customer_can_edit_company', $can_edit, $company_id);
    }

    /**
     * Check if user can delete specific company
     *
     * Uses filter: wp_customer_can_delete_company
     *
     * @param int $company_id Company ID
     * @return bool True if user can delete, false otherwise
     */
    public function can_delete_company($company_id) {
        // Default permission - use existing branch capability
        $can_delete = current_user_can('delete_customer_branch');

        /**
         * Filter: wp_customer_can_delete_company
         *
         * Example use cases:
         * - Prevent deletion of pusat (HQ) companies
         * - Agency-specific deletion rules
         * - Prevent deletion if has related data
         *
         * @param bool $can_delete Default delete permission
         * @param int $company_id Company ID
         *
         * @since 1.1.0
         */
        return apply_filters('wp_customer_can_delete_company', $can_delete, $company_id);
    }

    /**
     * Validate data for creating company
     *
     * @param array $data Raw input data
     * @return array|\WP_Error Validated data or WP_Error on failure
     */
    public function validate_create_data($data) {
        $errors = new \WP_Error();

        // Required: customer_id
        if (empty($data['customer_id'])) {
            $errors->add('customer_id', __('Customer is required', 'wp-customer'));
        } else {
            $data['customer_id'] = intval($data['customer_id']);
        }

        // Required: code
        if (empty($data['code'])) {
            $errors->add('code', __('Company code is required', 'wp-customer'));
        } else {
            $data['code'] = sanitize_text_field($data['code']);

            // Check if code already exists
            if ($this->code_exists($data['code'])) {
                $errors->add('code', __('Company code already exists', 'wp-customer'));
            }
        }

        // Required: name
        if (empty($data['name'])) {
            $errors->add('name', __('Company name is required', 'wp-customer'));
        } else {
            $data['name'] = sanitize_text_field($data['name']);
        }

        // Required: type
        if (empty($data['type'])) {
            $errors->add('type', __('Company type is required', 'wp-customer'));
        } else {
            $data['type'] = sanitize_text_field($data['type']);

            // Validate type values
            if (!in_array($data['type'], ['pusat', 'cabang'])) {
                $errors->add('type', __('Invalid company type', 'wp-customer'));
            }
        }

        // Required: agency_id
        if (empty($data['agency_id'])) {
            $errors->add('agency_id', __('Agency is required', 'wp-customer'));
        } else {
            $data['agency_id'] = intval($data['agency_id']);
        }

        // Optional fields
        $optional_text_fields = ['nitku', 'postal_code', 'address', 'phone', 'email'];
        foreach ($optional_text_fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = sanitize_text_field($data[$field]);
            }
        }

        // Optional numeric fields
        $optional_numeric_fields = ['provinsi_id', 'regency_id', 'division_id', 'user_id', 'inspector_id'];
        foreach ($optional_numeric_fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = intval($data[$field]);
            }
        }

        // Optional: latitude & longitude
        if (isset($data['latitude'])) {
            $data['latitude'] = floatval($data['latitude']);
        }
        if (isset($data['longitude'])) {
            $data['longitude'] = floatval($data['longitude']);
        }

        // Status defaults to active
        $data['status'] = isset($data['status']) ? sanitize_text_field($data['status']) : 'active';

        // Return errors if any
        if ($errors->has_errors()) {
            return $errors;
        }

        return $data;
    }

    /**
     * Validate data for updating company
     *
     * @param array $data Raw input data
     * @return array|\WP_Error Validated data or WP_Error on failure
     */
    public function validate_update_data($data) {
        $errors = new \WP_Error();

        // Code (if provided, check uniqueness excluding current record)
        if (isset($data['code']) && !empty($data['code'])) {
            $data['code'] = sanitize_text_field($data['code']);

            $company_id = isset($data['company_id']) ? intval($data['company_id']) : 0;

            if ($this->code_exists($data['code'], $company_id)) {
                $errors->add('code', __('Company code already exists', 'wp-customer'));
            }
        }

        // Name (if provided)
        if (isset($data['name']) && !empty($data['name'])) {
            $data['name'] = sanitize_text_field($data['name']);
        }

        // Type (if provided, validate)
        if (isset($data['type']) && !empty($data['type'])) {
            $data['type'] = sanitize_text_field($data['type']);

            if (!in_array($data['type'], ['pusat', 'cabang'])) {
                $errors->add('type', __('Invalid company type', 'wp-customer'));
            }
        }

        // Optional text fields
        $optional_text_fields = ['nitku', 'postal_code', 'address', 'phone', 'email', 'status'];
        foreach ($optional_text_fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = sanitize_text_field($data[$field]);
            }
        }

        // Optional numeric fields
        $optional_numeric_fields = ['customer_id', 'agency_id', 'provinsi_id', 'regency_id', 'division_id', 'user_id', 'inspector_id'];
        foreach ($optional_numeric_fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = intval($data[$field]);
            }
        }

        // Optional: latitude & longitude
        if (isset($data['latitude'])) {
            $data['latitude'] = floatval($data['latitude']);
        }
        if (isset($data['longitude'])) {
            $data['longitude'] = floatval($data['longitude']);
        }

        // Return errors if any
        if ($errors->has_errors()) {
            return $errors;
        }

        return $data;
    }

    /**
     * Check if company code exists
     *
     * @param string $code Company code
     * @param int $exclude_id Company ID to exclude (for updates)
     * @return bool True if code exists, false otherwise
     */
    private function code_exists($code, $exclude_id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'app_customer_branches';

        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE code = %s AND id != %d",
            $code,
            $exclude_id
        );

        return $wpdb->get_var($sql) > 0;
    }
}
