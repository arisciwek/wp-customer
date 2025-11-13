<?php
/**
 * Membership Features Cache Manager
 *
 * @package     WP_Customer
 * @subpackage  Cache
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Cache/MembershipFeaturesCacheManager.php
 *
 * Description: Cache manager untuk Membership Features entity.
 *              Extends AbstractCacheManager dari wp-app-core.
 *              Handles caching untuk membership features data, groups, dan DataTable.
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

class MembershipFeaturesCacheManager extends AbstractCacheManager {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return MembershipFeaturesCacheManager
     */
    public static function getInstance(): MembershipFeaturesCacheManager {
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
        return 'membership_feature';
    }

    /**
     * Get cache keys mapping
     *
     * @return array
     */
    protected function getCacheKeys(): array {
        return [
            'membership_feature' => 'membership_feature',
            'membership_feature_list' => 'membership_feature_list',
            'membership_feature_group' => 'membership_feature_group',
            'membership_feature_groups' => 'membership_feature_groups',
            'membership_features_by_group' => 'membership_features_by_group',
            'active_groups_and_features' => 'active_groups_and_features',
            'field_name_exists' => 'field_name_exists'
        ];
    }

    /**
     * Get known cache types for fallback clearing
     *
     * @return array
     */
    protected function getKnownCacheTypes(): array {
        return [
            'membership_feature',
            'membership_feature_list',
            'membership_feature_group',
            'membership_feature_groups',
            'membership_features_by_group',
            'active_groups_and_features',
            'field_name_exists',
            'datatable'
        ];
    }

    // ========================================
    // CUSTOM CACHE METHODS (Entity-specific)
    // ========================================

    /**
     * Get membership feature from cache
     *
     * @param int $id Feature ID
     * @return object|false Feature object or FALSE if not found (cache miss)
     */
    public function getMembershipFeature(int $id): object|false {
        return $this->get('membership_feature', $id);
    }

    /**
     * Set membership feature in cache
     *
     * @param int $id Feature ID
     * @param object $feature Feature data
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setMembershipFeature(int $id, object $feature, ?int $expiry = null): bool {
        return $this->set('membership_feature', $feature, $expiry, $id);
    }

    /**
     * Get membership feature group from cache
     *
     * @param int $id Group ID
     * @return object|false Group object or FALSE if not found
     */
    public function getMembershipFeatureGroup(int $id): object|false {
        return $this->get('membership_feature_group', $id);
    }

    /**
     * Set membership feature group in cache
     *
     * @param int $id Group ID
     * @param object $group Group data
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setMembershipFeatureGroup(int $id, object $group, ?int $expiry = null): bool {
        return $this->set('membership_feature_group', $group, $expiry, $id);
    }

    /**
     * Invalidate membership feature cache
     *
     * Clears all cache related to a specific feature:
     * - Feature entity
     * - DataTable cache
     * - Groups and features cache
     * - List cache
     *
     * @param int $id Feature ID
     * @return void
     */
    public function invalidateMembershipFeatureCache(int $id): void {
        // Clear feature entity cache
        $this->delete('membership_feature', $id);

        // Clear DataTable cache
        $this->invalidateDataTableCache('membership_feature_list');

        // Clear grouped features cache
        $this->clearCache('membership_features_by_group');
        $this->clearCache('active_groups_and_features');

        // Clear list cache
        $this->clearCache('membership_feature_list');

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[MembershipFeaturesCacheManager] Invalidated all cache for feature {$id}");
        }
    }

    /**
     * Invalidate membership feature group cache
     *
     * Clears all cache related to a specific group:
     * - Group entity
     * - DataTable cache
     * - Groups and features cache
     *
     * @param int $id Group ID
     * @return void
     */
    public function invalidateMembershipFeatureGroupCache(int $id): void {
        // Clear group entity cache
        $this->delete('membership_feature_group', $id);

        // Clear groups cache
        $this->clearCache('membership_feature_groups');
        $this->clearCache('active_groups_and_features');

        // Clear features by group cache
        $this->clearCache('membership_features_by_group');

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[MembershipFeaturesCacheManager] Invalidated all cache for group {$id}");
        }
    }

    /**
     * Invalidate ALL membership feature caches
     *
     * Clears all membership feature-related cache in the group.
     * Use with caution - this clears everything.
     *
     * @return bool
     */
    public function invalidateAllMembershipFeatureCache(): bool {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[MembershipFeaturesCacheManager] Invalidating ALL membership feature caches");
        }

        return $this->clearAll();
    }
}
