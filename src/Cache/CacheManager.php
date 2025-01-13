<?php
/**
 * Cache Management Class
 *
 * @package     WP_Customer
 * @subpackage  Cache
 * @version     1.0.0 
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Cache/CacheManager.php
 */

namespace WPCustomer\Cache;

class CacheManager {
    private const CACHE_GROUP = 'wp_customer';
    private const CACHE_EXPIRY = 12 * HOUR_IN_SECONDS;
    
    // Cache keys
    private const KEY_CUSTOMER = 'customer_';
    private const KEY_CUSTOMER_LIST = 'customer_list';

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
        wp_cache_delete(self::KEY_CUSTOMER_LIST, self::CACHE_GROUP);
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
}