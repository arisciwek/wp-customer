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
     * Filter count query for agency users (QueryBuilder)
     *
     * Agency-based filtering: hanya customers dengan branches di agency user.
     *
     * Hooked to: wpapp_datatable_customers_count_query (priority 20, after CustomerRoleFilter)
     *
     * @param QueryBuilder $query QueryBuilder instance
     * @param array $params Request parameters
     * @return QueryBuilder Modified query
     */
    public function filter_count_query($query, $params) {
        error_log('=== AgencyCustomerFilter::filter_count_query CALLED ===');
        error_log('User ID: ' . get_current_user_id());
        error_log('Query class: ' . get_class($query));

        // Check if admin (no filtering)
        if (current_user_can('manage_options')) {
            error_log('User is admin - skipping filter');
            return $query;
        }

        // Check if user has agency role
        if (!$this->hasAgencyRole()) {
            error_log('User does not have agency role - skipping filter');
            // Not an agency user - no filtering needed
            return $query;
        }

        error_log('User has agency role - applying filter');

        // Get user's agency_id
        $agency_id = $this->getUserAgencyId();
        error_log('Agency ID: ' . ($agency_id ?? 'NULL'));

        if (!$agency_id) {
            error_log('No agency_id - blocking all results');
            // No agency assigned - block all results
            $query->whereRaw('1=0');
            return $query;
        }

        // Filter customers: only those with branches in this agency
        global $wpdb;
        error_log('Adding agency_id filter: ' . $agency_id);

        // Use sprintf with intval to avoid wpdb::prepare array binding Notice
        // QueryBuilder's whereRaw doesn't need bindings array with sprintf approach
        $query->whereRaw(sprintf(
            "c.id IN (
                SELECT DISTINCT customer_id
                FROM {$wpdb->prefix}app_customer_branches
                WHERE agency_id = %d
            )",
            intval($agency_id)
        ));

        error_log('Query after filter: ' . $query->toSql());
        error_log('=== END AgencyCustomerFilter ===');

        return $query;
    }

    /**
     * Filter WHERE conditions for data query (DataTableQueryBuilder)
     *
     * Agency-based filtering: hanya customers dengan branches di agency user.
     *
     * Hooked to: wpapp_datatable_customers_where (priority 20, after CustomerRoleFilter)
     *
     * @param array $where_conditions Current WHERE conditions (array of SQL strings)
     * @param array $request_data DataTables request data
     * @param DataTableModel $model Model instance
     * @return array Modified WHERE conditions
     */
    public function filter_where_conditions($where_conditions, $request_data, $model) {
        error_log('=== AgencyCustomerFilter::filter_where_conditions CALLED ===');
        error_log('User ID: ' . get_current_user_id());
        error_log('Where conditions count: ' . count($where_conditions));

        // Check if admin (no filtering)
        if (current_user_can('manage_options')) {
            error_log('User is admin - skipping filter');
            return $where_conditions;
        }

        // Check if user has agency role
        if (!$this->hasAgencyRole()) {
            error_log('User does not have agency role - skipping filter');
            return $where_conditions;
        }

        error_log('User has agency role - applying filter');

        // Get user's agency_id
        $agency_id = $this->getUserAgencyId();
        error_log('Agency ID: ' . ($agency_id ?? 'NULL'));

        if (!$agency_id) {
            error_log('No agency_id - blocking all results');
            // No agency assigned - block all results
            $where_conditions[] = '1=0';
            return $where_conditions;
        }

        // Add agency filter to WHERE conditions
        global $wpdb;
        error_log('Adding agency_id WHERE condition: ' . $agency_id);

        $where_conditions[] = sprintf(
            "c.id IN (
                SELECT DISTINCT customer_id
                FROM {$wpdb->prefix}app_customer_branches
                WHERE agency_id = %d
            )",
            intval($agency_id)
        );

        error_log('=== END AgencyCustomerFilter::filter_where_conditions ===');
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
     * Get agency_id from user (agency admin OR employee)
     *
     * Logic (matches WP_Agency_WP_Customer_Integration::get_user_agency_id):
     * 1. Check if user is agency admin (wp_app_agencies.user_id)
     * 2. If not, check if user is agency employee (wp_app_agency_employees.agency_id)
     *
     * @return int|null Agency ID or null if not found
     */
    private function getUserAgencyId() {
        global $wpdb;
        static $cache = [];

        $user_id = get_current_user_id();

        error_log('getUserAgencyId() - User ID: ' . $user_id);

        // Check cache
        if (isset($cache[$user_id])) {
            error_log('getUserAgencyId() - Cache hit: ' . $cache[$user_id]);
            return $cache[$user_id];
        }

        // Check 1: Is user an agency admin?
        // Use sprintf + intval to avoid single placeholder wpdb::prepare Notice
        $agency_id = $wpdb->get_var(sprintf("
            SELECT id
            FROM {$wpdb->prefix}app_agencies
            WHERE user_id = %d
            LIMIT 1
        ", intval($user_id)));

        error_log('getUserAgencyId() - Check agency admin: ' . ($agency_id ?? 'NULL'));

        if ($agency_id) {
            $cache[$user_id] = (int) $agency_id;
            error_log('getUserAgencyId() - Found as admin, agency_id: ' . $cache[$user_id]);
            return $cache[$user_id];
        }

        // Check 2: Is user an agency employee?
        // Use sprintf + intval to avoid single placeholder wpdb::prepare Notice
        $agency_id = $wpdb->get_var(sprintf("
            SELECT agency_id
            FROM {$wpdb->prefix}app_agency_employees
            WHERE user_id = %d
            LIMIT 1
        ", intval($user_id)));

        error_log('getUserAgencyId() - Check agency employee: ' . ($agency_id ?? 'NULL'));

        $cache[$user_id] = $agency_id ? (int) $agency_id : null;
        error_log('getUserAgencyId() - Final result: ' . ($cache[$user_id] ?? 'NULL'));
        return $cache[$user_id];
    }

    /**
     * Get filter statistics (for debugging/logging)
     *
     * @return array Statistics
     */
    public function getFilterStats() {
        $current_user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');
        $has_agency_role = $this->hasAgencyRole();
        $agency_id = $this->getUserAgencyId();

        return [
            'user_id' => $current_user_id,
            'is_admin' => $is_admin,
            'has_agency_role' => $has_agency_role,
            'agency_id' => $agency_id,
            'filter_active' => !$is_admin && $has_agency_role && $agency_id !== null
        ];
    }
}
