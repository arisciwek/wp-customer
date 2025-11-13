<?php
/**
 * Customer Role-Based Filter
 *
 * @package     WP_Customer
 * @subpackage  Integrations
 * @version     1.0.3
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Integrations/CustomerRoleFilter.php
 *
 * Description: Implements role-based filtering for Customer DataTable.
 *              Hooks into wp-app-core DataTable filters.
 *              Filters customers based on user's employee associations.
 *              Follows same pattern as AgencyAccessFilter.
 *
 * Integration Pattern:
 * - wp-app-core provides filter hooks
 * - wp-customer hooks in to add filtering
 * - No tight coupling
 * - Follows centralized DataTable pattern
 *
 * Dependencies:
 * - wp_app_customer_employees table (single source of truth for user-customer association)
 * - includes/class-role-manager.php (role detection)
 * - AutoEntityCreator (ensures all users are in customer_employees table)
 *
 * Changelog:
 * 1.0.3 - 2025-11-02
 * - SIMPLIFIED: Single source of truth - customer_employees table only (Opsi 1)
 * - Removed UNION query with customers.user_id
 * - Relies on AutoEntityCreator to ensure all users are in customer_employees table
 * - Requires province_id/regency_id validation to ensure AutoEntityCreator succeeds
 *
 * 1.0.2 - 2025-11-02
 * - Check 2 sources for customer association (customers.user_id + customer_employees.user_id)
 * - Uses UNION query to get customer_ids from both tables
 *
 * 1.0.1 - 2025-11-02
 * - Removed hardcoded platform role checks (capability-based approach is better)
 * - Non-customer plugin roles can see all customers based on their capabilities
 * - Only customer plugin roles (customer, customer_admin, etc.) are filtered by employee association
 * - Cleaner, more maintainable code without role name hardcoding
 *
 * 1.0.0 - 2025-11-01 (Review-01 from TODO-2187)
 * - Initial implementation
 * - Hook into wpapp_datatable_customers_where
 * - Filter by customer_id from wp_app_customer_employees
 * - Administrator sees all (no filter)
 * - Customer roles see only their associated customers
 */

namespace WPCustomer\Integrations;

class CustomerRoleFilter {

    /**
     * Constructor
     * Register filter hooks
     */
    public function __construct() {
        // Hook into wp-app-core DataTable WHERE filter
        // Note: Hook name is 'customers' not 'app_customers' (app_ prefix removed by wp-app-core)
        add_filter('wpapp_datatable_customers_where', [$this, 'filter_customers_by_role'], 10, 3);
    }

    /**
     * Filter customers based on user's role and associations
     *
     * Hooked to: wpapp_datatable_customers_where
     *
     * Access Logic:
     * - Administrator (manage_options): No filtering (see all)
     * - Non-customer plugin roles (platform, agency, etc.): No filtering (see all based on capabilities)
     * - Customer plugin roles: Filter by customer_id from wp_app_customer_employees
     * - Customer role with no association: Block all (1=0)
     *
     * @param array $where Existing WHERE conditions
     * @param array $request DataTable request data
     * @param object $model DataTableModel instance
     * @return array Modified WHERE conditions
     */
    public function filter_customers_by_role($where, $request, $model) {
        global $wpdb;

        $current_user_id = get_current_user_id();

        // Check if admin (no filtering for admin)
        if (current_user_can('manage_options')) {
            return $where;
        }

        // Check if user has customer plugin role
        require_once WP_CUSTOMER_PATH . 'includes/class-role-manager.php';

        $user = wp_get_current_user();
        $user_roles = (array) $user->roles;
        $has_customer_role = false;

        foreach ($user_roles as $role) {
            if (\WP_Customer_Role_Manager::isPluginRole($role)) {
                $has_customer_role = true;
                break;
            }
        }

        if (!$has_customer_role) {
            // Not a customer plugin user (could be platform user, agency user, etc.)
            // No filtering needed - they can see all customers based on their capabilities
            return $where;
        }

        // Get customer_ids where this user is an employee
        // Single source of truth: customer_employees table only
        // AutoEntityCreator ensures all users (owners + employees) are in this table
        $customer_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT customer_id
             FROM {$wpdb->prefix}app_customer_employees
             WHERE user_id = %d
             AND status = 'active'",
            $current_user_id
        ));

        if (!empty($customer_ids)) {
            // User can see only their associated customers
            // CustomerDataTableModel uses 'c' as table alias
            $customer_ids_string = implode(',', array_map('intval', $customer_ids));
            $where[] = "c.id IN ({$customer_ids_string})";
        } else {
            // User has customer role but not associated with any customer
            // Block all results
            $where[] = '1=0';
        }

        return $where;
    }
}
