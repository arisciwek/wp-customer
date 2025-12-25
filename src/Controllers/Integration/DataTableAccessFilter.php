<?php
/**
 * DataTable Access Filter
 *
 * Generic access control for DataTables across entity types.
 * Filters query results based on user access permissions.
 *
 * @package WPCustomer\Controllers\Integration
 * @version 1.1.0
 * @since 1.0.12
 *
 * Changelog:
 * 1.1.0 - 2025-11-02 (TODO-2190)
 * - Added: Config for customer_branches entity
 * - Added: Config for customer_employees entity
 * - Added: customer_admin role to entity_admin_roles for customer_branches
 * - Added: customer_admin role to entity_admin_roles for customer_employees
 * - customer_admin can now bypass filter to see all branches/employees in their customer
 */

namespace WPCustomer\Controllers\Integration;

use WPCustomer\Models\Relation\EntityRelationModel;

defined('ABSPATH') || exit;

/**
 * DataTableAccessFilter Class
 *
 * Provides:
 * - Configuration-based access filtering
 * - Dynamic filter hook registration
 * - Database-level security
 * - Platform staff vs Customer employee differentiation
 *
 * @since 1.0.12
 */
class DataTableAccessFilter {

    /**
     * Entity relation model
     *
     * @var EntityRelationModel
     */
    private $model;

    /**
     * Access filter configurations
     *
     * @var array
     */
    private $configs = [];

    /**
     * Constructor
     *
     * @param EntityRelationModel|null $model Entity relation model instance (optional)
     * @since 1.0.12
     */
    public function __construct(?EntityRelationModel $model = null) {
        $this->model = $model ?: new EntityRelationModel();
        $this->load_configs();
        $this->register_filters();
    }

    /**
     * Load access filter configurations
     *
     * Simple inline configs for agency entity.
     * Other plugins can add configs via filter hook.
     *
     * @since 1.0.12
     */
    private function load_configs(): void {
        // Default configs for entities with access control
        $default_configs = [
            'agency' => [
                'hook' => 'wpapp_datatable_agencies_where',
                'table_alias' => 'a',
                'id_column' => 'id',
                'priority' => 10
            ],
            'customer_branches' => [
                'hook' => 'wpapp_datatable_customer_branches_where',
                'table_alias' => 'cb',
                'id_column' => 'id',
                'priority' => 10
            ],
            'company' => [
                'hook' => 'wpapp_datatable_company_where',
                'table_alias' => 'cc',
                'id_column' => 'id',
                'priority' => 10
            ],
            'customer_employees' => [
                'hook' => 'wpapp_datatable_customer_employees_where',
                'table_alias' => 'ce',
                'id_column' => 'id',
                'priority' => 10
            ]
        ];

        /**
         * Filter: wp_customer_datatable_access_configs
         *
         * Allows other plugins to add DataTable access filter configurations.
         *
         * @param array $configs Access filter configurations
         * @return array Modified configurations
         *
         * @since 1.0.12
         *
         * @example
         * ```php
         * add_filter('wp_customer_datatable_access_configs', function($configs) {
         *     $configs['company'] = [
         *         'hook' => 'wpapp_datatable_companies_where',
         *         'table_alias' => 'c',
         *         'id_column' => 'id'
         *     ];
         *     return $configs;
         * });
         * ```
         */
        $this->configs = apply_filters('wp_customer_datatable_access_configs', $default_configs);
    }

    /**
     * Register WordPress filter hooks
     *
     * Dynamically registers filter hooks for each configured entity type.
     * Hook name format: wpapp_datatable_{entity}_where
     *
     * @since 1.0.12
     */
    private function register_filters(): void {
        foreach ($this->configs as $entity_type => $config) {
            $hook = $config['hook'] ?? "wpapp_datatable_{$entity_type}_where";
            $priority = $config['priority'] ?? 10;

            // Register DataTable filter with entity type as parameter
            add_filter($hook, function($where, $request, $model) use ($entity_type) {
                return $this->filter_datatable_where($where, $request, $model, $entity_type);
            }, $priority, 3);

            // Register Statistics filter (same logic, different hook)
            $stats_hook = "wpapp_{$entity_type}_statistics_where";
            add_filter($stats_hook, function($where, $context) use ($entity_type) {
                return $this->filter_statistics_where($where, $context, $entity_type);
            }, $priority, 2);
        }
    }

    /**
     * Filter DataTable WHERE conditions
     *
     * Adds access control conditions to DataTable queries.
     * Platform staff see all, customer employees see only accessible entities.
     *
     * @param array  $where       WHERE conditions
     * @param array  $request     Request data
     * @param object $model       DataTable model instance
     * @param string $entity_type Entity type
     * @return array Modified WHERE conditions
     *
     * @since 1.0.12
     *
     * @example
     * ```php
     * // Before: ['status' => 'active']
     * // After:  ['status' => 'active', 'id IN (1,5,12)']
     * ```
     */
    public function filter_datatable_where(array $where, array $request, $model, string $entity_type): array {
        // DEBUG LOG - Start
        error_log('=== DataTableAccessFilter::filter_datatable_where ===');
        error_log('Entity Type: ' . $entity_type);
        error_log('User ID: ' . get_current_user_id());
        error_log('BEFORE WHERE: ' . json_encode($where));

        // Check if entity has filter config
        if (!isset($this->configs[$entity_type])) {
            error_log('No config for entity type: ' . $entity_type);
            return $where;
        }

        $config = $this->configs[$entity_type];
        error_log('Config found: ' . json_encode($config));

        // Get current user
        $user_id = get_current_user_id();

        if (!$user_id) {
            error_log('No user logged in - denying access');
            // No user logged in - deny access
            return $this->apply_deny_access_filter($where, $config);
        }

        /**
         * Filter: Check if user should be filtered
         *
         * @param bool   $should_filter Whether to apply filtering
         * @param int    $user_id       User ID
         * @param string $entity_type   Entity type
         * @param array  $config        Filter config
         * @return bool Modified should_filter
         *
         * @since 1.0.12
         */
        $should_bypass = $this->should_bypass_filter($user_id, $entity_type);
        error_log('Should bypass filter: ' . ($should_bypass ? 'YES' : 'NO'));

        $should_filter = apply_filters(
            'wp_customer_should_filter_datatable',
            !$should_bypass,
            $user_id,
            $entity_type,
            $config
        );

        error_log('Should filter: ' . ($should_filter ? 'YES' : 'NO'));

        if (!$should_filter) {
            error_log('AFTER WHERE (no filter): ' . json_encode($where));
            // Admin, platform staff, or whitelisted user - no filtering
            return $where;
        }

        // Get accessible entity IDs for this user
        try {
            $accessible_ids = $this->model->get_accessible_entity_ids($entity_type, $user_id);
            error_log('Accessible IDs: ' . json_encode($accessible_ids));
        } catch (\Exception $e) {
            // Error fetching accessible IDs - log and deny access
            error_log("WP Customer: Error fetching accessible IDs for {$entity_type}: " . $e->getMessage());
            return $this->apply_deny_access_filter($where, $config);
        }

        // If platform staff (returns empty array), no filtering needed
        if (empty($accessible_ids)) {
            // Empty array from model means either:
            // 1. Platform staff (see all) - but we checked above, so this shouldn't happen
            // 2. No accessible entities - deny access
            return $this->apply_deny_access_filter($where, $config);
        }

        /**
         * Filter: Modify accessible entity IDs
         *
         * Last chance to modify accessible IDs before applying filter.
         *
         * @param array  $accessible_ids Accessible entity IDs
         * @param int    $user_id        User ID
         * @param string $entity_type    Entity type
         * @param array  $config         Filter config
         * @return array Modified IDs
         *
         * @since 1.0.12
         */
        $accessible_ids = apply_filters(
            'wp_customer_accessible_entity_ids',
            $accessible_ids,
            $user_id,
            $entity_type,
            $config
        );

        // Apply access filter
        return $this->apply_access_filter($where, $accessible_ids, $config);
    }

    /**
     * Apply access filter to WHERE conditions
     *
     * Adds "entity_id IN (accessible_ids)" condition.
     *
     * @param array $where          WHERE conditions
     * @param array $accessible_ids Accessible entity IDs
     * @param array $config         Filter config
     * @return array Modified WHERE conditions
     *
     * @since 1.0.12
     */
    private function apply_access_filter(array $where, array $accessible_ids, array $config): array {
        error_log('=== apply_access_filter ===');
        error_log('Accessible IDs to filter: ' . json_encode($accessible_ids));

        $table_alias = $config['table_alias'] ?? 'a';
        $id_column = $config['id_column'] ?? 'id';

        // Build IN clause
        $ids_string = implode(',', array_map('intval', $accessible_ids));
        $filter_condition = "{$table_alias}.{$id_column} IN ({$ids_string})";

        error_log('Filter condition: ' . $filter_condition);

        // Add to WHERE conditions
        $where[] = $filter_condition;

        error_log('AFTER WHERE (with filter): ' . json_encode($where));
        error_log('=== End filter_datatable_where ===');

        return $where;
    }

    /**
     * Filter Statistics WHERE conditions
     *
     * Simplified version of filter_datatable_where for statistics queries.
     * Adds access control conditions to COUNT queries.
     *
     * @param array  $where       WHERE conditions
     * @param string $context     Statistics context (total, active, inactive)
     * @param string $entity_type Entity type
     * @return array Modified WHERE conditions
     *
     * @since 1.0.12
     */
    public function filter_statistics_where(array $where, string $context, string $entity_type): array {
        // Check if entity has filter config
        if (!isset($this->configs[$entity_type])) {
            return $where;
        }

        $config = $this->configs[$entity_type];

        // Get current user
        $user_id = get_current_user_id();

        if (!$user_id) {
            // No user logged in - deny access
            return $this->apply_deny_access_filter($where, $config);
        }

        // Check if user should bypass filter
        $should_bypass = $this->should_bypass_filter($user_id, $entity_type);

        if (!$should_bypass) {
            // Get accessible entity IDs
            try {
                $accessible_ids = $this->model->get_accessible_entity_ids($entity_type, $user_id);

                if (!empty($accessible_ids)) {
                    // Apply access filter (no table alias needed for simple queries)
                    $ids_string = implode(',', array_map('intval', $accessible_ids));
                    $id_column = $config['id_column'] ?? 'id';
                    $where[] = "{$id_column} IN ({$ids_string})";
                }
            } catch (\Exception $e) {
                error_log("WP Customer: Error fetching accessible IDs for statistics: " . $e->getMessage());
                return $this->apply_deny_access_filter($where, $config);
            }
        }

        return $where;
    }

    /**
     * Apply deny access filter
     *
     * Adds impossible condition to deny all access.
     *
     * @param array $where  WHERE conditions
     * @param array $config Filter config
     * @return array Modified WHERE conditions
     *
     * @since 1.0.12
     */
    private function apply_deny_access_filter(array $where, array $config): array {
        $table_alias = $config['table_alias'] ?? 'a';
        $id_column = $config['id_column'] ?? 'id';

        // Add impossible condition (no results)
        $where[] = "{$table_alias}.{$id_column} IN (0)";

        return $where;
    }

    /**
     * Check if user is platform staff
     *
     * Platform staff have access to all data without filtering.
     * Checks for 'admin_platform' capability and platform staff table.
     *
     * @param int $user_id User ID
     * @return bool True if platform staff, false otherwise
     *
     * @since 1.0.12
     */
    private function is_platform_staff(int $user_id): bool {
        global $wpdb;

        // Check platform staff table
        $table = $wpdb->prefix . 'app_platform_staff';

        $sql = "SELECT COUNT(*)
                FROM {$table}
                WHERE user_id = %d";

        $exists = (bool) $wpdb->get_var($wpdb->prepare($sql, $user_id));

        if ($exists) {
            return true;
        }

        // Also check user capability
        $user = get_user_by('id', $user_id);
        if ($user && $user->has_cap('admin_platform')) {
            return true;
        }

        return false;
    }

    /**
     * Check if user should bypass filter
     *
     * Determines if user should see all data without filtering based on:
     * 1. WordPress administrator role (global super admin)
     * 2. Platform staff (platform-level access)
     * 3. Entity-specific admin roles (configured per entity)
     *
     * Uses PermissionModel approach: Checks if user has required capabilities
     * for viewing the entity, then checks if they're in an admin-level role.
     *
     * @param int    $user_id     User ID
     * @param string $entity_type Entity type (agency, customer, etc.)
     * @return bool True if should bypass filter, false otherwise
     *
     * @since 1.0.12
     */
    private function should_bypass_filter(int $user_id, string $entity_type): bool {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }

        // 1. WordPress administrator always bypasses (global super admin)
        if (in_array('administrator', $user->roles)) {
            error_log("User {$user_id} is WordPress administrator - bypassing filter");
            return true;
        }

        // 2. Platform staff bypasses all entities
        if ($this->is_platform_staff($user_id)) {
            error_log("User {$user_id} is platform staff - bypassing filter");
            return true;
        }

        // 3. Entity-specific admin roles bypass
        // This mapping defines which roles should bypass filtering for each entity type
        // Admin roles can see ALL data, non-admin roles are filtered
        $entity_admin_roles = [
            'agency' => [
                'agency_admin_dinas',     // Admin Dinas (top level)
                'agency_admin_unit',      // Admin Unit (unit level)
                'agency_kepala_dinas',    // Kepala Dinas (head of agency)
                'agency_kepala_bidang',   // Kepala Bidang (head of division)
            ],
            'customer' => [
                'customer_admin',         // Customer Admin (owner/top level)
            ],
            'customer_branches' => [
                'customer_admin',         // Customer Admin can see all branches in their customer
            ],
            'company' => [
                'customer_admin',         // Customer Admin can see all companies in their customer
            ],
            'customer_employees' => [
                'customer_admin',         // Customer Admin can see all employees in their customer
            ],
            // Add more entity-specific admin roles here as needed
        ];

        /**
         * Filter: Modify entity admin roles mapping
         *
         * Allows plugins to add or modify admin roles for entities.
         * Admin roles will bypass data filtering and see all records.
         *
         * @param array  $entity_admin_roles Entity => roles mapping
         * @param int    $user_id            User ID being checked
         * @param string $entity_type        Entity type being checked
         * @return array Modified mapping
         *
         * @since 1.0.12
         */
        $entity_admin_roles = apply_filters(
            'wp_customer_entity_admin_roles',
            $entity_admin_roles,
            $user_id,
            $entity_type
        );

        // Check if user has any admin role for this entity
        if (isset($entity_admin_roles[$entity_type])) {
            $admin_roles = $entity_admin_roles[$entity_type];
            foreach ($admin_roles as $admin_role) {
                if (in_array($admin_role, $user->roles)) {
                    error_log("User {$user_id} has {$admin_role} role - bypassing filter for {$entity_type}");
                    return true;
                }
            }
        }

        /**
         * Filter: Override bypass filter check
         *
         * Allows plugins/themes to override the bypass logic.
         * This is the last check before filtering is applied.
         *
         * @param bool   $should_bypass Whether user should bypass filter
         * @param int    $user_id       User ID
         * @param string $entity_type   Entity type
         * @param object $user          User object
         * @return bool Modified should_bypass
         *
         * @since 1.0.12
         */
        return apply_filters(
            'wp_customer_should_bypass_filter',
            false,
            $user_id,
            $entity_type,
            $user
        );
    }

    /**
     * Check if user is customer employee
     *
     * @param int $user_id User ID
     * @return bool True if customer employee, false otherwise
     *
     * @since 1.0.12
     */
    public function is_customer_employee(int $user_id): bool {
        global $wpdb;

        $table = $wpdb->prefix . 'app_customer_employees';

        $sql = "SELECT COUNT(*)
                FROM {$table}
                WHERE user_id = %d";

        return (bool) $wpdb->get_var($wpdb->prepare($sql, $user_id));
    }

    /**
     * Get user access type
     *
     * @param int $user_id User ID
     * @return string Access type: 'platform_staff', 'customer_employee', or 'none'
     *
     * @since 1.0.12
     *
     * @example
     * ```php
     * $filter = new DataTableAccessFilter();
     * $access_type = $filter->get_user_access_type(123);
     * // Returns: 'platform_staff' or 'customer_employee' or 'none'
     * ```
     */
    public function get_user_access_type(int $user_id): string {
        if ($this->is_platform_staff($user_id)) {
            return 'platform_staff';
        }

        if ($this->is_customer_employee($user_id)) {
            return 'customer_employee';
        }

        return 'none';
    }

    /**
     * Get loaded configurations
     *
     * @return array All configurations
     * @since 1.0.12
     */
    public function get_configs(): array {
        return $this->configs;
    }

    /**
     * Check if entity has filter configured
     *
     * @param string $entity_type Entity type
     * @return bool True if configured
     * @since 1.0.12
     */
    public function has_filter_config(string $entity_type): bool {
        return isset($this->configs[$entity_type]);
    }

    /**
     * Get filter hook name for entity type
     *
     * @param string $entity_type Entity type
     * @return string|null Hook name or null if not configured
     * @since 1.0.12
     */
    public function get_filter_hook(string $entity_type): ?string {
        if (!isset($this->configs[$entity_type])) {
            return null;
        }

        return $this->configs[$entity_type]['hook'] ?? "wpapp_datatable_{$entity_type}_where";
    }
}
