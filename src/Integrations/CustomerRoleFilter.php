<?php
/**
 * Customer Role-Based Filter (QueryBuilder-only)
 *
 * @package     WP_Customer
 * @subpackage  Integrations
 * @version     2.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Integrations/CustomerRoleFilter.php
 *
 * Description: Role-based filtering untuk Customer DataTable menggunakan QueryBuilder.
 *              Uses EntityRelationModel for consistent access control.
 *
 * Active Hooks:
 * - wpapp_datatable_customers_count_query (QueryBuilder - for statistics)
 * - wpapp_datatable_customers_query (QueryBuilder - for data)
 *
 * Dependencies:
 * - EntityRelationModel (single source of truth)
 * - includes/class-role-manager.php
 * - WPQB\QueryBuilder
 *
 * Changelog:
 * 2.1.0 - 2025-12-26 (Phase 4 Consolidation)
 * - DELETED deprecated filter_customers_by_role() method (not called anywhere)
 * - DELETED old hook wpapp_datatable_customers_where (deprecated since Nov 2025)
 * - Simplified to use EntityRelationModel (removes duplicate SQL queries)
 * - QueryBuilder-only (old array-based hooks removed)
 * - Cleaner, DRY code
 *
 * 2.0.0 - 2025-11-05
 * - Added QueryBuilder hook support
 * - Maintained old hooks for backward compatibility
 *
 * 1.0.3 - 2025-11-02
 * - Single source of truth - customer_employees table only
 *
 * 1.0.0 - 2025-11-01
 * - Initial implementation
 */

namespace WPCustomer\Integrations;

class CustomerRoleFilter {

    /**
     * Constructor
     * Register filter hooks
     */
    public function __construct() {
        // QueryBuilder-based hooks (actively used by CustomerDataTableModel)
        add_filter('wpapp_datatable_customers_count_query', [$this, 'filter_count_query'], 10, 2);
        add_filter('wpapp_datatable_customers_query', [$this, 'filter_data_query'], 10, 2);
    }

    /**
     * Filter count query with QueryBuilder (Simplified - Phase 4)
     *
     * Uses EntityRelationModel for consistent access control.
     *
     * Hooked to: wpapp_datatable_customers_count_query
     *
     * @param \WPQB\QueryBuilder $query QueryBuilder instance
     * @param array $params Request parameters
     * @return \WPQB\QueryBuilder Modified query
     *
     * @since 2.1.0 Simplified to use EntityRelationModel
     */
    public function filter_count_query($query, $params) {
        // Check if admin (no filtering)
        if (current_user_can('manage_options')) {
            return $query;
        }

        // Check if user has customer plugin role
        if (!$this->hasCustomerRole()) {
            return $query;
        }

        // Use EntityRelationModel
        $entity_model = new \WPCustomer\Models\Relation\EntityRelationModel();
        $customer_ids = $entity_model->get_accessible_entity_ids('customer');

        if (empty($customer_ids)) {
            return $query; // See all
        }

        if ($customer_ids === [0]) {
            $query->whereRaw('1=0');
            return $query;
        }

        // Apply filter
        $query->whereIn('c.id', $customer_ids);

        return $query;
    }

    /**
     * Filter data query with QueryBuilder (NEW - Type-safe)
     *
     * Hooked to: wpapp_datatable_customers_query
     *
     * Same logic as filter_count_query but for main data queries.
     *
     * @param \WPQB\QueryBuilder $query QueryBuilder instance
     * @param array $params Request parameters
     * @return \WPQB\QueryBuilder Modified query
     */
    public function filter_data_query($query, $params) {
        // Use same logic as count query
        return $this->filter_count_query($query, $params);
    }

    // ========================================
    // SHARED HELPER METHODS
    // ========================================

    /**
     * Check if current user has customer plugin role
     *
     * @return bool True if user has customer role
     */
    private function hasCustomerRole() {
        require_once WP_CUSTOMER_PATH . 'includes/class-role-manager.php';

        $user = wp_get_current_user();
        $user_roles = (array) $user->roles;

        foreach ($user_roles as $role) {
            if (\WP_Customer_Role_Manager::isPluginRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get filter statistics (for debugging/logging - Phase 4 Updated)
     *
     * Uses EntityRelationModel for consistent data access.
     *
     * @return array Statistics
     *
     * @since 2.1.0 Updated to use EntityRelationModel
     */
    public function getFilterStats() {
        $current_user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');
        $has_customer_role = $this->hasCustomerRole();

        // Use EntityRelationModel
        $entity_model = new \WPCustomer\Models\Relation\EntityRelationModel();
        $accessible_ids = $entity_model->get_accessible_entity_ids('customer');

        return [
            'user_id' => $current_user_id,
            'is_admin' => $is_admin,
            'has_customer_role' => $has_customer_role,
            'accessible_customer_ids' => $accessible_ids,
            'accessible_count' => count($accessible_ids),
            'filtering_applied' => !$is_admin && $has_customer_role
        ];
    }
}
