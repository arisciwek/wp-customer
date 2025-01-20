<?php
/**
 * Cache Management Class
 *
 * @package     WP_Customer
 * @subpackage  Cache
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Cache/CacheManager.php
 *
 * Description: Manager untuk menangani caching data customer.
 *              Menggunakan WordPress Object Cache API.
 *              Includes cache untuk:
 *              - Single customer data
 *              - Customer lists
 *              - Customer statistics
 *              - User-customer relations
 *              Mendukung operasi CRUD dengan cache invalidation.
 * 
 * Cache Groups:
 * - wp_customer: Grup utama untuk semua cache customer
 * 
 * Cache Keys:
 * - customer_{id}: Data single customer
 * - customer_list: Daftar semua customer
 * - customer_stats: Statistik customer
 * - user_customers_{user_id}: Relasi user-customer
 * 
 * Dependencies:
 * - WordPress Object Cache API
 * - WPCustomer\Models\CustomerModel untuk data
 * 
 * Changelog:
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
    
    // Cache keys
    private const KEY_CUSTOMER = 'customer_';
    private const KEY_CUSTOMER_LIST = 'customer_list';
    private const KEY_CUSTOMER_STATS = 'customer_stats';
    private const KEY_USER_CUSTOMERS = 'user_customers_';

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

    /**
     * Get customer statistics from cache
     */
    public function getCustomerStats(): ?array {
        return wp_cache_get(self::KEY_CUSTOMER_STATS, self::CACHE_GROUP);
    }

    /**
     * Set customer statistics in cache
     */
    public function setCustomerStats(array $stats): bool {
        return wp_cache_set(
            self::KEY_CUSTOMER_STATS, 
            $stats, 
            self::CACHE_GROUP, 
            self::CACHE_EXPIRY
        );
    }

    /**
     * Invalidate customer statistics cache
     */
    public function invalidateCustomerStatsCache(): void {
        wp_cache_delete(self::KEY_CUSTOMER_STATS, self::CACHE_GROUP);
    }

    /**
     * Get customers associated with a user
     */
    public function getUserCustomers(int $user_id): ?array {
        return wp_cache_get(self::KEY_USER_CUSTOMERS . $user_id, self::CACHE_GROUP);
    }

    /**
     * Set customers associated with a user
     */
    public function setUserCustomers(int $user_id, array $customers): bool {
        return wp_cache_set(
            self::KEY_USER_CUSTOMERS . $user_id,
            $customers,
            self::CACHE_GROUP,
            self::CACHE_EXPIRY
        );
    }

    /**
     * Invalidate user's customers cache
     */
    public function invalidateUserCustomersCache(int $user_id): void {
        wp_cache_delete(self::KEY_USER_CUSTOMERS . $user_id, self::CACHE_GROUP);
    }

    /**
     * Clear all customer related caches
     */
    public function clearAllCustomerCaches(): void {
        wp_cache_delete_group(self::CACHE_GROUP);
    }
}
