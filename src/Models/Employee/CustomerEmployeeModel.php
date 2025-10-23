<?php
/**
 * Customer Employee Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models/Employee
 * @version     1.0.10
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Employee/CustomerEmployeeModel.php
 *
 * Description: Model untuk mengelola data karyawan customer di database.
 *              Handles operasi CRUD dengan caching terintegrasi.
 *              Includes query optimization dan data formatting.
 *              Menyediakan metode untuk DataTables server-side.
 *
 * Changelog:
 * 1.1.0 - 2025-01-18
 * - REFACTOR: getUserInfo() now handles ALL user types (employee, owner, branch admin, fallback)
 * - Added: getEmployeeInfo() private method (extracted from getUserInfo)
 * - Added: getCustomerOwnerInfo() private method (moved from Integration class)
 * - Added: getBranchAdminInfo() private method (moved from Integration class)
 * - Added: getFallbackInfo() private method (moved from Integration class)
 * - Improved: All business logic now in Model (separation of concerns)
 * - Improved: Consistent with wp-agency pattern (single delegation point)
 * - Added: Cache invalidation for customer_user_info in update/delete/changeStatus
 * - Benefits: Cleaner architecture, more maintainable, testable
 *
 * 1.0.0 - 2024-01-12
 * - Initial implementation
 * - Added core CRUD operations
 * - Added DataTables integration
 * - Added cache support
 */

namespace WPCustomer\Models\Employee;

use WPCustomer\Cache\CustomerCacheManager;

class CustomerEmployeeModel {
    private $table;
    private $customer_table;
    private $branch_table;
    private $cache; // Tambahkan properti cache

    // Add class constant for valid status values
    private const VALID_STATUSES = ['active', 'inactive'];

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'app_customer_employees';
        $this->customer_table = $wpdb->prefix . 'app_customers';
        $this->branch_table = $wpdb->prefix . 'app_customer_branches';
        $this->cache = new CustomerCacheManager();
    }

public function create(array $data): ?int {
    global $wpdb;

    $result = $wpdb->insert(
        $this->table,
        [
            'customer_id' => $data['customer_id'],  // Ambil customer_id dari data
            'branch_id' => $data['branch_id'],
            'user_id' => $data['user_id'] ?? get_current_user_id(), // Use provided user_id or current user as fallback
            'name' => $data['name'],
            'position' => $data['position'],
            'finance' => $data['finance'],
            'operation' => $data['operation'],
            'legal' => $data['legal'],
            'purchase' => $data['purchase'],
            'keterangan' => $data['keterangan'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'created_by' => $data['created_by'] ?? get_current_user_id(), // Allow override for demo data
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'status' => $data['status'] ?? 'active'
        ],
        [
            '%d', // customer_id
            '%d', // branch_id
            '%d', // user_id
            '%s', // name
            '%s', // position
            '%d', // finance
            '%d', // operation
            '%d', // legal
            '%d', // purchase
            '%s', // keterangan
            '%s', // email
            '%s', // phone
            '%d', // created_by
            '%s', // created_at
            '%s', // updated_at
            '%s'  // status
        ]
    );

    if ($result === false) {
        return null;
    }

    $employee_id = (int) $wpdb->insert_id;

    // Comprehensive cache invalidation for new employee
    if ($employee_id && isset($data['customer_id'])) {
        $this->cache->delete('active_customer_employee_count', (string)$data['customer_id']);

        // Invalidate DataTable cache for all access types
        $this->invalidateAllDataTableCache('customer_employee_list', (int)$data['customer_id']);
    }

    // Fire HOOK: wp_customer_employee_created (Task-2170)
    // Allows external plugins to react to employee creation
    // Example: Send welcome email, create default permissions, notify managers
    if ($employee_id) {
        do_action('wp_customer_employee_created', $employee_id, $data);
    }

    return $employee_id;
}

    public function find(int $id): ?object {
        global $wpdb;

        // Check cache first
        $cached_employee = $this->cache->get('customer_employee', $id);

        if ($cached_employee !== null) {
            return $cached_employee;
        }

        // Query database if not cached
        $result = $wpdb->get_row($wpdb->prepare("
            SELECT e.*,
                   c.name as customer_name,
                   b.name as branch_name,
                   u.display_name as created_by_name
            FROM {$this->table} e
            LEFT JOIN {$this->customer_table} c ON e.customer_id = c.id
            LEFT JOIN {$this->branch_table} b ON e.branch_id = b.id
            LEFT JOIN {$wpdb->users} u ON e.created_by = u.ID
            WHERE e.id = %d
        ", $id));

        // Cache the result
        if ($result) {
            $this->cache->set('customer_employee', $result, $this->cache::getCacheExpiry(), $id);
        }

        return $result;
    }

    public function update(int $id, array $data): bool {
        global $wpdb;

        // Only include status in update if it's provided and valid
        $updateData = [
            'name' => $data['name'],
            'position' => $data['position'],
            'finance' => $data['finance'],
            'operation' => $data['operation'],
            'legal' => $data['legal'],
            'purchase' => $data['purchase'],
            'keterangan' => $data['keterangan'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'branch_id' => $data['branch_id'],
            'updated_at' => current_time('mysql')
        ];

        $format = [
            '%s', // name
            '%s', // position
            '%d', // finance
            '%d', // operation
            '%d', // legal
            '%d', // purchase
            '%s', // keterangan
            '%s', // email
            '%s', // phone
            '%d', // branch_id
            '%s'  // updated_at
        ];

        // Add status to update data if provided and valid
        if (isset($data['status']) && in_array($data['status'], self::VALID_STATUSES)) {
            $updateData['status'] = $data['status'];
            $format[] = '%s'; // status
        }

        $result = $wpdb->update(
            $this->table,
            $updateData,
            ['id' => $id],
            $format,
            ['%d']
        );

	    if ($result === false) {
		error_log('Update customer employee error: ' . $wpdb->last_error);
		return false;
	    }

	    // Get employee data untuk customer_id - AFTER update
	    $employee = $this->find($id);
	    if ($employee && $employee->customer_id) {
		// ✓ FIXED: Invalidate ALL employee cache keys
		$this->cache->delete('customer_employee', $id);
		$this->cache->delete('customer_employee_count', (string)$employee->customer_id);
		$this->cache->delete('active_customer_employee_count', (string)$employee->customer_id);

		// Invalidate getUserInfo cache (for admin bar)
		if ($employee->user_id) {
		    $this->cache->delete('customer_user_info', $employee->user_id);
		}

		// Invalidate DataTable cache for all access types
		$this->invalidateAllDataTableCache('customer_employee_list', (int)$employee->customer_id);

		// Fire HOOK: wp_customer_employee_updated (Task-2170)
		// Allows external plugins to react to employee updates
		// Example: Sync changes to external systems, update permissions
		do_action('wp_customer_employee_updated', $id, $data, $employee);
	    }

        return true;
    }

	/**
	 * Delete employee with HOOK support
	 *
	 * Task-2170: Added soft/hard delete logic + HOOKs
	 * - Fires wp_customer_employee_before_delete HOOK
	 * - Performs soft delete (status='inactive') or hard delete (actual DELETE)
	 * - Fires wp_customer_employee_deleted HOOK
	 * - EmployeeCleanupHandler handles cache invalidation via HOOK
	 *
	 * @param int $id Employee ID to delete
	 * @return bool True on success, false on failure
	 *
	 * @since 1.1.0 Added HOOK support (Task-2170)
	 */
	public function delete(int $id): bool {
	    global $wpdb;

	    // 1. Get employee data BEFORE deletion for HOOK
	    $employee = $this->find($id);
	    if (!$employee) {
		return false;
	    }

	    // 2. Convert to array for HOOK
	    $employee_data = [
		'id' => $employee->id,
		'customer_id' => $employee->customer_id,
		'branch_id' => $employee->branch_id,
		'user_id' => $employee->user_id,
		'name' => $employee->name,
		'position' => $employee->position,
		'email' => $employee->email,
		'phone' => $employee->phone,
		'finance' => $employee->finance,
		'operation' => $employee->operation,
		'legal' => $employee->legal,
		'purchase' => $employee->purchase,
		'keterangan' => $employee->keterangan,
		'status' => $employee->status,
		'created_by' => $employee->created_by,
		'created_at' => $employee->created_at,
		'updated_at' => $employee->updated_at
	    ];

	    // 3. Fire before delete HOOK (Task-2170)
	    // For validation, logging, pre-deletion notifications
	    do_action('wp_customer_employee_before_delete', $id, $employee_data);

	    // 4. Check hard delete setting (same setting as Branch/Customer for consistency)
	    $settings = get_option('wp_customer_general_options', []);
	    $is_hard_delete = isset($settings['enable_hard_delete_branch']) &&
			     $settings['enable_hard_delete_branch'] === true;

	    // 5. Perform delete (soft or hard)
	    if ($is_hard_delete) {
		// Hard delete - actual DELETE from database
		$result = $wpdb->delete(
		    $this->table,
		    ['id' => $id],
		    ['%d']
		);
	    } else {
		// Soft delete - status = 'inactive'
		$result = $wpdb->update(
		    $this->table,
		    [
			'status' => 'inactive',
			'updated_at' => current_time('mysql')
		    ],
		    ['id' => $id],
		    ['%s', '%s'],
		    ['%d']
		);
	    }

	    // 6. Fire after delete HOOK and handle cleanup (Task-2170)
	    if ($result !== false) {
		// Fire HOOK - EmployeeCleanupHandler handles cache invalidation
		do_action('wp_customer_employee_deleted', $id, $employee_data, $is_hard_delete);

		// Direct cache invalidation (in addition to HOOK handler)
		$this->cache->delete('customer_employee', $id);
		$this->cache->delete('customer_employee_count', (string)$employee_data['customer_id']);
		$this->cache->delete('active_customer_employee_count', (string)$employee_data['customer_id']);

		// Invalidate getUserInfo cache (for admin bar)
		if ($employee->user_id) {
		    $this->cache->delete('customer_user_info', $employee->user_id);
		}

		// Invalidate DataTable cache for all access types
		$this->invalidateAllDataTableCache('customer_employee_list', (int)$employee_data['customer_id']);

		return true;
	    }

	    return false;
	}


    public function existsByEmail(string $email, ?int $excludeId = null): bool {
        global $wpdb;

        $sql = "SELECT EXISTS (SELECT 1 FROM {$this->table} WHERE email = %s";
        $params = [$email];

        if ($excludeId) {
            $sql .= " AND id != %d";
            $params[] = $excludeId;
        }

        $sql .= ") as result";

        return (bool) $wpdb->get_var($wpdb->prepare($sql, $params));
    }

    public function getDataTableData(int $customer_id, int $start, int $length, string $search, string $orderColumn, string $orderDir): array {
        global $wpdb;

        // Get access_type from CustomerModel::getUserRelation (same as Branch tab)
        $customerModel = new \WPCustomer\Models\Customer\CustomerModel();
        $relation = $customerModel->getUserRelation($customer_id);
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
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("CustomerEmployeeModel cache hit for DataTable - Key: customer_employee_list_{$access_type}");
            }
            return $cached_result;
        }

        error_log('=== Start Debug Employee DataTable Query ===');
        error_log('Customer ID: ' . $customer_id);
        error_log('Access Type: ' . $access_type);
        error_log('Start: ' . $start);
        error_log('Length: ' . $length);
        error_log('Search: ' . $search);
        error_log('Order Column: ' . $orderColumn);
        error_log('Order Direction: ' . $orderDir);

        // Base query parts
        $select = "SELECT SQL_CALC_FOUND_ROWS e.*, 
                         b.name as branch_name,
                         u.display_name as created_by_name";
        $from = " FROM {$this->table} e";
        $join = " LEFT JOIN {$this->branch_table} b ON e.branch_id = b.id
                  LEFT JOIN {$wpdb->users} u ON e.created_by = u.ID";
        $where = " WHERE e.customer_id = %d";
        $params = [$customer_id];

        // Add branch filtering for employees and branch admins
        $current_user_id = get_current_user_id();

        // Check if user is employee or branch admin
        $employee_info = $wpdb->get_row($wpdb->prepare(
            "SELECT branch_id FROM {$this->table}
             WHERE user_id = %d AND status = 'active'",
            $current_user_id
        ));

        $customer_branch_admin_info = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$this->branch_table}
             WHERE user_id = %d",
            $current_user_id
        ));

        // Apply branch filtering for non-admins
        if (!current_user_can('edit_all_customer_employees')) {
            if ($employee_info && $employee_info->branch_id) {
                // Employee - only see employees in same branch
                $where .= " AND e.branch_id = %d";
                $params[] = $employee_info->branch_id;
                error_log("Applied employee branch filter: branch_id = {$employee_info->branch_id}");
            } elseif ($customer_branch_admin_info && $customer_branch_admin_info->id) {
                // Customer Branch Admin - only see employees in their managed branch
                $where .= " AND e.branch_id = %d";
                $params[] = $customer_branch_admin_info->id;
                error_log("Applied customer branch admin filter: branch_id = {$customer_branch_admin_info->id}");
            }
        }

        error_log('Initial Query Parts:');
        error_log('Select: ' . $select);
        error_log('From: ' . $from);
        error_log('Join: ' . $join);
        error_log('Where: ' . $where);

        // Add search if provided
        if (!empty($search)) {
            $where .= " AND (e.name LIKE %s OR e.department LIKE %s)";
            $search_param = '%' . $wpdb->esc_like($search) . '%';
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
            error_log('Search Where Clause Added: ' . $where);
            error_log('Search Parameters: ' . print_r($params, true));
        }

        // Validate order column
        $validColumns = ['name', 'department', 'branch_name', 'status'];
        if (!in_array($orderColumn, $validColumns)) {
            $orderColumn = 'name';
        }
        error_log('Validated Order Column: ' . $orderColumn);

        // Map frontend column to actual column
        $orderColumnMap = [
            'name' => 'e.name',
            'department' => 'e.department',
            'branch_name' => 'b.name',
            'status' => 'e.status'
        ];

        $orderColumn = $orderColumnMap[$orderColumn] ?? 'e.name';
        error_log('Mapped Order Column: ' . $orderColumn);

        // Validate order direction
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
        error_log('Validated Order Direction: ' . $orderDir);

        // Build order clause
        $order = " ORDER BY " . esc_sql($orderColumn) . " " . esc_sql($orderDir);
        error_log('Order Clause: ' . $order);

        // Add limit
        $limit = $wpdb->prepare(" LIMIT %d, %d", $start, $length);
        error_log('Limit Clause: ' . $limit);

        // Complete query
        $sql = $select . $from . $join . $where . $order . $limit;

        // Log the final query with parameters
        $final_query = $wpdb->prepare($sql, $params);
        error_log('Final Complete Query: ' . $final_query);

        // Get paginated results
        $results = $wpdb->get_results($final_query);
        
        if ($results === null) {
            error_log('Query Error: ' . $wpdb->last_error);
            throw new \Exception($wpdb->last_error);
        }

        error_log('Query Results Count: ' . count($results));

        // Get total filtered count
        $filtered = $wpdb->get_var("SELECT FOUND_ROWS()");
        error_log('Filtered Count: ' . $filtered);

        // Get total count for customer
        $total_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE customer_id = %d",
            $customer_id
        );
        error_log('Total Count Query: ' . $total_query);
        
        $total = $wpdb->get_var($total_query);
        error_log('Total Count: ' . $total);

        error_log('Results Data Sample: ' . print_r(array_slice($results, 0, 1), true));
        error_log('=== End Debug Employee DataTable Query ===');

        $result = [
            'data' => $results,
            'total' => (int) $total,
            'filtered' => (int) $filtered
        ];

        // Set cache with 2 minute duration - use lowercase orderDir
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

    /**
     * Get total employee count based on user permission with access_type filtering
     *
     * @param int|null $customer_id Optional customer ID for filtering
     * @return int Total number of employees
     */
    public function getTotalCount(?int $customer_id = null): int {
        global $wpdb;

        error_log('=== Debug CustomerEmployeeModel getTotalCount ===');
        error_log('User ID: ' . get_current_user_id());
        error_log('Customer ID param: ' . ($customer_id ?? 'null'));

        // Get user relation from CustomerModel to determine access
        $customerModel = new \WPCustomer\Models\Customer\CustomerModel();
        $relation = $customerModel->getUserRelation(0);
        $access_type = $relation['access_type'];

        error_log('Access type: ' . $access_type);
        error_log('Is admin: ' . ($relation['is_admin'] ? 'yes' : 'no'));
        error_log('Is customer admin: ' . ($relation['is_customer_admin'] ? 'yes' : 'no'));
        error_log('Is customer branch admin: ' . ($relation['is_customer_branch_admin'] ? 'yes' : 'no'));
        error_log('Is employee: ' . ($relation['is_customer_employee'] ? 'yes' : 'no'));

        // Base query parts
        $select = "SELECT SQL_CALC_FOUND_ROWS e.*";
        $from = " FROM {$this->table} e";
        $join = " LEFT JOIN {$this->branch_table} b ON e.branch_id = b.id
                  LEFT JOIN {$this->customer_table} c ON e.customer_id = c.id";

        // Default where clause
        $where = " WHERE 1=1";
        $params = [];

        // Add customer_id filter if provided
        if ($customer_id) {
            $where .= " AND e.customer_id = %d";
            $params[] = $customer_id;
            error_log('Added customer filter: customer_id = ' . $customer_id);
        }

        // Apply filtering based on access type
        if ($relation['is_admin']) {
            // Administrator - see all employees
            error_log('User is admin - no additional restrictions');
        }
        elseif ($access_type === 'platform') {
            // Platform users (from wp-app-core) - see all employees
            // No additional restrictions (same as admin)
            // Access controlled via WordPress capabilities (view_customer_employee_detail)
            error_log('User is platform - no additional restrictions');
        }
        elseif ($relation['is_customer_admin']) {
            // Customer Admin - see all employees under their customer
            $where .= " AND c.user_id = %d";
            $params[] = get_current_user_id();
            error_log('Added customer admin restriction');
        }
        elseif ($relation['is_customer_branch_admin']) {
            // Customer Branch Admin - only see employees in their managed branch
            $branch_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$this->branch_table}
                 WHERE user_id = %d LIMIT 1",
                get_current_user_id()
            ));

            if ($branch_id) {
                $where .= " AND e.branch_id = %d";
                $params[] = $branch_id;
                error_log('Added customer branch admin restriction for branch: ' . $branch_id);
            } else {
                $where .= " AND 1=0"; // No branch found
                error_log('Customer branch admin has no branch - blocking access');
            }
        }
        elseif ($relation['is_customer_employee']) {
            // Employee - only see employees in the same branch
            $employee_branch = $wpdb->get_var($wpdb->prepare(
                "SELECT branch_id FROM {$this->table}
                 WHERE user_id = %d AND status = 'active' LIMIT 1",
                get_current_user_id()
            ));

            if ($employee_branch) {
                $where .= " AND e.branch_id = %d";
                $params[] = $employee_branch;
                error_log('Added employee restriction for branch: ' . $employee_branch);
            } else {
                $where .= " AND 1=0"; // No branch found
                error_log('Employee has no branch - blocking access');
            }
        }
        else {
            // No access
            $where .= " AND 1=0";
            error_log('User has no access - blocking all');
        }

        // Complete query
        $query = $select . $from . $join . $where;
        $final_query = !empty($params) ? $wpdb->prepare($query, $params) : $query;

        error_log('Final Query: ' . $final_query);

        // Execute query
        $wpdb->get_results($final_query);

        // Get total and log
        $total = (int) $wpdb->get_var("SELECT FOUND_ROWS()");
        error_log('Total count result: ' . $total);
        error_log('=== End Debug ===');

        return $total;
    }

    /**
     * Get employees by branch
     */
    public function getByBranch(int $branch_id): array {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare("
            SELECT e.*
            FROM {$this->table} e
            WHERE e.branch_id = %d
            ORDER BY e.name ASC
        ", $branch_id));
    }


    // Add method to validate status
    public function isValidStatus(string $status): bool {
        return in_array($status, self::VALID_STATUSES);
    }

    // Update changeStatus method to validate status

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
		$this->table,
		[
		    'status' => $status,
		    'updated_at' => current_time('mysql')
		],
		['id' => $id],
		['%s', '%s'],
		['%d']
	    );

	    if ($result !== false) {
		// ✓ FIXED: Invalidate ALL employee cache keys
		$this->cache->delete('customer_employee', $id);
		$this->cache->delete('customer_employee_count', (string)$customer_id);
		$this->cache->delete('active_customer_employee_count', (string)$customer_id);

		// Invalidate getUserInfo cache (for admin bar)
		if ($employee->user_id) {
		    $this->cache->delete('customer_user_info', $employee->user_id);
		}

		// Invalidate DataTable cache for all access types
		$this->invalidateAllDataTableCache('customer_employee_list', (int)$customer_id);
	    }

	    return $result !== false;
	}


    /**
     * Get employees in batches for efficient processing
     * This helps when dealing with large datasets
     */
    public function getInBatches(int $customer_id, int $batch_size = 1000): \Generator {
        global $wpdb;
        
        $offset = 0;
        
        while (true) {
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT e.*, 
                       b.name as branch_name,
                       u.display_name as created_by_name
                FROM {$this->table} e
                LEFT JOIN {$this->branch_table} b ON e.branch_id = b.id
                LEFT JOIN {$wpdb->users} u ON e.created_by = u.ID
                WHERE e.customer_id = %d
                LIMIT %d OFFSET %d
            ", $customer_id, $batch_size, $offset));
            
            if (empty($results)) {
                break;
            }
            
            yield $results;
            
            $offset += $batch_size;
            
            if (count($results) < $batch_size) {
                break;
            }
        }
    }

    /**
     * Bulk update employees
     * Useful for mass status changes or department updates
     */
    public function bulkUpdate(array $ids, array $data): int {
        global $wpdb;

        $validFields = [
            'branch_id',
            'status',
            'finance',
            'operation',
            'legal',
            'purchase'
        ];

        // Filter only valid fields
        $updateData = array_intersect_key($data, array_flip($validFields));

        if (empty($updateData)) {
            return 0;
        }

        $sql = "UPDATE {$this->table} SET ";
        $updates = [];
        $values = [];

        foreach ($updateData as $field => $value) {
            $updates[] = "{$field} = %s";
            $values[] = $value;
        }

        $sql .= implode(', ', $updates);
        $sql .= " WHERE id IN (" . implode(',', array_map('intval', $ids)) . ")";

        // Add updated_at timestamp
        $sql .= ", updated_at = %s";
        $values[] = current_time('mysql');

        return $wpdb->query($wpdb->prepare($sql, $values));
    }

    /**
     * Invalidate DataTable cache for all access types
     *
     * Since cache keys use access_type as component, we need to invalidate
     * all possible access types when data changes
     *
     * @param string $context The DataTable context (e.g., 'customer_employee_list')
     * @param int $customer_id The customer ID to invalidate cache for
     * @return void
     */
    private function invalidateAllDataTableCache(string $context, int $customer_id): void {
        try {
            $cache_group = 'wp_customer';
            $customer_hash = md5(serialize($customer_id));

            // List of all possible access types
            $access_types = [
                'admin',
                'customer_admin',
                'customer_branch_admin',
                'customer_employee',
                'none'
            ];

            // Possible pagination/ordering variations to try
            $starts = [0, 10, 20, 30, 40, 50];
            $lengths = [10, 25, 50, 100];
            $orders = ['asc', 'desc'];
            $columns = ['name', 'department', 'branch_name', 'status'];

            $deleted = 0;

            // Brute force delete all possible cache key combinations
            foreach ($access_types as $access_type) {
                foreach ($starts as $start) {
                    foreach ($lengths as $length) {
                        foreach ($orders as $orderDir) {
                            foreach ($columns as $orderColumn) {
                                // Try with empty search
                                $components = [
                                    $context,
                                    $access_type,
                                    "start_{$start}",
                                    "length_{$length}",
                                    md5(''), // empty search
                                    $orderColumn,
                                    $orderDir,
                                    "customer_id_{$customer_hash}"
                                ];

                                $key = $this->cache->delete('datatable', ...$components);
                                if ($key) $deleted++;
                            }
                        }
                    }
                }
            }

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Invalidated $deleted DataTable cache entries for context=$context, customer_id=$customer_id (brute force method)");
            }
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Error in invalidateAllDataTableCache: " . $e->getMessage());
            }
        }
    }

    /**
     * Ambil data lengkap employee berdasarkan user_id
     */
    public function getByUserId($user_id) {
        global $wpdb;

        $table_employees = "{$wpdb->prefix}app_customer_employees";
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

         // Debug: tampilkan query SQL ke error_log
         error_log('=== [CustomerEmployeeModel] SQL Query ===');
         error_log($query);


        $result = $wpdb->get_row($query, ARRAY_A);

        // Debug hasil query
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log('Query Result: ' . print_r($result, true));
        }

        return $result;
    }

    /**
     * Get comprehensive user information for admin bar integration
     *
     * This method retrieves complete user data including:
     * - Customer Employee information
     * - Customer details (code, name, npwp, nib, status)
     * - Branch details (code, name, type, nitku, address, phone, email, postal_code, latitude, longitude)
     * - Membership details (level_id, status, period_months, start_date, end_date, price_paid, payment_status, payment_method, payment_date)
     * - User email and capabilities
     *
     * Tries multiple user types in order:
     * 1. Employee (most common)
     * 2. Customer owner
     * 3. Branch admin
     * 4. Fallback (user with role but no entity)
     *
     * @param int $user_id WordPress user ID
     * @return array|null Array of user info or null if not found
     *
     * @version     1.0.10 - Refactored to handle all user types (employee, owner, branch admin)
     */
    public function getUserInfo(int $user_id): ?array {
        // Try to get from cache first
        $cache_key = 'customer_user_info';
        $cached_data = $this->cache->get($cache_key, $user_id);

        if ($cached_data !== null) {
            return $cached_data;
        }

        // TODO-2176: Single query optimization
        // Replace sequential queries (employee → owner → branch admin → fallback) with 1 optimized query
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
     * Get employee user information
     *
     * @param int $user_id WordPress user ID
     * @return array|null Employee info or null if not an employee
     */
    private function getEmployeeInfo(int $user_id): ?array {
        global $wpdb;

        // Single comprehensive query to get ALL user data
        // This query JOINs employees, customers, branches, memberships, users, and usermeta
        $user_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM (
                SELECT
                    e.*,
                    MAX(c.code) AS customer_code,
                    MAX(c.name) AS customer_name,
                    MAX(c.npwp) AS customer_npwp,
                    MAX(c.nib) AS customer_nib,
                    MAX(c.status) AS customer_status,
                    MAX(b.code) AS branch_code,
                    MAX(b.name) AS branch_name,
                    MAX(b.type) AS branch_type,
                    MAX(b.nitku) AS branch_nitku,
                    MAX(b.address) AS branch_address,
                    MAX(b.phone) AS branch_phone,
                    MAX(b.email) AS branch_email,
                    MAX(b.postal_code) AS branch_postal_code,
                    MAX(b.latitude) AS branch_latitude,
                    MAX(b.longitude) AS branch_longitude,
                    MAX(cm.level_id) AS membership_level_id,
                    MAX(cm.status) AS membership_status,
                    MAX(cm.period_months) AS membership_period_months,
                    MAX(cm.start_date) AS membership_start_date,
                    MAX(cm.end_date) AS membership_end_date,
                    MAX(cm.price_paid) AS membership_price_paid,
                    MAX(cm.payment_status) AS membership_payment_status,
                    MAX(cm.payment_method) AS membership_payment_method,
                    MAX(cm.payment_date) AS membership_payment_date,
                    u.user_login,
                    u.user_nicename,
                    u.user_email,
                    u.user_url,
                    u.user_registered,
                    u.user_status,
                    u.display_name,
                    MAX(um.meta_value) AS capabilities,
                    MAX(CASE WHEN um2.meta_key = 'first_name' THEN um2.meta_value END) AS first_name,
                    MAX(CASE WHEN um2.meta_key = 'last_name' THEN um2.meta_value END) AS last_name,
                    MAX(CASE WHEN um2.meta_key = 'description' THEN um2.meta_value END) AS description
                FROM
                    {$wpdb->prefix}app_customer_employees e
                INNER JOIN
                    {$wpdb->prefix}app_customers c ON e.customer_id = c.id
                INNER JOIN
                    {$wpdb->prefix}app_customer_branches b ON e.branch_id = b.id
                LEFT JOIN
                    {$wpdb->prefix}app_customer_memberships cm ON cm.customer_id = e.customer_id
                    AND cm.branch_id = e.branch_id
                    AND cm.status = 'active'
                INNER JOIN
                    {$wpdb->users} u ON e.user_id = u.ID
                INNER JOIN
                    {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = '{$wpdb->prefix}capabilities'
                LEFT JOIN
                    {$wpdb->usermeta} um2 ON u.ID = um2.user_id
                    AND um2.meta_key IN ('first_name', 'last_name', 'description')
                WHERE
                    e.user_id = %d
                    AND e.status = 'active'
                GROUP BY
                    e.id,
                    e.user_id,
                    u.ID,
                    u.user_login,
                    u.user_nicename,
                    u.user_email,
                    u.user_url,
                    u.user_registered,
                    u.user_status,
                    u.display_name
            ) AS subquery
            GROUP BY
                subquery.id
            LIMIT 1",
            $user_id
        ));

        if (!$user_data || !$user_data->branch_name) {
            return null;
        }

        // Build result array
        $result = [
            'entity_name' => $user_data->customer_name,
            'entity_code' => $user_data->customer_code,
            'customer_id' => $user_data->customer_id,
            'customer_npwp' => $user_data->customer_npwp,
            'customer_nib' => $user_data->customer_nib,
            'customer_status' => $user_data->customer_status,
            'branch_id' => $user_data->branch_id,
            'branch_code' => $user_data->branch_code,
            'branch_name' => $user_data->branch_name,
            'branch_type' => $user_data->branch_type,
            'branch_nitku' => $user_data->branch_nitku,
            'branch_address' => $user_data->branch_address,
            'branch_phone' => $user_data->branch_phone,
            'branch_email' => $user_data->branch_email,
            'branch_postal_code' => $user_data->branch_postal_code,
            'branch_latitude' => $user_data->branch_latitude,
            'branch_longitude' => $user_data->branch_longitude,
            'membership_level_id' => $user_data->membership_level_id,
            'membership_status' => $user_data->membership_status,
            'membership_period_months' => $user_data->membership_period_months,
            'membership_start_date' => $user_data->membership_start_date,
            'membership_end_date' => $user_data->membership_end_date,
            'membership_price_paid' => $user_data->membership_price_paid,
            'membership_payment_status' => $user_data->membership_payment_status,
            'membership_payment_method' => $user_data->membership_payment_method,
            'membership_payment_date' => $user_data->membership_payment_date,
            'position' => $user_data->position,
            'user_email' => $user_data->user_email,
            'capabilities' => $user_data->capabilities,
            'relation_type' => 'customer_employee',
            'icon' => '🏢'
        ];

        // Add role names dynamically from capabilities
        // Use AdminBarModel for generic capability parsing
        $admin_bar_model = new \WPAppCore\Models\AdminBarModel();

        $result['role_names'] = $admin_bar_model->getRoleNamesFromCapabilities(
            $user_data->capabilities,
            call_user_func(['WP_Customer_Role_Manager', 'getRoleSlugs']),
            ['WP_Customer_Role_Manager', 'getRoleName']
        );

        // Add permission names list
        // IMPORTANT: Use WP_User->allcaps to get ACTUAL permissions (including inherited from roles)
        // Not from wp_usermeta which only contains role assignments!
        $permission_model = new \WPCustomer\Models\Settings\PermissionModel();
        $result['permission_names'] = $admin_bar_model->getPermissionNamesFromUserId(
            $user_id,
            call_user_func(['WP_Customer_Role_Manager', 'getRoleSlugs']),
            $permission_model->getAllCapabilities()
        );

        return $result;
    }

    /**
     * Get customer owner user information
     *
     * @param int $user_id WordPress user ID
     * @return array|null Customer owner info or null if not a customer owner
     */
    private function getCustomerOwnerInfo(int $user_id): ?array {
        global $wpdb;

        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT c.id, c.name as customer_name, c.code as customer_code
             FROM {$wpdb->prefix}app_customers c
             WHERE c.user_id = %d",
            $user_id
        ));

        if (!$customer) {
            return null;
        }

        // User is a customer owner, get their main branch
        $branch = $wpdb->get_row($wpdb->prepare(
            "SELECT b.id, b.name, b.type
             FROM {$wpdb->prefix}app_customer_branches b
             WHERE b.customer_id = %d
             AND b.type = 'pusat'
             ORDER BY b.id ASC
             LIMIT 1",
            $customer->id
        ));

        if (!$branch) {
            return null;
        }

        return [
            'branch_id' => $branch->id,
            'branch_name' => $branch->name,
            'branch_type' => $branch->type,
            'entity_name' => $customer->customer_name,
            'entity_code' => $customer->customer_code,
            'relation_type' => 'owner',
            'icon' => '🏢'
        ];
    }

    /**
     * Get branch admin user information
     *
     * @param int $user_id WordPress user ID
     * @return array|null Branch admin info or null if not a branch admin
     */
    private function getBranchAdminInfo(int $user_id): ?array {
        global $wpdb;

        $customer_branch_admin = $wpdb->get_row($wpdb->prepare(
            "SELECT b.id, b.name, b.type, b.customer_id,
                    c.name as customer_name, c.code as customer_code
             FROM {$wpdb->prefix}app_customer_branches b
             LEFT JOIN {$wpdb->prefix}app_customers c ON b.customer_id = c.id
             WHERE b.user_id = %d",
            $user_id
        ));

        if (!$customer_branch_admin) {
            return null;
        }

        return [
            'branch_id' => $customer_branch_admin->id,
            'branch_name' => $customer_branch_admin->name,
            'branch_type' => $customer_branch_admin->type,
            'entity_name' => $customer_branch_admin->customer_name,
            'entity_code' => $customer_branch_admin->customer_code,
            'relation_type' => 'branch_admin',
            'icon' => '🏢'
        ];
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

}

