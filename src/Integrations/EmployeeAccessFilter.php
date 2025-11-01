<?php
/**
 * Agency Employee Access Filter
 *
 * @package     WP_Customer
 * @subpackage  Integrations
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Integrations/EmployeeAccessFilter.php
 *
 * Description: Cross-plugin integration untuk filter wp-agency employee data.
 *              Hooks into wp-agency EmployeeDataTable filters.
 *              Filters agency employees berdasarkan user's accessible agencies.
 *              Follows same pattern as AgencyAccessFilter.
 *
 * Integration Pattern:
 * - wp-agency provides filter hook: wpapp_datatable_agency_employees_where
 * - wp-customer hooks in to add filtering
 * - No tight coupling between plugins
 * - wp-agency works standalone without wp-customer
 *
 * Access Logic:
 * - Customer Admin/Branch Admin can only see employees from agencies they have access to
 * - User → CustomerEmployee → Branch → Agency → AgencyEmployees
 * - If user not a customer employee, no filtering applied
 *
 * Dependencies:
 * - wp-agency plugin (provides hooks)
 * - wp_app_customer_branches table (bridge with agency_id)
 * - wp_app_customer_employees table (user context)
 * - wp_app_agency_employees table (target data)
 *
 * Changelog:
 * 1.1.0 - 2025-11-01 (TODO-2183 Follow-up: Division Filtering for Branch Admin)
 * - ADDED: Division-based filtering for customer_branch_admin role
 * - Branch admin: filters by division_id (stricter access)
 * - Customer admin: filters by accessible agencies (broader access)
 * - Auto-detects user role based on branch_id and division_id presence
 *
 * 1.0.1 - 2025-11-01 (TODO-2183 Follow-up: CRITICAL FIX)
 * - FIXED: Wrong hook name (was wpapp_datatable_app_agency_employees_where)
 * - NOW: wpapp_datatable_agency_employees_where (app_ prefix removed by wp-app-core)
 * - Filter now works correctly - user sees only 3 employees instead of 50
 *
 * 1.0.0 - 2025-11-01 (TODO-2183 Follow-up)
 * - Initial implementation
 * - Hook into wpapp_datatable_agency_employees_where
 * - Filter agency employees by customer's accessible agencies
 * - Support Customer Admin & Branch Admin roles
 */

namespace WPCustomer\Integrations;

class EmployeeAccessFilter {

    /**
     * Constructor
     * Register filter hooks
     */
    public function __construct() {
        // Hook into wp-agency EmployeeDataTable WHERE filter
        // Note: Hook name is 'agency_employees' not 'app_agency_employees' (app_ prefix removed by wp-app-core)
        add_filter('wpapp_datatable_agency_employees_where', [$this, 'filter_employees_by_customer'], 10, 3);
    }

    /**
     * Filter agency employees based on customer's accessible agencies
     *
     * Hooked to: wpapp_datatable_agency_employees_where
     *
     * SQL Pattern:
     * User → CustomerEmployee → Branch → Agency → AgencyEmployees
     *
     * Access Logic:
     * - If user is CustomerEmployee → filter to accessible agencies only
     * - If user is NOT CustomerEmployee → no filtering (Platform/Agency user)
     *
     * @param array $where Existing WHERE conditions
     * @param array $request DataTable request data
     * @param object $model EmployeeDataTableModel instance
     * @return array Modified WHERE conditions
     */
    public function filter_employees_by_customer($where, $request, $model) {
        global $wpdb;

        $user_id = get_current_user_id();

        error_log('[EmployeeAccessFilter] filter_employees_by_customer CALLED for user_id: ' . $user_id);
        error_log('[EmployeeAccessFilter] Incoming WHERE conditions: ' . print_r($where, true));

        // Check if user is customer employee - get branch info too for division filtering
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT ce.*, b.division_id
             FROM {$wpdb->prefix}app_customer_employees ce
             LEFT JOIN {$wpdb->prefix}app_customer_branches b ON ce.branch_id = b.id
             WHERE ce.user_id = %d",
            $user_id
        ));

        // Not a customer employee, no filtering needed
        if (!$employee) {
            error_log('[EmployeeAccessFilter] User is NOT a customer employee, skipping filter');
            return $where;
        }

        error_log('[EmployeeAccessFilter] User IS customer employee (ID: ' . $employee->id . ')');

        // Check if user is branch admin (has specific branch_id and division_id)
        $is_branch_admin = !empty($employee->branch_id) && !empty($employee->division_id);

        if ($is_branch_admin) {
            // Branch admin: filter by division_id
            error_log('[EmployeeAccessFilter] User is BRANCH ADMIN - filtering by division_id: ' . $employee->division_id);

            // Get agency_id from division (for additional security)
            $division = $wpdb->get_row($wpdb->prepare(
                "SELECT agency_id FROM {$wpdb->prefix}app_agency_divisions WHERE id = %d",
                $employee->division_id
            ));

            if ($division) {
                $where[] = $wpdb->prepare('e.division_id = %d', $employee->division_id);
                $where[] = $wpdb->prepare('e.agency_id = %d', $division->agency_id);

                error_log('[EmployeeAccessFilter] Added WHERE: e.division_id = ' . $employee->division_id);
                error_log('[EmployeeAccessFilter] Added WHERE: e.agency_id = ' . $division->agency_id);
            } else {
                // Division not found, block all
                $where[] = "1=0";
                error_log('[EmployeeAccessFilter] Division not found, blocking all');
            }
        } else {
            // Customer admin: filter by accessible agencies (existing logic)
            error_log('[EmployeeAccessFilter] User is CUSTOMER ADMIN - filtering by accessible agencies');

            $accessible_agencies = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT b.agency_id
                 FROM {$wpdb->prefix}app_customer_branches b
                 INNER JOIN {$wpdb->prefix}app_customer_employees ce ON b.id = ce.branch_id
                 WHERE ce.user_id = %d
                 AND b.agency_id IS NOT NULL",
                $user_id
            ));

            if (empty($accessible_agencies)) {
                // User has no accessible agencies, block all
                error_log('[EmployeeAccessFilter] NO accessible agencies found, blocking all');
                $where[] = "1=0";
            } else {
                // Filter to accessible agencies only
                $ids = implode(',', array_map('intval', $accessible_agencies));
                $where[] = "e.agency_id IN ({$ids})";

                error_log(sprintf(
                    '[EmployeeAccessFilter] User %d has access to %d agencies: [%s]',
                    $user_id,
                    count($accessible_agencies),
                    implode(', ', $accessible_agencies)
                ));
                error_log('[EmployeeAccessFilter] Added WHERE: e.agency_id IN (' . $ids . ')');
            }
        }

        error_log('[EmployeeAccessFilter] Final WHERE conditions: ' . print_r($where, true));

        return $where;
    }
}
