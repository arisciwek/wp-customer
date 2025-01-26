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
     private $employee_table;

     public function __construct() {
         global $wpdb;
         $this->table = $wpdb->prefix . 'app_customers';
         $this->branch_table = $wpdb->prefix . 'app_branches';
         $this->employee_table = $wpdb->prefix . 'app_customer_employees';
     }

    private function generateCustomerCode(): string {
        do {
            $timestamp = substr(time(), -4);
            $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $code = 'CUST-' . $timestamp . $random;
            
            $exists = $this->existsByCode($code);
        } while ($exists);
        
        return $code;
    }

    public function create(array $data): ?int {
        global $wpdb;
        
        // Debug incoming data
        error_log('CustomerModel::create() - Input data: ' . print_r($data, true));
        
        $data['code'] = $this->generateCustomerCode();
        
        // Prepare insert data
        $insert_data = [
            'code' => $data['code'],
            'name' => $data['name'],
            'npwp' => $data['npwp'] ?? null,
            'nib' => $data['nib'] ?? null,
            'status' => $data['status'] ?? 'active',
            'user_id' => $data['user_id'],
            'provinsi_id' => $data['provinsi_id'] ?? null,
            'regency_id' => $data['regency_id'] ?? null,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        // Debug prepared data
        error_log('CustomerModel::create() - Prepared data for insert: ' . print_r($insert_data, true));

        // Prepare format array for $wpdb->insert
        $format = [
            '%s',  // code
            '%s',  // name
            '%s',  // npwp (nullable)
            '%s',  // nib (nullable)
            '%s',  // status
            '%d',  // user_id
            '%d',  // provinsi_id (nullable)
            '%d',  // regency_id (nullable)
            '%d',  // created_by
            '%s',  // created_at
            '%s'   // updated_at
        ];

        // Attempt the insert
        $result = $wpdb->insert(
            $this->table,
            $insert_data,
            $format
        );

        // Debug insert result
        if ($result === false) {
            error_log('CustomerModel::create() - Insert failed. Last error: ' . $wpdb->last_error);
            return null;
        }

        $new_id = (int) $wpdb->insert_id;
        error_log('CustomerModel::create() - Insert successful. New ID: ' . $new_id);

        return $new_id;
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

    // Di CustomerModel// Di CustomerModel
    public function getCustomer(?int $id = null): ?object {
        global $wpdb;

        // Basic query structure
        $select = "SELECT p.*, 
                   COUNT(r.id) as branch_count,
                   u.display_name as owner_name";
        $from = " FROM {$this->table} p";
        $join = " LEFT JOIN {$this->branch_table} r ON p.id = r.customer_id
                  LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID";

        // Handle different cases
        if (current_user_can('edit_all_customers')) {
            // Admin bisa akses semua customer
            $where = $id ? $wpdb->prepare(" WHERE p.id = %d", $id) : "";
        } else {
            // Regular user hanya bisa lihat customer sendiri
            $where = $wpdb->prepare(" WHERE p.user_id = %d", get_current_user_id());
            if ($id) {
                $where .= $wpdb->prepare(" AND p.id = %d", $id);
            }
        }

        $group = " GROUP BY p.id";
        $sql = $select . $from . $join . $where . $group;

        $result = $wpdb->get_row($sql);

        if ($result) {
            $result->branch_count = (int) $result->branch_count;
        }

        return $result;
    }

    public function getMembershipData(int $customer_id): array {
        // Get membership settings
        $settings = get_option('wp_customer_membership_settings', []);
        
        // Get customer data untuk cek level
        $customer = $this->find($customer_id);
        $level = $customer->membership_level ?? $settings['default_level'] ?? 'regular';

        return [
            'level' => $level,
            'max_staff' => $settings["{$level}_max_staff"] ?? 2,
            'capabilities' => [
                'can_add_staff' => $settings["{$level}_can_add_staff"] ?? false,
                'can_export' => $settings["{$level}_can_export"] ?? false,
                'can_bulk_import' => $settings["{$level}_can_bulk_import"] ?? false,
            ]
        ];
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
       $current_user_id = get_current_user_id();

       // Debug capabilities
       error_log('--- Debug User Capabilities ---');
       error_log('User ID: ' . $current_user_id);
       error_log('Can view_customer_list: ' . (current_user_can('view_customer_list') ? 'yes' : 'no'));
       error_log('Can view_own_customer: ' . (current_user_can('view_own_customer') ? 'yes' : 'no'));

       // Base query parts
       $select = "SELECT SQL_CALC_FOUND_ROWS p.*, COUNT(b.id) as branch_count";
       $from = " FROM {$this->table} p";
       $join = " LEFT JOIN {$this->branch_table} b ON p.id = b.customer_id";
       $where = " WHERE 1=1";

       // Debug query building process
       error_log('Building WHERE clause:');
       error_log('Initial WHERE: ' . $where);

       // Check user relationships
       $has_customer = $wpdb->get_var($wpdb->prepare(
           "SELECT COUNT(*) FROM {$this->table} WHERE user_id = %d",
           $current_user_id
       ));
       error_log('User has customer: ' . ($has_customer > 0 ? 'yes' : 'no'));

       $employee_customer = $wpdb->get_var($wpdb->prepare(
           "SELECT customer_id FROM {$this->employee_table} WHERE user_id = %d",
           $current_user_id
       ));
       error_log('User is employee of customer: ' . ($employee_customer ? $employee_customer : 'no'));

       $branch_admin = $wpdb->get_var($wpdb->prepare(
           "SELECT customer_id FROM {$this->branch_table} WHERE user_id = %d",
           $current_user_id
       ));
       error_log('User is branch admin of customer: ' . ($branch_admin ? $branch_admin : 'no'));

       // Handle permissions
       if (current_user_can('edit_all_customers')) {
           error_log('User can edit all customers - no additional restrictions');
       }
       else if ($has_customer > 0 && current_user_can('view_own_customer')) {
           $where .= $wpdb->prepare(" AND p.user_id = %d", $current_user_id);
           error_log('Added owner restriction: ' . $where);
       }
       else if ($employee_customer && current_user_can('view_own_customer')) {
           $where .= $wpdb->prepare(" AND p.id = %d", $employee_customer);
           error_log('Added employee restriction: ' . $where);
       }
       else if ($branch_admin && current_user_can('view_own_customer')) {
           $where .= $wpdb->prepare(" AND p.id = %d", $branch_admin);
           error_log('Added branch admin restriction: ' . $where);
       }
       else {
           $where .= " AND 1=0";
           error_log('User has no access - restricting all results');
       }

       // Add search if provided  
       if (!empty($search)) {
           $where .= $wpdb->prepare(" AND (p.name LIKE %s OR p.code LIKE %s)",
               '%' . $wpdb->esc_like($search) . '%',
               '%' . $wpdb->esc_like($search) . '%'
           );
           error_log('Added search: ' . $where);
       }

       // Complete query
       $group = " GROUP BY p.id";
       $order = " ORDER BY " . esc_sql($orderColumn) . " " . esc_sql($orderDir);
       $limit = $wpdb->prepare(" LIMIT %d, %d", $start, $length);

       $sql = $select . $from . $join . $where . $group . $order . $limit;
       error_log('Final Query: ' . $sql);

       $results = $wpdb->get_results($sql);
       error_log('Results count: ' . count($results));

       $filtered = $wpdb->get_var("SELECT FOUND_ROWS()");
       error_log('Filtered count: ' . $filtered);

       $total = $this->getTotalCount();
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
     
    // Di CustomerModel.php
    public function getProvinsiOptions() {
        return apply_filters('wilayah_indonesia_get_province_options', [
            '' => __('Pilih Provinsi', 'wp-customer')
        ], true);
    }

    public function getRegencyOptions($provinsi_id) {
        return apply_filters(
            'wilayah_indonesia_get_regency_options',
            [],
            $provinsi_id,
            true
        );
    }
 
 }
