<?php
/**
 * Branch Access Filter (for wp-agency New Companies Tab)
 *
 * @package     WP_Customer
 * @subpackage  Integrations
 * @version     1.4.0
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
 * - ALL customer roles (customer_admin, customer_branch_admin, customer_employee):
 *   Filter by customer_id only - see all branches in their customer
 * - Division filtering is NOT for customer users (division is for agency users)
 * - User → CustomerEmployee → Customer → Branches
 * - If user not a customer employee, no filtering applied (agency/platform users)
 *
 * Dependencies:
 * - wp-agency plugin (provides hooks via NewCompanyDataTableModel)
 * - wp_app_customer_branches table (target data)
 * - wp_app_customer_employees table (user context)
 *
 * Changelog:
 * 1.4.0 - 2025-12-25
 * - Added: Entity relation config for 'customer_employees' entity
 * - Fix: Register customer_employees with EntityRelationModel
 * - Support: Employee DataTable access filtering
 *
 * 1.3.0 - 2025-11-02 (TODO-2190 Fix)
 * - CRITICAL SIMPLIFICATION: Removed division_id filtering for customer roles
 * - All customer roles now filter by customer_id only (unified access)
 * - Removed: WordPress role checking (not needed - all customer roles have same access)
 * - Removed: JOIN to branches table for division_id (not used by customer roles)
 * - Division filtering is for agency users, not customer users
 * - Simplified query: only SELECT customer_id, branch_id from employees table
 *
 * 1.2.0 - 2025-11-02 (TODO-2190)
 * - CRITICAL FIX: Updated table alias from 'b' to dynamic alias via model->get_table_alias()
 * - Now supports flexible table aliasing (cb, b, or any future alias)
 * - Fallback to 'cb' if model doesn't have get_table_alias() method
 * - Fixes "Unknown column 'b.customer_id'" SQL error
 *
 * 1.1.0 - 2025-11-01 (TODO-2183 Follow-up: Division Filtering) [REVERTED in 1.3.0]
 * - ADDED: Division-based filtering for customer_branch_admin role
 * - NOTE: This was later removed in 1.3.0 - division filtering is for agency, not customer
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

        // Register entity relation configs for customer_branches and company
        add_filter('wp_customer_entity_relation_configs', [$this, 'register_entity_configs'], 10, 1);
    }

    /**
     * Register entity relation configurations
     *
     * Registers 'customer_branches' and 'company' entity configs for EntityRelationModel.
     * Both use the same branches table but different entity types for access control.
     *
     * @param array $configs Existing entity configs
     * @return array Modified configs
     *
     * @since 1.4.0
     */
    public function register_entity_configs(array $configs): array {
        // Register 'customer_branches' entity config
        // Used by BranchDataTableModel (branch management within customer)
        $configs['customer_branches'] = [
            'bridge_table' => 'app_customer_branches',
            'entity_column' => 'id',
            'customer_column' => 'customer_id',
            'access_filter' => true,
            'cache_group' => 'wp_customer_branch_relations',
            'cache_ttl' => 3600
        ];

        // Register 'company' entity config
        // Used by CompanyDataTableModel (company dashboard view)
        $configs['company'] = [
            'bridge_table' => 'app_customer_branches',
            'entity_column' => 'id',
            'customer_column' => 'customer_id',
            'access_filter' => true,
            'cache_group' => 'wp_customer_company_relations',
            'cache_ttl' => 3600
        ];

        // Register 'customer_employees' entity config
        // Used by EmployeeDataTableModel (employee listing)
        $configs['customer_employees'] = [
            'bridge_table' => 'app_customer_employees',
            'entity_column' => 'id',
            'customer_column' => 'customer_id',
            'access_filter' => true,
            'cache_group' => 'wp_customer_employee_relations',
            'cache_ttl' => 3600
        ];

        return $configs;
    }

    /**
     * Filter customer branches based on customer employee's access (Simplified - Phase 5)
     *
     * Uses EntityRelationModel for consistent access control.
     * Returns accessible branch IDs for current user.
     *
     * Hooked to: wpapp_datatable_customer_branches_where
     *
     * @param array $where Existing WHERE conditions
     * @param array $request DataTable request data
     * @param object $model NewCompanyDataTableModel instance
     * @return array Modified WHERE conditions
     *
     * @since 1.5.0 Simplified to use EntityRelationModel
     */
    public function filter_branches_by_customer($where, $request, $model) {
        // Platform staff bypass
        if (current_user_can('manage_options')) {
            return $where;
        }

        // Get table alias from model (supports flexible aliasing: cb, b, etc)
        $alias = method_exists($model, 'get_table_alias') ? $model->get_table_alias() : 'cb';

        // Use EntityRelationModel
        $entity_model = new \WPCustomer\Models\Relation\EntityRelationModel();
        $accessible_ids = $entity_model->get_accessible_entity_ids('customer_branches');

        // Empty = see all (should not happen for customer users)
        if (empty($accessible_ids)) {
            return $where;
        }

        // [0] = block all
        if ($accessible_ids === [0]) {
            $where[] = '1=0';
            return $where;
        }

        // Apply filter by branch IDs
        $ids = implode(',', array_map('intval', $accessible_ids));
        $where[] = "{$alias}.id IN ({$ids})";

        return $where;
    }
}
