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
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['%s', '%s', '%d', '%s', '%s']
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
             SELECT p.*, COUNT(r.id) as branch_count
             FROM {$this->table} p
             LEFT JOIN {$this->branch_table} r ON p.id = r.customer_id
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

     public function getDataTableData(int $start, int $length, string $search, string $orderColumn, string $orderDir): array {
         global $wpdb;

         // Base query parts
         $select = "SELECT SQL_CALC_FOUND_ROWS p.*, COUNT(r.id) as branch_count";
         $from = " FROM {$this->table} p";
         $join = " LEFT JOIN {$this->branch_table} r ON p.id = r.customer_id";
         $where = " WHERE 1=1";
         $group = " GROUP BY p.id";

         // Add search if provided
         if (!empty($search)) {
             $where .= $wpdb->prepare(
                 " AND p.name LIKE %s",
                 '%' . $wpdb->esc_like($search) . '%'
             );
         }

         // Validate order column
         $validColumns = ['name', 'branch_count'];
         if (!in_array($orderColumn, $validColumns)) {
             $orderColumn = 'name';
         }

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
