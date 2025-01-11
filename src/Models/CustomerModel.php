<?php
/**
 * Customer Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/CustomerModel.php
 *
 * Description: Model untuk mengelola data customer di database.
 *              Handles operasi CRUD dengan caching terintegrasi.
 *              Includes query optimization dan data formatting.
 *              Menyediakan metode untuk DataTables server-side.
 *
 * Changelog:
 * 2.0.0 - 2024-12-03 15:00:00
 * - Refactor create/update untuk return complete data
 * - Added proper error handling dan validasi
 * - Improved cache integration
 * - Added method untuk DataTables server-side
 */

 namespace WPCustomer\Models;

 class CustomerModel {
     private $table;
     private $branch_table;

     public function __construct() {
         global $wpdb;
         $this->table = $wpdb->prefix . 'app_customers';
         $this->branch_table = $wpdb->prefix . 'app_branches';
     }

    public function create(array $data): ?int {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table,
            [
                'code' => $data['code'],
                'name' => $data['name'],
                'user_id' => $data['user_id'],
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['%s', '%s', '%d', '%d', '%s', '%s']
        );

        if ($result === false) {
            return null;
        }

        return (int) $wpdb->insert_id;
    }

    public function find($id): ?object {
        global $wpdb;

        // Ensure integer type for ID
        $id = (int) $id;

        $result = $wpdb->get_row($wpdb->prepare("
            SELECT p.*, 
                   COUNT(r.id) as branch_count,
                   u.display_name as owner_name
            FROM {$this->table} p
            LEFT JOIN {$this->branch_table} r ON p.id = r.customer_id
            LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
            WHERE p.id = %d
            GROUP BY p.id
        ", $id));

        if ($result === null) {
            return null;
        }

        // Ensure branch_count is always an integer
        $result->branch_count = (int) $result->branch_count;

        return $result;
    }

    public function update(int $id, array $data): bool {
        global $wpdb;

        $updateData = array_merge($data, ['updated_at' => current_time('mysql')]);
        $format = [];

        // Add format for each field
        if (isset($data['code'])) $format[] = '%s';
        if (isset($data['name'])) $format[] = '%s';
        if (isset($data['user_id'])) $format[] = '%d';
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

    public function getDataTableData(int $start, int $length, string $search, string $orderColumn, string $orderDir): array {
        global $wpdb;

        // Base query parts
        $select = "SELECT SQL_CALC_FOUND_ROWS p.*, 
                         COUNT(r.id) as branch_count,
                         u.display_name as owner_name";
        $from = " FROM {$this->table} p";
        $join = " LEFT JOIN {$this->branch_table} r ON p.id = r.customer_id
                  LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID";
        $where = " WHERE 1=1";
        $group = " GROUP BY p.id";

        // Add search if provided
        if (!empty($search)) {
            $where .= $wpdb->prepare(
                " AND (p.name LIKE %s OR p.code LIKE %s OR u.display_name LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        // Check for user-specific view permission
        if (current_user_can('view_customer_list') && current_user_can('edit_own_customer')) {
            $current_user_id = get_current_user_id();
            $where .= $wpdb->prepare(" AND p.user_id = %d", $current_user_id);
        }


        if (!current_user_can('view_customer_list') && current_user_can('view_own_customer')) {
            //$current_user_id = get_current_user_id();
            //$where .= $wpdb->prepare(" AND p.user_id = %d", $current_user_id);
        }

        
        // Validate order column
        $validColumns = ['code', 'name', 'branch_count', 'owner_name'];
        if (!in_array($orderColumn, $validColumns)) {
            $orderColumn = 'code';
        }

        // Map frontend column to actual column
        $orderColumnMap = [
            'owner_name' => 'u.display_name',
            'code' => 'p.code',
            'name' => 'p.name',
            'branch_count' => 'branch_count'
        ];

        $orderColumn = $orderColumnMap[$orderColumn] ?? 'p.code';

        // Validate order direction
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

        // Build order clause
        $order = " ORDER BY " . esc_sql($orderColumn) . " " . esc_sql($orderDir);

        // Add limit
        $limit = $wpdb->prepare(" LIMIT %d, %d", $start, $length);

        // Complete query
        $sql = $select . $from . $join . $where . $group . $order . $limit;

        // Get paginated results
        $results = $wpdb->get_results($sql);

        if ($results === null) {
            throw new \Exception($wpdb->last_error);
        }

        // Get total filtered count
        $filtered = $wpdb->get_var("SELECT FOUND_ROWS()");

        // Get total count
        $total = $wpdb->get_var("SELECT COUNT(DISTINCT id) FROM {$this->table}");

        return [
            'data' => $results,
            'total' => (int) $total,
            'filtered' => (int) $filtered
        ];
    }

     public function delete(int $id): bool {
         global $wpdb;

         return $wpdb->delete(
             $this->table,
             ['id' => $id],
             ['%d']
         ) !== false;
     }

    public function existsByCode(string $code, ?int $excludeId = null): bool {
        global $wpdb;

        $sql = "SELECT EXISTS (SELECT 1 FROM {$this->table} WHERE code = %s";
        $params = [$code];

        if ($excludeId) {
            $sql .= " AND id != %d";
            $params[] = $excludeId;
        }

        $sql .= ") as result";

        return (bool) $wpdb->get_var($wpdb->prepare($sql, $params));
    }

     public function getBranchCount(int $id): int {
         global $wpdb;

         return (int) $wpdb->get_var($wpdb->prepare("
             SELECT COUNT(*)
             FROM {$this->branch_table}
             WHERE customer_id = %d
         ", $id));
     }

     public function existsByName(string $name, ?int $excludeId = null): bool {
         global $wpdb;

         $sql = "SELECT EXISTS (SELECT 1 FROM {$this->table} WHERE name = %s";
         $params = [$name];

         if ($excludeId) {
             $sql .= " AND id != %d";
             $params[] = $excludeId;
         }

         $sql .= ") as result";

         return (bool) $wpdb->get_var($wpdb->prepare($sql, $params));
     }

     public function getTotalCount(): int {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");
    }
    
 }
