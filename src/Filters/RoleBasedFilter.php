<?php
/**
 * Role-Based Filter - BRUTAL SIMPLE VERSION
 *
 * @package     WP_Customer
 * @subpackage  Filters
 * @version     2.1.1
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Filters/RoleBasedFilter.php
 *
 * Description: Simple role-based filtering dengan DIRECT SQL.
 *              3 functions untuk 3 roles.
 *              Works untuk SEMUA datatable dari ANY plugin.
 *              Supports cross-plugin filtering (wp-customer ↔ wp-agency).
 *
 * Changelog:
 * 2.1.0 - 2025-12-27
 * - Added: Cross-plugin support for AgencyDataTableModel (wp-agency)
 * - Added: Agency filtering based on branch assignment (Option 1)
 * - Updated: detect_entity() now recognizes 'agency' entity
 * - Updated: build_where_for_customer_admin() filters agencies assigned to customer's branches
 * - Updated: build_where_for_branch_admin() filters agency assigned to specific branch
 * - customer_admin: sees agencies assigned to ANY of their branches
 * - customer_branch_admin: sees ONLY agency assigned to their branch (or nothing if no assignment)
 *
 * 2.0.0 - 2025-12-27
 * - Simple helper functions
 * - Easy to understand and maintain
 */

namespace WPCustomer\Filters;

defined('ABSPATH') || exit;

class RoleBasedFilter {

    public function __construct() {
        // Register 3 filters untuk 3 roles
        add_filter('wpapp_datatable_where_customer_admin', [$this, 'filter_customer_admin'], 10, 3);
        add_filter('wpapp_datatable_where_customer_branch_admin', [$this, 'filter_customer_branch_admin'], 10, 3);
        add_filter('wpapp_datatable_where_customer_employee', [$this, 'filter_customer_employee'], 10, 3);
    }

    /**
     * Filter for customer_admin - lihat semua data di customer mereka
     */
    public function filter_customer_admin($where, $request, $model) {
        global $wpdb;

        $user = wp_get_current_user();
        error_log('=== RoleBasedFilter::filter_customer_admin ===');
        error_log('User: ' . $user->user_login . ' (ID: ' . $user->ID . ')');
        error_log('Roles: ' . implode(', ', $user->roles));

        // Admin bypass
        if (current_user_can('manage_options')) {
            error_log('Admin bypass - no filter applied');
            return $where;
        }

        $user_id = get_current_user_id();

        // Get customer_id dari user (DIRECT SQL)
        $customer_id = $wpdb->get_var($wpdb->prepare(
            "SELECT customer_id FROM {$wpdb->prefix}app_customer_employees
             WHERE user_id = %d LIMIT 1",
            $user_id
        ));

        error_log('Customer ID from DB: ' . ($customer_id ?: 'NULL'));

        if (!$customer_id) {
            error_log('No customer_id found - no filter applied');
            return $where; // No customer - no filter
        }

        // Detect entity dari model class
        $entity = $this->detect_entity($model);
        error_log('Detected entity: ' . $entity);
        error_log('Model class: ' . get_class($model));

        // Build WHERE condition
        $condition = $this->build_where_for_customer_admin($entity, $customer_id);

        if ($condition) {
            $where[] = $condition;
            error_log('Added WHERE condition: ' . $condition);
        } else {
            error_log('No WHERE condition built for entity: ' . $entity);
        }

        error_log('Final WHERE array: ' . print_r($where, true));
        error_log('=== END RoleBasedFilter::filter_customer_admin ===');

        return $where;
    }

    /**
     * Filter for customer_branch_admin - lihat data branch mereka saja
     */
    public function filter_customer_branch_admin($where, $request, $model) {
        global $wpdb;

        if (current_user_can('manage_options')) {
            return $where;
        }

        $user_id = get_current_user_id();

        // Get branch_id dari user (DIRECT SQL)
        $branch_id = $wpdb->get_var($wpdb->prepare(
            "SELECT branch_id FROM {$wpdb->prefix}app_customer_employees
             WHERE user_id = %d LIMIT 1",
            $user_id
        ));

        if (!$branch_id) {
            return $where;
        }

        // Detect entity
        $entity = $this->detect_entity($model);

        // Build WHERE condition
        $condition = $this->build_where_for_branch_admin($entity, $branch_id);

        if ($condition) {
            $where[] = $condition;
        }

        return $where;
    }

    /**
     * Filter for customer_employee - same as branch_admin
     */
    public function filter_customer_employee($where, $request, $model) {
        return $this->filter_customer_branch_admin($where, $request, $model);
    }

    /**
     * Detect entity dari model class name
     */
    private function detect_entity($model) {
        $class = get_class($model);

        if (strpos($class, 'CustomerDataTableModel') !== false) {
            return 'customer';
        }
        if (strpos($class, 'CompanyDataTableModel') !== false) {
            return 'company';
        }
        if (strpos($class, 'BranchDataTableModel') !== false) {
            return 'branch';
        }
        if (strpos($class, 'EmployeeDataTableModel') !== false) {
            return 'employee';
        }
        if (strpos($class, 'AgencyDataTableModel') !== false) {
            return 'agency';
        }

        return 'unknown';
    }

    /**
     * Build WHERE condition untuk customer_admin
     */
    private function build_where_for_customer_admin($entity, $customer_id) {
        global $wpdb;

        switch ($entity) {
            case 'customer':
                return "c.id = {$customer_id}";

            case 'company':
                return "cb.customer_id = {$customer_id}";

            case 'branch':
                return "cb.customer_id = {$customer_id}";

            case 'employee':
                return "ce.customer_id = {$customer_id}";

            case 'agency':
                // Only show agencies assigned to customer's branches (Option 1: Assignment-based)
                return "a.id IN (
                    SELECT DISTINCT agency_id
                    FROM {$wpdb->prefix}app_customer_branches
                    WHERE customer_id = {$customer_id}
                    AND agency_id IS NOT NULL
                )";

            default:
                return null;
        }
    }

    /**
     * Build WHERE condition untuk customer_branch_admin
     */
    private function build_where_for_branch_admin($entity, $branch_id) {
        global $wpdb;

        switch ($entity) {
            case 'customer':
                // Branch admin can see their customer
                $customer_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT customer_id FROM {$wpdb->prefix}app_customer_branches
                     WHERE id = %d LIMIT 1",
                    $branch_id
                ));
                return $customer_id ? "c.id = {$customer_id}" : null;

            case 'company':
                // Branch admin only sees their branch as company
                return "cb.id = {$branch_id}";

            case 'branch':
                // Branch admin only sees their branch
                return "cb.id = {$branch_id}";

            case 'employee':
                // Branch admin sees employees in their branch
                return "ce.branch_id = {$branch_id}";

            case 'agency':
                // Branch admin only sees agency assigned to their branch
                $agency_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT agency_id FROM {$wpdb->prefix}app_customer_branches
                     WHERE id = %d AND agency_id IS NOT NULL LIMIT 1",
                    $branch_id
                ));
                // If no agency assigned, return condition that shows nothing
                return $agency_id ? "a.id = {$agency_id}" : "1=0";

            default:
                return null;
        }
    }
}
