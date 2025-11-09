<?php
/**
 * Customer Employee Model
 *
 * @package     WP_Customer
 * @subpackage  Models/Employee
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Employee/CustomerEmployeeModel.php
 *
 * Description: CRUD model untuk Employee entity.
 *              Extends AbstractCrudModel dari wp-app-core.
 *              Handles create, read, update, delete operations.
 *              All CRUD operations INHERITED from AbstractCrudModel.
 *
 * Separation of Concerns:
 * - CustomerEmployeeModel: CRUD operations only
 * - EmployeeDataTableModel: DataTable server-side processing only
 * - EmployeeValidator: Validation only
 * - CustomerEmployeeController: HTTP request handling only
 *
 * Changelog:
 * 2.0.0 - 2025-11-09 (TODO-Employee-CRUD: Refactoring)
 * - BREAKING: Refactored to extend AbstractCrudModel
 * - CRUD methods INHERITED: find(), create(), update(), delete()
 * - Implements 8 abstract methods
 * - Custom methods: getUserInfo(), getByCustomer(), getByBranch(), etc.
 * - Removed: Duplicate CRUD code
 * - Uses: EmployeeCacheManager (not CustomerCacheManager)
 *
 * Previous version: CustomerEmployeeModel-OLD-*.php (backup)
 */

namespace WPCustomer\Models\Employee;

use WPAppCore\Models\Crud\AbstractCrudModel;
use WPCustomer\Cache\EmployeeCacheManager;
use WPCustomer\Models\Customer\CustomerModel;

defined('ABSPATH') || exit;

class CustomerEmployeeModel extends AbstractCrudModel {

    /**
     * Cache keys constants
     */
    private const KEY_EMPLOYEE = 'customer_employee';

    /**
     * Valid status values
     */
    private const VALID_STATUSES = ['active', 'inactive'];

    /**
     * Related models
     */
    private CustomerModel $customerModel;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(EmployeeCacheManager::getInstance());
        $this->customerModel = new CustomerModel();
    }

    // ========================================
    // IMPLEMENT ABSTRACT METHODS (8 required)
    // ========================================

    /**
     * Get database table name
     *
     * @return string
     */
    protected function getTableName(): string {
        global $wpdb;
        return $wpdb->prefix . 'app_customer_employees';
    }

    /**
     * Get format map for wpdb operations
     *
     * @return array Format map (field => format)
     */
    protected function getFormatMap(): array {
        return [
            'id' => '%d',
            'customer_id' => '%d',
            'branch_id' => '%d',
            'user_id' => '%d',
            'name' => '%s',
            'position' => '%s',
            'finance' => '%d',
            'operation' => '%d',
            'legal' => '%d',
            'purchase' => '%d',
            'keterangan' => '%s',
            'email' => '%s',
            'phone' => '%s',
            'status' => '%s',
            'created_by' => '%d',
            'updated_by' => '%d',
            'created_at' => '%s',
            'updated_at' => '%s'
        ];
    }

    /**
     * Get cache method name prefix
     *
     * @return string
     */
    protected function getCacheKey(): string {
        return 'Employee';
    }

    /**
     * Get entity name
     *
     * @return string
     */
    protected function getEntityName(): string {
        return 'employee';
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
            'customer_id',
            'branch_id',
            'user_id',
            'name',
            'position',
            'finance',
            'operation',
            'legal',
            'purchase',
            'keterangan',
            'email',
            'phone',
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
            'customer_id' => $data['customer_id'],
            'branch_id' => $data['branch_id'],
            'user_id' => $data['user_id'] ?? get_current_user_id(),
            'name' => $data['name'],
            'position' => $data['position'],
            'finance' => $data['finance'] ?? 0,
            'operation' => $data['operation'] ?? 0,
            'legal' => $data['legal'] ?? 0,
            'purchase' => $data['purchase'] ?? 0,
            'keterangan' => $data['keterangan'] ?? '',
            'email' => $data['email'],
            'phone' => $data['phone'] ?? '',
            'status' => $data['status'] ?? 'active',
            'created_by' => $data['created_by'] ?? get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
    }

    /**
     * Prepare update data from request
     *
     * @param array $data Raw request data
     * @return array Prepared update data
     */
    protected function prepareUpdateData(array $data): array {
        $updateData = [];

        // Only update fields that are present in request
        $allowed = $this->getAllowedFields();

        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        $updateData['updated_at'] = current_time('mysql');
        $updateData['updated_by'] = get_current_user_id();

        return $updateData;
    }

    // ========================================
    // CUSTOM METHODS (Business Logic)
    // ========================================

    /**
     * Check if email exists
     *
     * @param string $email Email to check
     * @param int|null $excludeId Exclude this ID (for update)
     * @return bool
     */
    public function existsByEmail(string $email, ?int $excludeId = null): bool {
        global $wpdb;

        $sql = "SELECT EXISTS (SELECT 1 FROM {$this->getTableName()} WHERE email = %s";
        $params = [$email];

        if ($excludeId) {
            $sql .= " AND id != %d";
            $params[] = $excludeId;
        }

        $sql .= ") as result";

        return (bool) $wpdb->get_var($wpdb->prepare($sql, $params));
    }

    /**
     * Get employees by customer ID
     *
     * @param int $customer_id Customer ID
     * @return array Array of employee objects
     */
    public function getByCustomer(int $customer_id): array {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare("
            SELECT e.*,
                   b.name as branch_name,
                   u.display_name as created_by_name
            FROM {$this->getTableName()} e
            LEFT JOIN {$wpdb->prefix}app_customer_branches b ON e.branch_id = b.id
            LEFT JOIN {$wpdb->users} u ON e.created_by = u.ID
            WHERE e.customer_id = %d
            ORDER BY e.name ASC
        ", $customer_id));
    }

    /**
     * Get employees by branch ID
     *
     * @param int $branch_id Branch ID
     * @return array Array of employee objects
     */
    public function getByBranch(int $branch_id): array {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare("
            SELECT e.*
            FROM {$this->getTableName()} e
            WHERE e.branch_id = %d
            ORDER BY e.name ASC
        ", $branch_id));
    }

    /**
     * Validate status
     *
     * @param string $status Status to validate
     * @return bool
     */
    public function isValidStatus(string $status): bool {
        return in_array($status, self::VALID_STATUSES);
    }

    /**
     * Change employee status
     *
     * @param int $id Employee ID
     * @param string $status New status
     * @return bool
     */
    public function changeStatus(int $id, string $status): bool {
        if (!$this->isValidStatus($status)) {
            return false;
        }

        // Get employee data BEFORE status change for cache invalidation
        $employee = $this->find($id);
        if (!$employee) {
            return false;
        }

        $customer_id = $employee->customer_id;

        global $wpdb;
        $result = $wpdb->update(
            $this->getTableName(),
            [
                'status' => $status,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );

        if ($result !== false) {
            // Invalidate cache
            $this->invalidateCache($id, $customer_id);
        }

        return $result !== false;
    }

    /**
     * Get employee by user ID
     *
     * @param int $user_id User ID
     * @return array|null Employee data or null
     */
    public function getByUserId(int $user_id): ?array {
        global $wpdb;

        $table_employees = $this->getTableName();
        $table_branches  = "{$wpdb->prefix}app_customer_branches";
        $table_customers = "{$wpdb->prefix}app_customers";
        $table_users     = "{$wpdb->prefix}users";

        $query = $wpdb->prepare("
            SELECT
                ce.user_id,
                u.display_name,
                cb.name AS branch_name,
                c.name AS customer_name
            FROM $table_employees ce
            JOIN $table_branches cb ON ce.branch_id = cb.id
            JOIN $table_customers c ON ce.customer_id = c.id
            JOIN $table_users u ON ce.user_id = u.ID
            WHERE ce.user_id = %d
            LIMIT 1
        ", $user_id);

        return $wpdb->get_row($query, ARRAY_A);
    }

    /**
     * Get comprehensive user information for admin bar integration
     *
     * Retrieves complete user data including employee, customer, branch, and membership info.
     * Tries multiple user types in order:
     * 1. Employee (most common)
     * 2. Customer owner
     * 3. Branch admin
     * 4. Fallback (user with role but no entity)
     *
     * @param int $user_id WordPress user ID
     * @return array|null Array of user info or null if not found
     */
    public function getUserInfo(int $user_id): ?array {
        // Try to get from cache first
        $cache_key = 'user_info';
        $cached_data = $this->cache->get($cache_key, $user_id);

        if ($cached_data !== false) {
            return $cached_data;
        }

        // Single optimized query (TODO-2176)
        global $wpdb;

        $query = $wpdb->prepare("
            SELECT
                -- Determine relation type (priority order: owner > branch_admin > employee)
                CASE
                    WHEN c_owner.user_id IS NOT NULL THEN 'customer_owner'
                    WHEN b_admin.user_id IS NOT NULL THEN 'customer_branch_admin'
                    WHEN e.user_id IS NOT NULL THEN 'customer_employee'
                    ELSE 'none'
                END as relation_type,

                -- Employee data (if employee)
                e.id as employee_id,
                e.customer_id as employee_customer_id,
                e.branch_id as employee_branch_id,
                e.name as employee_name,
                e.position as employee_position,
                e.finance as employee_finance,
                e.operation as employee_operation,
                e.legal as employee_legal,
                e.purchase as employee_purchase,
                e.keterangan as employee_keterangan,
                e.email as employee_email,
                e.phone as employee_phone,
                e.status as employee_status,
                e.created_by as employee_created_by,
                e.created_at as employee_created_at,
                e.updated_at as employee_updated_at,

                -- Customer data (via employee or as owner)
                COALESCE(c_emp.id, c_owner.id) as customer_id,
                COALESCE(c_emp.code, c_owner.code) as customer_code,
                COALESCE(c_emp.name, c_owner.name) as customer_name,
                COALESCE(c_emp.npwp, c_owner.npwp) as customer_npwp,
                COALESCE(c_emp.nib, c_owner.nib) as customer_nib,
                COALESCE(c_emp.status, c_owner.status) as customer_status,

                -- Branch data (via employee or as admin or pusat)
                COALESCE(b_emp.id, b_admin.id, b_pusat.id) as branch_id,
                COALESCE(b_emp.code, b_admin.code, b_pusat.code) as branch_code,
                COALESCE(b_emp.name, b_admin.name, b_pusat.name) as branch_name,
                COALESCE(b_emp.type, b_admin.type, b_pusat.type) as branch_type,
                COALESCE(b_emp.nitku, b_admin.nitku, b_pusat.nitku) as branch_nitku,
                COALESCE(b_emp.address, b_admin.address, b_pusat.address) as branch_address,
                COALESCE(b_emp.phone, b_admin.phone, b_pusat.phone) as branch_phone,
                COALESCE(b_emp.email, b_admin.email, b_pusat.email) as branch_email,
                COALESCE(b_emp.postal_code, b_admin.postal_code, b_pusat.postal_code) as branch_postal_code,
                COALESCE(b_emp.latitude, b_admin.latitude, b_pusat.latitude) as branch_latitude,
                COALESCE(b_emp.longitude, b_admin.longitude, b_pusat.longitude) as branch_longitude,

                -- Membership data (via employee's customer or owner's customer)
                cm.level_id as membership_level_id,
                cm.status as membership_status,
                cm.period_months as membership_period_months,
                cm.start_date as membership_start_date,
                cm.end_date as membership_end_date,
                cm.price_paid as membership_price_paid,
                cm.payment_status as membership_payment_status,
                cm.payment_method as membership_payment_method,
                cm.payment_date as membership_payment_date,

                -- User data
                u.user_login,
                u.user_nicename,
                u.user_email,
                u.user_url,
                u.user_registered,
                u.user_status,
                u.display_name

            FROM (SELECT %d as uid) params

            -- Check employee (priority 1)
            LEFT JOIN {$wpdb->prefix}app_customer_employees e
                ON e.user_id = params.uid AND e.status = 'active'
            LEFT JOIN {$wpdb->prefix}app_customers c_emp ON e.customer_id = c_emp.id
            LEFT JOIN {$wpdb->prefix}app_customer_branches b_emp ON e.branch_id = b_emp.id

            -- Check customer owner (priority 2)
            LEFT JOIN {$wpdb->prefix}app_customers c_owner ON c_owner.user_id = params.uid
            LEFT JOIN {$wpdb->prefix}app_customer_branches b_pusat
                ON b_pusat.customer_id = c_owner.id AND b_pusat.type = 'pusat'

            -- Check branch admin (priority 3)
            LEFT JOIN {$wpdb->prefix}app_customer_branches b_admin ON b_admin.user_id = params.uid

            -- Get membership (via employee's customer or owner's customer)
            LEFT JOIN {$wpdb->prefix}app_customer_memberships cm
                ON cm.customer_id = COALESCE(e.customer_id, c_owner.id)
                AND cm.status IN ('active', 'pending')

            -- Get user info
            INNER JOIN {$wpdb->users} u ON u.ID = params.uid

            LIMIT 1
        ", $user_id);

        $data = $wpdb->get_row($query, ARRAY_A);

        if (!$data || $data['relation_type'] === 'none') {
            // Fallback for users with role but no entity link
            $result = $this->getFallbackInfo($user_id);
            if ($result) {
                $this->cache->set($cache_key, $result, 5 * MINUTE_IN_SECONDS, $user_id);
            } else {
                // Cache null result for short time to prevent repeated queries
                $this->cache->set($cache_key, null, 5 * MINUTE_IN_SECONDS, $user_id);
            }
            return $result;
        }

        // Build result array based on relation type
        $result = $this->buildUserInfoFromData($data, $user_id);

        // Cache result
        $this->cache->set($cache_key, $result, 5 * MINUTE_IN_SECONDS, $user_id);

        return $result;
    }

    /**
     * Build user info array from single query data (TODO-2176)
     *
     * @param array $data Query result data
     * @param int $user_id WordPress user ID
     * @return array User info array
     */
    private function buildUserInfoFromData(array $data, int $user_id): array {
        $user = get_userdata($user_id);

        // Base result structure
        $result = [
            'branch_id' => $data['branch_id'],
            'branch_name' => $data['branch_name'],
            'branch_type' => $data['branch_type'],
            'entity_name' => $data['customer_name'],
            'entity_code' => $data['customer_code'],
            'relation_type' => $data['relation_type'],
            'icon' => '🏢'
        ];

        // Add type-specific fields
        if ($data['relation_type'] === 'customer_employee') {
            // Employee - add comprehensive data
            $result = array_merge($result, [
                'id' => $data['employee_id'],
                'user_id' => $user_id,
                'customer_id' => $data['employee_customer_id'],
                'branch_id' => $data['employee_branch_id'],
                'name' => $data['employee_name'],
                'position' => $data['employee_position'],
                'finance' => $data['employee_finance'],
                'operation' => $data['employee_operation'],
                'legal' => $data['employee_legal'],
                'purchase' => $data['employee_purchase'],
                'keterangan' => $data['employee_keterangan'],
                'email' => $data['employee_email'],
                'phone' => $data['employee_phone'],
                'status' => $data['employee_status'],
                'created_by' => $data['employee_created_by'],
                'created_at' => $data['employee_created_at'],
                'updated_at' => $data['employee_updated_at'],

                // Customer info
                'customer_code' => $data['customer_code'],
                'customer_name' => $data['customer_name'],
                'customer_npwp' => $data['customer_npwp'],
                'customer_nib' => $data['customer_nib'],
                'customer_status' => $data['customer_status'],

                // Branch info
                'branch_code' => $data['branch_code'],
                'branch_name' => $data['branch_name'],
                'branch_type' => $data['branch_type'],
                'branch_nitku' => $data['branch_nitku'],
                'branch_address' => $data['branch_address'],
                'branch_phone' => $data['branch_phone'],
                'branch_email' => $data['branch_email'],
                'branch_postal_code' => $data['branch_postal_code'],
                'branch_latitude' => $data['branch_latitude'],
                'branch_longitude' => $data['branch_longitude'],

                // Membership info
                'membership_level_id' => $data['membership_level_id'],
                'membership_status' => $data['membership_status'],
                'membership_period_months' => $data['membership_period_months'],
                'membership_start_date' => $data['membership_start_date'],
                'membership_end_date' => $data['membership_end_date'],
                'membership_price_paid' => $data['membership_price_paid'],
                'membership_payment_status' => $data['membership_payment_status'],
                'membership_payment_method' => $data['membership_payment_method'],
                'membership_payment_date' => $data['membership_payment_date'],

                // User info
                'user_email' => $data['user_email'],
                'user_login' => $data['user_login'],
                'display_name' => $data['display_name'],

                'entity_name' => $data['customer_name'],
                'entity_code' => $data['customer_code']
            ]);

        } else if ($data['relation_type'] === 'customer_owner') {
            // Customer owner - simpler structure
            $result['relation_type'] = 'owner';

        } else if ($data['relation_type'] === 'customer_branch_admin') {
            // Branch admin
            $result['relation_type'] = 'branch_admin';
        }

        // Add role names and permissions (if user data available)
        if ($user) {
            // Get capabilities from wp_usermeta
            $capabilities = get_user_meta($user_id, 'wp_capabilities', true);

            if ($capabilities && is_array($capabilities)) {
                $admin_bar_model = new \WPAppCore\Models\AdminBarModel();

                $result['role_names'] = $admin_bar_model->getRoleNamesFromCapabilities(
                    serialize($capabilities),
                    call_user_func(['WP_Customer_Role_Manager', 'getRoleSlugs']),
                    ['WP_Customer_Role_Manager', 'getRoleName']
                );

                $permission_model = new \WPCustomer\Models\Settings\PermissionModel();
                $result['permission_names'] = $admin_bar_model->getPermissionNamesFromUserId(
                    $user_id,
                    call_user_func(['WP_Customer_Role_Manager', 'getRoleSlugs']),
                    $permission_model->getAllCapabilities()
                );
            }
        }

        return $result;
    }

    /**
     * Get fallback user information for users with customer role but no entity link
     *
     * @param int $user_id WordPress user ID
     * @return array|null Fallback info or null if user has no customer role
     */
    private function getFallbackInfo(int $user_id): ?array {
        $user = get_user_by('ID', $user_id);

        if (!$user) {
            return null;
        }

        $customer_roles = call_user_func(['WP_Customer_Role_Manager', 'getRoleSlugs']);
        $user_roles = (array) $user->roles;

        // Check if user has any customer role
        $has_customer_role = !empty(array_intersect($user_roles, $customer_roles));

        if (!$has_customer_role) {
            return null;
        }

        // Get first customer role for display
        $first_customer_role = null;
        foreach ($customer_roles as $role_slug) {
            if (in_array($role_slug, $user_roles)) {
                $first_customer_role = $role_slug;
                break;
            }
        }

        $role_name = call_user_func(['WP_Customer_Role_Manager', 'getRoleName'], $first_customer_role);

        return [
            'entity_name' => 'Customer System',
            'entity_code' => 'CUSTOMER',
            'branch_id' => null,
            'branch_name' => $role_name ?? 'Staff',
            'branch_type' => 'admin',
            'relation_type' => 'role_only',
            'icon' => '🏢'
        ];
    }

    /**
     * Get total employee count based on user permission
     *
     * @param int|null $customer_id Optional customer ID for filtering
     * @return int Total number of employees
     */
    public function getTotalCount(?int $customer_id = null): int {
        global $wpdb;

        // Get user relation from CustomerModel to determine access
        $relation = $this->customerModel->getUserRelation(0);
        $access_type = $relation['access_type'];

        // Base query parts
        $select = "SELECT COUNT(*) ";
        $from = " FROM {$this->getTableName()} e";
        $join = " LEFT JOIN {$wpdb->prefix}app_customer_branches b ON e.branch_id = b.id
                  LEFT JOIN {$wpdb->prefix}app_customers c ON e.customer_id = c.id";

        // Default where clause
        $where = " WHERE 1=1";
        $params = [];

        // Add customer_id filter if provided
        if ($customer_id) {
            $where .= " AND e.customer_id = %d";
            $params[] = $customer_id;
        }

        // Apply filtering based on access type
        if ($relation['is_admin']) {
            // Administrator - see all employees
        }
        elseif ($access_type === 'platform') {
            // Platform users (from wp-app-core) - see all employees
        }
        elseif ($relation['is_customer_admin']) {
            // Customer Admin - see all employees under their customer
            $where .= " AND c.user_id = %d";
            $params[] = get_current_user_id();
        }
        elseif ($relation['is_customer_branch_admin']) {
            // Customer Branch Admin - only see employees in their managed branch
            $branch_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}app_customer_branches
                 WHERE user_id = %d LIMIT 1",
                get_current_user_id()
            ));

            if ($branch_id) {
                $where .= " AND e.branch_id = %d";
                $params[] = $branch_id;
            } else {
                $where .= " AND 1=0"; // No branch found
            }
        }
        elseif ($relation['is_customer_employee']) {
            // Employee - only see employees in the same branch
            $employee_branch = $wpdb->get_var($wpdb->prepare(
                "SELECT branch_id FROM {$this->getTableName()}
                 WHERE user_id = %d AND status = 'active' LIMIT 1",
                get_current_user_id()
            ));

            if ($employee_branch) {
                $where .= " AND e.branch_id = %d";
                $params[] = $employee_branch;
            } else {
                $where .= " AND 1=0"; // No branch found
            }
        }
        else {
            // No access
            $where .= " AND 1=0";
        }

        // Complete query
        $query = $select . $from . $join . $where;
        $final_query = !empty($params) ? $wpdb->prepare($query, $params) : $query;

        return (int) $wpdb->get_var($final_query);
    }

    /**
     * Get DataTable data for employees
     *
     * NOTE: This is a compatibility method for backward compatibility with old controller
     * Use EmployeeDataTableModel for new implementations
     *
     * @param int $customer_id Customer ID
     * @param int $start Offset
     * @param int $length Limit
     * @param string $search Search term
     * @param string $orderColumn Order column
     * @param string $orderDir Order direction
     * @return array DataTable result
     */
    public function getDataTableData(int $customer_id, int $start, int $length, string $search, string $orderColumn, string $orderDir): array {
        global $wpdb;

        // Get access_type from CustomerModel::getUserRelation (same as Branch tab)
        $relation = $this->customerModel->getUserRelation($customer_id);
        $access_type = $relation['access_type'];

        // Ensure orderDir lowercase for cache key consistency
        $orderDir = strtolower($orderDir);

        // Check cache first
        $cached_result = $this->cache->getDataTableCache(
            'customer_employee_list',
            $access_type,
            $start,
            $length,
            $search,
            $orderColumn,
            $orderDir,
            ['customer_id' => $customer_id]
        );

        if ($cached_result) {
            return $cached_result;
        }

        // Base query parts
        $select = "SELECT SQL_CALC_FOUND_ROWS e.*,
                         b.name as branch_name,
                         u.display_name as created_by_name";
        $from = " FROM {$this->getTableName()} e";
        $join = " LEFT JOIN {$wpdb->prefix}app_customer_branches b ON e.branch_id = b.id
                  LEFT JOIN {$wpdb->users} u ON e.created_by = u.ID";
        $where = " WHERE e.customer_id = %d";
        $params = [$customer_id];

        // Add branch filtering for employees and branch admins
        $current_user_id = get_current_user_id();

        // Check if user is employee or branch admin
        $employee_info = $wpdb->get_row($wpdb->prepare(
            "SELECT branch_id FROM {$this->getTableName()}
             WHERE user_id = %d AND status = 'active'",
            $current_user_id
        ));

        $customer_branch_admin_info = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}app_customer_branches
             WHERE user_id = %d",
            $current_user_id
        ));

        // Apply branch filtering for non-admins
        if (!current_user_can('edit_all_customer_employees')) {
            if ($employee_info && $employee_info->branch_id) {
                // Employee - only see employees in same branch
                $where .= " AND e.branch_id = %d";
                $params[] = $employee_info->branch_id;
            } elseif ($customer_branch_admin_info && $customer_branch_admin_info->id) {
                // Customer Branch Admin - only see employees in their managed branch
                $where .= " AND e.branch_id = %d";
                $params[] = $customer_branch_admin_info->id;
            }
        }

        // Add search if provided
        if (!empty($search)) {
            $where .= " AND (e.name LIKE %s OR e.position LIKE %s OR e.email LIKE %s OR e.phone LIKE %s)";
            $search_param = '%' . $wpdb->esc_like($search) . '%';
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        }

        // Validate order column
        $validColumns = ['name', 'position', 'branch_name', 'status'];
        if (!in_array($orderColumn, $validColumns)) {
            $orderColumn = 'name';
        }

        // Map frontend column to actual column
        $orderColumnMap = [
            'name' => 'e.name',
            'position' => 'e.position',
            'branch_name' => 'b.name',
            'status' => 'e.status'
        ];

        $orderColumn = $orderColumnMap[$orderColumn] ?? 'e.name';

        // Validate order direction
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

        // Build order clause
        $order = " ORDER BY " . esc_sql($orderColumn) . " " . esc_sql($orderDir);

        // Add limit
        $limit = $wpdb->prepare(" LIMIT %d, %d", $start, $length);

        // Complete query
        $sql = $select . $from . $join . $where . $order . $limit;

        // Get paginated results
        $results = $wpdb->get_results($wpdb->prepare($sql, $params));

        if ($results === null) {
            throw new \Exception($wpdb->last_error);
        }

        // Get total filtered count
        $filtered = $wpdb->get_var("SELECT FOUND_ROWS()");

        // Get total count for customer
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->getTableName()} WHERE customer_id = %d",
            $customer_id
        ));

        $result = [
            'data' => $results,
            'total' => (int) $total,
            'filtered' => (int) $filtered
        ];

        // Set cache with 2 minute duration
        $this->cache->setDataTableCache(
            'customer_employee_list',
            $access_type,
            $start,
            $length,
            $search,
            $orderColumn,
            $orderDir,
            $result,
            ['customer_id' => $customer_id]
        );

        return $result;
    }

    // ========================================
    // CACHE INVALIDATION
    // ========================================

    /**
     * Invalidate employee cache after CUD operations
     * Called automatically by AbstractCrudModel
     *
     * @param int $id Employee ID
     * @param mixed ...$additional_keys Additional cache keys to clear
     * @return void
     */
    protected function invalidateCache(int $id, ...$additional_keys): void {
        // Get customer_id if provided in additional_keys
        $customer_id = $additional_keys[0] ?? null;

        // Use EmployeeCacheManager's invalidation method
        /** @var EmployeeCacheManager $cache */
        $cache = $this->cache;
        $cache->invalidateEmployeeCache($id, $customer_id);
    }
}
