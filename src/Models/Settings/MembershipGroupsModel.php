<?php
/**
 * Membership Groups Model
 *
 * @package     WP_Customer
 * @subpackage  Models/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Settings/MembershipGroupsModel.php
 *
 * Description: CRUD model untuk Membership Groups entity.
 *              Extends AbstractCrudModel dari wp-app-core.
 *              Handles create, read, update, delete operations.
 *              All CRUD operations INHERITED from AbstractCrudModel.
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (Task-2204)
 * - Initial implementation
 * - Extends AbstractCrudModel
 * - Implements 7 abstract methods
 * - Custom methods: existsBySlug(), getAllActiveGroups()
 */

namespace WPCustomer\Models\Settings;

use WPAppCore\Models\Abstract\AbstractCrudModel;
use WPCustomer\Cache\MembershipGroupsCacheManager;

defined('ABSPATH') || exit;

class MembershipGroupsModel extends AbstractCrudModel {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(MembershipGroupsCacheManager::getInstance());
    }

    // ========================================
    // IMPLEMENT ABSTRACT METHODS (7 required)
    // ========================================

    /**
     * Get database table name
     *
     * @return string
     */
    protected function getTableName(): string {
        global $wpdb;
        return $wpdb->prefix . 'app_customer_membership_feature_groups';
    }

    /**
     * Get cache method name prefix
     *
     * @return string
     */
    protected function getCacheKey(): string {
        return 'MembershipGroup';
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
     * Get plugin prefix for hooks
     *
     * @return string
     */
    protected function getPluginPrefix(): string {
        return 'wp_customer';
    }

    /**
     * Get allowed fields for update operations
     *
     * @return array
     */
    protected function getAllowedFields(): array {
        return [
            'name',
            'slug',
            'capability_group',
            'description',
            'sort_order',
            'status'
        ];
    }

    /**
     * Prepare insert data from request
     *
     * @param array $data Raw request data
     * @return array Prepared insert data
     */
    protected function prepareInsertData(array $data): array {
        return [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'capability_group' => $data['capability_group'] ?? 'features',
            'description' => $data['description'] ?? '',
            'sort_order' => $data['sort_order'] ?? 0,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'status' => $data['status'] ?? 'active'
        ];
    }

    /**
     * Get format map for wpdb operations
     *
     * @return array
     */
    protected function getFormatMap(): array {
        return [
            'id' => '%d',
            'name' => '%s',
            'slug' => '%s',
            'capability_group' => '%s',
            'description' => '%s',
            'sort_order' => '%d',
            'created_by' => '%d',
            'created_at' => '%s',
            'updated_at' => '%s',
            'status' => '%s'
        ];
    }

    // ========================================
    // CUSTOM METHODS (Entity-specific)
    // ========================================

    /**
     * Check if slug exists (untuk validation)
     *
     * @param string $slug Group slug
     * @param int|null $excludeId Exclude ID (for update)
     * @return bool
     */
    public function existsBySlug(string $slug, ?int $excludeId = null): bool {
        global $wpdb;
        $table = $this->getTableName();

        $sql = "SELECT EXISTS (SELECT 1 FROM {$table} WHERE slug = %s";
        $params = [$slug];

        if ($excludeId) {
            $sql .= " AND id != %d";
            $params[] = $excludeId;
        }

        $sql .= ") as result";
        return (bool) $wpdb->get_var($wpdb->prepare($sql, $params));
    }

    /**
     * Get group by slug
     *
     * @param string $slug Group slug
     * @return object|null Group object or NULL
     */
    public function findBySlug(string $slug): ?object {
        // Check cache first
        $cached = $this->cache->getMembershipGroupBySlug($slug);
        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;
        $table = $this->getTableName();

        $group = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE slug = %s LIMIT 1",
            $slug
        ));

        if ($group) {
            // Cache it
            $this->cache->setMembershipGroupBySlug($slug, $group);
        }

        return $group;
    }

    /**
     * Get all active groups
     *
     * @return array List of active groups ordered by sort_order
     */
    public function getAllActiveGroups(): array {
        // Check cache first
        $cached = $this->cache->get('membership_groups_active');
        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;
        $table = $this->getTableName();

        $groups = $wpdb->get_results("
            SELECT *
            FROM {$table}
            WHERE status = 'active'
            ORDER BY sort_order ASC, name ASC
        ", ARRAY_A);

        // Cache for 12 hours
        $this->cache->set('membership_groups_active', $groups, 12 * HOUR_IN_SECONDS);

        return $groups ?: [];
    }

    /**
     * Get groups by capability_group
     *
     * @param string $capability_group Capability group (features, limits, notifications)
     * @return array List of groups
     */
    public function getGroupsByCapabilityGroup(string $capability_group): array {
        global $wpdb;
        $table = $this->getTableName();

        $groups = $wpdb->get_results($wpdb->prepare("
            SELECT *
            FROM {$table}
            WHERE capability_group = %s AND status = 'active'
            ORDER BY sort_order ASC, name ASC
        ", $capability_group), ARRAY_A);

        return $groups ?: [];
    }

    /**
     * Count features in a group
     *
     * @param int $group_id Group ID
     * @return int Number of active features
     */
    public function countFeatures(int $group_id): int {
        global $wpdb;
        $table_features = $wpdb->prefix . 'app_customer_membership_features';

        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$table_features}
            WHERE group_id = %d AND status = 'active'
        ", $group_id));
    }

    /**
     * Override update to add updated_at timestamp
     *
     * @param int $id Entity ID
     * @param array $data Update data
     * @return bool Success status
     */
    public function update(int $id, array $data): bool {
        // Add updated_at timestamp
        $data['updated_at'] = current_time('mysql');

        // Get old slug for cache invalidation
        $old_group = $this->find($id);
        $old_slug = $old_group->slug ?? null;

        // Call parent update
        $result = parent::update($id, $data);

        // Clear slug cache if slug changed
        if ($result && $old_slug && isset($data['slug']) && $data['slug'] !== $old_slug) {
            $this->cache->delete('membership_group_by_slug', $old_slug);
        }

        return $result;
    }
}
