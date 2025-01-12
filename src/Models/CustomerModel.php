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

        // Debug capabilities
        error_log('--- Debug User Capabilities ---');
        error_log('User ID: ' . get_current_user_id());
        error_log('Can view_customer_list: ' . (current_user_can('view_customer_list') ? 'yes' : 'no'));
        error_log('Can view_own_customer: ' . (current_user_can('view_own_customer') ? 'yes' : 'no'));
        error_log('Can edit_own_customer: ' . (current_user_can('edit_own_customer') ? 'yes' : 'no'));
        error_log('Can edit_all_customers: ' . (current_user_can('edit_all_customers') ? 'yes' : 'no'));

        // Base query
        $select = "SELECT SQL_CALC_FOUND_ROWS p.*, 
                         COUNT(r.id) as branch_count";
        $from = " FROM {$this->table} p";
        $join = " LEFT JOIN {$this->branch_table} r ON p.id = r.customer_id";
        $where = " WHERE 1=1";
        $group = " GROUP BY p.id";

        // Debug query building process
        error_log('Building WHERE clause:');
        error_log('Initial WHERE: ' . $where);


        // Cek dulu current_user_id untuk berbagai keperluan 
        $current_user_id = get_current_user_id();

        // dapatkan relasi User ID word press dengan customer User ID
        // atau p.user_id
        // disini


        // Kondisi 1: View own customer
        // tambahkan jika user login adalah pemilik customer
        // nantinya disini akan bertambah dengan user yang berelasi dengan customer seperti staff dari customer

        // Dapatkan relasi User ID wordpress dengan customer User ID
        $has_customer = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = %d",
            $current_user_id
        ));
        error_log('User has customer: ' . ($has_customer > 0 ? 'yes' : 'no'));

        // Kondisi 1: View own customer
        if ($has_customer > 0 && current_user_can('view_customer_list') && current_user_can('edit_own_customer')) {
            $where .= $wpdb->prepare(" AND p.user_id = %d", $current_user_id);
            $select .= ", u.display_name as owner_name";  
            $join .= " LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID";        
            error_log('Added own customer restriction: ' . $where);  
            error_log('Added own customer relation: ' . $join);
        }

        // Kondisi 2: Edit all customers
        if (current_user_can('view_customer_list') && current_user_can('edit_all_customers')) {
            // Tidak perlu tambahan WHERE karena bisa lihat semua
            error_log('User can edit all customers - no additional restrictions');
        }

        // Kondisi 3: Search filter
        if (!empty($search)) {
            $where .= $wpdb->prepare(
                " AND (p.name LIKE %s OR p.code LIKE %s OR u.display_name LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
            error_log('Added search filter: ' . $where);
        }
        
        // Map frontend column to actual column - ini sudah ada
        $orderColumnMap = [
            'owner_name' => 'u.display_name',
            'code' => 'p.code',
            'name' => 'p.name',
            'branch_count' => 'branch_count'
        ];

        $orderColumn = $orderColumnMap[$orderColumn] ?? 'p.code';
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

        // Add order
        $order = " ORDER BY " . esc_sql($orderColumn) . " " . esc_sql($orderDir);

        // Add limit
        $limit = $wpdb->prepare(" LIMIT %d, %d", $start, $length);

        // Complete query
        $sql = $select . $from . $join . $where . $group . $order . $limit;
        
        error_log('Final Query: ' . $sql);
        
        // Execute query
        $results = $wpdb->get_results($sql);
        if ($results === null) {
            error_log('Query Error: ' . $wpdb->last_error);
        }

        // Get counts
        $filtered = $wpdb->get_var("SELECT FOUND_ROWS()");
        $total = $wpdb->get_var("SELECT COUNT(DISTINCT id) FROM {$this->table}");

        error_log('Filtered count: ' . $filtered);
        error_log('Total count: ' . $total);
        error_log('--- End Debug ---');

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

    /**
     * Get total branch count for a specific customer
     * 
     * Used for:
     * 1. Customer deletion validation - prevent deletion if customer has branches
     * 2. Display branch count in customer detail panel
     * 
     * Note: This method does NOT handle permission filtering as it's used for 
     * internal validation and UI display where the customer ID is already validated.
     * For permission-based branch counting, use getTotalBranchesByPermission() instead.
     *
     * @param int $id Customer ID
     * @return int Total number of branches owned by the customer
     */
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
        
        // Debug capabilities
        error_log('--- Debug CustomerModel getTotalCount ---');
        error_log('User ID: ' . get_current_user_id());
        error_log('Can view_customer_list: ' . (current_user_can('view_customer_list') ? 'yes' : 'no'));
        error_log('Can view_own_customer: ' . (current_user_can('view_own_customer') ? 'yes' : 'no'));

        // Base query parts
        $select = "SELECT COUNT(DISTINCT p.id)";
        $from = " FROM {$this->table} p";
        $where = " WHERE 1=1";
        $params = [];

        // Debug query building process
        error_log('Building WHERE clause:');
        error_log('Initial WHERE: ' . $where);

        $current_user_id = get_current_user_id();

        // Dapatkan relasi User ID wordpress dengan customer User ID
        $has_customer = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = %d",
            $current_user_id
        ));
        error_log('User has customer: ' . ($has_customer > 0 ? 'yes' : 'no'));

        // Kondisi permission check
        if ($has_customer > 0 && current_user_can('view_customer_list') && current_user_can('edit_own_customer')) {
            // User hanya bisa melihat customer miliknya
            $where .= $wpdb->prepare(" AND p.user_id = %d", $current_user_id);
            error_log('Added own customer restriction: ' . $where);
        } elseif (current_user_can('view_customer_list') && current_user_can('edit_all_customers')) {
            // Admin/user dengan akses penuh bisa melihat semua customer
            error_log('User can view all customers - no additional restrictions');
        }

        // Complete query
        $sql = $select . $from . $where;
        $final_query = !empty($params) ? $wpdb->prepare($sql, $params) : $sql;
        
        error_log('Final Query: ' . $final_query);
        
        // Execute query dan get total
        $total = (int) $wpdb->get_var($final_query);
        error_log('Total count result: ' . $total);
        error_log('--- End Debug ---');

        return $total;
    }
 
 }
