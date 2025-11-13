<?php
/**
 * Membership Features Model
 *
 * @package     WP_Customer
 * @subpackage  Models/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Settings/MembershipFeaturesModel.php
 *
 * Description: CRUD model untuk Membership Features entity.
 *              Extends AbstractCrudModel dari wp-app-core.
 *              Handles create, read, update, delete operations.
 *              All CRUD operations INHERITED from AbstractCrudModel.
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (Task-2204)
 * - Initial implementation
 * - Extends AbstractCrudModel
 * - Implements 7 abstract methods
 * - Custom methods: getActiveGroupsAndFeatures(), existsByFieldName()
 */

namespace WPCustomer\Models\Settings;

use WPAppCore\Models\Abstract\AbstractCrudModel;
use WPCustomer\Cache\MembershipFeaturesCacheManager;

defined('ABSPATH') || exit;

class MembershipFeaturesModel extends AbstractCrudModel {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(MembershipFeaturesCacheManager::getInstance());
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
        return $wpdb->prefix . 'app_customer_membership_features';
    }

    /**
     * Get cache method name prefix
     *
     * @return string
     */
    protected function getCacheKey(): string {
        return 'MembershipFeature';
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
            'field_name',
            'group_id',
            'metadata',
            'settings',
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
            'field_name' => $data['field_name'],
            'group_id' => $data['group_id'],
            'metadata' => is_string($data['metadata']) ? $data['metadata'] : json_encode($data['metadata']),
            'settings' => is_string($data['settings']) ? $data['settings'] : json_encode($data['settings']),
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
            'field_name' => '%s',
            'group_id' => '%d',
            'metadata' => '%s',
            'settings' => '%s',
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
     * Get all features grouped by their groups
     *
     * @return array Grouped features with group information
     */
    public function getActiveGroupsAndFeatures(): array {
        // Check cache first
        $cached = $this->cache->get('active_groups_and_features');
        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;
        $table_features = $this->getTableName();
        $table_groups = $wpdb->prefix . 'app_customer_membership_feature_groups';

        $results = $wpdb->get_results("
            SELECT
                g.id as group_id,
                g.name as group_name,
                g.slug as group_slug,
                g.description as group_description,
                g.sort_order as group_sort_order,
                f.id as feature_id,
                f.field_name,
                f.metadata,
                f.settings,
                f.sort_order as feature_sort_order
            FROM {$table_groups} g
            LEFT JOIN {$table_features} f ON g.id = f.group_id AND f.status = 'active'
            WHERE g.status = 'active'
            ORDER BY g.sort_order ASC, f.sort_order ASC
        ", ARRAY_A);

        // Group by group_id
        $grouped = [];
        foreach ($results as $row) {
            $group_id = $row['group_id'];

            if (!isset($grouped[$group_id])) {
                $grouped[$group_id] = [
                    'id' => $group_id,
                    'name' => $row['group_name'],
                    'slug' => $row['group_slug'],
                    'description' => $row['group_description'],
                    'sort_order' => $row['group_sort_order'],
                    'features' => []
                ];
            }

            if ($row['feature_id']) {
                $grouped[$group_id]['features'][] = [
                    'id' => $row['feature_id'],
                    'field_name' => $row['field_name'],
                    'metadata' => json_decode($row['metadata'], true),
                    'settings' => json_decode($row['settings'], true),
                    'sort_order' => $row['feature_sort_order']
                ];
            }
        }

        // Cache for 12 hours
        $this->cache->set('active_groups_and_features', $grouped, 12 * HOUR_IN_SECONDS);

        return $grouped;
    }

    /**
     * Check if field_name exists (untuk validation)
     *
     * @param string $field_name Field name
     * @param int|null $excludeId Exclude ID (for update)
     * @return bool
     */
    public function existsByFieldName(string $field_name, ?int $excludeId = null): bool {
        global $wpdb;
        $table = $this->getTableName();

        $sql = "SELECT EXISTS (SELECT 1 FROM {$table} WHERE field_name = %s";
        $params = [$field_name];

        if ($excludeId) {
            $sql .= " AND id != %d";
            $params[] = $excludeId;
        }

        $sql .= ") as result";
        return (bool) $wpdb->get_var($wpdb->prepare($sql, $params));
    }

    /**
     * Get all feature groups
     *
     * @return array List of groups
     */
    public function getFeatureGroups(): array {
        // Check cache first
        $cached = $this->cache->get('membership_feature_groups');
        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;
        $table_groups = $wpdb->prefix . 'app_customer_membership_feature_groups';

        $groups = $wpdb->get_results("
            SELECT id, name, slug, description, sort_order
            FROM {$table_groups}
            WHERE status = 'active'
            ORDER BY sort_order ASC
        ", ARRAY_A);

        // Cache for 12 hours
        $this->cache->set('membership_feature_groups', $groups, 12 * HOUR_IN_SECONDS);

        return $groups ?: [];
    }

    /**
     * Get all features by group
     *
     * @param int|null $group_id Optional group ID filter
     * @return array Features grouped by group_id
     */
    public function getFeaturesByGroup(?int $group_id = null): array {
        global $wpdb;
        $table = $this->getTableName();

        $sql = "
            SELECT *
            FROM {$table}
            WHERE status = 'active'
        ";

        if ($group_id) {
            $sql .= $wpdb->prepare(" AND group_id = %d", $group_id);
        }

        $sql .= " ORDER BY sort_order ASC";

        $features = $wpdb->get_results($sql, ARRAY_A);

        // Decode JSON fields
        foreach ($features as &$feature) {
            $feature['metadata'] = json_decode($feature['metadata'], true);
            $feature['settings'] = json_decode($feature['settings'], true);
        }

        return $features ?: [];
    }

    /**
     * Override update to handle JSON encoding
     *
     * @param int $id Entity ID
     * @param array $data Update data
     * @return bool Success status
     */
    public function update(int $id, array $data): bool {
        // Encode JSON fields if they're arrays
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $data['metadata'] = json_encode($data['metadata']);
        }
        if (isset($data['settings']) && is_array($data['settings'])) {
            $data['settings'] = json_encode($data['settings']);
        }

        // Add updated_at timestamp
        $data['updated_at'] = current_time('mysql');

        // Call parent update
        return parent::update($id, $data);
    }
}
