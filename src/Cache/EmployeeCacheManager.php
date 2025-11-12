<?php
/**
 * Employee Cache Manager
 *
 * @package     WP_Customer
 * @subpackage  Cache
 * @version     1.0.1
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Cache/EmployeeCacheManager.php
 *
 * Description: Cache manager untuk Employee entity.
 *              Extends AbstractCacheManager dari wp-app-core.
 *              Handles caching untuk employee data, relations, dan DataTable.
 *
 * Separation of Concerns:
 * - EmployeeCacheManager: Employee-specific caching only
 * - CustomerCacheManager: Customer-wide caching (fallback)
 * - BranchCacheManager: Branch-specific caching
 * - All extend AbstractCacheManager
 *
 * Changelog:
 * 1.0.1 - 2025-01-13 (TODO-2199)
 * - Review against AbstractCacheManager v1.0.1
 * - Verified all abstract methods implemented correctly
 * - Added employee_stats cache key
 * - Confirmed 2-hour expiry appropriate for dynamic employee data
 * - Ready for use in EmployeeModel
 *
 * 1.0.0 - 2025-11-09 (TODO-2193: Cache Refactoring)
 * - Initial implementation
 * - Extends AbstractCacheManager
 * - Implements 5 abstract methods
 * - Cache expiry: 2 hours (default)
 * - Cache group: wp_customer_employee
 * - Employee-specific cache keys
 */

namespace WPCustomer\Cache;

use WPAppCore\Cache\Abstract\AbstractCacheManager;

defined('ABSPATH') || exit;

class EmployeeCacheManager extends AbstractCacheManager {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return EmployeeCacheManager
     */
    public static function getInstance(): EmployeeCacheManager {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ========================================
    // IMPLEMENT ABSTRACT METHODS (5 required)
    // ========================================

    /**
     * Get cache group name
     *
     * @return string
     */
    protected function getCacheGroup(): string {
        return 'wp_customer_employee';
    }

    /**
     * Get cache expiry time
     *
     * @return int Cache expiry in seconds (2 hours)
     */
    protected function getCacheExpiry(): int {
        return 2 * HOUR_IN_SECONDS;
    }

    /**
     * Get entity name
     *
     * @return string
     */
    protected function getEntityName(): string {
        return 'employee';
    }

    /**
     * Get cache keys mapping
     *
     * @return array
     */
    protected function getCacheKeys(): array {
        return [
            'customer_employee' => 'customer_employee',
            'customer_employee_list' => 'customer_employee_list',
            'employee_stats' => 'employee_stats',
            'employee_by_customer' => 'employee_by_customer',
            'employee_by_branch' => 'employee_by_branch',
            'employee_count' => 'employee_count',
            'employee_relation' => 'employee_relation',
            'employee_ids' => 'employee_ids',
            'user_info' => 'user_info',
            'email_exists' => 'email_exists',
            'nik_exists' => 'nik_exists'
        ];
    }

    /**
     * Get known cache types for fallback clearing
     *
     * @return array
     */
    protected function getKnownCacheTypes(): array {
        return [
            'customer_employee',
            'customer_employee_list',
            'employee_stats',
            'employee_by_customer',
            'employee_by_branch',
            'employee_count',
            'employee_relation',
            'employee_ids',
            'user_info',
            'email_exists',
            'nik_exists',
            'datatable'
        ];
    }

    // ========================================
    // CUSTOM CACHE METHODS (Entity-specific)
    // ========================================

    /**
     * Get employee from cache
     *
     * @param int $id Employee ID
     * @return object|false Employee object or FALSE if not found (cache miss)
     */
    public function getEmployee(int $id): object|false {
        return $this->get('customer_employee', $id);
    }

    /**
     * Set employee in cache
     *
     * @param int $id Employee ID
     * @param object $employee Employee data
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setEmployee(int $id, object $employee, ?int $expiry = null): bool {
        return $this->set('customer_employee', $employee, $expiry, $id);
    }

    /**
     * Get employees by customer from cache
     *
     * @param int $customer_id Customer ID
     * @return array|false Array of employees or FALSE if not found
     */
    public function getEmployeesByCustomer(int $customer_id): array|false {
        return $this->get('employee_by_customer', $customer_id);
    }

    /**
     * Set employees by customer in cache
     *
     * @param int $customer_id Customer ID
     * @param array $employees Array of employee objects
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setEmployeesByCustomer(int $customer_id, array $employees, ?int $expiry = null): bool {
        return $this->set('employee_by_customer', $employees, $expiry, $customer_id);
    }

    /**
     * Get employees by branch from cache
     *
     * @param int $branch_id Branch ID
     * @return array|false Array of employees or FALSE if not found
     */
    public function getEmployeesByBranch(int $branch_id): array|false {
        return $this->get('employee_by_branch', $branch_id);
    }

    /**
     * Set employees by branch in cache
     *
     * @param int $branch_id Branch ID
     * @param array $employees Array of employee objects
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setEmployeesByBranch(int $branch_id, array $employees, ?int $expiry = null): bool {
        return $this->set('employee_by_branch', $employees, $expiry, $branch_id);
    }

    /**
     * Get user info from cache
     *
     * @param int $user_id User ID
     * @return array|false User info or FALSE if not found
     */
    public function getUserInfo(int $user_id): array|false {
        return $this->get('user_info', $user_id);
    }

    /**
     * Set user info in cache
     *
     * @param int $user_id User ID
     * @param array $user_info User info array
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setUserInfo(int $user_id, array $user_info, ?int $expiry = null): bool {
        return $this->set('user_info', $user_info, $expiry, $user_id);
    }

    /**
     * Invalidate employee cache
     *
     * Clears all cache related to a specific employee:
     * - Employee entity
     * - DataTable cache
     * - Relation cache
     * - Customer's employee list
     * - Branch's employee list
     *
     * @param int $id Employee ID
     * @param int|null $customer_id Optional customer ID for targeted clearing
     * @param int|null $branch_id Optional branch ID for targeted clearing
     * @return void
     */
    public function invalidateEmployeeCache(int $id, ?int $customer_id = null, ?int $branch_id = null): void {
        // Clear employee entity cache
        $this->delete('customer_employee', $id);

        // Clear relation cache for this employee
        $this->clearCache('employee_relation');

        // Clear DataTable cache
        $this->invalidateDataTableCache('customer_employee_list');

        // Clear customer's employee list if customer_id provided
        if ($customer_id) {
            $this->delete('employee_by_customer', $customer_id);
            $this->delete('employee_count', $customer_id);
        }

        // Clear branch's employee list if branch_id provided
        if ($branch_id) {
            $this->delete('employee_by_branch', $branch_id);
        }

        // Clear employee IDs cache
        $this->delete('employee_ids', 'active');

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[EmployeeCacheManager] Invalidated all cache for employee {$id}");
        }
    }

    /**
     * Invalidate all employees cache for a customer
     *
     * @param int $customer_id Customer ID
     * @return void
     */
    public function invalidateCustomerEmployees(int $customer_id): void {
        $this->delete('employee_by_customer', $customer_id);
        $this->delete('employee_count', $customer_id);
        $this->invalidateDataTableCache('customer_employee_list');

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[EmployeeCacheManager] Invalidated all employees cache for customer {$customer_id}");
        }
    }

    /**
     * Invalidate all employees cache for a branch
     *
     * @param int $branch_id Branch ID
     * @return void
     */
    public function invalidateBranchEmployees(int $branch_id): void {
        $this->delete('employee_by_branch', $branch_id);
        $this->invalidateDataTableCache('customer_employee_list');

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[EmployeeCacheManager] Invalidated all employees cache for branch {$branch_id}");
        }
    }
}
