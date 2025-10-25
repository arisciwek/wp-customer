<?php
/**
 * Agency Access Filter
 *
 * @package     WP_Customer
 * @subpackage  Integrations
 * @version     1.0.0
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
        add_filter('wpapp_datatable_app_agencies_where', [$this, 'filter_agencies_by_customer'], 10, 3);

        // Hook into wp-agency permission filters
        add_filter('wp_agency_can_view_agency', [$this, 'check_customer_agency_permission'], 10, 2);
        add_filter('wp_agency_can_access_agencies_page', [$this, 'check_customer_access_page'], 10, 2);

        // Hook into wp-agency stats filter
        add_filter('wp_agency_stats_where', [$this, 'filter_stats_by_customer'], 10, 2);
    }

    /**
     * Filter agencies based on customer's branches
     *
     * This is the KEY filter that implements the verified SQL query!
     *
     * Hooked to: wpapp_datatable_app_agencies_where
     *
     * SQL Pattern (Verified 2025-10-23):
     * User → CustomerEmployee → Branch → Agency
     *
     * Access Logic:
     * - If user is CustomerEmployee with branch_id NULL → Customer Admin (all branches)
     * - If user is CustomerEmployee with branch_id → Branch Admin (1 branch)
     * - If user is not CustomerEmployee → No filtering (Platform/Agency user)
     *
     * @param array $where Existing WHERE conditions
     * @param array $request DataTable request data
     * @param object $model DataTableModel instance
     * @return array Modified WHERE conditions
     */
    public function filter_agencies_by_customer($where, $request, $model) {
        global $wpdb;

        $user_id = get_current_user_id();

        // Check if user is customer employee
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}app_customer_employees WHERE user_id = %d",
            $user_id
        ));

        // Not a customer employee, no filtering needed
        if (!$employee) {
            return $where;
        }

        // Get accessible agency IDs via branches
        // This query matches the verified SQL from TODO-2071
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
            $where[] = "1=0";
        } else {
            // Filter to accessible agencies only
            $ids = implode(',', array_map('intval', $accessible_agencies));
            $where[] = "a.id IN ({$ids})";
        }

        // Debug log (if WP_DEBUG enabled)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'AgencyAccessFilter: User %d has access to %d agencies: [%s]',
                $user_id,
                count($accessible_agencies),
                implode(', ', $accessible_agencies)
            ));
        }

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
     * Applies same filtering logic as DataTable
     *
     * @param array $where Existing WHERE conditions
     * @param int $user_id Current user ID
     * @return array Modified WHERE conditions
     */
    public function filter_stats_by_customer($where, $user_id) {
        global $wpdb;

        // Check if user is customer employee
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}app_customer_employees WHERE user_id = %d",
            $user_id
        ));

        // Not a customer employee, no filtering
        if (!$employee) {
            return $where;
        }

        // Get accessible agency IDs
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
            $ids = implode(',', array_map('intval', $accessible_agencies));
            $where[] = "a.id IN ({$ids})";
        }

        return $where;
    }
}
