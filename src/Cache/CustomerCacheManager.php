<?php
/**
 * Customer Cache Manager
 *
 * @package     WP_Customer
 * @subpackage  Cache
 * @version     1.0.1
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Cache/CustomerCacheManager.php
 *
 * Description: Cache manager untuk Customer entity.
 *              Extends AbstractCacheManager dari wp-app-core.
 *              Handles caching untuk customer data, relations, dan DataTable.
 *
 * Changelog:
 * 1.0.1 - 2025-01-13 (TODO-2199)
 * - Review against AbstractCacheManager v1.0.1
 * - Added cache keys: customer_by_code, customer_hierarchy, customer_branches
 * - Added helper methods: getCustomerByCode, invalidateAllCustomerCache
 * - Verified TODO-2192 fix (return false on cache miss)
 * - Ready to use as template for other cache managers
 *
 * 1.0.0 - 2025-01-08 (Task-2191)
 * - Initial implementation
 * - Extends AbstractCacheManager
 * - Implements 5 abstract methods
 * - Cache expiry: 12 hours (default)
 * - Cache group: wp_customer
 */

namespace WPCustomer\Cache;

use WPAppCore\Cache\Abstract\AbstractCacheManager;

defined('ABSPATH') || exit;

class CustomerCacheManager extends AbstractCacheManager {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return CustomerCacheManager
     */
    public static function getInstance(): CustomerCacheManager {
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
        return 'wp_customer';
    }

    /**
     * Get cache expiry time
     *
     * @return int Cache expiry in seconds (12 hours)
     */
    protected function getCacheExpiry(): int {
        return 12 * HOUR_IN_SECONDS;
    }

    /**
     * Get entity name
     *
     * @return string
     */
    protected function getEntityName(): string {
        return 'customer';
    }

    /**
     * Get cache keys mapping
     *
     * @return array
     */
    protected function getCacheKeys(): array {
        return [
            'customer' => 'customer',
            'customer_list' => 'customer_list',
            'customer_stats' => 'customer_stats',
            'customer_total_count' => 'customer_total_count',
            'customer_relation' => 'customer_relation',
            'customer_by_code' => 'customer_by_code',
            'customer_hierarchy' => 'customer_hierarchy',
            'customer_branches' => 'customer_branches',
            'branch_count' => 'branch_count',
            'customer_ids' => 'customer_ids',
            'code_exists' => 'code_exists',
            'name_exists' => 'name_exists',
            'user_customers' => 'user_customers'
        ];
    }

    /**
     * Get known cache types for fallback clearing
     *
     * @return array
     */
    protected function getKnownCacheTypes(): array {
        return [
            'customer',
            'customer_list',
            'customer_stats',
            'customer_total_count',
            'customer_relation',
            'customer_by_code',
            'customer_hierarchy',
            'customer_branches',
            'branch_count',
            'customer_ids',
            'code_exists',
            'name_exists',
            'user_customers',
            'datatable'
        ];
    }

    // ========================================
    // CUSTOM CACHE METHODS (Entity-specific)
    // ========================================

    /**
     * Get customer from cache
     *
     * @param int $id Customer ID
     * @return object|false Customer object or FALSE if not found (cache miss)
     */
    public function getCustomer(int $id): object|false {
        // TODO-2192 FIXED: Return false on cache miss (not null)
        // This is required by AbstractCrudModel find() method
        return $this->get('customer', $id);
    }

    /**
     * Set customer in cache
     *
     * @param int $id Customer ID
     * @param object $customer Customer data
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setCustomer(int $id, object $customer, ?int $expiry = null): bool {
        return $this->set('customer', $customer, $expiry, $id);
    }

    /**
     * Invalidate customer cache
     *
     * Clears all cache related to a specific customer:
     * - Customer entity
     * - DataTable cache
     * - Relation cache
     * - Stats cache
     *
     * @param int $id Customer ID
     * @return void
     */
    public function invalidateCustomerCache(int $id): void {
        // Clear customer entity cache
        $this->delete('customer', $id);

        // Clear relation cache for this customer
        $this->clearCache('customer_relation');

        // Clear DataTable cache
        $this->invalidateDataTableCache('customer_list');

        // Clear stats cache
        $this->clearCache('customer_stats');
        $this->clearCache('customer_total_count');

        // Clear branch count cache
        $this->delete('branch_count', $id);

        // Clear customer IDs cache
        $this->delete('customer_ids', 'active');

        // Clear hierarchy cache
        $this->clearCache('customer_hierarchy');

        // Clear branches cache
        $this->clearCache('customer_branches');

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[CustomerCacheManager] Invalidated all cache for customer {$id}");
        }
    }

    /**
     * Get customer by code from cache
     *
     * @param string $code Customer code
     * @return object|false Customer object or FALSE if not found
     */
    public function getCustomerByCode(string $code): object|false {
        return $this->get('customer_by_code', $code);
    }

    /**
     * Set customer by code in cache
     *
     * @param string $code Customer code
     * @param object $customer Customer data
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setCustomerByCode(string $code, object $customer, ?int $expiry = null): bool {
        return $this->set('customer_by_code', $customer, $expiry, $code);
    }

    /**
     * Invalidate ALL customer caches
     *
     * Clears all customer-related cache in the group.
     * Use with caution - this clears everything.
     *
     * @return bool
     */
    public function invalidateAllCustomerCache(): bool {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[CustomerCacheManager] Invalidating ALL customer caches");
        }

        return $this->clearAll();
    }
}
