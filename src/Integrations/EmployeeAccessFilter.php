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
     * Filter agency employees based on customer's accessible agencies (Simplified - Phase 3)
     *
     * Uses EntityRelationModel for consistent access control.
     * Branch admin: filters by division_id
     * Customer admin: filters by accessible agency_ids
     *
     * Hooked to: wpapp_datatable_agency_employees_where
     *
     * @param array $where Existing WHERE conditions
     * @param array $request DataTable request data
     * @param object $model EmployeeDataTableModel instance
     * @return array Modified WHERE conditions
     *
     * @since 1.2.0 Simplified to use EntityRelationModel
     */
    public function filter_employees_by_customer($where, $request, $model) {
        global $wpdb;

        // Platform staff bypass
        if (current_user_can('manage_options')) {
            return $where;
        }

        // Check if customer employee
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT ce.*, b.division_id
             FROM {$wpdb->prefix}app_customer_employees ce
             LEFT JOIN {$wpdb->prefix}app_customer_branches b ON ce.branch_id = b.id
             WHERE ce.user_id = %d",
            get_current_user_id()
        ));

        if (!$employee) {
            return $where; // Not customer employee
        }

        // Detect branch admin
        $user = wp_get_current_user();
        $is_customer_admin = in_array('customer_admin', $user->roles);
        $is_branch_admin = !empty($employee->division_id) && !$is_customer_admin;

        if ($is_branch_admin) {
            // Branch admin: filter by division_id
            $where[] = $wpdb->prepare('e.division_id = %d', $employee->division_id);
            return $where;
        } else {
            // Customer admin: use EntityRelationModel (Phase 3 - Simplified)
            $entity_model = new \WPCustomer\Models\Relation\EntityRelationModel();
            $accessible_ids = $entity_model->get_accessible_entity_ids('agency');

            if (empty($accessible_ids)) {
                return $where; // See all
            }

            if ($accessible_ids === [0]) {
                $where[] = '1=0';
                return $where;
            }

            // Apply agency filter (table alias 'e' for employees)
            $ids = implode(',', array_map('intval', $accessible_ids));
            $where[] = "e.agency_id IN ({$ids})";
        }

        return $where;
    }
}
