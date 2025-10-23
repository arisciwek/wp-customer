<?php
/**
 * Company Validator Class
 *
 * @package     WP_Customer
 * @subpackage  Validators/Company
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Validators/Company/CompanyValidator.php
 *
 * Description: Validator untuk operasi view Company (Branch).
 *              Memastikan user memiliki permission yang tepat untuk mengakses data.
 *              Menyediakan validasi untuk view operations.
 *              Includes validasi permission dan access control.
 *
 * Permission Logic:
 * 1. Administrator (manage_options): Full access to all companies
 * 2. User with view_customer_branch_list: Can view list of companies
 * 3. User with view_own_customer_branch: Can view branches they manage
 * 4. Customer Owner (user_id in customers): Can view all branches under their customer
 * 5. Employee (in customer_employees): Can view branches they work in
 *
 * Changelog:
 * 1.0.0 - 2024-02-14
 * - Initial release
 * - Added view permission validation
 * - Added access validation
 * - Added user relation checking
 */

namespace WPCustomer\Validators\Company;

use WPCustomer\Models\Company\CompanyModel;
use WPCustomer\Models\Customer\CustomerModel;

class CompanyValidator {
    private CompanyModel $model;
    private CustomerModel $customer_model;
    private array $relationCache = [];

    public function __construct() {
        $this->model = new CompanyModel();
        $this->customer_model = new CustomerModel();
    }

    /**
     * Get access summary for current user
     *
     * @return array Summary of user's access capabilities
     */
    public function getAccessSummary(): array {
        $current_user_id = get_current_user_id();
        $user = wp_get_current_user();

        global $wpdb;

        // Get customer count
        $customer_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customers WHERE user_id = %d",
            $current_user_id
        ));

        // Get employee count
        $employee_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees 
             WHERE user_id = %d AND status = 'active'",
            $current_user_id
        ));

        // Get branch count user can access
        $accessible_branches = 0;
        if (current_user_can('manage_options')) {
            $accessible_branches = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_branches"
            );
        } elseif ($customer_count > 0) {
            $accessible_branches = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(b.id) 
                 FROM {$wpdb->prefix}app_customer_branches b
                 INNER JOIN {$wpdb->prefix}app_customers c ON b.customer_id = c.id
                 WHERE c.user_id = %d",
                $current_user_id
            ));
        } elseif ($employee_count > 0) {
            $accessible_branches = $employee_count;
        }

        return [
            'user_id' => $current_user_id,
            'username' => $user->user_login,
            'roles' => $user->roles,
            'is_admin' => current_user_can('manage_options'),
            'has_view_branch_list' => current_user_can('view_customer_branch_list'),
            'has_view_own_branch' => current_user_can('view_own_customer_branch'),
            'customer_count' => (int)$customer_count,
            'employee_count' => (int)$employee_count,
            'accessible_branches' => (int)$accessible_branches,
            'can_access_list' => $this->canAccessCompanyList()
        ];
    }

    /**
     * Clear relation cache
     *
     * @param int|null $branch_id Specific branch ID or null to clear all
     * @return void
     */
    public function clearCache(?int $branch_id = null): void {
        if ($branch_id === null) {
            $this->relationCache = [];
        } else {
            $current_user_id = get_current_user_id();
            $cache_key = "{$branch_id}_{$current_user_id}";
            unset($this->relationCache[$cache_key]);
        }
    }

    /**
     * Check if user can access company list page
     *
     * @return bool
     */
    public function canAccessCompanyPage(): bool {
        $current_user_id = get_current_user_id();

        // Check if admin
        if (current_user_can('manage_options')) {
            return true;
        }

        // Check if has view_customer_branch_list capability
        if (current_user_can('view_customer_branch_list')) {
            return true;
        }

        // Check if has view_own_customer_branch capability
        if (current_user_can('view_own_customer_branch')) {
            return true;
        }

        // Check if user owns any customers
        global $wpdb;
        $customer_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customers WHERE user_id = %d",
            $current_user_id
        ));

        if ($customer_count > 0) {
            return true;
        }

        // Check if user is employee in any branch
        $employee_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees 
             WHERE user_id = %d AND status = 'active'",
            $current_user_id
        ));

        if ($employee_count > 0) {
            return true;
        }

        // Allow plugins to add custom access rules
        return apply_filters('wp_customer_can_access_company_page', false, $current_user_id);
    }

}
