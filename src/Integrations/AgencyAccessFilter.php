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
        // Register entity relation config for 'agency'
        add_filter('wp_customer_entity_relation_configs', [$this, 'register_entity_configs'], 10, 1);

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
     * Register entity relation configurations
     *
     * Registers 'agency' entity config for EntityRelationModel.
     * Bridge table: app_customer_branches (customer_id → agency_id)
     *
     * @param array $configs Existing entity configs
     * @return array Modified configs
     *
     * @since 1.2.0 (Phase 1 Consolidation)
     */
    public function register_entity_configs(array $configs): array {
        // Register 'agency' entity config
        // Used by AgencyAccessFilter and EmployeeAccessFilter
        $configs['agency'] = [
            'bridge_table' => 'app_customer_branches',
            'entity_column' => 'agency_id',
            'customer_column' => 'customer_id',
            'access_filter' => true,
            'cache_group' => 'wp_customer_agency_relations',
            'cache_ttl' => 3600
        ];

        return $configs;
    }

    /**
     * Filter agencies based on customer's branches (Simplified - Phase 2)
     *
     * Uses EntityRelationModel for consistent access control.
     * Supports both customer_admin (all agencies) and customer_branch_admin (single agency).
     *
     * Hooked to: wpapp_datatable_agencies_where
     *
     * @param array $where Existing WHERE conditions
     * @param array $request DataTable request data
     * @param object $model DataTableModel instance
     * @return array Modified WHERE conditions
     *
     * @since 1.2.0 Simplified to use EntityRelationModel
     */
    public function filter_agencies_by_customer($where, $request, $model) {
        global $wpdb;

        // Platform staff bypass
        if (current_user_can('manage_options')) {
            return $where;
        }

        // Check if user is customer employee (for branch admin detection)
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT ce.*, b.division_id, b.agency_id as user_agency_id
             FROM {$wpdb->prefix}app_customer_employees ce
             LEFT JOIN {$wpdb->prefix}app_customer_branches b ON ce.branch_id = b.id
             WHERE ce.user_id = %d",
            get_current_user_id()
        ));

        // Not a customer employee, no filtering
        if (!$employee) {
            return $where;
        }

        // Check if branch admin (has division_id and NOT customer_admin)
        $user = wp_get_current_user();
        $is_customer_admin = in_array('customer_admin', $user->roles);
        $is_branch_admin = !empty($employee->division_id) && !$is_customer_admin;

        if ($is_branch_admin) {
            // Branch admin: single agency from their branch
            if (!empty($employee->user_agency_id)) {
                $where[] = $wpdb->prepare('a.id = %d', $employee->user_agency_id);
            } else {
                $where[] = '1=0'; // No agency, block all
            }
            return $where;
        }

        // Customer admin: use EntityRelationModel
        $entity_model = new \WPCustomer\Models\Relation\EntityRelationModel();
        $accessible_ids = $entity_model->get_accessible_entity_ids('agency');

        // Empty = see all (should not happen for customer users)
        if (empty($accessible_ids)) {
            return $where;
        }

        // [0] = block all
        if ($accessible_ids === [0]) {
            $where[] = '1=0';
            return $where;
        }

        // Apply agency filter
        $ids = implode(',', array_map('intval', $accessible_ids));
        $where[] = "a.id IN ({$ids})";

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
     * Filter statistics based on customer's branches (Simplified - Phase 2)
     *
     * Uses same logic as filter_agencies_by_customer but for stats queries.
     * Stats queries use 'a' as table alias.
     *
     * Hooked to: wp_agency_stats_where
     *
     * @param array $where Existing WHERE conditions
     * @param int $user_id Current user ID
     * @return array Modified WHERE conditions
     *
     * @since 1.2.0 Simplified to use EntityRelationModel
     */
    public function filter_stats_by_customer($where, $user_id) {
        // Delegate to main filter method, then adjust for stats table alias
        return $this->apply_agency_filter($where, 'a', $user_id);
    }

    /**
     * Filter statistics for initial page load (Simplified - Phase 2)
     *
     * Uses same logic as filter_agencies_by_customer but without table alias.
     * Statistics queries use column name directly (id instead of a.id).
     *
     * Hooked to: wpapp_agency_statistics_where
     *
     * @param array $where WHERE conditions array
     * @param string $context Statistics context ('total', 'active', 'inactive')
     * @return array Modified WHERE conditions
     *
     * @since 1.2.0 Simplified to use EntityRelationModel
     */
    public function filter_statistics_by_customer($where, $context) {
        // Delegate to main filter method, no table alias for statistics
        return $this->apply_agency_filter($where, '', get_current_user_id());
    }

    /**
     * Apply agency filter (Shared helper method - Phase 2)
     *
     * Centralizes filtering logic for all hooks.
     * Supports different table aliases for different query contexts.
     *
     * @param array $where Existing WHERE conditions
     * @param string $table_alias Table alias ('a', '', etc)
     * @param int $user_id User ID
     * @return array Modified WHERE conditions
     *
     * @since 1.2.0
     */
    private function apply_agency_filter(array $where, string $table_alias, int $user_id): array {
        global $wpdb;

        // Platform staff bypass
        if (current_user_can('manage_options')) {
            return $where;
        }

        // Check if customer employee
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT ce.*, b.division_id, b.agency_id as user_agency_id
             FROM {$wpdb->prefix}app_customer_employees ce
             LEFT JOIN {$wpdb->prefix}app_customer_branches b ON ce.branch_id = b.id
             WHERE ce.user_id = %d",
            $user_id
        ));

        if (!$employee) {
            return $where; // Not customer employee
        }

        // Detect branch admin
        $user = get_userdata($user_id);
        $is_customer_admin = $user && in_array('customer_admin', (array) $user->roles);
        $is_branch_admin = !empty($employee->division_id) && !$is_customer_admin;

        // Prepare column name with optional alias
        $id_column = $table_alias ? "{$table_alias}.id" : 'id';

        if ($is_branch_admin) {
            // Branch admin: single agency
            if (!empty($employee->user_agency_id)) {
                $where[] = $wpdb->prepare("{$id_column} = %d", $employee->user_agency_id);
            } else {
                $where[] = '1=0';
            }
            return $where;
        }

        // Customer admin: use EntityRelationModel
        $entity_model = new \WPCustomer\Models\Relation\EntityRelationModel();
        $accessible_ids = $entity_model->get_accessible_entity_ids('agency', $user_id);

        if (empty($accessible_ids)) {
            return $where; // See all (should not happen)
        }

        if ($accessible_ids === [0]) {
            $where[] = '1=0';
            return $where;
        }

        // Apply filter
        $ids = implode(',', array_map('intval', $accessible_ids));
        $where[] = "{$id_column} IN ({$ids})";

        return $where;
    }
}
