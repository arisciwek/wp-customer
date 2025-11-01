<?php
/**
 * Branch Access Filter (for wp-agency New Companies Tab)
 *
 * @package     WP_Customer
 * @subpackage  Integrations
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Integrations/BranchAccessFilter.php
 *
 * Description: Cross-plugin integration untuk filter wp-agency new companies data.
 *              Hooks into wp-agency NewCompanyDataTableModel filters.
 *              Filters customer branches berdasarkan user's customer_id.
 *              Follows same pattern as AgencyAccessFilter and EmployeeAccessFilter.
 *
 * Integration Pattern:
 * - wp-agency provides filter hook: wpapp_datatable_customer_branches_where
 * - wp-customer hooks in to add filtering
 * - No tight coupling between plugins
 * - wp-agency works standalone without wp-customer
 *
 * Access Logic:
 * - Customer Admin/Branch Admin can only see branches from their own customer
 * - User → CustomerEmployee → Customer → Branches
 * - If user not a customer employee, no filtering applied
 *
 * Dependencies:
 * - wp-agency plugin (provides hooks via NewCompanyDataTableModel)
 * - wp_app_customer_branches table (target data)
 * - wp_app_customer_employees table (user context)
 *
 * Changelog:
 * 1.1.0 - 2025-11-01 (TODO-2183 Follow-up: Division Filtering for Branch Admin)
 * - ADDED: Division-based filtering for customer_branch_admin role
 * - Branch admin: filters by customer_id AND division_id (stricter access)
 * - Customer admin: filters by customer_id only (broader access)
 * - Auto-detects user role based on branch_id and division_id presence
 *
 * 1.0.0 - 2025-11-01 (TODO-2183 Follow-up)
 * - Initial implementation
 * - Hook into wpapp_datatable_customer_branches_where
 * - Filter branches by customer employee's customer_id
 * - Support Customer Admin & Branch Admin roles
 */

namespace WPCustomer\Integrations;

class BranchAccessFilter {

    /**
     * Constructor
     * Register filter hooks
     */
    public function __construct() {
        // Hook into wp-agency NewCompanyDataTableModel WHERE filter
        // Note: Hook name is 'customer_branches' (app_ prefix removed by wp-app-core)
        add_filter('wpapp_datatable_customer_branches_where', [$this, 'filter_branches_by_customer'], 10, 3);
    }

    /**
     * Filter customer branches based on customer employee's customer_id
     *
     * Hooked to: wpapp_datatable_customer_branches_where
     *
     * SQL Pattern:
     * User → CustomerEmployee → Customer → Branches
     *
     * Access Logic:
     * - If user is CustomerEmployee → filter to their customer_id only
     * - If user is NOT CustomerEmployee → no filtering (Platform/Agency user)
     *
     * @param array $where Existing WHERE conditions
     * @param array $request DataTable request data
     * @param object $model NewCompanyDataTableModel instance
     * @return array Modified WHERE conditions
     */
    public function filter_branches_by_customer($where, $request, $model) {
        global $wpdb;

        $user_id = get_current_user_id();

        error_log('[BranchAccessFilter] filter_branches_by_customer CALLED for user_id: ' . $user_id);
        error_log('[BranchAccessFilter] Incoming WHERE conditions: ' . print_r($where, true));

        // Check if user is customer employee - get branch info for division filtering
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT ce.*, ub.division_id as user_division_id
             FROM {$wpdb->prefix}app_customer_employees ce
             LEFT JOIN {$wpdb->prefix}app_customer_branches ub ON ce.branch_id = ub.id
             WHERE ce.user_id = %d",
            $user_id
        ));

        // Not a customer employee, no filtering needed
        if (!$employee) {
            error_log('[BranchAccessFilter] User is NOT a customer employee, skipping filter');
            return $where;
        }

        error_log('[BranchAccessFilter] User IS customer employee (ID: ' . $employee->id . ')');
        error_log('[BranchAccessFilter] Customer ID: ' . $employee->customer_id);

        // Check if user is branch admin (has specific branch_id and division_id)
        $is_branch_admin = !empty($employee->branch_id) && !empty($employee->user_division_id);

        if ($is_branch_admin) {
            // Branch admin: filter by customer_id AND division_id
            error_log('[BranchAccessFilter] User is BRANCH ADMIN - filtering by division_id: ' . $employee->user_division_id);

            $where[] = $wpdb->prepare('b.customer_id = %d', $employee->customer_id);
            $where[] = $wpdb->prepare('b.division_id = %d', $employee->user_division_id);

            error_log('[BranchAccessFilter] Added WHERE: b.customer_id = ' . $employee->customer_id);
            error_log('[BranchAccessFilter] Added WHERE: b.division_id = ' . $employee->user_division_id);
        } else {
            // Customer admin: filter by customer_id only (existing logic)
            error_log('[BranchAccessFilter] User is CUSTOMER ADMIN - filtering by customer_id only');

            $where[] = $wpdb->prepare('b.customer_id = %d', $employee->customer_id);

            error_log('[BranchAccessFilter] Added WHERE: b.customer_id = ' . $employee->customer_id);
        }

        error_log('[BranchAccessFilter] Final WHERE conditions: ' . print_r($where, true));

        return $where;
    }
}
