<?php
/**
 * Branch Model
 *
 * @package     WP_Customer
 * @subpackage  Models/Branch
 * @version     2.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Branch/BranchModel.php
 *
 * Description: CRUD model untuk Branch entity.
 *              Extends AbstractCrudModel dari wp-app-core.
 *              Handles create, read, update, delete operations.
 *              All CRUD operations INHERITED from AbstractCrudModel.
 *              AUTO-TRACKING: Uses Auditable trait for audit logging.
 *
 * Separation of Concerns:
 * - BranchModel: CRUD operations only
 * - BranchDataTableModel: DataTable server-side processing only
 * - BranchValidator: Validation only
 * - BranchController: HTTP request handling only
 *
 * Changelog:
 * 2.1.0 - 2025-12-28 (Audit Log Integration)
 * - Added: Auditable trait for automatic audit logging
 * - Added: Auto-tracking for create, update, delete operations
 * - Override: create(), update(), delete() methods untuk inject logging
 * - Config: auditable_type = 'branch', excluded fields = updated_at, created_at, updated_by
 * - All changes now logged to app_customer_audit_logs table
 *
 * 2.0.0 - 2025-11-09 (TODO-2193: CRUD Refactoring)
 * - BREAKING: Refactored to extend AbstractCrudModel
 * - CRUD methods INHERITED: find(), create(), update(), delete()
 * - Implements 7 abstract methods
 * - Custom methods: generateBranchCode(), findPusatByCustomer(), getAgencyAndDivisionIds()
 * - Removed: DataTable queries (use BranchDataTableModel)
 * - Removed: getUserRelation() (moved to BranchValidator)
 * - Kept: Agency/Division/Inspector assignment logic
 *
 * Previous version: BranchModel-OLD-*.php (backup)
 */

namespace WPCustomer\Models\Branch;

use WPAppCore\Models\Abstract\AbstractCrudModel;
use WPCustomer\Cache\BranchCacheManager;
use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Traits\Auditable;

defined('ABSPATH') || exit;

class BranchModel extends AbstractCrudModel {
    use Auditable;

    /**
     * Auditable configuration
     */
    protected $auditable_type = 'branch';
    protected $auditable_excluded = ['updated_at', 'created_at', 'updated_by'];

    /**
     * Reference field mappings for audit log
     * Format: 'field' => ['table' => 'table_name', 'key' => 'id_col', 'label' => 'name_col']
     */
    protected $auditable_references = [
        'province_id' => ['table' => 'wi_provinces', 'key' => 'id', 'label' => 'name'],
        'regency_id' => ['table' => 'wi_regencies', 'key' => 'id', 'label' => 'name'],
        'user_id' => ['table' => 'users', 'key' => 'ID', 'label' => 'display_name'],
        'customer_id' => ['table' => 'app_customers', 'key' => 'id', 'label' => 'name'],
        'agency_id' => ['table' => 'app_agencies', 'key' => 'id', 'label' => 'name'],
        'division_id' => ['table' => 'app_agency_divisions', 'key' => 'id', 'label' => 'name'],
        'inspector_id' => ['table' => 'app_agency_employees', 'key' => 'id', 'label' => 'name'],
    ];

    /**
     * Cache keys constants
     */
    private const KEY_BRANCH = 'branch';

    /**
     * Related models
     */
    private CustomerModel $customerModel;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(BranchCacheManager::getInstance());
        $this->customerModel = new CustomerModel();
    }

    // ========================================
    // IMPLEMENT ABSTRACT METHODS (8 required)
    // ========================================

    /**
     * Get database table name
     *
     * @return string
     */
    protected function getTableName(): string {
        global $wpdb;
        return $wpdb->prefix . 'app_customer_branches';
    }

    /**
     * Get format map for wpdb operations
     *
     * @return array Format map (field => format)
     */
    protected function getFormatMap(): array {
        return [
            'id' => '%d',
            'customer_id' => '%d',
            'code' => '%s',
            'name' => '%s',
            'type' => '%s',
            'email' => '%s',
            'phone' => '%s',
            'address' => '%s',
            'province_id' => '%d',
            'regency_id' => '%d',
            'postal_code' => '%s',
            'user_id' => '%d',
            'agency_id' => '%d',
            'division_id' => '%d',
            'inspector_id' => '%d',
            'status' => '%s',
            'created_by' => '%d',
            'updated_by' => '%d'
        ];
    }

    /**
     * Get cache method name prefix
     *
     * @return string
     */
    protected function getCacheKey(): string {
        return 'Branch';
    }

    /**
     * Get entity name
     *
     * @return string
     */
    protected function getEntityName(): string {
        return 'branch';
    }

    /**
     * Get plugin prefix for hooks
     *
     * @return string
     */
    protected function getPluginPrefix(): string {
        return 'wp_customer';
    }

    /**
     * Get allowed fields for update operations
     *
     * @return array
     */
    protected function getAllowedFields(): array {
        return [
            'customer_id',
            'code',
            'name',
            'type',
            'email',
            'phone',
            'address',
            'province_id',
            'regency_id',
            'postal_code',
            'user_id',
            'agency_id',
            'division_id',
            'inspector_id',
            'status'
        ];
    }

    /**
     * Prepare insert data from request
     *
     * @param array $data Raw request data
     * @return array Prepared insert data
     */
    protected function prepareInsertData(array $data): array {
        // Generate unique code if not provided
        if (empty($data['code'])) {
            $data['code'] = $this->generateBranchCode($data['customer_id']);
        }

        return [
            'customer_id' => $data['customer_id'],
            'code' => $data['code'],
            'name' => $data['name'],
            'type' => $data['type'] ?? 'cabang',
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'province_id' => $data['province_id'] ?? null,
            'regency_id' => $data['regency_id'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'agency_id' => $data['agency_id'] ?? null,
            'division_id' => $data['division_id'] ?? null,
            'inspector_id' => $data['inspector_id'] ?? null,
            'status' => $data['status'] ?? 'active',
            'created_by' => get_current_user_id()
        ];
    }

    /**
     * Prepare update data from request
     *
     * @param array $data Raw request data
     * @return array Prepared update data
     */
    protected function prepareUpdateData(array $data): array {
        $updateData = [];

        // Only update fields that are present in request
        $allowed = $this->getAllowedFields();

        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        $updateData['updated_by'] = get_current_user_id();

        return $updateData;
    }

    // ========================================
    // AUDIT LOG INTEGRATION
    // ========================================

    /**
     * Create branch with audit logging
     *
     * @param array $data Branch data
     * @return int|null Branch ID or null on failure
     */
    public function create(array $data): ?int {
        // Call parent create method
        $branch_id = parent::create($data);

        // Log creation
        if ($branch_id) {
            $this->logAudit('created', $branch_id, null, $data);
        }

        return $branch_id;
    }

    /**
     * Update branch with audit logging
     *
     * @param int $id Branch ID
     * @param array $data Update data
     * @return bool Success status
     */
    public function update(int $id, array $data): bool {
        // Get old data before update
        $old_data = $this->find($id);

        // Call parent update method
        $result = parent::update($id, $data);

        // Log update (only changed fields will be logged)
        if ($result && $old_data) {
            $this->logAudit('updated', $id, $old_data, $data);
        }

        return $result;
    }

    /**
     * Delete branch with audit logging
     *
     * @param int $id Branch ID
     * @return bool Success status
     */
    public function delete(int $id): bool {
        // Get data before deletion
        $old_data = $this->find($id);

        // Call parent delete method
        $result = parent::delete($id);

        // Log deletion
        if ($result && $old_data) {
            $this->logAudit('deleted', $id, $old_data, null);
        }

        return $result;
    }

    // ========================================
    // CUSTOM METHODS (Business Logic)
    // ========================================

    /**
     * Find Pusat branch by customer ID
     *
     * @param int $customer_id Customer ID
     * @return object|null Branch object or null
     */
    public function findPusatByCustomer(int $customer_id): ?object {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$this->getTableName()}
            WHERE customer_id = %d
            AND type = 'pusat'
            AND status = 'active'
            LIMIT 1
        ", $customer_id));
    }

    /**
     * Count Pusat branches for a customer
     *
     * @param int $customer_id Customer ID
     * @return int Count
     */
    public function countPusatByCustomer(int $customer_id): int {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$this->getTableName()}
            WHERE customer_id = %d
            AND type = 'pusat'
        ", $customer_id));
    }

    /**
     * Generate unique branch code
     *
     * @param int $customer_id Customer ID
     * @return string Generated code
     */
    private function generateBranchCode(int $customer_id): string {
        $customer = $this->customerModel->find($customer_id);

        if (!$customer) {
            return 'BR-' . str_pad($customer_id, 4, '0', STR_PAD_LEFT) . '-001';
        }

        $customerCode = $customer->code;
        $maxAttempts = 10;

        for ($i = 0; $i < $maxAttempts; $i++) {
            global $wpdb;

            $lastBranch = $wpdb->get_row($wpdb->prepare("
                SELECT code FROM {$this->getTableName()}
                WHERE customer_id = %d
                ORDER BY id DESC
                LIMIT 1
            ", $customer_id));

            if ($lastBranch && preg_match('/-(\d+)$/', $lastBranch->code, $matches)) {
                $nextNumber = intval($matches[1]) + 1;
            } else {
                $nextNumber = 1;
            }

            $code = $customerCode . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            if (!$this->existsByCode($code)) {
                return $code;
            }
        }

        return $customerCode . '-' . str_pad(time() % 1000, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Check if branch code exists
     *
     * @param string $code Branch code
     * @return bool
     */
    public function existsByCode(string $code): bool {
        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$this->getTableName()}
            WHERE code = %s
        ", $code));

        return $count > 0;
    }

    /**
     * Check if branch name exists in customer
     *
     * @param string $name Branch name
     * @param int $customer_id Customer ID
     * @param int|null $excludeId Exclude this ID (for update)
     * @return bool
     */
    public function existsByNameInCustomer(string $name, int $customer_id, ?int $excludeId = null): bool {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$this->getTableName()}
                WHERE customer_id = %d AND name = %s";

        $params = [$customer_id, $name];

        if ($excludeId) {
            $sql .= " AND id != %d";
            $params[] = $excludeId;
        }

        $count = $wpdb->get_var($wpdb->prepare($sql, $params));

        return $count > 0;
    }

    /**
     * Get all branches by customer ID
     *
     * @param int $customer_id Customer ID
     * @return array Array of branch objects
     */
    public function getByCustomer(int $customer_id): array {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$this->getTableName()}
            WHERE customer_id = %d
            AND status = 'active'
            ORDER BY type DESC, name ASC
        ", $customer_id));
    }

    /**
     * Get agency and division IDs from location
     *
     * Used for inspector assignment
     *
     * @param int $province_id Province ID
     * @param int $regency_id Regency ID
     * @return array ['agency_id' => int, 'division_id' => int]
     */
    public function getAgencyAndDivisionIds(int $province_id, int $regency_id): array {
        global $wpdb;

        // Get agency from province
        // Note: wp_app_agencies table uses 'province_id' (not 'province_id')
        $agency = $wpdb->get_row($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}app_agencies
            WHERE province_id = %d
            AND status = 'active'
            LIMIT 1
        ", $province_id));

        if (!$agency) {
            return ['agency_id' => null, 'division_id' => null];
        }

        // Get division from regency
        $division = $wpdb->get_row($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}app_agency_divisions
            WHERE agency_id = %d
            AND regency_id = %d
            AND status = 'active'
            LIMIT 1
        ", $agency->id, $regency_id));

        return [
            'agency_id' => $agency->id,
            'division_id' => $division ? $division->id : null
        ];
    }

    /**
     * Get inspector ID from location
     *
     * @param int $province_id Province ID
     * @param int|null $division_id Division ID
     * @return int|null Inspector employee ID (not user_id!)
     */
    public function getInspectorId(int $province_id, ?int $division_id = null): ?int {
        global $wpdb;

        // Find inspector from division or agency
        // IMPORTANT: Return employee.id (not user_id) because FK is to wp_app_agency_employees(id)
        $inspector = $wpdb->get_row($wpdb->prepare("
            SELECT id
            FROM {$wpdb->prefix}app_agency_employees
            WHERE division_id = %d
            AND status = 'active'
            LIMIT 1
        ", $division_id));

        return $inspector ? $inspector->id : null;
    }

    /**
     * Get DataTable data for branches
     *
     * NOTE: This is a compatibility method for backward compatibility with old controller
     * Use BranchDataTableModel for new implementations
     *
     * @param int $customer_id Customer ID
     * @param int $start Offset
     * @param int $length Limit
     * @param string $search Search term
     * @param string $orderColumn Order column
     * @param string $orderDir Order direction
     * @return array DataTable result
     */
    public function getDataTableData(int $customer_id, int $start, int $length, string $search, string $orderColumn, string $orderDir): array {
        global $wpdb;

        // Build WHERE clause
        $where = ["customer_id = %d"];
        $params = [$customer_id];

        // Add search filter
        if (!empty($search)) {
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where[] = "(name LIKE %s OR code LIKE %s OR email LIKE %s OR phone LIKE %s)";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $where_clause = implode(' AND ', $where);

        // Get total count
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->getTableName()} WHERE customer_id = %d",
            $customer_id
        ));

        // Get filtered count
        $filtered = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->getTableName()} WHERE {$where_clause}",
            ...$params
        ));

        // Get data with pagination
        $orderBy = in_array($orderColumn, ['name', 'code', 'type', 'email', 'phone']) ? $orderColumn : 'name';
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->getTableName()}
             WHERE {$where_clause}
             ORDER BY {$orderBy} {$orderDir}
             LIMIT %d, %d",
            array_merge($params, [$start, $length])
        );

        $data = $wpdb->get_results($query);

        return [
            'total' => (int) $total,
            'filtered' => (int) $filtered,
            'data' => $data ?: []
        ];
    }

    // ========================================
    // CACHE INVALIDATION
    // ========================================

    /**
     * Invalidate branch cache after CUD operations
     * Called automatically by AbstractCrudModel
     *
     * @param int $id Branch ID
     * @param mixed ...$additional_keys Additional cache keys to clear
     * @return void
     */
    protected function invalidateCache(int $id, ...$additional_keys): void {
        // Get customer_id if provided in additional_keys
        $customer_id = $additional_keys[0] ?? null;

        // Use BranchCacheManager's invalidation method
        /** @var BranchCacheManager $cache */
        $cache = $this->cache;
        $cache->invalidateBranchCache($id, $customer_id);
    }
}
