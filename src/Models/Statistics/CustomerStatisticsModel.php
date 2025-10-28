<?php
/**
 * Customer Statistics Model
 *
 * Model for customer-related statistics and analytics.
 * Provides aggregated data about customers in relation to other entities.
 *
 * @package     WPCustomer
 * @subpackage  Models/Statistics
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Statistics/CustomerStatisticsModel.php
 *
 * Description: Statistics model untuk data aggregation.
 *              Provides methods untuk count customers, branches, dll
 *              dengan user access filtering.
 *              Digunakan untuk inject statistics ke external plugins.
 *
 * Changelog:
 * 1.0.0 - 2025-10-28
 * - Initial implementation (TODO-2177, Task-3085)
 * - Add get_customer_count_for_agency() with user filtering
 * - Add get_branch_count_for_agency() with user filtering
 * - Support platform staff (see all) vs customer employee (filtered)
 * - Use WordPress object caching
 */

namespace WPCustomer\Models\Statistics;

defined('ABSPATH') || exit;

class CustomerStatisticsModel {

    /**
     * WordPress database instance
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * Cache group name
     *
     * @var string
     */
    private const CACHE_GROUP = 'wp_customer_statistics';

    /**
     * Default cache TTL (5 minutes)
     *
     * @var int
     */
    private const DEFAULT_CACHE_TTL = 300;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Get customer count for specific agency
     *
     * Returns count of customers that have branches connected to the agency.
     * Filtered by user access for customer employees.
     * Admins and platform staff see all customers.
     *
     * Single optimized query with JOINs.
     *
     * @param int      $agency_id Agency ID
     * @param int|null $user_id   User ID for access filtering (null = current user)
     * @return int Customer count
     *
     * @since 1.0.0
     */
    public function get_customer_count_for_agency(int $agency_id, ?int $user_id = null): int {
        // Use current user if not specified
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        // Check cache
        $cache_key = "customer_count_agency_{$agency_id}_user_{$user_id}";
        $cached = wp_cache_get($cache_key, self::CACHE_GROUP);

        if ($cached !== false) {
            return (int) $cached;
        }

        // Check if user should bypass filter (see all)
        $should_bypass = $this->should_bypass_agency_filter($user_id);

        // Build query based on user access
        if ($should_bypass) {
            // Admin/Platform staff: Count all customers for this agency
            $sql = "SELECT COUNT(DISTINCT c.id)
                    FROM {$this->wpdb->prefix}app_customers c
                    INNER JOIN {$this->wpdb->prefix}app_customer_branches b
                        ON c.id = b.customer_id
                    WHERE b.agency_id = %d";

            $count = (int) $this->wpdb->get_var(
                $this->wpdb->prepare($sql, $agency_id)
            );
        } else {
            // Customer employee: Count only accessible customers
            $accessible_customer_ids = $this->get_accessible_customer_ids($user_id);

            if (empty($accessible_customer_ids)) {
                return 0;
            }

            $placeholders = implode(',', array_fill(0, count($accessible_customer_ids), '%d'));

            $sql = "SELECT COUNT(DISTINCT c.id)
                    FROM {$this->wpdb->prefix}app_customers c
                    INNER JOIN {$this->wpdb->prefix}app_customer_branches b
                        ON c.id = b.customer_id
                    WHERE b.agency_id = %d
                    AND c.id IN ($placeholders)";

            $params = array_merge([$agency_id], $accessible_customer_ids);
            $count = (int) $this->wpdb->get_var(
                $this->wpdb->prepare($sql, ...$params)
            );
        }

        // Cache result
        wp_cache_set($cache_key, $count, self::CACHE_GROUP, self::DEFAULT_CACHE_TTL);

        return $count;
    }

    /**
     * Get branch count for specific agency
     *
     * Returns count of customer branches connected to the agency.
     * Filtered by user access for customer employees.
     * Admins and platform staff see all branches.
     *
     * @param int      $agency_id Agency ID
     * @param int|null $user_id   User ID for access filtering (null = current user)
     * @return int Branch count
     *
     * @since 1.0.0
     */
    public function get_branch_count_for_agency(int $agency_id, ?int $user_id = null): int {
        // Use current user if not specified
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        // Check cache
        $cache_key = "branch_count_agency_{$agency_id}_user_{$user_id}";
        $cached = wp_cache_get($cache_key, self::CACHE_GROUP);

        if ($cached !== false) {
            return (int) $cached;
        }

        // Check if user should bypass filter (see all)
        $should_bypass = $this->should_bypass_agency_filter($user_id);

        // Build query based on user access
        if ($should_bypass) {
            // Admin/Platform staff: Count all branches for this agency
            $sql = "SELECT COUNT(*)
                    FROM {$this->wpdb->prefix}app_customer_branches
                    WHERE agency_id = %d";

            $count = (int) $this->wpdb->get_var(
                $this->wpdb->prepare($sql, $agency_id)
            );
        } else {
            // Customer employee: Count only branches of accessible customers
            $accessible_customer_ids = $this->get_accessible_customer_ids($user_id);

            if (empty($accessible_customer_ids)) {
                return 0;
            }

            $placeholders = implode(',', array_fill(0, count($accessible_customer_ids), '%d'));

            $sql = "SELECT COUNT(*)
                    FROM {$this->wpdb->prefix}app_customer_branches
                    WHERE agency_id = %d
                    AND customer_id IN ($placeholders)";

            $params = array_merge([$agency_id], $accessible_customer_ids);
            $count = (int) $this->wpdb->get_var(
                $this->wpdb->prepare($sql, ...$params)
            );
        }

        // Cache result
        wp_cache_set($cache_key, $count, self::CACHE_GROUP, self::DEFAULT_CACHE_TTL);

        return $count;
    }

    /**
     * Get customer statistics for agency
     *
     * Returns comprehensive statistics about customers for an agency.
     * Includes customer count, branch count, and other metrics.
     *
     * @param int      $agency_id Agency ID
     * @param int|null $user_id   User ID for access filtering (null = current user)
     * @return array Statistics data
     *
     * @since 1.0.0
     */
    public function get_agency_customer_statistics(int $agency_id, ?int $user_id = null): array {
        $customer_count = $this->get_customer_count_for_agency($agency_id, $user_id);
        $branch_count = $this->get_branch_count_for_agency($agency_id, $user_id);

        return [
            'customer_count' => $customer_count,
            'branch_count' => $branch_count,
            'has_customers' => $customer_count > 0,
            'has_branches' => $branch_count > 0,
        ];
    }

    /**
     * Get accessible customer IDs for user
     *
     * Returns customer IDs that the user has access to based on
     * customer_employees table relationship.
     *
     * @param int $user_id User ID
     * @return array Customer IDs
     *
     * @since 1.0.0
     */
    private function get_accessible_customer_ids(int $user_id): array {
        // Check cache
        $cache_key = "accessible_customers_user_{$user_id}";
        $cached = wp_cache_get($cache_key, self::CACHE_GROUP);

        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT DISTINCT customer_id
                FROM {$this->wpdb->prefix}app_customer_employees
                WHERE user_id = %d";

        $customer_ids = $this->wpdb->get_col(
            $this->wpdb->prepare($sql, $user_id)
        );

        $customer_ids = array_map('intval', $customer_ids);

        // Cache result
        wp_cache_set($cache_key, $customer_ids, self::CACHE_GROUP, self::DEFAULT_CACHE_TTL);

        return $customer_ids;
    }

    /**
     * Check if user is platform staff
     *
     * Platform staff have access to all data without filtering.
     *
     * @param int $user_id User ID
     * @return bool True if platform staff
     *
     * @since 1.0.0
     */
    private function is_platform_staff(int $user_id): bool {
        // Check platform staff table
        $table = $this->wpdb->prefix . 'app_platform_staff';

        $sql = "SELECT COUNT(*)
                FROM {$table}
                WHERE user_id = %d";

        $exists = (bool) $this->wpdb->get_var(
            $this->wpdb->prepare($sql, $user_id)
        );

        // Also check user capability
        $user = get_user_by('id', $user_id);
        if ($user && $user->has_cap('admin_platform')) {
            return true;
        }

        return $exists;
    }

    /**
     * Check if user should bypass filter for agency statistics
     *
     * Determines if user should see all data without filtering based on:
     * 1. WordPress administrator role (global super admin)
     * 2. Platform staff (platform-level access)
     * 3. Agency admin roles (configured list)
     *
     * Uses same logic as DataTableAccessFilter for consistency.
     *
     * @param int $user_id User ID
     * @return bool True if should bypass filter, false otherwise
     *
     * @since 1.0.0
     */
    private function should_bypass_agency_filter(int $user_id): bool {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }

        // 1. WordPress administrator always bypasses (global super admin)
        if (in_array('administrator', $user->roles)) {
            return true;
        }

        // 2. Platform staff bypasses all entities
        if ($this->is_platform_staff($user_id)) {
            return true;
        }

        // 3. Agency admin roles bypass
        // Admin roles can see ALL data, non-admin roles are filtered
        $agency_admin_roles = [
            'agency_admin_dinas',     // Admin Dinas (top level)
            'agency_admin_unit',      // Admin Unit (unit level)
            'agency_kepala_dinas',    // Kepala Dinas (head of agency)
            'agency_kepala_bidang',   // Kepala Bidang (head of division)
        ];

        /**
         * Filter: Modify agency admin roles list
         *
         * Allows plugins to add or modify admin roles for agency statistics.
         * Admin roles will bypass data filtering and see all records.
         *
         * @param array $agency_admin_roles List of admin role slugs
         * @param int   $user_id            User ID being checked
         * @return array Modified list of admin roles
         *
         * @since 1.0.0
         */
        $agency_admin_roles = apply_filters(
            'wp_customer_agency_admin_roles',
            $agency_admin_roles,
            $user_id
        );

        // Check if user has any admin role
        foreach ($agency_admin_roles as $admin_role) {
            if (in_array($admin_role, $user->roles)) {
                return true;
            }
        }

        /**
         * Filter: Override agency statistics bypass check
         *
         * Allows plugins/themes to override the bypass logic for agency statistics.
         * This is the last check before filtering is applied.
         *
         * @param bool   $should_bypass Whether user should bypass filter
         * @param int    $user_id       User ID
         * @param object $user          User object
         * @return bool Modified should_bypass
         *
         * @since 1.0.0
         */
        return apply_filters(
            'wp_customer_should_bypass_agency_statistics_filter',
            false,
            $user_id,
            $user
        );
    }

    /**
     * Clear cache for specific agency
     *
     * Clears all cached statistics for an agency.
     *
     * @param int $agency_id Agency ID
     * @return void
     *
     * @since 1.0.0
     */
    public function clear_agency_cache(int $agency_id): void {
        // Note: WordPress doesn't provide cache wildcard deletion
        // This is a simplified version - in production, consider using
        // cache groups or external cache with wildcard support
        wp_cache_flush();
    }
}
