<?php
/**
 * Customer Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models
 * @version     1.0.10
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

 use WPCustomer\Cache\CustomerCacheManager;
 
 class CustomerModel {
     private $table;
     private $branch_table;
     private $employee_table;
     private $cache;
     static $used_codes = [];


     public function __construct() {
         global $wpdb;
         $this->table = $wpdb->prefix . 'app_customers';
         $this->branch_table = $wpdb->prefix . 'app_customer_branches';
         $this->employee_table = $wpdb->prefix . 'app_customer_employees';
         $this->cache = new CustomerCacheManager();
     }

    public function find($id): ?object {
        global $wpdb;
        $id = (int) $id;

        // Check cache first
        $cached_result = $this->cache->get('customer', $id);
        if ($cached_result !== null) {
            return $cached_result;
        }

        try {
            $sql = $wpdb->prepare("
                SELECT 
                    c.*,
                    COUNT(DISTINCT b.id) as branch_count,
                    COUNT(DISTINCT e.id) as employee_count,
                    u.display_name as owner_name,
                    creator.display_name as created_by_name,
                    bp.name as pusat_name,
                    bp.code as pusat_code,
                    bp.address as pusat_address,
                    bp.postal_code as pusat_postal_code,
                    bp.latitude as latitude,
                    bp.longitude as longitude,
                    wp.name as province_name,
                    wr.name as regency_name
                FROM {$this->table} c
                LEFT JOIN {$this->branch_table} b ON c.id = b.customer_id
                LEFT JOIN {$this->employee_table} e ON c.id = e.customer_id
                LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
                LEFT JOIN {$wpdb->users} creator ON c.created_by = creator.ID
                LEFT JOIN {$this->branch_table} bp ON (c.id = bp.customer_id AND bp.type = 'pusat')
                LEFT JOIN wp_wi_provinces wp ON c.provinsi_id = wp.id 
                LEFT JOIN wp_wi_regencies wr ON c.regency_id = wr.id
                WHERE c.id = %d
                GROUP BY c.id
            ", $id);

            $result = $wpdb->get_row($sql);

            if ($wpdb->last_error) {
                throw new \Exception("Database error: " . $wpdb->last_error);
            }

            if ($result) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Debug: CustomerModel::find() - user_id: ' . ($result->user_id ?? 'NULL'));
                }

                // Cache the result for 2 minutes
                $this->cache->set('customer', $result, 120, $id);
            }

            return $result;

        } catch (\Exception $e) {
            error_log("Error in CustomerModel::find(): " . $e->getMessage());
            throw $e;
        }
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
            'reg_type' => $data['reg_type'] ?? 'self',
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
            '%s',  // reg_type
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

        if ($result === false) {
            return null;
        }

        $new_id = (int) $wpdb->insert_id;

        $this->cache->invalidateCustomerCache($new_id);

        // Task-2165: Fire hook for auto-create branch pusat
        if ($new_id) {
            do_action('wp_customer_created', $new_id, $insert_data);
        }

        return $new_id;
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
        
        // Remove null values but keep empty strings for NPWP and NIB
        $updateData = array_filter($updateData, function($value, $key) {
            if ($key === 'npwp' || $key === 'nib') {
                return $value !== null;
            }
            return $value !== null;
        }, ARRAY_FILTER_USE_BOTH);

        $formats = [];
        foreach ($updateData as $key => $value) {
            switch ($key) {
                case 'provinsi_id':
                case 'regency_id':
                case 'user_id':
                    $formats[] = '%d';
                    break;
                default:
                    $formats[] = '%s';
            }
        }

        $result = $wpdb->update(
            $this->table,
            $updateData,
            ['id' => $id],
            $formats,
            ['%d']
        );

        if ($result === false) {
            error_log('Update failed. Last error: ' . $wpdb->last_error);
            error_log('Update data: ' . print_r($updateData, true));
            return false;
        }

        // Invalidate cache after successful update
        $this->cache->invalidateCustomerCache($id);

        return true;
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

        // Get user relation to determine access
        $relation = $this->getUserRelation(0); // 0 for general access check
        $access_type = $relation['access_type'];

        error_log('Access type: ' . $access_type);
        error_log('Is admin: ' . ($relation['is_admin'] ? 'yes' : 'no'));
        error_log('Is customer admin: ' . ($relation['is_customer_admin'] ? 'yes' : 'no'));
        error_log('Is branch admin: ' . ($relation['is_customer_branch_admin'] ? 'yes' : 'no'));
        error_log('Is employee: ' . ($relation['is_customer_employee'] ? 'yes' : 'no'));

        // Apply filtering based on access type
        if ($relation['is_admin']) {
            // Administrator - see all customers
            error_log('User is admin - no additional restrictions');
        }
        elseif ($access_type === 'platform') {
            // Platform users (from wp-app-core) - see all customers
            error_log('User is platform - no additional restrictions');
        }
        elseif ($access_type === 'agency') {
            // Agency users (from wp-agency) - see customers with branches in their agency
            // wp-agency TODO-2065: Agency-based filtering via branch join
            // IMPORTANT: Show customers that have AT LEAST ONE branch in this agency
            // Example: PT A (Jakarta) → has branch in Banten → Agency Banten can see PT A
            if (class_exists('\\WP_Agency_WP_Customer_Integration')) {
                $agency_id = \WP_Agency_WP_Customer_Integration::get_user_agency_id($current_user_id);
                if ($agency_id) {
                    // Join to branches and filter by agency_id
                    $from .= " INNER JOIN {$this->branch_table} agency_branch ON p.id = agency_branch.customer_id";
                    $where .= $wpdb->prepare(" AND agency_branch.agency_id = %d", $agency_id);
                    error_log("Agency user - filtered to customers with branches in agency_id {$agency_id}");
                } else {
                    $where .= " AND 1=0"; // No access if no agency
                    error_log('Agency user has no agency - blocking access');
                }
            } else {
                error_log('WP_Agency_WP_Customer_Integration class not found');
                $where .= " AND 1=0"; // Fallback: no access
            }
        }
        elseif ($relation['is_customer_admin']) {
            // Customer Admin - only see their own customer
            $where .= $wpdb->prepare(" AND p.user_id = %d", $current_user_id);
            error_log('Added customer admin restriction: ' . $where);
        }
        elseif ($relation['is_customer_branch_admin']) {
            // Branch Admin - only see customer where they manage a branch
            $customer_id = $relation['customer_branch_admin_of_customer_id'];
            if ($customer_id) {
                $where .= $wpdb->prepare(" AND p.id = %d", $customer_id);
                error_log('Added branch admin restriction for customer: ' . $customer_id);
            } else {
                $where .= " AND 1=0"; // No access if no customer found
                error_log('Branch admin has no customer - blocking access');
            }
        }
        elseif ($relation['is_customer_employee']) {
            // Employee - only see customer where they work
            $customer_id = $relation['employee_of_customer_id'];
            if ($customer_id) {
                $where .= $wpdb->prepare(" AND p.id = %d", $customer_id);
                error_log('Added employee restriction for customer: ' . $customer_id);
            } else {
                $where .= " AND 1=0"; // No access if no customer found
                error_log('Employee has no customer - blocking access');
            }
        }
        else {
            // No access
            $where .= " AND 1=0";
            error_log('User has no access - blocking all');
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
        // Pastikan orderDir lowercase untuk konsistensi cache key
        $orderDir = strtolower($orderDir);

        // Dapatkan access_type untuk cache key dengan cara yang konsisten
        $current_user_id = get_current_user_id();
        $relation = $this->getUserRelation(0); // 0 untuk general access check
        $access_type = $relation['access_type'];
        
        // Check cache first
        $cached_result = $this->cache->getDataTableCache(
            'customer_list',
            $access_type,
            $start, 
            $length,
            $search,
            $orderColumn,
            $orderDir,
            []
        );

        if ($cached_result) {
            return $cached_result;
        }
        
        global $wpdb;

        // Base query parts
        $select = "SELECT SQL_CALC_FOUND_ROWS p.*, COUNT(r.id) as branch_count, u.display_name as owner_name";
        $from = " FROM {$this->table} p";
        $join = " LEFT JOIN {$this->branch_table} r ON p.id = r.customer_id";
        $join .= " LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID";
        $where = " WHERE 1=1";

        // Apply same filtering logic as getTotalCount using relation
        if ($relation['is_admin']) {
            // Administrator - see all customers
            // No additional restrictions
        }
        elseif ($access_type === 'platform') {
            // Platform users (from wp-app-core) - see all customers
            // No additional restrictions (same as admin)
            // Access controlled via WordPress capabilities (view_customer_detail)
        }
        elseif ($access_type === 'agency') {
            // Agency users (from wp-agency) - see customers with branches in their agency
            // wp-agency TODO-2065: Agency-based filtering via branch join
            if (class_exists('\\WP_Agency_WP_Customer_Integration')) {
                $agency_id = \WP_Agency_WP_Customer_Integration::get_user_agency_id($current_user_id);
                if ($agency_id) {
                    // Join to branches and filter by agency_id
                    $from .= " INNER JOIN {$this->branch_table} agency_branch ON p.id = agency_branch.customer_id";
                    $where .= $wpdb->prepare(" AND agency_branch.agency_id = %d", $agency_id);
                } else {
                    $where .= " AND 1=0"; // No access if no agency
                }
            } else {
                $where .= " AND 1=0"; // Fallback: no access
            }
        }
        elseif ($relation['is_customer_admin']) {
            // Customer Admin - only see their own customer
            $where .= $wpdb->prepare(" AND p.user_id = %d", $current_user_id);
        }
        elseif ($relation['is_customer_branch_admin']) {
            // Branch Admin - only see customer where they manage a branch
            $customer_id = $relation['customer_branch_admin_of_customer_id'];
            if ($customer_id) {
                $where .= $wpdb->prepare(" AND p.id = %d", $customer_id);
            } else {
                $where .= " AND 1=0"; // No access if no customer found
            }
        }
        elseif ($relation['is_customer_employee']) {
            // Employee - only see customer where they work
            $customer_id = $relation['employee_of_customer_id'];
            if ($customer_id) {
                $where .= $wpdb->prepare(" AND p.id = %d", $customer_id);
            } else {
                $where .= " AND 1=0"; // No access if no customer found
            }
        }
        else {
            // No access
            $where .= " AND 1=0";
        }

        // Add search condition if present
        if (!empty($search)) {
            $where .= $wpdb->prepare(
                " AND (p.name LIKE %s OR p.code LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        // Complete query parts
        $group = " GROUP BY p.id";
        
        // Gunakan lowercase untuk cache key dan uppercase untuk SQL query
        $sqlOrderDir = $orderDir === 'desc' ? 'DESC' : 'ASC';
        $order = " ORDER BY " . esc_sql($orderColumn) . " " . $sqlOrderDir;
        
        $limit = $wpdb->prepare(" LIMIT %d, %d", $start, $length);

        $sql = $select . $from . $join . $where . $group . $order . $limit;

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

        // Prepare result
        $result = [
            'data' => $results,
            'total' => $total,
            'filtered' => (int) $filtered
        ];
        
        // Set cache dengan durasi 2 menit - gunakan orderDir yang sama (lowercase)
        $this->cache->setDataTableCache(
            'customer_list',
            $access_type,
            $start,
            $length,
            $search,
            $orderColumn,
            $orderDir,
            $result,
            []
        );

        return $result;
    }

    /**
     * Delete customer with soft/hard delete logic and HOOKs
     *
     * Production: Soft delete (status='inactive')
     * Demo: Hard delete (actual DELETE from database)
     *
     * HOOKs fired:
     * - wp_customer_before_delete: Before deletion for validation
     * - wp_customer_deleted: After deletion for cascade cleanup
     *
     * @param int $id Customer ID to delete
     * @return bool Success status
     */
    public function delete(int $id): bool {
        global $wpdb;

        // 1. Get customer data before deletion
        $customer = $this->find($id);
        if (!$customer) {
            return false; // Customer not found
        }

        // 2. Convert customer object to array for HOOK
        $customer_data = [
            'id' => $customer->id,
            'code' => $customer->code,
            'name' => $customer->name,
            'npwp' => $customer->npwp ?? null,
            'nib' => $customer->nib ?? null,
            'status' => $customer->status,
            'user_id' => $customer->user_id,
            'provinsi_id' => $customer->provinsi_id ?? null,
            'regency_id' => $customer->regency_id ?? null,
            'reg_type' => $customer->reg_type ?? 'self',
            'created_by' => $customer->created_by ?? null,
            'created_at' => $customer->created_at ?? null,
            'updated_at' => $customer->updated_at ?? null
        ];

        // 3. Fire before delete HOOK (for validation)
        do_action('wp_customer_before_delete', $id, $customer_data);

        // 4. Check if hard delete is enabled (reuse branch setting for consistency)
        $settings = get_option('wp_customer_general_options', []);
        $is_hard_delete = isset($settings['enable_hard_delete_branch']) &&
                         $settings['enable_hard_delete_branch'] === true;

        // 5. Perform delete (soft or hard)
        if ($is_hard_delete) {
            // Hard delete - actual DELETE from database (for demo data)
            error_log("[CustomerModel] Hard deleting customer {$id} ({$customer_data['name']})");

            $result = $wpdb->delete(
                $this->table,
                ['id' => $id],
                ['%d']
            );
        } else {
            // Soft delete - set status to 'inactive' (for production)
            error_log("[CustomerModel] Soft deleting customer {$id} ({$customer_data['name']})");

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

        // 6. If successful, fire after delete HOOK and invalidate cache
        if ($result !== false) {
            // Fire after delete HOOK (for cascade cleanup)
            do_action('wp_customer_deleted', $id, $customer_data, $is_hard_delete);

            // Clear all related cache
            $this->cache->invalidateCustomerCache($id);

            // If customer had a user_id, clear that user's cache too
            if (!empty($customer->user_id)) {
                $this->cache->delete('user_customers', $customer->user_id);
            }

            error_log("[CustomerModel] Customer {$id} deleted successfully (hard_delete: " .
                     ($is_hard_delete ? 'YES' : 'NO') . ")");

            return true;
        }

        error_log("[CustomerModel] Failed to delete customer {$id}");
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

    // Tambah method helper
    public function existsByNPWP($npwp, $excludeId = null): bool 
    {
        global $wpdb;
        
        if ($excludeId) {
            $sql = "SELECT EXISTS (SELECT 1 FROM {$this->table} WHERE npwp = %s AND id != %d)";
            return (bool)$wpdb->get_var($wpdb->prepare($sql, $npwp, $excludeId));
        } else {
            $sql = "SELECT EXISTS (SELECT 1 FROM {$this->table} WHERE npwp = %s)";
            return (bool)$wpdb->get_var($wpdb->prepare($sql, $npwp));
        }
    }

    public function existsByNIB($nib, $excludeId = null): bool
    {
        global $wpdb;
        
        if ($excludeId) {
            $sql = "SELECT EXISTS (SELECT 1 FROM {$this->table} WHERE nib = %s AND id != %d)";
            return (bool)$wpdb->get_var($wpdb->prepare($sql, $nib, $excludeId));
        } else {
            $sql = "SELECT EXISTS (SELECT 1 FROM {$this->table} WHERE nib = %s)";
            return (bool)$wpdb->get_var($wpdb->prepare($sql, $nib));
        }
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

    /**
     * Create demo data with fixed ID and trigger hooks
     *
     * @param array $data Customer data with fixed ID
     * @return bool Success status
     */
    public function createDemoData(array $data): bool {
        global $wpdb;

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Disable foreign key checks temporarily
            $wpdb->query('SET FOREIGN_KEY_CHECKS = 0');

            // First, delete any existing records with the same name-region combination
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$this->table}
                 WHERE name = %s AND provinsi_id = %d AND regency_id = %d",
                $data['name'],
                $data['provinsi_id'],
                $data['regency_id']
            ));

            // Then delete any existing record with the same ID
            $wpdb->delete($this->table, ['id' => $data['id']], ['%d']);

            // Now insert the new record
            $result = $wpdb->insert(
                $this->table,
                $data,
                $this->getFormatArray($data)
            );

            if ($result === false) {
                throw new \Exception($wpdb->last_error);
            }

            // Verify insertion
            $inserted = $this->find($data['id']);
            if (!$inserted) {
                throw new \Exception("Failed to verify inserted data");
            }

            // Re-enable foreign key checks
            $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');

            // Commit transaction
            $wpdb->query('COMMIT');

            // Trigger hook after successful creation (Task-2166)
            do_action('wp_customer_created', $data['id'], $data);

            return true;

        } catch (\Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            error_log("Error in createDemoData: " . $e->getMessage());
            throw $e;
        } finally {
            // Make sure foreign key checks are re-enabled
            $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    /**
     * Helper to get format array for wpdb insert
     */
    private function getFormatArray(array $data): array {
        $formats = [];
        foreach ($data as $value) {
            if (is_int($value)) {
                $formats[] = '%d';
            } elseif (is_float($value)) {
                $formats[] = '%f';
            } else {
                $formats[] = '%s';
            }
        }
        return $formats;
    }

    /**
     * Get user relation with customer
     * 
     * Determines the relationship between a user and a customer:
     * - is_admin: User has admin privileges
     * - is_customer_admin: User is the owner of the customer
     * - is_customer_employee: User is an employee of the customer
     * 
     * @param int $customer_id Customer ID (0 for general check)
     * @param int|null $user_id User ID (current user if null)
     * @return array Relationship array with boolean flags
     */
    public function getUserRelation(int $customer_id, int $user_id = null): array {
        try {
            global $wpdb;

            // Validate input
            $user_id = $user_id && is_numeric($user_id) ? (int)$user_id : get_current_user_id();
            $customer_id = is_numeric($customer_id) ? (int)$customer_id : 0;

            // Determine access type - need to check database FIRST for correct access_type
            $is_admin = current_user_can('edit_all_customers');
            $is_customer_admin = false;
            $is_customer_branch_admin = false;
            $is_customer_employee = false;

            if (!$is_admin) {
                // Check if user is owner (lightweight query)
                if ($customer_id > 0) {
                    $is_customer_admin = (bool) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}app_customers
                        WHERE id = %d AND user_id = %d",
                        $customer_id, $user_id
                    ));
                } else {
                    $is_customer_admin = (bool) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}app_customers
                        WHERE user_id = %d LIMIT 1",
                        $user_id
                    ));
                }

                // Check if user is branch admin - only if not customer owner
                if (!$is_customer_admin) {
                    if ($customer_id > 0) {
                        // Check if user is admin of any branch of this customer
                        $is_customer_branch_admin = (bool) $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_branches
                            WHERE customer_id = %d AND user_id = %d",
                            $customer_id, $user_id
                        ));
                    } else {
                        // General check - is user admin of any branch
                        $is_customer_branch_admin = (bool) $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_branches
                            WHERE user_id = %d LIMIT 1",
                            $user_id
                        ));
                    }
                }

                // Check if user is employee - only if not owner and not branch admin
                if (!$is_customer_admin && !$is_customer_branch_admin) {
                    if ($customer_id > 0) {
                        $is_customer_employee = (bool) $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees
                            WHERE customer_id = %d AND user_id = %d AND status = 'active'",
                            $customer_id, $user_id
                        ));
                    } else {
                        $is_customer_employee = (bool) $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees
                            WHERE user_id = %d AND status = 'active' LIMIT 1",
                            $user_id
                        ));
                    }
                }
            }

            // NOW we can determine correct access_type
            $access_type = 'none';
            if ($is_admin) $access_type = 'admin';
            else if ($is_customer_admin) $access_type = 'customer_admin';
            else if ($is_customer_branch_admin) $access_type = 'customer_branch_admin';
            else if ($is_customer_employee) $access_type = 'customer_employee';

            // Apply access_type filter - allow plugins to modify access type
            $access_type = apply_filters('wp_customer_access_type', $access_type, [
                'is_admin' => $is_admin,
                'is_customer_admin' => $is_customer_admin,
                'is_customer_branch_admin' => $is_customer_branch_admin,
                'is_customer_employee' => $is_customer_employee,
                'user_id' => $user_id,
                'customer_id' => $customer_id
            ]);

            // Generate appropriate cache key based on access_type
            if ($customer_id === 0) {
                // Special case for general access check - group by access_type
                $cache_key = "customer_relation_general_{$access_type}";
            } else {
                // Specific customer check - group by customer and access_type
                $cache_key = "customer_relation_{$customer_id}_{$access_type}";
            }

            // Check cache with correct access_type
            $cached_relation = $this->cache->get('customer_relation', $cache_key);
            if ($cached_relation !== null) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("CustomerModel::getUserRelation - Cache hit for access_type {$access_type} and customer {$customer_id}");
                }
                return $cached_relation;
            }

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("CustomerModel::getUserRelation - Cache miss for access_type {$access_type} and customer {$customer_id}");
            }

            // Build relation with values we already have
            $relation = [
                'is_admin' => $is_admin,
                'is_customer_admin' => $is_customer_admin,
                'is_customer_branch_admin' => $is_customer_branch_admin,
                'is_customer_employee' => $is_customer_employee,
                'owner_of_customer_id' => null,
                'owner_of_customer_name' => null,
                'customer_branch_admin_of_customer_id' => null,
                'customer_branch_admin_of_branch_name' => null,
                'employee_of_customer_id' => null,
                'employee_of_customer_name' => null
            ];

            // Get additional details if user is owner (already know from lightweight query above)
            if ($is_customer_admin) {
                if ($customer_id > 0) {
                    // Specific customer check - get customer name
                    $customer = $this->find($customer_id);
                    if ($customer) {
                        $relation['owner_of_customer_id'] = $customer_id;
                        $relation['owner_of_customer_name'] = $customer->name;
                    }
                } else {
                    // General check - get owned customer details
                    $customer = $wpdb->get_row($wpdb->prepare(
                        "SELECT id, name FROM {$wpdb->prefix}app_customers
                        WHERE user_id = %d
                        LIMIT 1",
                        $user_id
                    ));

                    if ($customer) {
                        $relation['owner_of_customer_id'] = (int)$customer->id;
                        $relation['owner_of_customer_name'] = $customer->name;
                    }
                }
            }

            // Get additional details if user is branch admin (already know from lightweight query above)
            if ($is_customer_branch_admin) {
                if ($customer_id > 0) {
                    // Specific customer check - get branch details
                    $branch_row = $wpdb->get_row($wpdb->prepare(
                        "SELECT b.id, b.name as branch_name, b.customer_id
                        FROM {$wpdb->prefix}app_customer_branches b
                        WHERE b.customer_id = %d AND b.user_id = %d
                        LIMIT 1",
                        $customer_id, $user_id
                    ));

                    if ($branch_row) {
                        $relation['customer_branch_admin_of_customer_id'] = (int)$branch_row->customer_id;
                        $relation['customer_branch_admin_of_branch_name'] = $branch_row->branch_name;
                    }
                } else {
                    // General check - get branch admin details
                    $branch_row = $wpdb->get_row($wpdb->prepare(
                        "SELECT b.customer_id, b.name as branch_name, c.name as customer_name
                        FROM {$wpdb->prefix}app_customer_branches b
                        JOIN {$wpdb->prefix}app_customers c ON b.customer_id = c.id
                        WHERE b.user_id = %d
                        LIMIT 1",
                        $user_id
                    ));

                    if ($branch_row) {
                        $relation['customer_branch_admin_of_customer_id'] = (int)$branch_row->customer_id;
                        $relation['customer_branch_admin_of_branch_name'] = $branch_row->branch_name;
                    }
                }
            }

            // Get additional details if user is employee (already know from lightweight query above)
            if ($is_customer_employee) {
                if ($customer_id > 0) {
                    // Specific customer check - get customer name
                    $relation['employee_of_customer_id'] = $customer_id;
                    $customer = $this->find($customer_id);
                    $relation['employee_of_customer_name'] = $customer ? $customer->name : null;
                } else {
                    // General check - get employee customer details
                    $employee_row = $wpdb->get_row($wpdb->prepare(
                        "SELECT ce.customer_id, c.name as customer_name
                        FROM {$wpdb->prefix}app_customer_employees ce
                        JOIN {$wpdb->prefix}app_customers c ON ce.customer_id = c.id
                        WHERE ce.user_id = %d AND ce.status = 'active'
                        LIMIT 1",
                        $user_id
                    ));

                    if ($employee_row) {
                        $relation['employee_of_customer_id'] = (int)$employee_row->customer_id;
                        $relation['employee_of_customer_name'] = $employee_row->customer_name;
                    }
                }
            }
            
            // Apply filters to allow extensions
            $relation = apply_filters('wp_customer_user_relation', $relation, $customer_id, $user_id);

            // Add access_type to relation
            $relation['access_type'] = $access_type;

            // Debug logging for access validation (requested by user)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Access Result: " . print_r([
                    'has_access' => ($access_type !== 'none'),
                    'access_type' => $access_type,
                    'relation' => $relation,
                    'customer_id' => $customer_id,
                    'user_id' => $user_id
                ], true));
            }
            
            // Get cache duration (configurable or default 2 minutes)
            $cache_duration = defined('WP_CUSTOMER_RELATION_CACHE_DURATION') ? 
                             WP_CUSTOMER_RELATION_CACHE_DURATION : 120;
            
            // Cache result
            $this->cache->set('customer_relation', $relation, $cache_duration, $cache_key);
            
            return $relation;
            
        } catch (\Exception $e) {
            // Log error and return default relation on failure
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Error in CustomerModel::getUserRelation: " . $e->getMessage());
                error_log($e->getTraceAsString());
            }
            
            return [
                'is_admin' => current_user_can('edit_all_customers'),
                'is_customer_admin' => false,
                'is_customer_branch_admin' => false,
                'is_customer_employee' => false,
                'access_type' => 'none',
                'error' => true
            ];
        }
    }


    /**
     * Invalidate user relation cache
     * 
     * @param int|null $customer_id Customer ID (null for all customers)
     * @param int|null $user_id User ID (null for all users)
     * @return void
     */
    public function invalidateUserRelationCache(int $customer_id = null, int $user_id = null): void {
        try {
            if ($customer_id && $user_id) {
                // Invalidate specific relation
                $this->cache->delete('customer_relation', "customer_relation_{$user_id}_{$customer_id}");
                $this->cache->delete('customer_relation', "customer_relation_general_{$user_id}");
            } else if ($customer_id) {
                // We need to invalidate all relations for this customer
                // This is a bit tricky without key pattern matching
                // For now, let's just clear all customer relation cache
                $this->cache->clearCache('customer_relation');
            } else if ($user_id) {
                // Invalidate general relation for this user
                $this->cache->delete('customer_relation', "customer_relation_general_{$user_id}");
                
                // Ideally we'd clear all relations for this user
                // For now, just clear all customer relation cache
                $this->cache->clearCache('customer_relation');
            } else {
                // Clear all relation cache
                $this->cache->clearCache('customer_relation');
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Invalidated user relation cache: customer_id=$customer_id, user_id=$user_id");
            }
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Error in invalidateUserRelationCache: " . $e->getMessage());
            }
        }
    }

 }
