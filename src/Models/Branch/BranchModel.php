<?php
/**
 * Branch Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models/Branch
 * @version     1.0.0
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
    private const KEY_BRANCH = 'branch';
    private const KEY_CUSTOMER_BRANCH_LIST = 'customer_branch_list';
    private const KEY_CUSTOMER_BRANCH = 'customer_branch';
    private const KEY_BRANCH_LIST = 'branch_list';
    private const CACHE_EXPIRY = 7200; // 2 hours in seconds

    private $table;
    private $customer_table;
    private CustomerModel $customerModel;
    private $cache;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'app_branches';
        $this->customer_table = $wpdb->prefix . 'app_customers';
        $this->customerModel = new CustomerModel();
        $this->cache = new CustomerCacheManager();   
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
            'regency_id' => $data['regency_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
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
                '%d', // regency_id
                '%d', // user_id
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

        return (int) $wpdb->insert_id;
    }

    public function find(int $id): ?object {
        global $wpdb;

        // Cek cache dulu
        $cached = $this->cache->get(self::KEY_BRANCH, $id);
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
            $this->cache->set(self::KEY_BRANCH, $result, self::CACHE_EXPIRY, $id);
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
            'regency_id' => $data['regency_id'] ?? null,
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
                case 'regency_id':
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
            $this->cache->delete(self::KEY_BRANCH, $id);
            $this->cache->delete(self::KEY_BRANCH_LIST);          
            return false;
        }

        return true;
    }

    public function delete(int $id): bool {
        global $wpdb;

        return $wpdb->delete(
            $this->table,
            ['id' => $id],
            ['%d']
        ) !== false;
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
            'branch_list',
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
                error_log("BranchModel cache hit for DataTable - Key: branch_list_{$access_type}");
            }
            return $cached_result;
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("BranchModel cache miss for DataTable - Key: branch_list_{$access_type}");
        }
        
        global $wpdb;

        // Base query parts
        $select = "SELECT SQL_CALC_FOUND_ROWS r.*, p.name as customer_name";
        $from = " FROM {$this->table} r";
        $join = " LEFT JOIN {$this->customer_table} p ON r.customer_id = p.id";
        $where = " WHERE r.customer_id = %d";
        $params = [$customer_id];

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
            'branch_list',
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
     * Only users with 'view_branch_list' capability can see all branches
     * 
     * @param int|null $id Optional customer ID for filtering
     * @return int Total number of branches
     */

    public function getTotalCount($customer_id): int {
        global $wpdb;

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
        // Cek dulu current_user_id untuk berbagai keperluan 
        $current_user_id = get_current_user_id();

        // Dapatkan relasi User ID wordpress dengan customer User ID
        $has_customer = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->customer_table} WHERE user_id = %d",
            $current_user_id
        ));
        error_log('User has customer: ' . ($has_customer > 0 ? 'yes' : 'no'));

        if ($has_customer > 0 && current_user_can('view_own_customer') && current_user_can('view_own_branch')) {
            $where .= " AND p.user_id = %d";
            $params[] = get_current_user_id();
            error_log('Added user restriction: ' . $where);
        }

        if (current_user_can('edit_all_customer') && current_user_can('edit_all_branch')) {
            error_log('Added user restriction: ' . $where);
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
        
        $table = $wpdb->prefix . 'app_branches';
        
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
     * Get user relation with branch
     * 
     * Determines the relationship between a user and a branch:
     * - is_admin: User has admin privileges for all branches
     * - is_customer_owner: User is the owner of the parent customer
     * - is_branch_admin: User is the admin of this specific branch
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
            
            // Determine base relation first - needed for access_type
            $base_relation = [
                'is_admin' => current_user_can('edit_all_branches'),
                'is_customer_owner' => false,
                'is_branch_admin' => false,
                'is_customer_employee' => false
            ];
            
            // Determine access type from base relation
            $access_type = 'none';
            if ($base_relation['is_admin']) $access_type = 'admin';
            
            // Apply access_type filter
            $access_type = apply_filters('wp_branch_access_type', $access_type, $base_relation);
            
            // Generate appropriate cache key based on access_type
            if ($branch_id === 0) {
                // Special case for general access check - group by access_type
                $cache_key = "branch_relation_general_{$access_type}";
            } else {
                // Specific branch check - group by branch and access_type
                $cache_key = "branch_relation_{$branch_id}_{$access_type}";
            }
            
            // Check cache first
            $cached_relation = $this->cache->get('branch_relation', $cache_key);
            if ($cached_relation !== null) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("BranchModel::getUserRelation - Cache hit for access_type {$access_type} and branch {$branch_id}");
                }
                return $cached_relation;
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("BranchModel::getUserRelation - Cache miss for access_type {$access_type} and branch {$branch_id}");
            }
            
            // Get branch data
            $branch = $this->find($branch_id);
            if (!$branch) {
                return [
                    'is_admin' => $base_relation['is_admin'],
                    'is_customer_owner' => false,
                    'is_branch_admin' => false,
                    'is_customer_employee' => false,
                    'access_type' => $access_type
                ];
            }
            
            // Get customer info for this branch
            $customer = null;
            if ($branch->customer_id) {
                $customer_table = $wpdb->prefix . 'app_customers';
                $customer = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$customer_table} WHERE id = %d",
                    $branch->customer_id
                ));
            }
            
            // Build relation
            $relation = [
                'is_admin' => $base_relation['is_admin'],
                'is_customer_owner' => $customer && (int)$customer->user_id === $user_id,
                'is_branch_admin' => (int)$branch->user_id === $user_id,
                'is_customer_employee' => false,
                'branch_id' => $branch_id,
                'customer_id' => $branch->customer_id,
                'customer_name' => $customer ? $customer->name : null
            ];
            
            // Check if user is staff member of this branch
            $employee_table = $wpdb->prefix . 'app_customer_employees';
            $is_customer_employee = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$employee_table} 
                 WHERE user_id = %d AND branch_id = %d AND status = 'active'",
                $user_id, $branch_id
            )) > 0;
            
            $relation['is_customer_employee'] = $is_customer_employee;
            
            // Redetermine access type with complete relation info
            if ($relation['is_admin']) $access_type = 'admin';
            else if ($relation['is_customer_owner']) $access_type = 'customer_owner';
            else if ($relation['is_branch_admin']) $access_type = 'branch_admin';
            else if ($relation['is_customer_employee']) $access_type = 'staff';
            else $access_type = 'none';
            
            // Apply access_type filter again with complete info
            $access_type = apply_filters('wp_branch_access_type', $access_type, $relation);
            $relation['access_type'] = $access_type;
            
            // Apply filters for extensions
            $relation = apply_filters('wp_branch_user_relation', $relation, $branch_id, $user_id);
            
            // Get cache duration (configurable or default 2 minutes)
            $cache_duration = defined('WP_BRANCH_RELATION_CACHE_DURATION') ? 
                             WP_BRANCH_RELATION_CACHE_DURATION : 120;
            
            // Cache result
            $this->cache->set('branch_relation', $relation, $cache_duration, $cache_key);
            
            return $relation;
            
        } catch (\Exception $e) {
            // Log error and return default relation on failure
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Error in BranchModel::getUserRelation: " . $e->getMessage());
                error_log($e->getTraceAsString());
            }
            
            return [
                'is_admin' => current_user_can('edit_all_branches'),
                'is_customer_owner' => false,
                'is_branch_admin' => false,
                'is_customer_employee' => false,
                'access_type' => 'none',
                'error' => true
            ];
        }
    }

    /**
     * Invalidate user relation cache
     * 
     * @param int|null $branch_id Branch ID (null for all branches)
     * @param string|null $access_type Access type to invalidate (null for all types)
     * @return void
     */
    public function invalidateUserRelationCache(int $branch_id = null, string $access_type = null): void {
        try {
            if ($branch_id && $access_type) {
                // Invalidate specific relation by branch and access type
                $this->cache->delete('branch_relation', "branch_relation_{$branch_id}_{$access_type}");
            } else if ($branch_id) {
                // Invalidate all access types for this branch
                // Since we can't easily list all access types, just clear all branch relation cache
                $this->cache->clearCache('branch_relation');
            } else if ($access_type) {
                // Invalidate specific access type for all branches
                // We'd need a pattern match capability in cache manager
                $this->cache->clearCache('branch_relation');
            } else {
                // Clear all relation cache
                $this->cache->clearCache('branch_relation');
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Invalidated branch relation cache: branch_id=$branch_id, access_type=$access_type");
            }
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Error in invalidateUserRelationCache: " . $e->getMessage());
            }
        }
    }
}
