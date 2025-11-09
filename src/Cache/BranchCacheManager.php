<?php
/**
 * Branch Cache Manager
 *
 * @package     WP_Customer
 * @subpackage  Cache
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Cache/BranchCacheManager.php
 *
 * Description: Cache manager untuk Branch entity.
 *              Extends AbstractCacheManager dari wp-app-core.
 *              Handles caching untuk branch data, relations, dan DataTable.
 *
 * Separation of Concerns:
 * - BranchCacheManager: Branch-specific caching only
 * - CustomerCacheManager: Customer-wide caching (fallback)
 * - Both extend AbstractCacheManager
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2193: Cache Refactoring)
 * - Initial implementation
 * - Extends AbstractCacheManager
 * - Implements 5 abstract methods
 * - Cache expiry: 2 hours (default)
 * - Cache group: wp_customer_branch
 * - Branch-specific cache keys
 */

namespace WPCustomer\Cache;

use WPAppCore\Cache\Abstract\AbstractCacheManager;

defined('ABSPATH') || exit;

class BranchCacheManager extends AbstractCacheManager {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return BranchCacheManager
     */
    public static function getInstance(): BranchCacheManager {
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
        return 'wp_customer_branch';
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
        return 'branch';
    }

    /**
     * Get cache keys mapping
     *
     * @return array
     */
    protected function getCacheKeys(): array {
        return [
            'branch' => 'branch',
            'branch_list' => 'branch_list',
            'branch_by_customer' => 'branch_by_customer',
            'branch_pusat' => 'branch_pusat',
            'branch_count' => 'branch_count',
            'branch_relation' => 'branch_relation',
            'branch_ids' => 'branch_ids',
            'code_exists' => 'code_exists',
            'name_exists' => 'name_exists',
            'inspector_assignment' => 'inspector_assignment'
        ];
    }

    /**
     * Get known cache types for fallback clearing
     *
     * @return array
     */
    protected function getKnownCacheTypes(): array {
        return [
            'branch',
            'branch_list',
            'branch_by_customer',
            'branch_pusat',
            'branch_count',
            'branch_relation',
            'branch_ids',
            'code_exists',
            'name_exists',
            'inspector_assignment',
            'datatable'
        ];
    }

    // ========================================
    // CUSTOM CACHE METHODS (Entity-specific)
    // ========================================

    /**
     * Get branch from cache
     *
     * @param int $id Branch ID
     * @return object|false Branch object or FALSE if not found (cache miss)
     */
    public function getBranch(int $id): object|false {
        return $this->get('branch', $id);
    }

    /**
     * Set branch in cache
     *
     * @param int $id Branch ID
     * @param object $branch Branch data
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setBranch(int $id, object $branch, ?int $expiry = null): bool {
        return $this->set('branch', $branch, $expiry, $id);
    }

    /**
     * Get branches by customer from cache
     *
     * @param int $customer_id Customer ID
     * @return array|false Array of branches or FALSE if not found
     */
    public function getBranchesByCustomer(int $customer_id): array|false {
        return $this->get('branch_by_customer', $customer_id);
    }

    /**
     * Set branches by customer in cache
     *
     * @param int $customer_id Customer ID
     * @param array $branches Array of branch objects
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setBranchesByCustomer(int $customer_id, array $branches, ?int $expiry = null): bool {
        return $this->set('branch_by_customer', $branches, $expiry, $customer_id);
    }

    /**
     * Get pusat branch by customer from cache
     *
     * @param int $customer_id Customer ID
     * @return object|false Pusat branch or FALSE if not found
     */
    public function getPusatBranch(int $customer_id): object|false {
        return $this->get('branch_pusat', $customer_id);
    }

    /**
     * Set pusat branch in cache
     *
     * @param int $customer_id Customer ID
     * @param object $branch Pusat branch object
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setPusatBranch(int $customer_id, object $branch, ?int $expiry = null): bool {
        return $this->set('branch_pusat', $branch, $expiry, $customer_id);
    }

    /**
     * Invalidate branch cache
     *
     * Clears all cache related to a specific branch:
     * - Branch entity
     * - DataTable cache
     * - Relation cache
     * - Customer's branch list
     *
     * @param int $id Branch ID
     * @param int|null $customer_id Optional customer ID for targeted clearing
     * @return void
     */
    public function invalidateBranchCache(int $id, ?int $customer_id = null): void {
        // Clear branch entity cache
        $this->delete('branch', $id);

        // Clear relation cache for this branch
        $this->clearCache('branch_relation');

        // Clear DataTable cache
        $this->invalidateDataTableCache('branch_list');

        // Clear customer's branch list if customer_id provided
        if ($customer_id) {
            $this->delete('branch_by_customer', $customer_id);
            $this->delete('branch_pusat', $customer_id);
            $this->delete('branch_count', $customer_id);
        }

        // Clear branch IDs cache
        $this->delete('branch_ids', 'active');

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[BranchCacheManager] Invalidated all cache for branch {$id}");
        }
    }

    /**
     * Invalidate all branches cache for a customer
     *
     * @param int $customer_id Customer ID
     * @return void
     */
    public function invalidateCustomerBranches(int $customer_id): void {
        $this->delete('branch_by_customer', $customer_id);
        $this->delete('branch_pusat', $customer_id);
        $this->delete('branch_count', $customer_id);
        $this->invalidateDataTableCache('branch_list');

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[BranchCacheManager] Invalidated all branches cache for customer {$customer_id}");
        }
    }
}
