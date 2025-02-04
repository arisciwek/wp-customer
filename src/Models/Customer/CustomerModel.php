<?php
/**
 * Customer Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Customer/CustomerModel.php
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

 namespace WPCustomer\Models\Customer;

 use WPCustomer\Cache\CacheManager;
 
 class CustomerModel {
     private $table;
     private $branch_table;
     private $employee_table;
     private $cache;
     static $used_codes = [];


     public function __construct() {
         global $wpdb;
         $this->table = $wpdb->prefix . 'app_customers';
         $this->branch_table = $wpdb->prefix . 'app_branches';
         $this->employee_table = $wpdb->prefix . 'app_customer_employees';
         $this->cache = new CacheManager();
     }

    public function find($id): ?object {
        global $wpdb;
        $id = (int) $id;

        // Check cache first
        $cached_result = $this->cache->get('customer_detail', $id);
        if ($cached_result !== null) {
            return $cached_result;
        }

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

        if ($result) {
            // Cache for 2 minutes
            $this->cache->set('customer_detail', $result, 120, $id);
        }

        return $result;
    }

    public function getCustomer(?int $id = null): ?object {
        // Check cache first
        if ($id !== null) {
            $cached_result = $this->cache->get('customer', $id);
            if ($cached_result !== null) {
                return $cached_result;
            }
        }

        global $wpdb;
        $current_user_id = get_current_user_id();

        // Base query structure
        $select = "SELECT p.*, 
                   COUNT(r.id) as branch_count,
                   u.display_name as owner_name";
        $from = " FROM {$this->table} p";
        $join = " LEFT JOIN {$this->branch_table} r ON p.id = r.customer_id
                  LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID";

        // Handle different cases
        if (current_user_can('edit_all_customers')) {
            $where = $id ? $wpdb->prepare(" WHERE p.id = %d", $id) : "";
        } else {
            $where = $wpdb->prepare(" WHERE p.user_id = %d", $current_user_id);
            if ($id) {
                $where .= $wpdb->prepare(" AND p.id = %d", $id);
            }
        }

        $group = " GROUP BY p.id";
        $sql = $select . $from . $join . $where . $group;

        $result = $wpdb->get_row($sql);

        if ($result && $id !== null) {
            // Cache for 2 minutes
            $this->cache->set('customer', $result, 120, $id);
        }

        return $result;
    }

    /**
     * Generate unique customer code
     * Format: TTTTRRXxRRXx
     * TTTT = 4 digit timestamp
     * Xx = 1 uppercase + 1 lowercase letters
     * RR = 2 digit random number
     * Xx = 1 uppercase + 1 lowercase letters
     */
    public static function generateCustomerCode(): string {
        do {
            // Get 4 digits from timestamp
            $timestamp = substr(time(), -4);
                        
            // Generate first Xx (1 upper + 1 lower)
            $upperLetter1 = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
            $lowerLetter1 = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 1);
            
            // Generate second RR (2 random digits)
            $random2 = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
            
            // Generate second Xx (1 upper + 1 lower)
            $upperLetter2 = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
            $lowerLetter2 = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 1);
            
            $code = sprintf('%s%s%s%s%s%s', 
                $timestamp,
                $upperLetter1,
                $lowerLetter1,
                $random2,
                $upperLetter2,
                $lowerLetter2
            );
            
            $exists = in_array($code, self::$used_codes) || self::codeExists($code);
        } while ($exists);

        self::$used_codes[] = $code;
        return $code;
    }

    /**
     * Check if code exists in database
     */
    public static function codeExists(string $code): bool {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT EXISTS (
                SELECT 1 FROM {$wpdb->prefix}app_customers 
                WHERE code = %s
            ) as result",
            $code
        ));
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
        
        $this->cache->invalidateCustomerCache($new_id);
        
        return $new_id;
    }

    private function getMembershipData(int $customer_id): array {
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

        $this->cache->invalidateCustomerCache($id);

        return $result !== false;
    }

    // Di CustomerModel.php
    // VERSION 1: getTotalCount dengan query terpisah + cache
    public function getTotalCount(): int {
        global $wpdb;
        
        error_log('--- Debug CustomerModel getTotalCount ---');
        error_log('Checking cache first...');
        
        // Cek cache
        $cached_total = $this->cache->get('customer_total_count', get_current_user_id());
        if ($cached_total !== null) {
            error_log('Found cached total: ' . $cached_total);
            return (int) $cached_total;
        }

        error_log('No cache found, getting fresh count...');
        error_log('User ID: ' . get_current_user_id());
        error_log('Can view_customer_list: ' . (current_user_can('view_customer_list') ? 'yes' : 'no'));
        error_log('Can view_own_customer: ' . (current_user_can('view_own_customer') ? 'yes' : 'no'));

        // Base query parts
        $select = "SELECT COUNT(DISTINCT p.id)";
        $from = " FROM {$this->table} p";
        $where = " WHERE 1=1";

        error_log('Building WHERE clause:');
        error_log('Initial WHERE: ' . $where);

        $current_user_id = get_current_user_id();

        $has_customer = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = %d",
            $current_user_id
        ));
        error_log('User has customer: ' . ($has_customer > 0 ? 'yes' : 'no'));

        if ($has_customer > 0 && current_user_can('view_customer_list') && current_user_can('edit_own_customer')) {
            $where .= $wpdb->prepare(" AND p.user_id = %d", $current_user_id);
            error_log('Added own customer restriction: ' . $where);
        } elseif (current_user_can('view_customer_list') && current_user_can('edit_all_customers')) {
            error_log('User can view all customers - no additional restrictions');
        }

        $sql = $select . $from . $where;
        error_log('Final Query: ' . $sql);
        
        $total = (int) $wpdb->get_var($sql);
        
        // Set cache
        $this->cache->set('customer_total_count', $total, 120, get_current_user_id());
        error_log('Set new cache value: ' . $total);
        
        error_log('Total count result: ' . $total);
        error_log('--- End Debug ---');

        return $total;
    }

    public function getDataTableData(int $start, int $length, string $search, string $orderColumn, string $orderDir): array {
        global $wpdb;

        error_log("=== getDataTableData start ===");
        error_log("Query params: start=$start, length=$length, search=$search");

        $current_user_id = get_current_user_id();

        // Debug capabilities
        error_log('--- Debug User Capabilities ---');
        error_log('User ID: ' . $current_user_id);
        error_log('Can view_customer_list: ' . (current_user_can('view_customer_list') ? 'yes' : 'no'));
        error_log('Can view_own_customer: ' . (current_user_can('view_own_customer') ? 'yes' : 'no'));

        // Base query parts
        $select = "SELECT SQL_CALC_FOUND_ROWS p.*, COUNT(r.id) as branch_count, u.display_name as owner_name";

        $from = " FROM {$this->table} p";
        $join = " LEFT JOIN {$this->branch_table} r ON p.id = r.customer_id";
        $join .= " LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID";
        $where = " WHERE 1=1";

        error_log('Building WHERE clause:');
        error_log('Initial WHERE: ' . $where);

        // Cek relasi user dengan customer
        $has_customer = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = %d",
            $current_user_id
        ));
        error_log('User has customer: ' . ($has_customer > 0 ? 'yes' : 'no'));

        // Cek status employee
        $employee_customer = $wpdb->get_var($wpdb->prepare(
            "SELECT customer_id FROM {$this->employee_table} WHERE user_id = %d",
            $current_user_id
        ));
        error_log('User is employee of customer: ' . ($employee_customer ? $employee_customer : 'no'));

        // Permission based filtering
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
        else {
            $where .= " AND 1=0";
            error_log('User has no access - restricting all results');
        }

        // Add search condition if present
        if (!empty($search)) {
            $where .= $wpdb->prepare(
                " AND (p.name LIKE %s OR p.code LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
            error_log('Added search condition: ' . $where);
        }

        // Complete query parts
        $group = " GROUP BY p.id";
        $order = " ORDER BY " . esc_sql($orderColumn) . " " . esc_sql($orderDir);
        $limit = $wpdb->prepare(" LIMIT %d, %d", $start, $length);

        $sql = $select . $from . $join . $where . $group . $order . $limit;
        error_log('Final Query: ' . $sql);

        // Execute query
        $results = $wpdb->get_results($sql);

        // Get filtered count from SQL_CALC_FOUND_ROWS
        $filtered = $wpdb->get_var("SELECT FOUND_ROWS()");
        
        // Calculate total count using same WHERE clause but without search
        $total_sql = "SELECT COUNT(DISTINCT p.id)" . $from . $join . $where;
        // Remove search condition from WHERE for total count if it exists
        if (!empty($search)) {
            $total_sql = str_replace(
                $wpdb->prepare(
                    " AND (p.name LIKE %s OR p.code LIKE %s)",
                    '%' . $wpdb->esc_like($search) . '%',
                    '%' . $wpdb->esc_like($search) . '%'
                ),
                '',
                $total_sql
            );
        }
        $total = (int) $wpdb->get_var($total_sql);

        error_log("Found rows (filtered): " . $filtered);
        error_log("Total count: " . $total);
        error_log("Results count: " . count($results));
        error_log("=== getDataTableData end ===");

        return [
            'data' => $results,
            'total' => $total,
            'filtered' => (int) $filtered
        ];
    }

    public function delete(int $id): bool {
        global $wpdb;
        
        // Store the current customer data before deletion (for cache invalidation)
        $customer = $this->find($id);
        if (!$customer) {
            return false; // Customer not found
        }

        $result = $wpdb->delete(
            $this->table,
            ['id' => $id],
            ['%d']
        );

        if ($result !== false) {
            // Clear all related cache
            $this->cache->invalidateCustomerCache($id);
            
            // If customer had a user_id, clear that user's cache too
            if (!empty($customer->user_id)) {
                $this->cache->delete('user_customers', $customer->user_id);
            }

            return true;
        }

        return false;
    }

    public function existsByCode(string $code, ?int $excludeId = null): bool {
        // Generate unique cache key based on parameters
        $cache_key = 'code_exists_' . md5($code . ($excludeId ?? ''));
        
        // Check cache first
        $cached_result = $this->cache->get('code_exists', $cache_key);
        if ($cached_result !== null) {
            return (bool) $cached_result;
        }

        global $wpdb;
        $sql = "SELECT EXISTS (SELECT 1 FROM {$this->table} WHERE code = %s";
        $params = [$code];

        if ($excludeId) {
            $sql .= " AND id != %d";
            $params[] = $excludeId;
        }

        $sql .= ") as result";
        $exists = (bool) $wpdb->get_var($wpdb->prepare($sql, $params));

        // Cache for 5 minutes since this rarely changes
        $this->cache->set('code_exists', $exists, 300, $cache_key);

        return $exists;
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
        // Check cache first
        $cached_count = $this->cache->get('branch_count', $id);
        if ($cached_count !== null) {
            return (int) $cached_count;
        }

        global $wpdb;
        $count = (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$this->branch_table}
            WHERE customer_id = %d
        ", $id));

        // Cache for 2 minutes
        $this->cache->set('branch_count', $count, 120, $id);

        return $count;
    }

    public function existsByName(string $name, ?int $excludeId = null): bool {
        // Generate unique cache key based on parameters
        $cache_key = 'name_exists_' . md5($name . ($excludeId ?? ''));
        
        // Check cache first
        $cached_result = $this->cache->get('name_exists', $cache_key);
        if ($cached_result !== null) {
            return (bool) $cached_result;
        }

        global $wpdb;
        $sql = "SELECT EXISTS (SELECT 1 FROM {$this->table} WHERE name = %s";
        $params = [$name];

        if ($excludeId) {
            $sql .= " AND id != %d";
            $params[] = $excludeId;
        }

        $sql .= ") as result";
        $exists = (bool) $wpdb->get_var($wpdb->prepare($sql, $params));

        // Cache for 5 minutes since this rarely changes
        $this->cache->set('name_exists', $exists, 300, $cache_key);

        return $exists;
    }

    /**
     * Get all active customer IDs with cache implementation
     *
     * @return array Array of customer IDs
     */
    public function getAllCustomerIds(): array {
        try {
            // Try to get from cache first using the cache manager
            $cached_ids = $this->cache->get('customer_ids', 'active');
            
            if ($cached_ids !== null) {
                error_log('Cache hit: getAllCustomerIds');
                return $cached_ids;
            }
            
            error_log('Cache miss: getAllCustomerIds - fetching from database');
            
            global $wpdb;
            
            // Get fresh data from database
            $results = $wpdb->get_col("
                SELECT id 
                FROM {$this->table}
                WHERE status = 'active'
                ORDER BY id ASC
            ");
            
            if ($wpdb->last_error) {
                throw new \Exception('Database error: ' . $wpdb->last_error);
            }
            
            // Convert all IDs to integers
            $customer_ids = array_map('intval', $results);
            
            // Cache the results using cache manager
            // Using 2 minutes cache time to match other cache durations in the system
            $this->cache->set('customer_ids', $customer_ids, 120, 'active');
            
            return $customer_ids;
            
        } catch (\Exception $e) {
            error_log('Error in getAllCustomerIds: ' . $e->getMessage());
            return [];
        }
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
