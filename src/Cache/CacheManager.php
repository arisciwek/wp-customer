<?php
/**
 * Cache Management Class
 *
 * @package     WP_Customer
 * @subpackage  Cache
 * @version     3.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Cache/CacheManager.php
 *
 * Description: Manager untuk menangani caching data customer.
 *              Menggunakan WordPress Object Cache API.
 *              Includes cache untuk:
 *              - Single customer/branch/employee data
 *              - Lists (customer/branch/employee)
 *              - Statistics
 *              - Relations
 *
 * Cache Groups:
 * - wp_customer: Grup utama untuk semua cache
 * 
 * Cache Keys:
 * - customer_{id}: Data single customer
 * - customer_list: Daftar semua customer
 * - customer_stats: Statistik customer
 * - branch_{id}: Data single branch
 * - branch_list: Daftar semua branch
 * - branch_stats: Statistik branch
 * - employee_{id}: Data single employee
 * - employee_list: Daftar semua employee
 * - employee_stats: Statistik employee
 * - user_customers_{user_id}: Relasi user-customer
 * 
 * Dependencies:
 * - WordPress Object Cache API
 * 
 * Changelog:
 * 3.0.0 - 2024-01-31
 * - Added branch caching support
 * - Added employee caching support
 * - Extended cache key management
 * - Added statistics caching for all entities
 * 
 * 2.0.0 - 2024-01-20
 * - Added customer statistics cache
 * - Added user-customer relations cache
 * - Added cache group invalidation
 * - Enhanced cache key management
 * - Improved cache expiry handling
 * 
 * 1.0.0 - 2024-12-03
 * - Initial implementation
 * - Basic customer data caching
 * - List caching functionality
 */

namespace WPCustomer\Cache;

class CacheManager {
    private const CACHE_GROUP = 'wp_customer';
    private const CACHE_EXPIRY = 12 * HOUR_IN_SECONDS;
    
    // Cache keys for Customer
    private const KEY_CUSTOMER = 'customer_';
    private const KEY_CUSTOMER_LIST = 'customer_list';
    private const KEY_CUSTOMER_STATS = 'customer_stats';
    private const KEY_USER_CUSTOMERS = 'user_customers_';

    // Cache keys for Branch
    private const KEY_BRANCH = 'branch_';
    private const KEY_BRANCH_LIST = 'branch_list';
    private const KEY_BRANCH_STATS = 'branch_stats';
    private const KEY_CUSTOMER_BRANCHES = 'customer_branches_';

    // Cache keys for Employee
    private const KEY_EMPLOYEE = 'employee_';
    private const KEY_EMPLOYEE_LIST = 'employee_list';
    private const KEY_EMPLOYEE_STATS = 'employee_stats';
    private const KEY_BRANCH_EMPLOYEES = 'branch_employees_';

    /**
     * Get value from cache
     *
     * @param string $key Cache key
     * @return mixed|null Cached value or null if not found
     */
    public function get(string $key) {
        // Log cache attempt in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Cache attempt for key: {$key}");
        }
        
        $result = wp_cache_get($key, self::CACHE_GROUP);
        
        // Check if cache miss (wp_cache_get returns false on cache miss)
        if ($result === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Cache miss for key: {$key}");
            }
            return null;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Cache hit for key: {$key}");
        }
        
        return $result;
    }

    /**
     * Set value in cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $expiry Cache expiration in seconds. Default: 12 hours
     * @return bool True on success, false on failure
     */
    public function set(string $key, $value, int $expiry = null): bool {
        if ($expiry === null) {
            $expiry = self::CACHE_EXPIRY;
        }
        
        // Log cache setting in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Setting cache for key: {$key} with expiry: {$expiry}s");
        }
        
        return wp_cache_set($key, $value, self::CACHE_GROUP, $expiry);
    }

    /**
     * Delete value from cache
     *
     * @param string $key Cache key
     * @return bool True on success, false on failure
     */
    public function delete(string $key): bool {
        // Log cache deletion in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Deleting cache for key: {$key}");
        }
        
        return wp_cache_delete($key, self::CACHE_GROUP);
    }

    /**
     * Check if key exists in cache
     *
     * @param string $key Cache key
     * @return bool True if key exists, false otherwise
     */
    public function exists(string $key): bool {
        return wp_cache_get($key, self::CACHE_GROUP) !== false;
    }

    // Customer Methods
    public function getCustomer(int $id): ?object {
        $result = wp_cache_get(self::KEY_CUSTOMER . $id, self::CACHE_GROUP);
        if ($result === false) {
            return null;
        }
        return $result;
    }

    public function setCustomer(int $id, object $data): bool {
        return wp_cache_set(
            self::KEY_CUSTOMER . $id, 
            $data, 
            self::CACHE_GROUP, 
            self::CACHE_EXPIRY
        );
    }

    public function invalidateCustomerCache(int $id): void {
        wp_cache_delete(self::KEY_CUSTOMER . $id, self::CACHE_GROUP);
    }

    public function getCustomerList(): ?array {
        return wp_cache_get(self::KEY_CUSTOMER_LIST, self::CACHE_GROUP);
    }

    public function setCustomerList(array $data): bool {
        return wp_cache_set(
            self::KEY_CUSTOMER_LIST,
            $data,
            self::CACHE_GROUP,
            self::CACHE_EXPIRY
        );
    }

    public function invalidateCustomerListCache(): void {
        wp_cache_delete(self::KEY_CUSTOMER_LIST, self::CACHE_GROUP);
    }

    // Branch Methods
    public function getBranch(int $id): ?object {
        $result = wp_cache_get(self::KEY_BRANCH . $id, self::CACHE_GROUP);
        if ($result === false) {
            return null;
        }
        return $result;
    }

    public function setBranch(int $id, object $data): bool {
        return wp_cache_set(
            self::KEY_BRANCH . $id,
            $data,
            self::CACHE_GROUP,
            self::CACHE_EXPIRY
        );
    }

    public function invalidateBranchCache(int $id): void {
        wp_cache_delete(self::KEY_BRANCH . $id, self::CACHE_GROUP);
    }

    public function getBranchList(): ?array {
        return wp_cache_get(self::KEY_BRANCH_LIST, self::CACHE_GROUP);
    }

    public function setBranchList(array $data): bool {
        return wp_cache_set(
            self::KEY_BRANCH_LIST,
            $data,
            self::CACHE_GROUP,
            self::CACHE_EXPIRY
        );
    }

    public function invalidateBranchListCache(): void {
        wp_cache_delete(self::KEY_BRANCH_LIST, self::CACHE_GROUP);
    }

    public function getCustomerBranches(int $customer_id): ?array {
        return wp_cache_get(self::KEY_CUSTOMER_BRANCHES . $customer_id, self::CACHE_GROUP);
    }

    public function setCustomerBranches(int $customer_id, array $branches): bool {
        return wp_cache_set(
            self::KEY_CUSTOMER_BRANCHES . $customer_id,
            $branches,
            self::CACHE_GROUP,
            self::CACHE_EXPIRY
        );
    }

    public function invalidateCustomerBranchesCache(int $customer_id): void {
        wp_cache_delete(self::KEY_CUSTOMER_BRANCHES . $customer_id, self::CACHE_GROUP);
    }

    // Employee Methods
    public function getEmployee(int $id): ?object {
        $result = wp_cache_get(self::KEY_EMPLOYEE . $id, self::CACHE_GROUP);
        if ($result === false) {
            return null;
        }
        return $result;
    }

    public function setEmployee(int $id, object $data): bool {
        return wp_cache_set(
            self::KEY_EMPLOYEE . $id,
            $data,
            self::CACHE_GROUP,
            self::CACHE_EXPIRY
        );
    }

    public function invalidateEmployeeCache(int $id): void {
        wp_cache_delete(self::KEY_EMPLOYEE . $id, self::CACHE_GROUP);
    }

    public function getEmployeeList(): ?array {
        return wp_cache_get(self::KEY_EMPLOYEE_LIST, self::CACHE_GROUP);
    }

    public function setEmployeeList(array $data): bool {
        return wp_cache_set(
            self::KEY_EMPLOYEE_LIST,
            $data,
            self::CACHE_GROUP,
            self::CACHE_EXPIRY
        );
    }

    public function invalidateEmployeeListCache(): void {
        wp_cache_delete(self::KEY_EMPLOYEE_LIST, self::CACHE_GROUP);
    }

    public function getBranchEmployees(int $branch_id): ?array {
        return wp_cache_get(self::KEY_BRANCH_EMPLOYEES . $branch_id, self::CACHE_GROUP);
    }

    public function setBranchEmployees(int $branch_id, array $employees): bool {
        return wp_cache_set(
            self::KEY_BRANCH_EMPLOYEES . $branch_id,
            $employees,
            self::CACHE_GROUP,
            self::CACHE_EXPIRY
        );
    }

    public function invalidateBranchEmployeesCache(int $branch_id): void {
        wp_cache_delete(self::KEY_BRANCH_EMPLOYEES . $branch_id, self::CACHE_GROUP);
    }

    // Statistics Methods
    public function getBranchStats(): ?array {
        return wp_cache_get(self::KEY_BRANCH_STATS, self::CACHE_GROUP);
    }

    public function setBranchStats(array $stats): bool {
        return wp_cache_set(
            self::KEY_BRANCH_STATS,
            $stats,
            self::CACHE_GROUP,
            self::CACHE_EXPIRY
        );
    }

    public function invalidateBranchStatsCache(): void {
        wp_cache_delete(self::KEY_BRANCH_STATS, self::CACHE_GROUP);
    }

    public function getEmployeeStats(): ?array {
        return wp_cache_get(self::KEY_EMPLOYEE_STATS, self::CACHE_GROUP);
    }

    public function setEmployeeStats(array $stats): bool {
        return wp_cache_set(
            self::KEY_EMPLOYEE_STATS,
            $stats,
            self::CACHE_GROUP,
            self::CACHE_EXPIRY
        );
    }

    public function invalidateEmployeeStatsCache(): void {
        wp_cache_delete(self::KEY_EMPLOYEE_STATS, self::CACHE_GROUP);
    }

    // General Methods
    public function clearAllCaches(): void {
        wp_cache_delete_group(self::CACHE_GROUP);
    }

    /**
     * Clear all caches related to a specific customer, including relations
     */
    public function clearCustomerRelatedCaches(int $customer_id): void {
        $this->invalidateCustomerCache($customer_id);
        $this->invalidateCustomerListCache();
        $this->invalidateCustomerStatsCache();
        $this->invalidateCustomerBranchesCache($customer_id);
    }

    /**
     * Clear all caches related to a specific branch, including relations
     */
    public function clearBranchRelatedCaches(int $branch_id): void {
        $this->invalidateBranchCache($branch_id);
        $this->invalidateBranchListCache();
        $this->invalidateBranchStatsCache();
        $this->invalidateBranchEmployeesCache($branch_id);
    }

    /**
     * Clear all caches related to a specific employee
     */
    public function clearEmployeeRelatedCaches(int $employee_id): void {
        $this->invalidateEmployeeCache($employee_id);
        $this->invalidateEmployeeListCache();
        $this->invalidateEmployeeStatsCache();
    }
}
