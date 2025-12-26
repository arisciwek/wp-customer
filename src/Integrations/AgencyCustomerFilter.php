<?php
/**
 * Agency Customer Filter
 *
 * @package     WP_Customer
 * @subpackage  Integrations
 * @version     1.3.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Integrations/AgencyCustomerFilter.php
 *
 * Description: Filter customer list untuk agency users.
 *              Agency users hanya bisa melihat customers yang punya branch
 *              di agency mereka (filtered by agency_id).
 *
 * Filter Logic:
 * - Agency user → Get agency_id
 * - Filter customers: hanya yang punya branches di agency tersebut
 *
 * Integration Pattern:
 * - Works alongside CustomerRoleFilter
 * - CustomerRoleFilter: handles customer plugin roles
 * - AgencyCustomerFilter: handles agency plugin roles
 * - Both can coexist without conflict
 *
 * Dependencies:
 * - wp-agency plugin (provides agency roles & data)
 * - wp_app_agency_employees table (user → agency mapping)
 * - wp_app_customer_branches table (customer → agency mapping)
 *
 * Changelog:
 * 1.3.0 - 2025-11-05
 * - FIXED: Added filter_where_conditions() for DataTable data query
 * - Hook: wpapp_datatable_customers_where (for DataTableQueryBuilder WHERE array)
 * - Fixes DataTable showing all 10 records instead of filtered 4
 * - Now uses 2 different hooks:
 *   * wpapp_datatable_customers_count_query (QueryBuilder) → for statistics
 *   * wpapp_datatable_customers_where (WHERE array) → for data query
 *
 * 1.2.0 - 2025-11-05
 * - FIXED: getUserAgencyId() now checks BOTH agency admin AND employee
 * - Check 1: wp_app_agencies.user_id (for agency admins like ahmad_bambang)
 * - Check 2: wp_app_agency_employees.agency_id (for agency staff)
 * - Matches WP_Agency_WP_Customer_Integration::get_user_agency_id() logic
 * - Fixes "Agency ID: NULL" issue in Customer V2
 * - FIXED: Replaced wpdb::prepare with sprintf + intval (avoid single placeholder Notice)
 * - FIXED: whereRaw() now uses sprintf + intval instead of array bindings (avoid array binding Notice)
 *
 * 1.1.0 - 2025-11-05
 * - FIXED: Changed from province_id to agency_id filtering
 * - Match CustomerModel logic for consistency
 * - Now matches "WP Customer" (OLD) menu behavior
 *
 * 1.0.0 - 2025-11-05
 * - Initial implementation
 * - Province-based filtering for agency users (WRONG - caused 0 results)
 * - QueryBuilder integration
 * - Hook into wpapp_datatable_customers_count_query
 * - Hook into wpapp_datatable_customers_query
 */

namespace WPCustomer\Integrations;

use WPQB\QueryBuilder;

class AgencyCustomerFilter {

    /**
     * Constructor
     * Register filter hooks
     */
    public function __construct() {
        // Hook into customer DataTable QueryBuilder filters

        // For statistics count (uses WPQB\QueryBuilder)
        add_filter('wpapp_datatable_customers_count_query', [$this, 'filter_count_query'], 20, 2);

        // For data query (uses DataTableQueryBuilder with WHERE array)
        add_filter('wpapp_datatable_customers_where', [$this, 'filter_where_conditions'], 20, 3);
    }

    /**
     * Filter count query for agency users (Simplified - Phase 6)
     *
     * Uses wp-agency EntityRelationModel for consistent access control.
     * Agency users see only customers with branches assigned to their agency.
     *
     * Hooked to: wpapp_datatable_customers_count_query (priority 20, after CustomerRoleFilter)
     *
     * @param QueryBuilder $query QueryBuilder instance
     * @param array $params Request parameters
     * @return QueryBuilder Modified query
     *
     * @since 1.4.0 Simplified to use wp-agency EntityRelationModel
     */
    public function filter_count_query($query, $params) {
        // Check if admin (no filtering)
        if (current_user_can('manage_options')) {
            return $query;
        }

        // Check if user has agency role
        if (!$this->hasAgencyRole()) {
            return $query;
        }

        // Check if wp-agency plugin is active and has EntityRelationModel
        if (!class_exists('\\WPAgency\\Models\\Relation\\EntityRelationModel')) {
            return $query; // wp-agency not available, skip filter
        }

        // Use wp-agency EntityRelationModel
        $agency_entity_model = new \WPAgency\Models\Relation\EntityRelationModel();
        $customer_ids = $agency_entity_model->get_accessible_entity_ids('customer');

        // Empty = see all (platform staff)
        if (empty($customer_ids)) {
            return $query;
        }

        // [0] = block all
        if ($customer_ids === [0]) {
            $query->whereRaw('1=0');
            return $query;
        }

        // Apply filter
        $query->whereIn('c.id', $customer_ids);

        return $query;
    }

    /**
     * Filter WHERE conditions for data query (Simplified - Phase 6)
     *
     * Uses wp-agency EntityRelationModel for consistent access control.
     * Agency users see only customers with branches assigned to their agency.
     *
     * Hooked to: wpapp_datatable_customers_where (priority 20, after CustomerRoleFilter)
     *
     * @param array $where_conditions Current WHERE conditions (array of SQL strings)
     * @param array $request_data DataTables request data
     * @param DataTableModel $model Model instance
     * @return array Modified WHERE conditions
     *
     * @since 1.4.0 Simplified to use wp-agency EntityRelationModel
     */
    public function filter_where_conditions($where_conditions, $request_data, $model) {
        // Check if admin (no filtering)
        if (current_user_can('manage_options')) {
            return $where_conditions;
        }

        // Check if user has agency role
        if (!$this->hasAgencyRole()) {
            return $where_conditions;
        }

        // Check if wp-agency plugin is active
        if (!class_exists('\\WPAgency\\Models\\Relation\\EntityRelationModel')) {
            return $where_conditions;
        }

        // Use wp-agency EntityRelationModel
        $agency_entity_model = new \WPAgency\Models\Relation\EntityRelationModel();
        $customer_ids = $agency_entity_model->get_accessible_entity_ids('customer');

        // Empty = see all
        if (empty($customer_ids)) {
            return $where_conditions;
        }

        // [0] = block all
        if ($customer_ids === [0]) {
            $where_conditions[] = '1=0';
            return $where_conditions;
        }

        // Apply filter
        $ids = implode(',', array_map('intval', $customer_ids));
        $where_conditions[] = "c.id IN ({$ids})";

        return $where_conditions;
    }

    /**
     * Check if current user has agency plugin role
     *
     * @return bool True if user has agency role
     */
    private function hasAgencyRole() {
        $user = wp_get_current_user();
        $user_roles = (array) $user->roles;

        // Check for agency plugin roles
        $agency_roles = [
            'agency',
            'agency_admin_provinsi',
            'agency_admin_dinas',
            'agency_inspector',
            'agency_division_admin'
        ];

        foreach ($user_roles as $role) {
            if (in_array($role, $agency_roles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get filter statistics (for debugging/logging - Phase 6 Updated)
     *
     * Uses wp-agency EntityRelationModel for consistent data access.
     *
     * @return array Statistics
     *
     * @since 1.4.0 Updated to use wp-agency EntityRelationModel
     */
    public function getFilterStats() {
        $current_user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');
        $has_agency_role = $this->hasAgencyRole();

        // Check if wp-agency available
        if (!class_exists('\\WPAgency\\Models\\Relation\\EntityRelationModel')) {
            return [
                'user_id' => $current_user_id,
                'is_admin' => $is_admin,
                'has_agency_role' => $has_agency_role,
                'accessible_customer_ids' => [],
                'accessible_count' => 0,
                'filter_active' => false,
                'note' => 'wp-agency plugin not active'
            ];
        }

        // Use wp-agency EntityRelationModel
        $agency_entity_model = new \WPAgency\Models\Relation\EntityRelationModel();
        $accessible_ids = $agency_entity_model->get_accessible_entity_ids('customer');

        return [
            'user_id' => $current_user_id,
            'is_admin' => $is_admin,
            'has_agency_role' => $has_agency_role,
            'accessible_customer_ids' => $accessible_ids,
            'accessible_count' => count($accessible_ids),
            'filter_active' => !$is_admin && $has_agency_role && !empty($accessible_ids)
        ];
    }
}
