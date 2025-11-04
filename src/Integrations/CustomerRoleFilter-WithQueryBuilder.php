<?php
/**
 * Customer Role-Based Filter (WITH QueryBuilder Support)
 *
 * @package     WP_Customer
 * @subpackage  Integrations
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Integrations/CustomerRoleFilter-WithQueryBuilder.php
 *
 * Description: ENHANCED role-based filtering untuk Customer DataTable.
 *              Supports BOTH old array-based hooks AND new QueryBuilder hooks.
 *              Maintains backward compatibility during migration period.
 *
 * Key Changes from v1.0.3:
 * - ✅ Added QueryBuilder hook support (wpapp_datatable_customers_count_query)
 * - ✅ Added QueryBuilder data hook (wpapp_datatable_customers_query)
 * - ✅ Maintained backward compatibility (old hook still works)
 * - ✅ Type-safe filtering with QueryBuilder
 * - ✅ Cleaner code, easier to maintain
 *
 * Migration Strategy:
 * - Phase 1: Add new hooks alongside old hooks (THIS VERSION)
 * - Phase 2: Test thoroughly with QueryBuilder-based DataTable
 * - Phase 3: Remove old hook after full migration verified
 *
 * Dependencies:
 * - wp_app_customer_employees table
 * - includes/class-role-manager.php
 * - WPQB\QueryBuilder (optional, for new hooks)
 *
 * Changelog:
 * 2.0.0 - 2025-11-05
 * - Added QueryBuilder hook support
 * - Backward compatible with array-based hooks
 * - Cleaner, type-safe filtering
 * - Prepared for full QueryBuilder migration
 *
 * 1.0.3 - 2025-11-02
 * - Single source of truth - customer_employees table only
 * - Removed UNION query
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
        // ========================================
        // OLD HOOKS (Array-based) - Keep for backward compatibility
        // ========================================
        add_filter('wpapp_datatable_customers_where', [$this, 'filter_customers_by_role'], 10, 3);

        // ========================================
        // NEW HOOKS (QueryBuilder-based) - For refactored DataTable models
        // ========================================
        add_filter('wpapp_datatable_customers_count_query', [$this, 'filter_count_query'], 10, 2);
        add_filter('wpapp_datatable_customers_query', [$this, 'filter_data_query'], 10, 2);
    }

    // ========================================
    // OLD METHOD (Array-based) - Backward Compatibility
    // ========================================

    /**
     * Filter customers based on user's role (OLD - Array-based)
     *
     * ⚠️ DEPRECATED: Will be removed after full migration to QueryBuilder
     *
     * Hooked to: wpapp_datatable_customers_where
     *
     * @param array $where Existing WHERE conditions
     * @param array $request DataTable request data
     * @param object $model DataTableModel instance
     * @return array Modified WHERE conditions
     */
    public function filter_customers_by_role($where, $request, $model) {
        global $wpdb;

        // Check if admin (no filtering)
        if (current_user_can('manage_options')) {
            return $where;
        }

        // Check if user has customer plugin role
        if (!$this->hasCustomerRole()) {
            // Not a customer plugin user - no filtering needed
            return $where;
        }

        // Get accessible customer IDs
        $customer_ids = $this->getAccessibleCustomerIds();

        if (!empty($customer_ids)) {
            // ❌ OLD: Manual SQL string building
            $customer_ids_string = implode(',', array_map('intval', $customer_ids));
            $where[] = "c.id IN ({$customer_ids_string})";
        } else {
            // Block all results
            $where[] = '1=0';
        }

        return $where;
    }

    // ========================================
    // NEW METHODS (QueryBuilder-based)
    // ========================================

    /**
     * Filter count query with QueryBuilder (NEW - Type-safe)
     *
     * Hooked to: wpapp_datatable_customers_count_query
     *
     * @param \WPQB\QueryBuilder $query QueryBuilder instance
     * @param array $params Request parameters
     * @return \WPQB\QueryBuilder Modified query
     */
    public function filter_count_query($query, $params) {
        // Check if admin (no filtering)
        if (current_user_can('manage_options')) {
            return $query;
        }

        // Check if user has customer plugin role
        if (!$this->hasCustomerRole()) {
            // Not a customer plugin user - no filtering needed
            return $query;
        }

        // Get accessible customer IDs
        $customer_ids = $this->getAccessibleCustomerIds();

        if (!empty($customer_ids)) {
            // ✅ NEW: Type-safe QueryBuilder method
            $query->whereIn('c.id', $customer_ids);
        } else {
            // Block all results
            $query->whereRaw('1=0');
        }

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
     * Get customer IDs accessible to current user
     *
     * Single source of truth: customer_employees table
     *
     * @return array Customer IDs
     */
    private function getAccessibleCustomerIds() {
        global $wpdb;

        $current_user_id = get_current_user_id();

        // Query customer_employees table
        $customer_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT customer_id
             FROM {$wpdb->prefix}app_customer_employees
             WHERE user_id = %d
             AND status = 'active'",
            $current_user_id
        ));

        return array_map('intval', $customer_ids);
    }

    /**
     * Get filter statistics (for debugging/logging)
     *
     * @return array Statistics
     */
    public function getFilterStats() {
        $current_user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');
        $has_customer_role = $this->hasCustomerRole();
        $accessible_ids = $this->getAccessibleCustomerIds();

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
