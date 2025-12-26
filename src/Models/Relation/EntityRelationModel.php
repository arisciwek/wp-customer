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
     * @var array|null
     */
    private $configs = null;

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
     * Initializes the model. Configs are loaded lazily on first access.
     *
     * @since 1.0.12
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
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
        // Lazy load configs on first access
        if ($this->configs === null) {
            $this->load_configs();
        }

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

        // Special handling for 'company' entity
        if ($entity_type === 'company') {
            // Check if user is agency user first
            if ($this->is_agency_user($user_id)) {
                // Agency users see companies in their province
                $entity_ids = $this->get_accessible_company_ids_for_agency_user($user_id);
            } else {
                // Customer users: role-based access
                // customer_admin sees all branches, customer_branch_admin sees only assigned branch
                $entity_ids = $this->get_accessible_branch_ids_for_user($user_id);
            }
        } else {
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
        }

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
     * Get accessible branch IDs for user
     *
     * Returns branch IDs that the user has access to based on role:
     * - customer_admin (owner): all branches in their customer
     * - customer_branch_admin: only their assigned branch
     *
     * @param int $user_id User ID
     * @return array Array of branch IDs
     *
     * @since 1.0.12
     */
    private function get_accessible_branch_ids_for_user(int $user_id): array {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return [];
        }

        // Check if user is customer_admin (owner) - can see all branches in customer
        if ($user->has_cap('customer_admin')) {
            // Get customer IDs for this user
            $accessible_customer_ids = $this->get_accessible_customer_ids_for_user($user_id);

            if (empty($accessible_customer_ids)) {
                return [];
            }

            // Return all branches in those customers
            $table = $this->wpdb->prefix . 'app_customer_branches';
            $placeholders = implode(',', array_fill(0, count($accessible_customer_ids), '%d'));

            $sql = "SELECT DISTINCT id
                    FROM {$table}
                    WHERE customer_id IN ($placeholders)";

            $branch_ids = $this->wpdb->get_col($this->wpdb->prepare($sql, $accessible_customer_ids));
            return array_map('intval', $branch_ids);
        }

        // customer_branch_admin or regular employee - only see assigned branches
        $table = $this->wpdb->prefix . 'app_customer_employees';

        $sql = "SELECT DISTINCT branch_id
                FROM {$table}
                WHERE user_id = %d";

        $branch_ids = $this->wpdb->get_col($this->wpdb->prepare($sql, $user_id));

        return array_map('intval', $branch_ids);
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

    /**
     * Check if user is agency user
     *
     * Check if user has any agency-related role.
     * Uses WP_Agency_Role_Manager for role list if available.
     *
     * @param int $user_id User ID
     * @return bool True if agency user, false otherwise
     *
     * @since 1.1.0
     */
    private function is_agency_user(int $user_id): bool {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }

        $user_roles = (array) $user->roles;

        // Get agency roles from WP_Agency_Role_Manager if available
        if (class_exists('WP_Agency_Role_Manager')) {
            $agency_roles = \WP_Agency_Role_Manager::getRoleSlugs();
        } else {
            // Fallback to hardcoded list if wp-agency not available
            $agency_roles = [
                'agency',
                'agency_employee',
                'agency_admin_dinas',
                'agency_admin_unit',
                'agency_pengawas',
                'agency_pengawas_spesialis',
                'agency_kepala_unit',
                'agency_kepala_seksi',
                'agency_kepala_bidang',
                'agency_kepala_dinas'
            ];
        }

        foreach ($user_roles as $role) {
            if (in_array($role, $agency_roles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get accessible company IDs for agency user
     *
     * Returns company (branch) IDs in the same province as the user's agency.
     *
     * @param int $user_id User ID
     * @return array Array of company IDs
     *
     * @since 1.1.0
     */
    private function get_accessible_company_ids_for_agency_user(int $user_id): array {
        // Get agency's province_id
        $province_id = $this->get_user_agency_province_id($user_id);

        if (!$province_id) {
            return []; // No province = no access
        }

        // Get all companies (branches) in this province
        $table = $this->wpdb->prefix . 'app_customer_branches';

        $sql = "SELECT DISTINCT id
                FROM {$table}
                WHERE province_id = %d
                AND status = 'active'";

        $company_ids = $this->wpdb->get_col($this->wpdb->prepare($sql, $province_id));

        return array_map('intval', $company_ids);
    }

    /**
     * Get user's agency province ID
     *
     * Logic:
     * 1. Get user's agency_id (from agency admin OR employee table)
     * 2. Get agency's province_id
     *
     * @param int $user_id User ID
     * @return int|null Province ID or null if not found
     *
     * @since 1.1.0
     */
    private function get_user_agency_province_id(int $user_id): ?int {
        // Get agency_id first
        $agency_id = $this->get_user_agency_id($user_id);

        if (!$agency_id) {
            return null;
        }

        // Get province_id from agency
        $table = $this->wpdb->prefix . 'app_agencies';

        $sql = "SELECT province_id
                FROM {$table}
                WHERE id = %d
                LIMIT 1";

        $province_id = $this->wpdb->get_var($this->wpdb->prepare($sql, $agency_id));

        return $province_id ? (int) $province_id : null;
    }

    /**
     * Get user's agency ID
     *
     * Logic:
     * 1. Check if user is agency admin (wp_app_agencies.user_id)
     * 2. If not, check if user is agency employee (wp_app_agency_employees.agency_id)
     *
     * @param int $user_id User ID
     * @return int|null Agency ID or null if not found
     *
     * @since 1.1.0
     */
    private function get_user_agency_id(int $user_id): ?int {
        // Check 1: Is user an agency admin?
        $table = $this->wpdb->prefix . 'app_agencies';

        $sql = "SELECT id
                FROM {$table}
                WHERE user_id = %d
                LIMIT 1";

        $agency_id = $this->wpdb->get_var($this->wpdb->prepare($sql, $user_id));

        if ($agency_id) {
            return (int) $agency_id;
        }

        // Check 2: Is user an agency employee?
        $table = $this->wpdb->prefix . 'app_agency_employees';

        $sql = "SELECT agency_id
                FROM {$table}
                WHERE user_id = %d
                LIMIT 1";

        $agency_id = $this->wpdb->get_var($this->wpdb->prepare($sql, $user_id));

        return $agency_id ? (int) $agency_id : null;
    }
}
