<?php
/**
 * Customer Employee Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models/Employee
 * @version     1.0.0
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
            'user_id' => get_current_user_id(),
            'name' => $data['name'],
            'position' => $data['position'],
            'finance' => $data['finance'],
            'operation' => $data['operation'],
            'legal' => $data['legal'],
            'purchase' => $data['purchase'],
            'keterangan' => $data['keterangan'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'created_by' => get_current_user_id(),
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

    // Clear cache untuk customer yang bersangkutan
    $this->cache->delete('customer_active_employee_count', (string)$data['customer_id']);

    return (int) $wpdb->insert_id;
}

    public function find(int $id): ?object {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare("
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

        return $wpdb->update(
            $this->table,
            $updateData,
            ['id' => $id],
            $format,
            ['%d']
        ) !== false;
    }

    public function delete(int $id): bool {
        global $wpdb;

        return $wpdb->delete(
            $this->table,
            ['id' => $id],
            ['%d']
        ) !== false;
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

        error_log('=== Start Debug Employee DataTable Query ===');
        error_log('Customer ID: ' . $customer_id);
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

        return [
            'data' => $results,
            'total' => (int) $total,
            'filtered' => (int) $filtered
        ];
    }
    
    /**
     * Get total employee count for a specific customer
     */
    public function getTotalCount(?int $customer_id = null): int {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $params = [];

        if ($customer_id) {
            $sql .= " WHERE customer_id = %d";
            $params[] = $customer_id;
        }

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return (int) $wpdb->get_var($sql);
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

        global $wpdb;
        return $wpdb->update(
            $this->table,
            [
                'status' => $status,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        ) !== false;
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


}

