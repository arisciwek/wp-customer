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
/**
 * Cache Management Class
 * 
 * @package     WP_Customer
 * @subpackage  Cache
 */
namespace WPCustomer\Cache;

class CacheManager {
    private const CACHE_GROUP = 'wp_customer';
    private const CACHE_EXPIRY = 12 * HOUR_IN_SECONDS;
    
    /**
     * Generates valid cache key based on components
     */
    private function generateKey(string ...$components): string {
        // Filter out empty components
        $validComponents = array_filter($components, function($component) {
            return !empty($component) && is_string($component);
        });
        
        if (empty($validComponents)) {
            return 'default_key_' . md5(microtime());
        }

        // Join with underscore and ensure valid length
        $key = implode('_', $validComponents);
        
        // WordPress has a key length limit of 172 characters
        if (strlen($key) > 172) {
            $key = substr($key, 0, 140) . '_' . md5($key);
        }
        
        return $key;
    }

    /**
     * Get value from cache with validation
     */
    public function get(string $type, ...$keyComponents) {
        $key = $this->generateKey($type, ...$keyComponents);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Cache attempt - Key: {$key}, Type: {$type}");
        }
        
        $result = wp_cache_get($key, self::CACHE_GROUP);
        
        if ($result === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Cache miss - Key: {$key}");
            }
            return null;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Cache hit - Key: {$key}");
        }
        
        return $result;
    }

    /**
     * Set value in cache with validation
     */
    public function set(string $type, $value, int $expiry = null, ...$keyComponents): bool {
        $key = $this->generateKey($type, ...$keyComponents);
        
        if ($expiry === null) {
            $expiry = self::CACHE_EXPIRY;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Setting cache - Key: {$key}, Type: {$type}, Expiry: {$expiry}s");
        }
        
        return wp_cache_set($key, $value, self::CACHE_GROUP, $expiry);
    }

    /**
     * Delete value from cache
     */
    public function delete(string $type, ...$keyComponents): bool {
        $key = $this->generateKey($type, ...$keyComponents);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Deleting cache - Key: {$key}, Type: {$type}");
        }
        
        return wp_cache_delete($key, self::CACHE_GROUP);
    }

    /**
     * Check if key exists in cache
     */
    public function exists(string $type, ...$keyComponents): bool {
        $key = $this->generateKey($type, ...$keyComponents);
        return wp_cache_get($key, self::CACHE_GROUP) !== false;
    }

    /**
     * Example method for DataTable caching
     */
    public function getDataTableCache(int $userId, int $start, int $length, string $search, string $orderColumn, string $orderDir) {
        return $this->get('datatable', 
            (string)$userId,
            (string)$start,
            (string)$length, 
            md5($search),
            $orderColumn,
            $orderDir
        );
    }

    /**
     * Example method for setting DataTable cache
     */
    public function setDataTableCache(int $userId, int $start, int $length, string $search, string $orderColumn, string $orderDir, $data) {
        return $this->set('datatable',
            $data,
            2 * MINUTE_IN_SECONDS,
            (string)$userId,
            (string)$start,
            (string)$length,
            md5($search),
            $orderColumn,
            $orderDir
        );
    }

    /**
     * Clear all caches in group
     */
    public function clearAll(): bool {
        return wp_cache_delete_group(self::CACHE_GROUP);
    }
}
