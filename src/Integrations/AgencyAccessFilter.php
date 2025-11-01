<?php
/**
 * Agency Access Filter
 *
 * @package     WP_Customer
 * @subpackage  Integrations
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Integrations/AgencyAccessFilter.php
 *
 * Description: Cross-plugin integration untuk filter wp-agency data.
 *              Hooks into wp-agency DataTable filters.
 *              Filters agencies berdasarkan user's accessible branches.
 *              Implements verified SQL query from TODO-2071.
 *
 * Integration Pattern:
 * - wp-agency provides filter hooks
 * - wp-customer hooks in to add filtering
 * - No tight coupling between plugins
 * - wp-agency works standalone without wp-customer
 *
 * Dependencies:
 * - wp-agency plugin (provides hooks)
 * - wp_app_customer_branches table (bridge with agency_id)
 * - wp_app_customer_employees table (user context)
 *
 * Changelog:
 * 1.1.0 - 2025-11-01 (TODO-2183 Follow-up: Division Filtering + Critical Hook Fix)
 * - CRITICAL FIX: Changed hook name from wpapp_datatable_app_agencies_where to wpapp_datatable_agencies_where
 * - Reason: wp-app-core's get_filter_hook() removes app_ prefix from table names
 * - ADDED: Division-based filtering for customer_branch_admin role
 * - Branch admin: filters by single agency from their branch (stricter access)
 * - Customer admin: filters by accessible agencies (broader access)
 * - Auto-detects user role based on branch_id and division_id presence
 *
 * 1.0.1 - 2025-10-31 (TODO-2183)
 * - Fixed: Changed table alias from 'a' to 'p' to match wp-agency AgencyModel
 * - Fixed: filter_agencies_by_customer() now uses correct alias 'p.id'
 * - Fixed: filter_stats_by_customer() now uses correct alias 'p.id'
 * - Resolves customer_admin not seeing Disnaker list (Task-2176)
 *
 * 1.0.0 - 2025-10-24
 * - Initial implementation (TODO-2071 Phase 6)
 * - Hook into wpapp_datatable_app_agencies_where
 * - Hook into wp_agency permission filters
 * - Implement verified SQL query for filtering
 * - Support Customer Admin & Branch Admin roles
 */

namespace WPCustomer\Integrations;

class AgencyAccessFilter {

    /**
     * Constructor
     * Register filter hooks
     */
    public function __construct() {
        // Hook into wp-agency DataTable WHERE filter
        // Note: Hook name is 'agencies' not 'app_agencies' (app_ prefix removed by wp-app-core)
        add_filter('wpapp_datatable_agencies_where', [$this, 'filter_agencies_by_customer'], 10, 3);

        // Hook into wp-agency permission filters
        add_filter('wp_agency_can_view_agency', [$this, 'check_customer_agency_permission'], 10, 2);
        add_filter('wp_agency_can_access_agencies_page', [$this, 'check_customer_access_page'], 10, 2);

        // Hook into wp-agency stats filters
        add_filter('wp_agency_stats_where', [$this, 'filter_stats_by_customer'], 10, 2); // AJAX stats
        add_filter('wpapp_agency_statistics_where', [$this, 'filter_statistics_by_customer'], 10, 2); // Initial page load stats
    }

    /**
     * Filter agencies based on customer's branches
     *
     * This is the KEY filter that implements the verified SQL query!
     *
     * Hooked to: wpapp_datatable_agencies_where (NOT app_agencies - prefix removed by wp-app-core)
     *
     * SQL Pattern (Verified 2025-10-23):
     * User → CustomerEmployee → Branch → Agency
     *
     * Access Logic (Updated 2025-11-01 with division support):
     * - Branch Admin (has branch_id + division_id): Single agency from their branch (strictest)
     * - Customer Admin (no branch_id or division_id): All agencies via branches (broader)
     * - Non-customer employee: No filtering (Platform/Agency user)
     *
     * @param array $where Existing WHERE conditions
     * @param array $request DataTable request data
     * @param object $model DataTableModel instance
     * @return array Modified WHERE conditions
     */
    public function filter_agencies_by_customer($where, $request, $model) {
        global $wpdb;

        $user_id = get_current_user_id();

        error_log('[AgencyAccessFilter] filter_agencies_by_customer CALLED for user_id: ' . $user_id);
        error_log('[AgencyAccessFilter] Incoming WHERE conditions: ' . print_r($where, true));

        // Check if user is customer employee - get branch info for division filtering
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT ce.*, b.division_id, b.agency_id as user_agency_id
             FROM {$wpdb->prefix}app_customer_employees ce
             LEFT JOIN {$wpdb->prefix}app_customer_branches b ON ce.branch_id = b.id
             WHERE ce.user_id = %d",
            $user_id
        ));

        // Not a customer employee, no filtering needed
        if (!$employee) {
            error_log('[AgencyAccessFilter] User is NOT a customer employee, skipping filter');
            return $where;
        }

        error_log('[AgencyAccessFilter] User IS customer employee (ID: ' . $employee->id . ')');

        // Check if user is branch admin (has specific branch_id and division_id)
        $is_branch_admin = !empty($employee->branch_id) && !empty($employee->division_id);

        if ($is_branch_admin) {
            // Branch admin: filter by the single agency from their branch
            error_log('[AgencyAccessFilter] User is BRANCH ADMIN - filtering by agency_id: ' . $employee->user_agency_id);

            if (!empty($employee->user_agency_id)) {
                // AgencyDataTableModel always uses 'a' as table alias
                $where[] = $wpdb->prepare('a.id = %d', $employee->user_agency_id);
                error_log('[AgencyAccessFilter] Added WHERE: a.id = ' . $employee->user_agency_id);
            } else {
                // Branch has no agency, block all
                $where[] = "1=0";
                error_log('[AgencyAccessFilter] Branch has no agency, blocking all');
            }
        } else {
            // Customer admin: filter by accessible agencies (existing logic)
            error_log('[AgencyAccessFilter] User is CUSTOMER ADMIN - filtering by accessible agencies');

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
                error_log('[AgencyAccessFilter] NO accessible agencies found, blocking all');
                $where[] = "1=0";
            } else {
                // Filter to accessible agencies only
                // AgencyDataTableModel always uses 'a' as table alias
                $ids = implode(',', array_map('intval', $accessible_agencies));
                $where[] = "a.id IN ({$ids})";

                error_log(sprintf(
                    '[AgencyAccessFilter] User %d has access to %d agencies: [%s]',
                    $user_id,
                    count($accessible_agencies),
                    implode(', ', $accessible_agencies)
                ));
                error_log('[AgencyAccessFilter] Added WHERE: a.id IN (' . $ids . ')');
            }
        }

        error_log('[AgencyAccessFilter] Final WHERE conditions: ' . print_r($where, true));

        return $where;
    }

    /**
     * Check if customer employee can view specific agency
     *
     * Hooked to: wp_agency_can_view_agency
     *
     * @param bool $can_view Current permission status
     * @param int $agency_id Agency ID to check
     * @return bool Whether user can view this agency
     */
    public function check_customer_agency_permission($can_view, $agency_id) {
        global $wpdb;

        $user_id = get_current_user_id();

        // Check if user is customer employee
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}app_customer_employees WHERE user_id = %d",
            $user_id
        ));

        // Not a customer employee, return original permission
        if (!$employee) {
            return $can_view;
        }

        // Check if user has access to this agency via branches
        $has_access = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$wpdb->prefix}app_customer_branches b
             INNER JOIN {$wpdb->prefix}app_customer_employees ce ON b.id = ce.branch_id
             WHERE ce.user_id = %d
             AND b.agency_id = %d",
            $user_id,
            $agency_id
        ));

        if ($has_access > 0) {
            return true; // Customer employee can view this agency
        }

        // No access via branches, return original permission
        return $can_view;
    }

    /**
     * Check if customer employee can access agencies page
     *
     * Hooked to: wp_agency_can_access_agencies_page
     *
     * @param bool $can_access Current permission status
     * @param int $user_id User ID to check
     * @return bool Whether user can access agencies page
     */
    public function check_customer_access_page($can_access, $user_id) {
        global $wpdb;

        // Check if user is customer employee
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}app_customer_employees WHERE user_id = %d",
            $user_id
        ));

        // Not a customer employee, return original permission
        if (!$employee) {
            return $can_access;
        }

        // Check if user has any agencies via branches
        $has_agencies = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT b.agency_id)
             FROM {$wpdb->prefix}app_customer_branches b
             INNER JOIN {$wpdb->prefix}app_customer_employees ce ON b.id = ce.branch_id
             WHERE ce.user_id = %d
             AND b.agency_id IS NOT NULL",
            $user_id
        ));

        if ($has_agencies > 0) {
            return true; // Customer employee can access page
        }

        // No agencies, return original permission
        return $can_access;
    }

    /**
     * Filter statistics based on customer's branches
     *
     * Hooked to: wp_agency_stats_where
     *
     * Applies same filtering logic as DataTable (with division support)
     *
     * @param array $where Existing WHERE conditions
     * @param int $user_id Current user ID
     * @return array Modified WHERE conditions
     */
    public function filter_stats_by_customer($where, $user_id) {
        global $wpdb;

        // Check if user is customer employee - get branch info for division filtering
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT ce.*, b.division_id, b.agency_id as user_agency_id
             FROM {$wpdb->prefix}app_customer_employees ce
             LEFT JOIN {$wpdb->prefix}app_customer_branches b ON ce.branch_id = b.id
             WHERE ce.user_id = %d",
            $user_id
        ));

        // Not a customer employee, no filtering
        if (!$employee) {
            return $where;
        }

        // Check if user is branch admin (has specific branch_id and division_id)
        $is_branch_admin = !empty($employee->branch_id) && !empty($employee->division_id);

        if ($is_branch_admin) {
            // Branch admin: filter by the single agency from their branch
            if (!empty($employee->user_agency_id)) {
                // Stats queries always use 'a' as table alias
                $where[] = $wpdb->prepare('a.id = %d', $employee->user_agency_id);
            } else {
                // Branch has no agency, block all
                $where[] = "1=0";
            }
        } else {
            // Customer admin: filter by accessible agencies
            $accessible_agencies = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT b.agency_id
                 FROM {$wpdb->prefix}app_customer_branches b
                 INNER JOIN {$wpdb->prefix}app_customer_employees ce ON b.id = ce.branch_id
                 WHERE ce.user_id = %d
                 AND b.agency_id IS NOT NULL",
                $user_id
            ));

            if (empty($accessible_agencies)) {
                // No accessible agencies
                $where[] = "1=0";
            } else {
                // Filter to accessible agencies
                // Stats queries always use 'a' as table alias
                $ids = implode(',', array_map('intval', $accessible_agencies));
                $where[] = "a.id IN ({$ids})";
            }
        }

        return $where;
    }

    /**
     * Filter statistics for initial page load
     *
     * Hooked to: wpapp_agency_statistics_where
     *
     * This filter is used during initial page load (server-side PHP rendering)
     * Different from wp_agency_stats_where which is used for AJAX updates
     *
     * @param array $where WHERE conditions array
     * @param string $context Statistics context ('total', 'active', 'inactive')
     * @return array Modified WHERE conditions
     */
    public function filter_statistics_by_customer($where, $context) {
        global $wpdb;

        $user_id = get_current_user_id();

        // Check if user is customer employee - get branch info for division filtering
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT ce.*, b.division_id, b.agency_id as user_agency_id
             FROM {$wpdb->prefix}app_customer_employees ce
             LEFT JOIN {$wpdb->prefix}app_customer_branches b ON ce.branch_id = b.id
             WHERE ce.user_id = %d",
            $user_id
        ));

        // Not a customer employee, no filtering
        if (!$employee) {
            return $where;
        }

        // Check if user is branch admin (has specific branch_id and division_id)
        $is_branch_admin = !empty($employee->branch_id) && !empty($employee->division_id);

        if ($is_branch_admin) {
            // Branch admin: filter by the single agency from their branch
            if (!empty($employee->user_agency_id)) {
                // Add agency_id filter (use indexed array with raw SQL)
                $where[] = $wpdb->prepare('id = %d', $employee->user_agency_id);
            } else {
                // Branch has no agency, block all
                $where[] = "1=0";
            }
        } else {
            // Customer admin: filter by accessible agencies
            $accessible_agencies = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT b.agency_id
                 FROM {$wpdb->prefix}app_customer_branches b
                 INNER JOIN {$wpdb->prefix}app_customer_employees ce ON b.id = ce.branch_id
                 WHERE ce.user_id = %d
                 AND b.agency_id IS NOT NULL",
                $user_id
            ));

            if (empty($accessible_agencies)) {
                // No accessible agencies, block all
                $where[] = "1=0";
            } else {
                // Filter to accessible agencies (use indexed array with raw SQL)
                $ids = implode(',', array_map('intval', $accessible_agencies));
                $where[] = "id IN ({$ids})";
            }
        }

        return $where;
    }
}
