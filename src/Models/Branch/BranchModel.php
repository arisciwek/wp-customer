<?php
/**
 * Branch Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models/Branch
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Branch/BranchModel.php
 *
 * Description: Model untuk mengelola data cabang di database.
 *              Handles operasi CRUD dengan caching terintegrasi.
 *              Includes query optimization dan data formatting.
 *              Menyediakan metode untuk DataTables server-side.
 *
 * Changelog:
 * 1.0.0 - 2024-12-10
 * - Initial implementation
 * - Added core CRUD operations
 * - Added DataTables integration
 * - Added cache support
 */

namespace WPCustomer\Models\Branch;

use WPCustomer\Cache\CustomerCacheManager;
use WPCustomer\Models\Customer\CustomerModel;

class BranchModel {

    // Cache keys - pindahkan dari CustomerCacheManager ke sini untuk akses langsung
    private const KEY_CUSTOMER_BRANCH = 'customer_branch';
    private const KEY_CUSTOMER_BRANCH_LIST = 'customer_branch_list';
    private const CACHE_EXPIRY = 7200; // 2 hours in seconds

    private $table;
    private $customer_table;
    private CustomerModel $customerModel;
    private $cache;
    private string $log_file;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'app_customer_branches';
        $this->customer_table = $wpdb->prefix . 'app_customers';
        $this->customerModel = new CustomerModel();
        $this->cache = new CustomerCacheManager();

        // Initialize log file
        $this->initLogFile();
    }

    /**
     * Initialize log file path
     */
    private function initLogFile(): void {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/wp-customer/logs';

        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        $this->log_file = $log_dir . '/branch-' . date('Y-m') . '.log';
    }

    /**
     * Debug logging
     */
    private function debug_log($message): void {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        // Log to WordPress debug.log instead of separate file
        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }

        error_log($message);
    }
    
    public function findPusatByCustomer(int $customer_id): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table} 
             WHERE customer_id = %d 
             AND type = 'pusat' 
             AND status = 'active'
             LIMIT 1",
            $customer_id
        ));
    }

    public function countPusatByCustomer(int $customer_id): int {
        global $wpdb;
        return (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} 
             WHERE customer_id = %d 
             AND type = 'pusat' 
             AND status = 'active'",
            $customer_id
        ));
    }

    private function generateBranchCode(int $customer_id): string {
        // Get customer code 
        $customer = $this->customerModel->find($customer_id);
        if (!$customer || empty($customer->code)) {
            throw new \Exception('Invalid customer code');
        }
        
        do {
            // Generate 2 digit random number (RR)
            $random = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
            
            // Format: customer_code + '-' + RR
            $branch_code = $customer->code . '-' . $random;
            
            $exists = $this->existsByCode($branch_code);
        } while ($exists);
        
        return $branch_code;
    }

    public function create(array $data): ?int {
        global $wpdb;

        $data['code'] = $this->generateBranchCode($data['customer_id']);
        
        $insertData = [
            'customer_id' => $data['customer_id'],
            'code' => $data['code'],
            'name' => $data['name'],
            'type' => $data['type'],
            'nitku' => $data['nitku'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'provinsi_id' => $data['provinsi_id'] ?? null,
            'agency_id' => $data['agency_id'] ?? null,
            'regency_id' => $data['regency_id'] ?? null,
            'division_id' => $data['division_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'inspector_id' => $data['inspector_id'] ?? null,
            'created_by' => $data['created_by'],
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'status' => $data['status'] ?? 'active'
        ];

        $result = $wpdb->insert(
            $this->table,
            $insertData,
            [
                '%d', // customer_id
                '%s', // code
                '%s', // name
                '%s', // type
                '%s', // nitku
                '%s', // postal_code
                '%f', // latitude
                '%f', // longitude
                '%s', // address
                '%s', // phone
                '%s', // email
                '%d', // provinsi_id
                '%d', // agency_id
                '%d', // regency_id
                '%d', // division_id
                '%d', // user_id
                '%d', // inspector_id
                '%d', // created_by
                '%s', // created_at
                '%s', // updated_at
                '%s'  // status
            ]
        );

        if ($result === false) {
            error_log('Failed to insert branch: ' . $wpdb->last_error);
            error_log('Insert data: ' . print_r($insertData, true));
            return null;
        }

        $branch_id = (int) $wpdb->insert_id;

        // Comprehensive cache invalidation for new branch
        if ($branch_id && isset($data['customer_id'])) {
            // Invalidate DataTable cache for all access types
            $this->invalidateAllDataTableCache('customer_branch_list', (int)$data['customer_id']);

            // Also invalidate company_list (menu Perusahaan - global branch list)
            $this->invalidateAllDataTableCache('company_list', (int)$data['customer_id']);
        }

        // Task-2165: Fire hook for auto-create employee
        if ($branch_id) {
            do_action('wp_customer_branch_created', $branch_id, $insertData);
        }

        return $branch_id;
    }

    public function find(int $id): ?object {
        global $wpdb;

        // Cek cache dulu
        $cached = $this->cache->get(self::KEY_CUSTOMER_BRANCH, $id);
        if ($cached !== null) {
            return $cached;
        }

        // Jika tidak ada di cache, ambil dari database
        $result  = $wpdb->get_row($wpdb->prepare("
            SELECT r.*, p.name as customer_name
            FROM {$this->table} r
            LEFT JOIN {$this->customer_table} p ON r.customer_id = p.id
            WHERE r.id = %d
        ", $id));

        // Simpan ke cache
        if ($result) {
            $this->cache->set(self::KEY_CUSTOMER_BRANCH, $result, self::CACHE_EXPIRY, $id);
        }

        return $result;
    }

    public function update(int $id, array $data): bool {
        global $wpdb;

        $updateData = [
            'name' => $data['name'] ?? null,
            'type' => $data['type'] ?? null,
            'nitku' => $data['nitku'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'provinsi_id' => $data['provinsi_id'] ?? null,
            'agency_id' => $data['agency_id'] ?? null,
            'regency_id' => $data['regency_id'] ?? null,
            'division_id' => $data['division_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'status' => $data['status'] ?? null,
            'updated_at' => current_time('mysql')
        ];

        // Remove null values
        $updateData = array_filter($updateData, function($value) {
            return $value !== null;
        });

        $formats = array_map(function($key) {
            switch($key) {
                case 'latitude':
                case 'longitude':
                    return '%f';
                case 'provinsi_id':
                case 'agency_id':
                case 'regency_id':
                case 'division_id':
                    return '%d';
                default:
                    return '%s';
            }
        }, array_keys($updateData));

        $result = $wpdb->update(
            $this->table,
            $updateData,
            ['id' => $id],
            $formats,
            ['%d']
        );

        if ($result === false) {
            error_log('Update branch error: ' . $wpdb->last_error);
            return false;
        }

        // Get branch data untuk customer_id
        $branch = $this->find($id);
        if ($branch && $branch->customer_id) {
            // Invalidate user relation cache
            $this->invalidateUserRelationCache($id);

            // Comprehensive cache invalidation
            $this->cache->delete(self::KEY_CUSTOMER_BRANCH, $id);

            // Invalidate DataTable cache for all access types
            // Since we use access_type in cache key, we need to invalidate all possible access types
            $this->invalidateAllDataTableCache('customer_branch_list', (int)$branch->customer_id);
        }

        return true;
    }

    public function delete(int $id): bool {
        global $wpdb;

        // Get branch data before deletion
        $branch = $this->find($id);
        if (!$branch) {
            return false;
        }

        // Convert object to array untuk HOOK parameters
        $branch_data = [
            'id' => $branch->id,
            'customer_id' => $branch->customer_id,
            'name' => $branch->name,
            'type' => $branch->type,
            'code' => $branch->code,
            'user_id' => $branch->user_id
        ];

        // Fire before delete HOOK
        do_action('wp_customer_branch_before_delete', $id, $branch_data);

        // Check if hard delete is enabled
        $settings = get_option('wp_customer_general_options', []);
        $is_hard_delete = isset($settings['enable_hard_delete_branch']) && $settings['enable_hard_delete_branch'] === true;

        if ($is_hard_delete) {
            // Hard delete: Actual DELETE from database
            $result = $wpdb->delete(
                $this->table,
                ['id' => $id],
                ['%d']
            );
        } else {
            // Soft delete: Set status='inactive'
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

        if ($result !== false) {
            // Comprehensive cache invalidation
            $this->cache->delete(self::KEY_CUSTOMER_BRANCH, $id);

            // Invalidate DataTable cache for all access types
            $this->invalidateAllDataTableCache('customer_branch_list', (int)$branch->customer_id);

            // Invalidate user relation cache
            $this->invalidateUserRelationCache($id);

            // Fire after delete HOOK for cascade cleanup
            do_action('wp_customer_branch_deleted', $id, $branch_data, $is_hard_delete);
        }

        return $result !== false;
    }
    public function existsByCode(string $code): bool {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT EXISTS (SELECT 1 FROM {$this->table} WHERE code = %s) as result",
            $code
        ));
    }

    public function existsByNameInCustomer(string $name, int $customer_id, ?int $excludeId = null): bool {
        global $wpdb;

        $sql = "SELECT EXISTS (SELECT 1 FROM {$this->table}
                WHERE name = %s AND customer_id = %d";
        $params = [$name, $customer_id];

        if ($excludeId) {
            $sql .= " AND id != %d";
            $params[] = $excludeId;
        }

        $sql .= ") as result";

        return (bool) $wpdb->get_var($wpdb->prepare($sql, $params));
    }

    public function getDataTableData(int $customer_id, int $start, int $length, string $search, string $orderColumn, string $orderDir): array {
        // Dapatkan access_type dari validator
        global $wp_branch_validator;
        if (!$wp_branch_validator) {
            $wp_branch_validator = new \WPCustomer\Validators\Branch\BranchValidator();
        }
        $access = $wp_branch_validator->validateAccess(0);
        $access_type = $access['access_type'];
        
        // Pastikan orderDir lowercase untuk konsistensi cache key
        $orderDir = strtolower($orderDir);
        
        // Check cache first
        $cached_result = $this->cache->getDataTableCache(
            'customer_branch_list',
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
                error_log("BranchModel cache hit for DataTable - Key: customer_branch_list_{$access_type}");
            }
            return $cached_result;
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("BranchModel cache miss for DataTable - Key: customer_branch_list_{$access_type}");
        }
        
        global $wpdb;

        // Get user relation from CustomerModel to determine access
        $customerModel = new CustomerModel();
        $relation = $customerModel->getUserRelation(0); // 0 for general access check
        $user_access_type = $relation['access_type'];

        // Base query parts
        $select = "SELECT SQL_CALC_FOUND_ROWS r.*, p.name as customer_name";
        $from = " FROM {$this->table} r";
        $join = " LEFT JOIN {$this->customer_table} p ON r.customer_id = p.id";

        // Handle customer_id = 0 as "all customers"
        if ($customer_id > 0) {
            $where = " WHERE r.customer_id = %d";
            $params = [$customer_id];
        } else {
            $where = " WHERE 1=1";
            $params = [];
        }

        // Apply agency filtering (role-based)
        if ($user_access_type === 'agency') {
            if (class_exists('\\WP_Agency_WP_Customer_Integration')) {
                $user_id = get_current_user_id();
                $access_level = \WP_Agency_WP_Customer_Integration::get_user_access_level($user_id);

                if ($access_level === 'agency') {
                    $agency_id = \WP_Agency_WP_Customer_Integration::get_user_agency_id($user_id);
                    if ($agency_id) {
                        $where .= " AND r.agency_id = %d";
                        $params[] = $agency_id;
                    } else {
                        $where .= " AND 1=0";
                    }
                }
                elseif ($access_level === 'division') {
                    $regency_ids = \WP_Agency_WP_Customer_Integration::get_user_division_jurisdictions($user_id);
                    if (!empty($regency_ids)) {
                        $placeholders = implode(',', array_fill(0, count($regency_ids), '%d'));
                        $where .= " AND r.regency_id IN ($placeholders)";
                        $params = array_merge($params, $regency_ids);
                    } else {
                        $where .= " AND 1=0";
                    }
                }
                elseif ($access_level === 'inspector') {
                    $inspector_id = \WP_Agency_WP_Customer_Integration::get_user_inspector_id($user_id);
                    if ($inspector_id) {
                        $where .= " AND r.inspector_id = %d";
                        $params[] = $inspector_id;
                    } else {
                        $where .= " AND 1=0";
                    }
                }
                else {
                    $where .= " AND 1=0";
                }
            } else {
                $where .= " AND 1=0";
            }
        }

        // Add search if provided
        if (!empty($search)) {
            $where .= " AND r.name LIKE %s";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        // Validate order column
        $validColumns = ['code', 'name', 'type'];
        if (!in_array($orderColumn, $validColumns)) {
            $orderColumn = 'code';
        }

        // Gunakan lowercase untuk SQL juga, lalu konversi ke uppercase untuk query
        $sqlOrderDir = $orderDir === 'desc' ? 'DESC' : 'ASC';
        $order = " ORDER BY " . esc_sql($orderColumn) . " " . $sqlOrderDir;

        // Add limit
        $limit = $wpdb->prepare(" LIMIT %d, %d", $start, $length);

        // Complete query
        $sql = $select . $from . $join . $where . $order . $limit;
        
        // Log query for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $query_log = $wpdb->prepare($sql, $params);
            error_log("Branch DataTable Query: " . $query_log);
        }

        // Get paginated results
        $results = $wpdb->get_results($wpdb->prepare($sql, $params));

        if ($results === null) {
            throw new \Exception($wpdb->last_error);
        }

        // Get total filtered count
        $filtered = $wpdb->get_var("SELECT FOUND_ROWS()");

        // Get total count for customer
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE customer_id = %d",
            $customer_id
        ));

        // Prepare result
        $result = [
            'data' => $results,
            'total' => (int) $total,
            'filtered' => (int) $filtered
        ];
        
        // Set cache dengan durasi 2 menit - gunakan orderDir yang sama (lowercase)
        $this->cache->setDataTableCache(
            'customer_branch_list',
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
     * Get total branch count based on user permission
     * Only users with 'view_customer_branch_list' capability can see all branches
     *
     * @param int|null $id Optional customer ID for filtering
     * @return int Total number of branches
     */

    public function getTotalCount($customer_id): int {
        global $wpdb;

        error_log('--- Debug BranchModel getTotalCount ---');
        error_log('User ID: ' . get_current_user_id());

        // Get user relation from CustomerModel to determine access
        $customerModel = new CustomerModel();
        $relation = $customerModel->getUserRelation(0); // 0 for general access check
        $access_type = $relation['access_type'];

        error_log('Access type: ' . $access_type);
        error_log('Is admin: ' . ($relation['is_admin'] ? 'yes' : 'no'));
        error_log('Is customer admin: ' . ($relation['is_customer_admin'] ? 'yes' : 'no'));
        error_log('Is branch admin: ' . ($relation['is_customer_branch_admin'] ? 'yes' : 'no'));
        error_log('Is employee: ' . ($relation['is_customer_employee'] ? 'yes' : 'no'));

        // Base query parts
        $select = "SELECT SQL_CALC_FOUND_ROWS r.*, p.name as customer_name";
        $from = " FROM {$this->table} r";
        $join = " LEFT JOIN {$this->customer_table} p ON r.customer_id = p.id";

        // Default where clause
        $where = " WHERE 1=1";
        $params = [];

        // Debug query building process
        error_log('Building WHERE clause:');
        error_log('Initial WHERE: ' . $where);

        // Apply filtering based on access type
        if ($relation['is_admin']) {
            // Administrator - see all branches
            error_log('User is admin - no additional restrictions');
        }
        elseif ($access_type === 'platform') {
            // Platform users (from wp-app-core) - see all branches
            // No additional restrictions (same as admin)
            // Access controlled via WordPress capabilities (view_customer_branch_detail)
            error_log('User is platform - no additional restrictions');
        }
        elseif ($access_type === 'agency') {
            // Agency users (from wp-agency) - role-based filtering
            // wp-agency TODO-2065: Multi-level access control
            if (class_exists('\\WP_Agency_WP_Customer_Integration')) {
                $user_id = get_current_user_id();
                $access_level = \WP_Agency_WP_Customer_Integration::get_user_access_level($user_id);

                if ($access_level === 'agency') {
                    // Level 1: Agency admin (Province level)
                    // Roles: agency_admin_dinas, agency_kepala_dinas
                    $agency_id = \WP_Agency_WP_Customer_Integration::get_user_agency_id($user_id);
                    if ($agency_id) {
                        $where .= " AND r.agency_id = %d";
                        $params[] = $agency_id;
                        error_log("Agency-level user - filtered to agency_id {$agency_id}");
                    } else {
                        $where .= " AND 1=0";
                    }
                }
                elseif ($access_level === 'division') {
                    // Level 2: Division admin (Jurisdiction/Kabupaten level)
                    // Roles: agency_admin_unit, agency_kepala_unit, etc
                    $regency_ids = \WP_Agency_WP_Customer_Integration::get_user_division_jurisdictions($user_id);
                    if (!empty($regency_ids)) {
                        $placeholders = implode(',', array_fill(0, count($regency_ids), '%d'));
                        $where .= " AND r.regency_id IN ($placeholders)";
                        $params = array_merge($params, $regency_ids);
                        error_log("Division-level user - filtered to " . count($regency_ids) . " jurisdictions");
                    } else {
                        $where .= " AND 1=0";
                        error_log('Division-level user has no jurisdictions - blocking access');
                    }
                }
                elseif ($access_level === 'inspector') {
                    // Level 3: Inspector (Per branch level)
                    // Roles: agency_pengawas, agency_pengawas_spesialis
                    $inspector_id = \WP_Agency_WP_Customer_Integration::get_user_inspector_id($user_id);
                    if ($inspector_id) {
                        $where .= " AND r.inspector_id = %d";
                        $params[] = $inspector_id;
                        error_log("Inspector-level user - filtered to inspector_id {$inspector_id}");
                    } else {
                        $where .= " AND 1=0";
                        error_log('Inspector has no ID - blocking access');
                    }
                }
                else {
                    $where .= " AND 1=0";
                    error_log('Agency user has no recognized access level - blocking access');
                }
            } else {
                error_log('WP_Agency_WP_Customer_Integration class not found');
                $where .= " AND 1=0";
            }
        }
        elseif ($relation['is_customer_admin']) {
            // Customer Admin - see all branches under their customer
            $where .= " AND p.user_id = %d";
            $params[] = get_current_user_id();
            error_log('Added customer admin restriction: ' . $where);
        }
        elseif ($relation['is_customer_branch_admin']) {
            // Branch Admin - only see their own branch
            $where .= " AND r.user_id = %d";
            $params[] = get_current_user_id();
            error_log('Added branch admin restriction - only own branch');
        }
        elseif ($relation['is_customer_employee']) {
            // Employee - only see the branch they work in
            $employee_branch = $wpdb->get_var($wpdb->prepare(
                "SELECT branch_id FROM {$wpdb->prefix}app_customer_employees
                 WHERE user_id = %d AND status = 'active' LIMIT 1",
                get_current_user_id()
            ));

            if ($employee_branch) {
                $where .= " AND r.id = %d";
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
        error_log('--- End Debug ---');

        return $total;
    }

    public function getByCustomer($customer_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'app_customer_branches';

        $query = $wpdb->prepare(
            "SELECT id, name, address, phone, email, status
             FROM {$table}
             WHERE customer_id = %d
             AND status = 'active'
             ORDER BY name ASC",
            $customer_id
        );

        return $wpdb->get_results($query);
    }

    /**
     * Get agency and division IDs based on province and regency IDs
     *
     * This method automatically assigns agency and division for branch creation
     * based on geographical location. Agency is determined by province, and division
     * is found through jurisdiction relationships for the selected regency.
     *
     * @param int $provinsi_id Province ID from wilayah-indonesia plugin
     * @param int $regency_id Regency ID from wilayah-indonesia plugin
     * @return array ['agency_id' => int|null, 'division_id' => int|null]
     * @throws \Exception if agency not found for the province
     */
    public function getAgencyAndDivisionIds(int $provinsi_id, int $regency_id): array {
        global $wpdb;

        // Get agency_id using logic from BranchDemoData::generateAgencyID()
        $province_code = $wpdb->get_var($wpdb->prepare(
            "SELECT code FROM {$wpdb->prefix}wi_provinces WHERE id = %d",
            $provinsi_id
        ));

        if (!$province_code) {
            throw new \Exception("Province not found for ID: {$provinsi_id}");
        }

        $agency_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}app_agencies WHERE provinsi_code = %s LIMIT 1",
            $province_code
        ));

        if (!$agency_id) {
            throw new \Exception("Agency not found for province code: {$province_code}");
        }

        // Get division_id using logic from BranchDemoData::generateDivisionID() with fallback
        $regency_code = $wpdb->get_var($wpdb->prepare(
            "SELECT code FROM {$wpdb->prefix}wi_regencies WHERE id = %d",
            $regency_id
        ));

        $division_id = null;

        if ($regency_code) {
            // Find division with matching regency_code
            $division_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}app_agency_divisions WHERE regency_code = %s LIMIT 1",
                $regency_code
            ));

            // Fallback: find any division from the same province's agency
            if (!$division_id) {
                $division_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}app_agency_divisions WHERE agency_id = %d LIMIT 1",
                    $agency_id
                ));
            }
        }

        return [
            'agency_id' => (int)$agency_id,
            'division_id' => $division_id ? (int)$division_id : null
        ];
    }

    /**
     * Get inspector (pengawas) user ID for a division
     *
     * Finds an active pengawas from the division that covers the branch location.
     * If division_id provided, finds pengawas from that specific division.
     * Otherwise falls back to any pengawas from the province's agency.
     *
     * @param int $provinsi_id Province ID from wilayah-indonesia plugin
     * @param int|null $division_id Division ID from wp-agency plugin
     * @return int|null Inspector user ID or null if not found
     */
    public function getInspectorId(int $provinsi_id, ?int $division_id = null): ?int {
        global $wpdb;

        // If division_id provided, find pengawas from that division first
        if ($division_id) {
            $inspector_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ae.user_id
                 FROM {$wpdb->prefix}app_agency_employees ae
                 JOIN {$wpdb->prefix}usermeta um ON ae.user_id = um.user_id
                 WHERE ae.division_id = %d
                 AND ae.status = 'active'
                 AND um.meta_key = %s
                 AND (um.meta_value LIKE %s OR um.meta_value LIKE %s)
                 LIMIT 1",
                $division_id,
                $wpdb->prefix . 'capabilities',
                '%"agency_pengawas"%',
                '%"agency_pengawas_spesialis"%'
            ));

            if ($inspector_id) {
                return (int)$inspector_id;
            }
        }

        // Fallback: Get pengawas from province's agency
        $province_code = $wpdb->get_var($wpdb->prepare(
            "SELECT code FROM {$wpdb->prefix}wi_provinces WHERE id = %d",
            $provinsi_id
        ));

        if (!$province_code) {
            return null;
        }

        $agency_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}app_agencies WHERE provinsi_code = %s LIMIT 1",
            $province_code
        ));

        if (!$agency_id) {
            return null;
        }

        // Get any pengawas from the agency
        $inspector_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ae.user_id
             FROM {$wpdb->prefix}app_agency_employees ae
             JOIN {$wpdb->prefix}usermeta um ON ae.user_id = um.user_id
             WHERE ae.agency_id = %d
             AND ae.status = 'active'
             AND um.meta_key = %s
             AND (um.meta_value LIKE %s OR um.meta_value LIKE %s)
             LIMIT 1",
            $agency_id,
            $wpdb->prefix . 'capabilities',
            '%"agency_pengawas"%',
            '%"agency_pengawas_spesialis"%'
        ));

        return $inspector_id ? (int)$inspector_id : null;
    }

    /**
     * Get user relation with branch
     * 
     * Determines the relationship between a user and a branch:
     * - is_admin: User has admin privileges for all branches
     * - is_customer_admin: User is the owner of the parent customer
     * - is_customer_branch_admin: User is the admin of this specific branch
     * - is_customer_employee: User is a staff member of this branch
     * 
     * @param int $branch_id Branch ID
     * @param int|null $user_id User ID (current user if null)
     * @return array Relationship array with boolean flags
     */
    
    public function getUserRelation(int $branch_id, int $user_id = null): array {
        try {
            global $wpdb;

            // Validate input
            $user_id = $user_id && is_numeric($user_id) ? (int)$user_id : get_current_user_id();
            $branch_id = is_numeric($branch_id) ? (int)$branch_id : 0;

            // Determine access type - need to check database FIRST for correct access_type
            // Use edit_all_customers to check for admin (not edit_all_customer_branches which customer_admin also has)
            $is_admin = current_user_can('edit_all_customers');
            $is_customer_admin = false;
            $is_customer_branch_admin = false;
            $is_customer_employee = false;

            // TODO-2174: Single query optimization (Branch version of Task-2173)
            // Replace multiple separate queries with 1 optimized query using LEFT JOINs

            $relation_data = null;
            $customer_id = 0;

            if (!$is_admin) {
                // Get customer_id first if checking specific branch
                if ($branch_id > 0) {
                    $customer_id = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT customer_id FROM {$wpdb->prefix}app_customer_branches WHERE id = %d",
                        $branch_id
                    ));
                }

                // Single query to check all user relations
                $query = $wpdb->prepare("
                    SELECT
                        -- Role checks (boolean)
                        CASE WHEN c.user_id IS NOT NULL THEN 1 ELSE 0 END as is_customer_admin,
                        CASE WHEN b.user_id IS NOT NULL THEN 1 ELSE 0 END as is_customer_branch_admin,
                        CASE
                            WHEN ce.user_id IS NOT NULL
                                 AND c.user_id IS NULL
                                 AND b.user_id IS NULL
                            THEN 1
                            ELSE 0
                        END as is_customer_employee,

                        -- Customer Owner details
                        c.id as owner_of_customer_id,
                        c.name as owner_of_customer_name,

                        -- Branch Admin details
                        b.customer_id as branch_admin_of_customer_id,
                        b.id as branch_admin_of_branch_id,
                        b.name as branch_admin_of_branch_name,

                        -- Employee details
                        ce.customer_id as employee_of_customer_id,
                        ce.branch_id as employee_of_branch_id,
                        c_emp.name as employee_of_customer_name,
                        b_emp.name as employee_of_branch_name

                    FROM (SELECT %d as uid, %d as bid, %d as cid) u

                    -- LEFT JOIN customers (check ownership)
                    LEFT JOIN {$wpdb->prefix}app_customers c
                        ON c.user_id = u.uid
                        AND (u.cid = 0 OR c.id = u.cid)
                        AND c.status = 'active'

                    -- LEFT JOIN branches (check branch admin)
                    LEFT JOIN {$wpdb->prefix}app_customer_branches b
                        ON b.user_id = u.uid
                        AND (u.bid = 0 OR b.id = u.bid)
                        AND (u.cid = 0 OR b.customer_id = u.cid)
                        AND b.status = 'active'

                    -- LEFT JOIN employees (check employee)
                    LEFT JOIN {$wpdb->prefix}app_customer_employees ce
                        ON ce.user_id = u.uid
                        AND (u.cid = 0 OR ce.customer_id = u.cid)
                        AND ce.status = 'active'
                    LEFT JOIN {$wpdb->prefix}app_customers c_emp ON ce.customer_id = c_emp.id
                    LEFT JOIN {$wpdb->prefix}app_customer_branches b_emp ON ce.branch_id = b_emp.id

                    LIMIT 1
                ",
                    $user_id,      // uid in subquery
                    $branch_id,    // bid in subquery
                    $customer_id   // cid in subquery
                );

                $relation_data = $wpdb->get_row($query, ARRAY_A);

                if ($relation_data) {
                    // Convert boolean integers to actual booleans
                    $is_customer_admin = (bool) $relation_data['is_customer_admin'];
                    $is_customer_branch_admin = (bool) $relation_data['is_customer_branch_admin'];
                    $is_customer_employee = (bool) $relation_data['is_customer_employee'];
                }
            }

            // NOW we can determine correct access_type
            $access_type = 'none';
            if ($is_admin) $access_type = 'admin';
            else if ($is_customer_admin) $access_type = 'customer_admin';
            else if ($is_customer_branch_admin) $access_type = 'customer_branch_admin';
            else if ($is_customer_employee) $access_type = 'customer_employee';

            // Apply access_type filter - allow plugins to modify access type
            $context = [
                'is_admin' => $is_admin,
                'is_customer_admin' => $is_customer_admin,
                'is_customer_branch_admin' => $is_customer_branch_admin,
                'is_customer_employee' => $is_customer_employee,
                'user_id' => $user_id,
                'branch_id' => $branch_id
            ];

            // Fire NEW filter (v1.1.0+) - consistent naming
            $access_type = apply_filters('wp_customer_branch_access_type', $access_type, $context);

            // Fire OLD filter with deprecation notice (backward compatibility)
            if (has_filter('wp_branch_access_type')) {
                _deprecated_hook(
                    'wp_branch_access_type',
                    '1.1.0',
                    'wp_customer_branch_access_type',
                    'Please update your code to use the new standardized HOOK name for consistency with wp_customer_{entity}_{purpose} naming pattern.'
                );
                $access_type = apply_filters('wp_branch_access_type', $access_type, $context);
            }

            // Generate appropriate cache key based on access_type
            if ($branch_id === 0) {
                // Special case for general access check - group by access_type
                $cache_key = "customer_branch_relation_general_{$access_type}";
            } else {
                // Specific branch check - group by branch and access_type
                $cache_key = "customer_branch_relation_{$branch_id}_{$access_type}";
            }

            // Check cache with correct access_type
            $cached_relation = $this->cache->get('customer_branch_relation', $cache_key);
            if ($cached_relation !== null) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("BranchModel::getUserRelation - Cache hit for access_type {$access_type} and branch {$branch_id}");
                }
                return $cached_relation;
            }

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("BranchModel::getUserRelation - Cache miss for access_type {$access_type} and branch {$branch_id}");
            }

            // Build relation array using data from single query
            $relation = [
                'is_admin' => $is_admin,
                'is_customer_admin' => $is_customer_admin,
                'is_customer_branch_admin' => $is_customer_branch_admin,
                'is_customer_employee' => $is_customer_employee,
                'branch_id' => null,
                'customer_id' => null,
                'customer_name' => null,
                'branch_name' => null
            ];

            // Populate details from query result if available
            if ($relation_data && !$is_admin) {
                // Determine which details to use based on role priority
                if ($is_customer_admin && $relation_data['owner_of_customer_id']) {
                    $relation['customer_id'] = (int) $relation_data['owner_of_customer_id'];
                    $relation['customer_name'] = $relation_data['owner_of_customer_name'];
                } else if ($is_customer_branch_admin && $relation_data['branch_admin_of_branch_id']) {
                    $relation['branch_id'] = (int) $relation_data['branch_admin_of_branch_id'];
                    $relation['branch_name'] = $relation_data['branch_admin_of_branch_name'];
                    $relation['customer_id'] = (int) $relation_data['branch_admin_of_customer_id'];
                    // Get customer name if needed
                    if ($branch_id > 0) {
                        $branch_obj = $this->find($branch_id);
                        if ($branch_obj) {
                            $relation['customer_name'] = $branch_obj->customer_name ?? null;
                        }
                    }
                } else if ($is_customer_employee && $relation_data['employee_of_branch_id']) {
                    $relation['branch_id'] = (int) $relation_data['employee_of_branch_id'];
                    $relation['branch_name'] = $relation_data['employee_of_branch_name'];
                    $relation['customer_id'] = (int) $relation_data['employee_of_customer_id'];
                    $relation['customer_name'] = $relation_data['employee_of_customer_name'];
                }
            }

            // If checking specific branch, add branch details
            if ($branch_id > 0 && !$relation['branch_id']) {
                $branch_obj = $this->find($branch_id);
                if ($branch_obj) {
                    $relation['branch_id'] = $branch_id;
                    $relation['branch_name'] = $branch_obj->name;
                    $relation['customer_id'] = (int) $branch_obj->customer_id;
                    $relation['customer_name'] = $branch_obj->customer_name ?? null;
                }
            }

            // Apply filters to allow extensions
            // Fire NEW filter (v1.1.0+) - consistent naming
            $relation = apply_filters('wp_customer_branch_user_relation', $relation, $branch_id, $user_id);

            // Fire OLD filter with deprecation notice (backward compatibility)
            if (has_filter('wp_branch_user_relation')) {
                _deprecated_hook(
                    'wp_branch_user_relation',
                    '1.1.0',
                    'wp_customer_branch_user_relation',
                    'Please update your code to use the new standardized HOOK name for consistency with wp_customer_{entity}_{purpose} naming pattern.'
                );
                $relation = apply_filters('wp_branch_user_relation', $relation, $branch_id, $user_id);
            }

            // Add access_type to relation
            $relation['access_type'] = $access_type;

            // TODO-2175: Hierarchical logging (Branch version of Task-2172)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $user = get_userdata($user_id);
                $user_login = $user ? $user->user_login : 'unknown';

                error_log("============================================================");
                error_log("[BRANCH ACCESS] User {$user_id} ({$user_login}) - Hierarchical Validation:");
                error_log("");

                // LEVEL 1: Capability Check
                $has_cap = current_user_can('view_customer_branch_list');
                error_log("  LEVEL 1 (Capability Check):");
                error_log("    " . ($has_cap ? "✓ PASS" : "✗ FAIL") . " - Has 'view_customer_branch_list' capability");

                // LEVEL 2: Database Record Check
                error_log("  LEVEL 2 (Database Record Check):");
                if ($is_admin) {
                    error_log("    ✓ PASS - WordPress Administrator");
                } else if ($is_customer_admin) {
                    error_log("    ✓ PASS - Customer Owner (customer_id: {$relation['customer_id']})");
                } else if ($is_customer_branch_admin) {
                    error_log("    ✓ PASS - Branch Admin (branch_id: {$relation['branch_id']})");
                } else if ($is_customer_employee) {
                    error_log("    ✓ PASS - Customer Employee (branch_id: {$relation['branch_id']})");
                } else {
                    error_log("    ⊘ SKIP - Not a direct customer record");
                }

                // LEVEL 3: Access Type Filter
                error_log("  LEVEL 3 (Access Type Filter):");
                error_log("    Filter: 'wp_customer_branch_access_type'");
                error_log("    Result: {$access_type}");

                if ($access_type === 'agency' || $access_type === 'platform') {
                    error_log("    ✓ Modified by external plugin ({$access_type})");

                    // Display agency/platform context if available
                    if (isset($relation['agency_id'])) {
                        error_log("");
                        error_log("  Context (from 'wp_customer_branch_user_relation' filter):");
                        error_log("    Agency: {$relation['agency_name']} (ID: {$relation['agency_id']})");

                        if (isset($relation['division_id']) && $relation['division_id']) {
                            error_log("    Division: {$relation['division_name']} (ID: {$relation['division_id']})");
                        }

                        if (isset($relation['access_level'])) {
                            $level_desc = [
                                'agency_wide' => 'Agency-wide (admin_dinas)',
                                'division_specific' => 'Division-specific (admin_unit)',
                                'branch_specific' => 'Branch-specific (pengawas)'
                            ];
                            $access_level_display = isset($level_desc[$relation['access_level']])
                                ? $level_desc[$relation['access_level']]
                                : $relation['access_level'];
                            error_log("    Access Level: {$access_level_display}");
                        }

                        if (isset($relation['assigned_branch_ids']) && !empty($relation['assigned_branch_ids'])) {
                            $branch_count = count($relation['assigned_branch_ids']);
                            error_log("    Assigned Branches: {$branch_count} branches [" .
                                     implode(', ', array_slice($relation['assigned_branch_ids'], 0, 5)) .
                                     ($branch_count > 5 ? '...' : '') . "]");
                        }
                    }
                } else if ($access_type === 'customer_admin') {
                    error_log("    = Customer Owner access");
                } else if ($access_type === 'customer_branch_admin') {
                    error_log("    = Branch Admin access");
                } else if ($access_type === 'admin') {
                    error_log("    = Full administrator access");
                } else {
                    error_log("    = No access");
                }

                // LEVEL 4: Data Scope Filter
                error_log("");
                error_log("  LEVEL 4 (Data Scope Filter):");
                if ($access_type === 'admin') {
                    error_log("    Scope: ALL branches (no filter)");
                } else if ($access_type === 'customer_admin') {
                    error_log("    Scope: Customer {$relation['customer_id']} branches only");
                } else if ($access_type === 'customer_branch_admin') {
                    error_log("    Scope: Branch {$relation['branch_id']} only");
                } else if ($access_type === 'agency') {
                    if (isset($relation['access_level'])) {
                        if ($relation['access_level'] === 'branch_specific') {
                            $assigned_branches = $relation['assigned_branch_ids'] ?? [];
                            $branch_count = count($assigned_branches);
                            error_log("    Scope: {$branch_count} assigned branches only (WHERE inspector_id={$user_id})");
                        } else if ($relation['access_level'] === 'division_specific') {
                            error_log("    Scope: Division {$relation['division_id']} only (WHERE division_id={$relation['division_id']})");
                        } else {
                            error_log("    Scope: Agency {$relation['agency_id']} (WHERE agency_id={$relation['agency_id']})");
                        }
                    } else {
                        error_log("    Scope: Agency-filtered branches");
                    }
                } else if ($access_type === 'platform') {
                    error_log("    Scope: Platform-filtered branches");
                } else {
                    error_log("    Scope: NONE (access denied)");
                }

                // FINAL RESULT
                error_log("");
                error_log("  FINAL RESULT:");
                $has_access = ($access_type !== 'none');
                error_log("    Has Access: " . ($has_access ? "✓ TRUE" : "✗ FALSE"));
                error_log("    Access Type: {$access_type}");
                error_log("    Branch ID: " . ($branch_id ?: 'N/A (list view)'));

                error_log("============================================================");
                error_log("");
            }

            // Get cache duration (configurable or default 2 minutes)
            $cache_duration = defined('WP_CUSTOMER_BRANCH_RELATION_CACHE_DURATION') ?
                             WP_CUSTOMER_BRANCH_RELATION_CACHE_DURATION : 120;

            // Cache result
            $this->cache->set('customer_branch_relation', $relation, $cache_duration, $cache_key);

            return $relation;
            
        } catch (\Exception $e) {
            // Log error and return default relation on failure
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Error in BranchModel::getUserRelation: " . $e->getMessage());
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
     * NOTE: Cache keys use pattern: customer_branch_relation_{$branch_id}_{$access_type}
     *       Since we don't always know the access_type when invalidating,
     *       we use clearCache() to clear all branch relation cache entries.
     *       This is the same approach as CustomerModel.
     *
     * @param int|null $branch_id Branch ID (null for all branches)
     * @param int|null $user_id User ID (null for all users) - not used in cache key but kept for API consistency
     * @return void
     */
    public function invalidateUserRelationCache(int $branch_id = null, int $user_id = null): void {
        try {
            if ($branch_id && $user_id) {
                // Invalidate specific relation
                // Since cache key pattern is customer_branch_relation_{$branch_id}_{$access_type},
                // and we don't know access_type here, we clear all branch relation cache
                $this->cache->clearCache('customer_branch_relation');
            } else if ($branch_id) {
                // We need to invalidate all relations for this branch
                // This is a bit tricky without key pattern matching
                // For now, let's just clear all branch relation cache
                $this->cache->clearCache('customer_branch_relation');
            } else if ($user_id) {
                // Invalidate general relation for this user
                // Since we don't know access_type, clear all
                $this->cache->clearCache('customer_branch_relation');
            } else {
                // Clear all relation cache
                $this->cache->clearCache('customer_branch_relation');
            }

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Invalidated customer branch relation cache: branch_id=$branch_id, user_id=$user_id");
            }
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Error in invalidateUserRelationCache: " . $e->getMessage());
            }
        }
    }

    /**
     * Invalidate DataTable cache for all access types
     *
     * Since cache keys use access_type as component, we need to invalidate
     * all possible access types when data changes
     *
     * @param string $context The DataTable context (e.g., 'branch_list')
     * @param int $customer_id The customer ID to invalidate cache for
     * @return void
     */
    private function invalidateAllDataTableCache(string $context, int $customer_id): void {
        try {
            $cache_group = 'wp_customer';
            $customer_hash = md5(serialize($customer_id));

            // List of all possible access types
            $access_types = ['admin', 'customer_admin', 'customer_branch_admin', 'customer_employee', 'none'];

            // Possible pagination/ordering variations to try
            $starts = [0, 10, 20, 30, 40, 50];
            $lengths = [10, 25, 50, 100];
            $orders = ['asc', 'desc'];
            $columns = ['name', 'code', 'type'];

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
}
