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

class CustomerEmployeeModel {
    private $table;
    private $customer_table;
    private $branch_table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'app_customer_employees';
        $this->customer_table = $wpdb->prefix . 'app_customers';
        $this->branch_table = $wpdb->prefix . 'app_branches';
    }

    public function create(array $data): ?int {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table,
            [
                'customer_id' => $data['customer_id'],
                'branch_id' => $data['branch_id'],
                'name' => $data['name'],
                'position' => $data['position'],
                'keterangan' => $data['keterangan'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
                'status' => 'active'
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );

        if ($result === false) {
            return null;
        }

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

        $updateData = array_merge($data, ['updated_at' => current_time('mysql')]);
        $format = [];

        // Add format for each field
        if (isset($data['name'])) $format[] = '%s';
        if (isset($data['position'])) $format[] = '%s';
        if (isset($data['keterangan'])) $format[] = '%s';
        if (isset($data['email'])) $format[] = '%s';
        if (isset($data['phone'])) $format[] = '%s';
        if (isset($data['status'])) $format[] = '%s';
        if (isset($data['branch_id'])) $format[] = '%d';
        $format[] = '%s'; // for updated_at

        $result = $wpdb->update(
            $this->table,
            $updateData,
            ['id' => $id],
            $format,
            ['%d']
        );

        return $result !== false;
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
            $where .= " AND (e.name LIKE %s OR e.keterangan LIKE %s)";
            $search_param = '%' . $wpdb->esc_like($search) . '%';
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
            error_log('Search Where Clause Added: ' . $where);
            error_log('Search Parameters: ' . print_r($params, true));
        }

        // Validate order column
        $validColumns = ['name', 'keterangan', 'branch_name', 'status'];
        if (!in_array($orderColumn, $validColumns)) {
            $orderColumn = 'name';
        }
        error_log('Validated Order Column: ' . $orderColumn);

        // Map frontend column to actual column
        $orderColumnMap = [
            'name' => 'e.name',
            'keterangan' => 'e.keterangan',
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

    /**
     * Change employee status
     */
    public function changeStatus(int $id, string $status): bool {
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

    
    public function getByCustomer($customer_id) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT e.*, b.name as branch_name 
             FROM {$this->table} e
             LEFT JOIN {$this->branch_table} b ON e.branch_id = b.id
             WHERE e.customer_id = %d 
             AND e.status = 'active'
             ORDER BY e.name ASC",
            $customer_id
        );
        
        return $wpdb->get_results($query);
    }

    public function getEmployeeData(int $user_id, int $customer_id): ?object {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, b.name as branch_name
             FROM {$this->table} e
             LEFT JOIN {$this->branch_table} b ON e.branch_id = b.id
             WHERE e.user_id = %d 
             AND e.customer_id = %d 
             AND e.status = 'active'",
            $user_id,
            $customer_id
        ));
    }
}
