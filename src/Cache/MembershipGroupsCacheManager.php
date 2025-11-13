<?php
/**
 * Membership Groups Cache Manager
 *
 * @package     WP_Customer
 * @subpackage  Cache
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Cache/MembershipGroupsCacheManager.php
 *
 * Description: Cache manager untuk Membership Groups entity.
 *              Extends AbstractCacheManager dari wp-app-core.
 *              Handles caching untuk membership groups data dan DataTable.
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (Task-2204)
 * - Initial implementation
 * - Extends AbstractCacheManager
 * - Implements 5 abstract methods
 * - Cache expiry: 12 hours (default)
 * - Cache group: wp_customer
 */

namespace WPCustomer\Cache;

use WPAppCore\Cache\Abstract\AbstractCacheManager;

defined('ABSPATH') || exit;

class MembershipGroupsCacheManager extends AbstractCacheManager {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return MembershipGroupsCacheManager
     */
    public static function getInstance(): MembershipGroupsCacheManager {
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
        return 'membership_group';
    }

    /**
     * Get cache keys mapping
     *
     * @return array
     */
    protected function getCacheKeys(): array {
        return [
            'membership_group' => 'membership_group',
            'membership_group_list' => 'membership_group_list',
            'membership_groups_active' => 'membership_groups_active',
            'membership_group_by_slug' => 'membership_group_by_slug'
        ];
    }

    /**
     * Get known cache types for fallback clearing
     *
     * @return array
     */
    protected function getKnownCacheTypes(): array {
        return [
            'membership_group',
            'membership_group_list',
            'membership_groups_active',
            'membership_group_by_slug',
            'datatable'
        ];
    }

    // ========================================
    // CUSTOM CACHE METHODS (Entity-specific)
    // ========================================

    /**
     * Get membership group from cache
     *
     * @param int $id Group ID
     * @return object|false Group object or FALSE if not found (cache miss)
     */
    public function getMembershipGroup(int $id): object|false {
        return $this->get('membership_group', $id);
    }

    /**
     * Set membership group in cache
     *
     * @param int $id Group ID
     * @param object $group Group data
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setMembershipGroup(int $id, object $group, ?int $expiry = null): bool {
        return $this->set('membership_group', $group, $expiry, $id);
    }

    /**
     * Get membership group by slug from cache
     *
     * @param string $slug Group slug
     * @return object|false Group object or FALSE if not found
     */
    public function getMembershipGroupBySlug(string $slug): object|false {
        return $this->get('membership_group_by_slug', $slug);
    }

    /**
     * Set membership group by slug in cache
     *
     * @param string $slug Group slug
     * @param object $group Group data
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setMembershipGroupBySlug(string $slug, object $group, ?int $expiry = null): bool {
        return $this->set('membership_group_by_slug', $group, $expiry, $slug);
    }

    /**
     * Invalidate membership group cache
     *
     * Clears all cache related to a specific group:
     * - Group entity
     * - DataTable cache
     * - Active groups cache
     * - List cache
     * - Slug cache
     *
     * @param int $id Group ID
     * @param string|null $slug Optional group slug for clearing slug cache
     * @return void
     */
    public function invalidateMembershipGroupCache(int $id, ?string $slug = null): void {
        // Clear group entity cache
        $this->delete('membership_group', $id);

        // Clear slug cache if provided
        if ($slug) {
            $this->delete('membership_group_by_slug', $slug);
        }

        // Clear DataTable cache
        $this->invalidateDataTableCache('membership_group_list');

        // Clear active groups cache
        $this->clearCache('membership_groups_active');

        // Clear list cache
        $this->clearCache('membership_group_list');

        // Also invalidate related features cache
        $featuresCacheManager = MembershipFeaturesCacheManager::getInstance();
        $featuresCacheManager->invalidateMembershipFeatureGroupCache($id);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[MembershipGroupsCacheManager] Invalidated all cache for group {$id}");
        }
    }

    /**
     * Invalidate ALL membership group caches
     *
     * Clears all membership group-related cache in the group.
     * Use with caution - this clears everything.
     *
     * @return bool
     */
    public function invalidateAllMembershipGroupCache(): bool {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[MembershipGroupsCacheManager] Invalidating ALL membership group caches");
        }

        return $this->clearAll();
    }
}
