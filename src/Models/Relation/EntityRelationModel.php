<?php
/**
 * Entity Relation Model
 *
 * Generic model for querying customer relations across different entity types.
 * Supports configuration-based queries for agency, company, branch, and any custom entity.
 *
 * @package WPCustomer\Models\Relation
 * @since 1.0.12
 */

namespace WPCustomer\Models\Relation;

defined('ABSPATH') || exit;

/**
 * EntityRelationModel Class
 *
 * Provides generic query methods for customer-entity relations with:
 * - Configuration-based entity support
 * - User access filtering (Platform staff vs Customer employee)
 * - WordPress object caching
 * - Prepared statements for security
 *
 * @since 1.0.12
 */
class EntityRelationModel {

    /**
     * Entity relation configurations
     *
     * @var array
     */
    private $configs = [];

    /**
     * Global database instance
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * Default cache TTL in seconds
     *
     * @var int
     */
    private const DEFAULT_CACHE_TTL = 3600;

    /**
     * Default cache group
     *
     * @var string
     */
    private const DEFAULT_CACHE_GROUP = 'wp_customer_entity_relations';

    /**
     * Constructor
     *
     * Loads entity relation configurations via filter hook.
     *
     * @since 1.0.12
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->load_configs();
    }

    /**
     * Load entity relation configurations
     *
     * Configurations are registered via filter hook by integration classes.
     * Each entity type must provide bridge table and column mappings.
     *
     * @since 1.0.12
     */
    private function load_configs(): void {
        /**
         * Filter: wp_customer_entity_relation_configs
         *
         * Register entity relation configurations.
         *
         * @param array $configs Entity configurations
         * @return array Modified configurations
         *
         * @since 1.0.12
         */
        $this->configs = apply_filters('wp_customer_entity_relation_configs', []);
    }

    /**
     * Get configuration for specific entity type
     *
     * @param string $entity_type Entity type (e.g., 'agency', 'company')
     * @return array|null Configuration array or null if not registered
     *
     * @since 1.0.12
     */
    private function get_config(string $entity_type): ?array {
        return $this->configs[$entity_type] ?? null;
    }

    /**
     * Get customer count for specific entity
     *
     * Returns the number of customers related to a specific entity,
     * optionally filtered by user access permissions.
     *
     * @param string   $entity_type Entity type ('agency', 'company', etc.)
     * @param int      $entity_id   Entity ID
     * @param int|null $user_id     User ID for access filtering (optional)
     * @return int Customer count
     * @throws \InvalidArgumentException If entity type not registered
     *
     * @since 1.0.12
     *
     * @example
     * ```php
     * $model = new EntityRelationModel();
     * $count = $model->get_customer_count_for_entity('agency', 123);
     * // Returns: 5
     * ```
     */
    public function get_customer_count_for_entity(string $entity_type, int $entity_id, ?int $user_id = null): int {
        // Validate entity type
        $config = $this->get_config($entity_type);
        if (!$config) {
            throw new \InvalidArgumentException("Entity type '{$entity_type}' is not registered.");
        }

        // Check cache
        $cache_key = $this->get_cache_key('count', $entity_type, $entity_id, $user_id);
        $cache_group = $config['cache_group'] ?? self::DEFAULT_CACHE_GROUP;

        $cached = wp_cache_get($cache_key, $cache_group);
        if (false !== $cached) {
            return (int) $cached;
        }

        // Build query
        $bridge_table = $this->wpdb->prefix . $config['bridge_table'];
        $entity_column = $config['entity_column'];
        $customer_column = $config['customer_column'];

        // Base query
        $sql = "SELECT COUNT(DISTINCT b.{$customer_column})
                FROM {$bridge_table} b
                WHERE b.{$entity_column} = %d";

        $params = [$entity_id];

        // Apply user access filter if enabled
        if (!empty($config['access_filter']) && $user_id) {
            if (!$this->is_platform_staff($user_id)) {
                // Customer employee - filter by accessible customers
                $accessible_customer_ids = $this->get_accessible_customer_ids_for_user($user_id);

                if (empty($accessible_customer_ids)) {
                    return 0; // No accessible customers
                }

                $placeholders = implode(',', array_fill(0, count($accessible_customer_ids), '%d'));
                $sql .= " AND b.{$customer_column} IN ($placeholders)";
                $params = array_merge($params, $accessible_customer_ids);
            }
        }

        // Execute query
        $count = (int) $this->wpdb->get_var($this->wpdb->prepare($sql, $params));

        // Cache result
        $cache_ttl = $config['cache_ttl'] ?? self::DEFAULT_CACHE_TTL;
        wp_cache_set($cache_key, $count, $cache_group, $cache_ttl);

        return $count;
    }

    /**
     * Get accessible entity IDs for user
     *
     * Returns array of entity IDs that the user has access to.
     * Platform staff see all entities, customer employees see only related entities.
     *
     * @param string   $entity_type Entity type
     * @param int|null $user_id     User ID (null = current user)
     * @return array Array of entity IDs
     * @throws \InvalidArgumentException If entity type not registered
     *
     * @since 1.0.12
     *
     * @example
     * ```php
     * $model = new EntityRelationModel();
     * $accessible_ids = $model->get_accessible_entity_ids('agency', 456);
     * // Returns: [1, 5, 12] (agency IDs user can access)
     * ```
     */
    public function get_accessible_entity_ids(string $entity_type, ?int $user_id = null): array {
        // Validate entity type
        $config = $this->get_config($entity_type);
        if (!$config) {
            throw new \InvalidArgumentException("Entity type '{$entity_type}' is not registered.");
        }

        // Use current user if not specified
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // Platform staff see all
        if ($this->is_platform_staff($user_id)) {
            return []; // Empty array means "no filter" (see all)
        }

        // Check cache
        $cache_key = $this->get_cache_key('accessible_ids', $entity_type, 0, $user_id);
        $cache_group = $config['cache_group'] ?? self::DEFAULT_CACHE_GROUP;

        $cached = wp_cache_get($cache_key, $cache_group);
        if (false !== $cached) {
            return $cached;
        }

        // Get accessible customer IDs first
        $accessible_customer_ids = $this->get_accessible_customer_ids_for_user($user_id);

        if (empty($accessible_customer_ids)) {
            return []; // No accessible customers = no accessible entities
        }

        // Query entity IDs related to accessible customers
        $bridge_table = $this->wpdb->prefix . $config['bridge_table'];
        $entity_column = $config['entity_column'];
        $customer_column = $config['customer_column'];

        $placeholders = implode(',', array_fill(0, count($accessible_customer_ids), '%d'));

        $sql = "SELECT DISTINCT b.{$entity_column}
                FROM {$bridge_table} b
                WHERE b.{$customer_column} IN ($placeholders)";

        $entity_ids = $this->wpdb->get_col($this->wpdb->prepare($sql, $accessible_customer_ids));
        $entity_ids = array_map('intval', $entity_ids);

        // Cache result
        $cache_ttl = $config['cache_ttl'] ?? self::DEFAULT_CACHE_TTL;
        wp_cache_set($cache_key, $entity_ids, $cache_group, $cache_ttl);

        return $entity_ids;
    }

    /**
     * Get accessible customer IDs for user
     *
     * Returns customer IDs that the user has access to based on
     * customer_employees table relationship.
     *
     * @param int $user_id User ID
     * @return array Array of customer IDs
     *
     * @since 1.0.12
     */
    private function get_accessible_customer_ids_for_user(int $user_id): array {
        $table = $this->wpdb->prefix . 'app_customer_employees';

        $sql = "SELECT DISTINCT customer_id
                FROM {$table}
                WHERE user_id = %d";

        $customer_ids = $this->wpdb->get_col($this->wpdb->prepare($sql, $user_id));

        return array_map('intval', $customer_ids);
    }

    /**
     * Invalidate cache for specific entity
     *
     * Removes cached data for a specific entity and user combination.
     * Call this when entity relations change (customer added/removed).
     *
     * @param string   $entity_type Entity type
     * @param int      $entity_id   Entity ID
     * @param int|null $user_id     User ID (optional, invalidates all if null)
     *
     * @since 1.0.12
     *
     * @example
     * ```php
     * // After adding customer to agency
     * $model->invalidate_cache('agency', 123);
     * ```
     */
    public function invalidate_cache(string $entity_type, int $entity_id, ?int $user_id = null): void {
        $config = $this->get_config($entity_type);
        if (!$config) {
            return;
        }

        $cache_group = $config['cache_group'] ?? self::DEFAULT_CACHE_GROUP;

        if ($user_id) {
            // Invalidate specific user's cache
            $cache_key_count = $this->get_cache_key('count', $entity_type, $entity_id, $user_id);
            $cache_key_ids = $this->get_cache_key('accessible_ids', $entity_type, 0, $user_id);

            wp_cache_delete($cache_key_count, $cache_group);
            wp_cache_delete($cache_key_ids, $cache_group);
        } else {
            // Invalidate all cache for this entity
            // Note: WordPress object cache doesn't support wildcard delete,
            // so we flush the entire group
            wp_cache_flush();
        }
    }

    /**
     * Get cache key
     *
     * Generates consistent cache key for different query types.
     *
     * @param string   $type        Cache type ('count', 'accessible_ids')
     * @param string   $entity_type Entity type
     * @param int      $entity_id   Entity ID
     * @param int|null $user_id     User ID (optional)
     * @return string Cache key
     *
     * @since 1.0.12
     */
    private function get_cache_key(string $type, string $entity_type, int $entity_id, ?int $user_id = null): string {
        $parts = [
            'entity_relation',
            $type,
            $entity_type,
            $entity_id
        ];

        if ($user_id) {
            $parts[] = 'user';
            $parts[] = $user_id;
        }

        return implode('_', $parts);
    }

    /**
     * Check if user is platform staff
     *
     * Platform staff have access to all data without filtering.
     * Checks for 'admin_platform' capability.
     *
     * @param int $user_id User ID
     * @return bool True if platform staff, false otherwise
     *
     * @since 1.0.12
     */
    private function is_platform_staff(int $user_id): bool {
        // Check platform staff table
        $table = $this->wpdb->prefix . 'app_platform_staff';

        $sql = "SELECT COUNT(*)
                FROM {$table}
                WHERE user_id = %d";

        $exists = (bool) $this->wpdb->get_var($this->wpdb->prepare($sql, $user_id));

        // Also check user capability
        $user = get_user_by('id', $user_id);
        if ($user && $user->has_cap('admin_platform')) {
            return true;
        }

        return $exists;
    }

    /**
     * Get branch count for specific entity
     *
     * Returns the number of branches related to a specific entity.
     *
     * @param string   $entity_type Entity type
     * @param int      $entity_id   Entity ID
     * @param int|null $user_id     User ID for access filtering (optional)
     * @return int Branch count
     * @throws \InvalidArgumentException If entity type not registered
     *
     * @since 1.0.12
     *
     * @example
     * ```php
     * $model = new EntityRelationModel();
     * $count = $model->get_branch_count_for_entity('agency', 123);
     * // Returns: 3
     * ```
     */
    public function get_branch_count_for_entity(string $entity_type, int $entity_id, ?int $user_id = null): int {
        // Validate entity type
        $config = $this->get_config($entity_type);
        if (!$config) {
            throw new \InvalidArgumentException("Entity type '{$entity_type}' is not registered.");
        }

        // Check cache
        $cache_key = $this->get_cache_key('branch_count', $entity_type, $entity_id, $user_id);
        $cache_group = $config['cache_group'] ?? self::DEFAULT_CACHE_GROUP;

        $cached = wp_cache_get($cache_key, $cache_group);
        if (false !== $cached) {
            return (int) $cached;
        }

        // Build query
        $bridge_table = $this->wpdb->prefix . $config['bridge_table'];
        $entity_column = $config['entity_column'];

        // Base query - count branch IDs
        $sql = "SELECT COUNT(DISTINCT b.id)
                FROM {$bridge_table} b
                WHERE b.{$entity_column} = %d";

        $params = [$entity_id];

        // Apply user access filter if enabled
        if (!empty($config['access_filter']) && $user_id) {
            if (!$this->is_platform_staff($user_id)) {
                // Customer employee - filter by accessible customers
                $accessible_customer_ids = $this->get_accessible_customer_ids_for_user($user_id);

                if (empty($accessible_customer_ids)) {
                    return 0;
                }

                $customer_column = $config['customer_column'];
                $placeholders = implode(',', array_fill(0, count($accessible_customer_ids), '%d'));
                $sql .= " AND b.{$customer_column} IN ($placeholders)";
                $params = array_merge($params, $accessible_customer_ids);
            }
        }

        // Execute query
        $count = (int) $this->wpdb->get_var($this->wpdb->prepare($sql, $params));

        // Cache result
        $cache_ttl = $config['cache_ttl'] ?? self::DEFAULT_CACHE_TTL;
        wp_cache_set($cache_key, $count, $cache_group, $cache_ttl);

        return $count;
    }
}
