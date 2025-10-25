<?php
/**
 * EXAMPLE: Agency Companies Access Integration
 *
 * This is a REFERENCE implementation showing how wp-agency plugin can integrate
 * with the Companies (Branches) system using the hook-based permission system.
 *
 * ⚠️  THIS FILE IS DISABLED BY DEFAULT - FOR REFERENCE ONLY ⚠️
 *
 * To implement in production:
 * 1. Copy this file to: wp-agency/src/Integrations/WPCustomer/CompaniesAccessIntegration.php
 * 2. Update namespace to: WPAgency\Integrations\WPCustomer
 * 3. Initialize in wp-agency main plugin file
 * 4. Test thoroughly before deploying
 *
 * @package WPCustomer
 * @subpackage Examples\Hooks
 * @since 1.1.0
 * @author arisciwek
 */

namespace WPCustomer\Examples\Hooks;

use WPCustomer\Cache\CustomerCacheManager;

// IMPORTANT: This file is not autoloaded by default
// It exists purely as documentation/reference
if (!defined('WP_CUSTOMER_ENABLE_EXAMPLES')) {
    return; // Exit if examples are not explicitly enabled
}

/**
 * AgencyCompaniesAccess class
 *
 * Demonstrates how agency employees can be granted access to companies (branches)
 * based on agency assignment.
 *
 * Business Rules Implemented:
 * 1. Agency employees can view companies list page
 * 2. Agency employees can only view/edit companies assigned to their agency
 * 3. Agency managers can create companies for their agency
 * 4. Agency managers can delete companies (with restrictions)
 * 5. All checks are cached for performance
 *
 * @since 1.1.0
 */
class AgencyCompaniesAccess {

    /**
     * Cache manager instance
     *
     * @var CustomerCacheManager
     */
    private $cache;

    /**
     * Constructor
     */
    public function __construct() {
        $this->cache = new CustomerCacheManager();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Page access filter
        add_filter('wp_customer_can_access_companies_page', [$this, 'can_access_page'], 10, 2);

        // CRUD permission filters
        add_filter('wp_customer_can_view_company', [$this, 'can_view_company'], 10, 2);
        add_filter('wp_customer_can_create_company', [$this, 'can_create_company'], 10, 2);
        add_filter('wp_customer_can_edit_company', [$this, 'can_edit_company'], 10, 2);
        add_filter('wp_customer_can_delete_company', [$this, 'can_delete_company'], 10, 2);

        // DataTable filter to show only assigned companies
        add_filter('wpapp_datatable_customer_branches_where', [$this, 'filter_datatable_query'], 10, 3);

        // Cache invalidation hooks
        add_action('wp_agency_employee_updated', [$this, 'clear_employee_cache'], 10, 2);
        add_action('wp_customer_company_updated', [$this, 'clear_company_cache'], 10, 3);
    }

    /**
     * Filter: Allow agency employees to access companies page
     *
     * @param bool $can_access Default access permission
     * @param array $context Context data (user_id, is_admin)
     * @return bool
     */
    public function can_access_page($can_access, $context) {
        // If already has access via capabilities, return early
        if ($can_access) {
            return $can_access;
        }

        $user_id = $context['user_id'];

        // Check if user is an active agency employee
        return $this->is_agency_employee($user_id);
    }

    /**
     * Filter: Allow agency employees to view their assigned companies
     *
     * @param bool $can_view Default view permission
     * @param int|null $company_id Company ID
     * @return bool
     */
    public function can_view_company($can_view, $company_id) {
        // If already has permission or no specific company, return early
        if ($can_view || !$company_id) {
            return $can_view;
        }

        $user_id = get_current_user_id();

        // Check if user's agency matches company's agency
        return $this->user_can_access_company($user_id, $company_id);
    }

    /**
     * Filter: Allow agency managers to create companies
     *
     * @param bool $can_create Default create permission
     * @param array $context Context data (user_id)
     * @return bool
     */
    public function can_create_company($can_create, $context) {
        // If already has permission, return early
        if ($can_create) {
            return $can_create;
        }

        $user_id = $context['user_id'];

        // Check if user is agency manager
        $employee = $this->get_agency_employee($user_id);

        if (!$employee) {
            return false;
        }

        // Only managers can create companies
        return in_array($employee->role, ['manager', 'admin']);
    }

    /**
     * Filter: Allow agency employees to edit their assigned companies
     *
     * @param bool $can_edit Default edit permission
     * @param int $company_id Company ID
     * @return bool
     */
    public function can_edit_company($can_edit, $company_id) {
        // If already has permission, return early
        if ($can_edit) {
            return $can_edit;
        }

        $user_id = get_current_user_id();

        // Check if user's agency matches company's agency
        return $this->user_can_access_company($user_id, $company_id);
    }

    /**
     * Filter: Allow agency managers to delete companies (with restrictions)
     *
     * @param bool $can_delete Default delete permission
     * @param int $company_id Company ID
     * @return bool
     */
    public function can_delete_company($can_delete, $company_id) {
        // If already has permission, return early
        if ($can_delete) {
            return $can_delete;
        }

        $user_id = get_current_user_id();

        // Get employee data
        $employee = $this->get_agency_employee($user_id);

        if (!$employee) {
            return false;
        }

        // Only managers can delete
        if (!in_array($employee->role, ['manager', 'admin'])) {
            return false;
        }

        // Check if company belongs to employee's agency
        return $this->user_can_access_company($user_id, $company_id);
    }

    /**
     * Filter: Modify DataTable query to show only assigned companies
     *
     * Filters branches based on:
     * 1. Agency assignment
     * 2. Division jurisdiction (regency-based access)
     *
     * @param array $where WHERE conditions
     * @param array $request_data Request data from DataTable
     * @param object $model DataTable model instance
     * @return array Modified WHERE conditions
     */
    public function filter_datatable_query($where, $request_data, $model) {
        $user_id = get_current_user_id();

        // Skip filter if user has full access capability
        if (current_user_can('view_all_customer_branches')) {
            return $where;
        }

        // Get agency employee data
        $employee = $this->get_agency_employee($user_id);

        if (!$employee) {
            return $where; // Return unmodified if not an agency employee
        }

        global $wpdb;
        $table = $wpdb->prefix . 'app_customer_branches';

        // Add WHERE condition to filter by agency
        $where[] = $wpdb->prepare(
            "{$table}.agency_id = %d",
            $employee->agency_id
        );

        // If employee has division, filter by jurisdiction
        if (!empty($employee->division_id)) {
            // Get jurisdiction codes for this division
            $jurisdiction_codes = $this->get_division_jurisdictions($employee->division_id);

            if (!empty($jurisdiction_codes)) {
                // Add subquery to filter by regency jurisdiction
                // Branches are accessible if their regency_id matches jurisdiction codes
                $codes_placeholder = implode(',', array_fill(0, count($jurisdiction_codes), '%s'));

                $where[] = $wpdb->prepare(
                    "{$table}.regency_id IN (
                        SELECT id FROM {$wpdb->prefix}wi_regencies
                        WHERE code IN ($codes_placeholder)
                    )",
                    ...$jurisdiction_codes
                );
            }
        }

        return $where;
    }

    /**
     * Check if user is an active agency employee
     *
     * @param int $user_id User ID
     * @return bool
     */
    private function is_agency_employee($user_id) {
        // Check cache first
        $cache_key = "is_agency_employee_{$user_id}";
        $cached = $this->cache->get('agency_access', $cache_key);

        if ($cached !== null) {
            return (bool) $cached;
        }

        global $wpdb;

        $is_employee = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_agency_employees
             WHERE user_id = %d AND status = 'active'",
            $user_id
        ));

        $result = $is_employee > 0;

        // Cache for 15 minutes
        $this->cache->set('agency_access', $result, 15 * MINUTE_IN_SECONDS, $cache_key);

        return $result;
    }

    /**
     * Get agency employee record for user
     *
     * @param int $user_id User ID
     * @return object|null Employee data or null
     */
    private function get_agency_employee($user_id) {
        // Check cache first
        $cache_key = "agency_employee_{$user_id}";
        $cached = $this->cache->get('agency_employee', $cache_key);

        if ($cached !== null) {
            return $cached;
        }

        global $wpdb;

        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}app_agency_employees
             WHERE user_id = %d AND status = 'active'",
            $user_id
        ));

        // Cache for 15 minutes
        $this->cache->set('agency_employee', $employee, 15 * MINUTE_IN_SECONDS, $cache_key);

        return $employee;
    }

    /**
     * Get jurisdiction codes (regency codes) for a division
     *
     * @param int $division_id Division ID
     * @return array Array of jurisdiction codes
     */
    private function get_division_jurisdictions($division_id) {
        // Check cache first
        $cache_key = "division_jurisdictions_{$division_id}";
        $cached = $this->cache->get('division_jurisdiction', $cache_key);

        if ($cached !== null) {
            return $cached;
        }

        global $wpdb;

        $jurisdiction_codes = $wpdb->get_col($wpdb->prepare(
            "SELECT jurisdiction_code FROM {$wpdb->prefix}app_agency_jurisdictions
             WHERE division_id = %d",
            $division_id
        ));

        // Cache for 1 hour (jurisdictions don't change often)
        $this->cache->set('division_jurisdiction', $jurisdiction_codes, HOUR_IN_SECONDS, $cache_key);

        return $jurisdiction_codes;
    }

    /**
     * Check if user can access specific company based on agency assignment
     *
     * @param int $user_id User ID
     * @param int $company_id Company ID
     * @return bool
     */
    private function user_can_access_company($user_id, $company_id) {
        // Check cache first
        $cache_key = "company_access_{$user_id}_{$company_id}";
        $cached = $this->cache->get('company_access', $cache_key);

        if ($cached !== null) {
            return (bool) $cached;
        }

        // Get employee data
        $employee = $this->get_agency_employee($user_id);

        if (!$employee) {
            return false;
        }

        global $wpdb;

        // Check if company belongs to employee's agency
        $company_agency_id = $wpdb->get_var($wpdb->prepare(
            "SELECT agency_id FROM {$wpdb->prefix}app_customer_branches
             WHERE id = %d",
            $company_id
        ));

        $has_access = ($company_agency_id == $employee->agency_id);

        // Cache for 15 minutes
        $this->cache->set('company_access', $has_access, 15 * MINUTE_IN_SECONDS, $cache_key);

        return $has_access;
    }

    /**
     * Clear employee-related cache when employee is updated
     *
     * @param int $employee_id Employee ID
     * @param object $employee_data Employee data
     */
    public function clear_employee_cache($employee_id, $employee_data) {
        if (isset($employee_data->user_id)) {
            $user_id = $employee_data->user_id;

            // Clear all caches for this user
            $this->cache->delete('agency_access', "is_agency_employee_{$user_id}");
            $this->cache->delete('agency_employee', "agency_employee_{$user_id}");

            // Clear company access caches (pattern-based deletion)
            $this->cache->clearCache("company_access_{$user_id}_");

            // Clear jurisdiction cache if division changed
            if (isset($employee_data->division_id)) {
                $this->cache->delete('division_jurisdiction', "division_jurisdictions_{$employee_data->division_id}");
            }
        }
    }

    /**
     * Clear company cache when company is updated
     *
     * @param int $company_id Company ID
     * @param object $old_data Old company data
     * @param object $new_data New company data
     */
    public function clear_company_cache($company_id, $old_data, $new_data) {
        // If agency changed, clear all access caches for this company
        if ($old_data->agency_id !== $new_data->agency_id) {
            // Clear pattern: company_access_*_{company_id}
            $this->cache->clearCache("company_access_");
        }
    }
}

/**
 * Initialize the integration
 *
 * PRODUCTION: This should be called from wp-agency plugin:
 * new \WPAgency\Integrations\WPCustomer\CompaniesAccessIntegration();
 */
if (defined('WP_CUSTOMER_ENABLE_EXAMPLES') && WP_CUSTOMER_ENABLE_EXAMPLES) {
    new AgencyCompaniesAccess();
}
