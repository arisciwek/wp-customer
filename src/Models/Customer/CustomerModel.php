<?php
/**
 * Customer Model
 *
 * @package     WP_Customer
 * @subpackage  Models/Customer
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Customer/CustomerModel.php
 *
 * Description: CRUD model untuk Customer entity.
 *              Extends AbstractCrudModel dari wp-app-core.
 *              Handles create, read, update, delete operations.
 *              All CRUD operations INHERITED from AbstractCrudModel.
 *
 * Changelog:
 * 2.0.0 - 2025-01-08 (Task-2191: CRUD Refactoring)
 * - BREAKING: Refactored to extend AbstractCrudModel
 * - Code reduction: 1451 lines → ~250 lines (83% reduction)
 * - CRUD methods INHERITED: find(), create(), update(), delete()
 * - Implements 7 abstract methods
 * - Custom methods: generateCustomerCode(), getMembershipData()
 * - Removed: DataTable queries (moved to CustomerDataTableModel - future)
 * - Removed: Statistics (use CustomerStatisticsModel)
 * - Removed: getUserRelation() (moved to CustomerValidator)
 */

namespace WPCustomer\Models\Customer;

use WPAppCore\Models\Crud\AbstractCrudModel;
use WPCustomer\Cache\CustomerCacheManager;

defined('ABSPATH') || exit;

class CustomerModel extends AbstractCrudModel {

    /**
     * Static code tracker (untuk uniqueness)
     */
    private static $used_codes = [];

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(CustomerCacheManager::getInstance());
    }

    // ========================================
    // IMPLEMENT ABSTRACT METHODS (7 required)
    // ========================================

    /**
     * Get database table name
     *
     * @return string
     */
    protected function getTableName(): string {
        global $wpdb;
        return $wpdb->prefix . 'app_customers';
    }

    /**
     * Get cache method name prefix
     *
     * @return string
     */
    protected function getCacheKey(): string {
        return 'Customer';
    }

    /**
     * Get entity name
     *
     * @return string
     */
    protected function getEntityName(): string {
        return 'customer';
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
            'name',
            'npwp',
            'nib',
            'status',
            'provinsi_id',
            'regency_id',
            'user_id'
        ];
    }

    /**
     * Prepare insert data from request
     *
     * @param array $data Raw request data
     * @return array Prepared insert data
     */
    protected function prepareInsertData(array $data): array {
        // Generate unique code
        $data['code'] = $this->generateCustomerCode();

        return [
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
    }

    /**
     * Get format map for wpdb operations
     *
     * @return array
     */
    protected function getFormatMap(): array {
        return [
            'id' => '%d',
            'code' => '%s',
            'name' => '%s',
            'npwp' => '%s',
            'nib' => '%s',
            'status' => '%s',
            'user_id' => '%d',
            'provinsi_id' => '%d',
            'regency_id' => '%d',
            'reg_type' => '%s',
            'created_by' => '%d',
            'created_at' => '%s',
            'updated_at' => '%s'
        ];
    }

    // ========================================
    // CUSTOM METHODS (Entity-specific)
    // ========================================

    /**
     * Generate unique customer code
     *
     * Format: TTTT-XX-RR
     * - TTTT = 4 digit timestamp (last 4 digits)
     * - XX = 2 random uppercase letters
     * - RR = 2 random digits
     *
     * @return string Unique 8-character code
     */
    public function generateCustomerCode(): string {
        $max_attempts = 100;
        $attempt = 0;

        do {
            // Get 4 digits from timestamp
            $timestamp = substr(time(), -4);

            // Generate 2 random uppercase letters
            $letter1 = substr('ABCDEFGHIJKLMNOPQRSTUVWXYZ', rand(0, 25), 1);
            $letter2 = substr('ABCDEFGHIJKLMNOPQRSTUVWXYZ', rand(0, 25), 1);

            // Generate 2 random digits
            $random = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);

            // Format: TTTTXXRR (8 chars)
            $code = sprintf('%s%s%s',
                $timestamp,
                $letter1 . $letter2,
                $random
            );

            // Check uniqueness
            $exists = in_array($code, self::$used_codes) || $this->codeExists($code);

            $attempt++;

            // Safety: If too many attempts, add microseconds
            if ($attempt > 50) {
                $micro = substr((string)microtime(true), -2);
                $code = $timestamp . $letter1 . $letter2 . $micro;
                $exists = in_array($code, self::$used_codes) || $this->codeExists($code);
            }

        } while ($exists && $attempt < $max_attempts);

        if ($attempt >= $max_attempts) {
            // Fallback: use uniqid for guaranteed uniqueness
            $code = substr(uniqid(), -8);
            error_log("[CustomerModel] WARNING: Code generation max attempts reached, using uniqid: {$code}");
        }

        self::$used_codes[] = $code;
        return $code;
    }

    /**
     * Check if code exists in database
     *
     * @param string $code Customer code
     * @return bool
     */
    public function codeExists(string $code): bool {
        global $wpdb;
        $table = $this->getTableName();

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT EXISTS (SELECT 1 FROM {$table} WHERE code = %s) as result",
            $code
        ));
    }

    /**
     * Get membership data for customer
     *
     * @param int $customer_id Customer ID
     * @return array Membership data
     */
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

    /**
     * Check if name exists (untuk validation)
     *
     * @param string $name Customer name
     * @param int|null $excludeId Exclude ID (for update)
     * @return bool
     */
    public function existsByName(string $name, ?int $excludeId = null): bool {
        global $wpdb;
        $table = $this->getTableName();

        $sql = "SELECT EXISTS (SELECT 1 FROM {$table} WHERE name = %s";
        $params = [$name];

        if ($excludeId) {
            $sql .= " AND id != %d";
            $params[] = $excludeId;
        }

        $sql .= ") as result";
        return (bool) $wpdb->get_var($wpdb->prepare($sql, $params));
    }

    /**
     * Check if NPWP exists
     *
     * @param string $npwp NPWP
     * @param int|null $excludeId Exclude ID (for update)
     * @return bool
     */
    public function existsByNPWP(string $npwp, ?int $excludeId = null): bool {
        global $wpdb;
        $table = $this->getTableName();

        if ($excludeId) {
            $sql = "SELECT EXISTS (SELECT 1 FROM {$table} WHERE npwp = %s AND id != %d)";
            return (bool) $wpdb->get_var($wpdb->prepare($sql, $npwp, $excludeId));
        }

        $sql = "SELECT EXISTS (SELECT 1 FROM {$table} WHERE npwp = %s)";
        return (bool) $wpdb->get_var($wpdb->prepare($sql, $npwp));
    }

    /**
     * Check if NIB exists
     *
     * @param string $nib NIB
     * @param int|null $excludeId Exclude ID (for update)
     * @return bool
     */
    public function existsByNIB(string $nib, ?int $excludeId = null): bool {
        global $wpdb;
        $table = $this->getTableName();

        if ($excludeId) {
            $sql = "SELECT EXISTS (SELECT 1 FROM {$table} WHERE nib = %s AND id != %d)";
            return (bool) $wpdb->get_var($wpdb->prepare($sql, $nib, $excludeId));
        }

        $sql = "SELECT EXISTS (SELECT 1 FROM {$table} WHERE nib = %s)";
        return (bool) $wpdb->get_var($wpdb->prepare($sql, $nib));
    }

    /**
     * Get branch count for customer (untuk validation)
     *
     * @param int $id Customer ID
     * @return int Branch count
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
            FROM {$wpdb->prefix}app_customer_branches
            WHERE customer_id = %d
        ", $id));

        // Cache for 2 minutes
        $this->cache->set('branch_count', $count, 120, $id);

        return $count;
    }

    /**
     * Get all customer IDs
     * Used by demo data generators
     *
     * @param string $status Filter by status (default: 'active')
     * @return array Array of customer IDs
     */
    public function getAllCustomerIds(string $status = 'active'): array {
        global $wpdb;
        $table = $this->getTableName();

        $sql = "SELECT id FROM {$table}";

        if ($status !== 'all') {
            $sql .= $wpdb->prepare(" WHERE status = %s", $status);
        }

        $sql .= " ORDER BY id ASC";

        $results = $wpdb->get_col($sql);

        return $results ? array_map('intval', $results) : [];
    }
}
