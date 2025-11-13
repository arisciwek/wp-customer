<?php
/**
 * Customer Validator
 *
 * @package     WP_Customer
 * @subpackage  Validators
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Validators/CustomerValidator.php
 *
 * Description: Validator untuk Customer CRUD operations.
 *              Extends AbstractValidator dari wp-app-core.
 *              Handles form validation dan permission checks.
 *
 * Changelog:
 * 2.0.0 - 2025-01-08 (Task-2191: CRUD Refactoring)
 * - BREAKING: Refactored to extend AbstractValidator
 * - Code reduction: 463 lines → ~300 lines (35% reduction)
 * - Implements 13 abstract methods
 * - getUserRelation() moved from CustomerModel (permission logic)
 * - Custom validation: NPWP/NIB format, admin fields, delete checks
 */

namespace WPCustomer\Validators;

use WPAppCore\Validators\Abstract\AbstractValidator;
use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Cache\CustomerCacheManager;

defined('ABSPATH') || exit;

class CustomerValidator extends AbstractValidator {

    /**
     * @var CustomerModel
     */
    private $model;

    /**
     * @var CustomerCacheManager
     */
    private $cache;

    /**
     * Relation cache (in-memory)
     * Must be protected array (same as parent AbstractValidator)
     */
    protected array $relationCache = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->model = new CustomerModel();
        $this->cache = CustomerCacheManager::getInstance();
    }

    // ========================================
    // IMPLEMENT ABSTRACT METHODS (13 required)
    // ========================================

    /**
     * Get entity name
     *
     * @return string
     */
    protected function getEntityName(): string {
        return 'customer';
    }

    /**
     * Get entity display name
     *
     * @return string
     */
    protected function getEntityDisplayName(): string {
        return 'Customer';
    }

    /**
     * Get text domain
     *
     * @return string
     */
    protected function getTextDomain(): string {
        return 'wp-customer';
    }

    /**
     * Get model instance
     *
     * @return CustomerModel
     */
    protected function getModel() {
        return $this->model;
    }

    /**
     * Get create capability
     *
     * @return string
     */
    protected function getCreateCapability(): string {
        return 'add_customer';
    }

    /**
     * Get view capabilities
     *
     * @return array
     */
    protected function getViewCapabilities(): array {
        return ['view_customer_detail', 'view_own_customer'];
    }

    /**
     * Get update capabilities
     *
     * @return array
     */
    protected function getUpdateCapabilities(): array {
        return ['edit_all_customers', 'edit_own_customer'];
    }

    /**
     * Get delete capability
     *
     * @return string
     */
    protected function getDeleteCapability(): string {
        return 'delete_customer';
    }

    /**
     * Get list capability
     *
     * @return string
     */
    protected function getListCapability(): string {
        return 'view_customer_list';
    }

    /**
     * Validate create operation
     *
     * @param array $data Data to validate
     * @return array Errors (empty if valid)
     */
    protected function validateCreate(array $data): array {
        return $this->validateForm($data);
    }

    /**
     * Validate update operation
     *
     * @param int $id Entity ID
     * @param array $data Data to validate
     * @return array Errors (empty if valid)
     */
    protected function validateUpdate(int $id, array $data): array {
        return $this->validateForm($data, $id);
    }

    /**
     * Validate view operation
     *
     * @param int $id Entity ID
     * @return array Errors (empty if valid)
     */
    protected function validateView(int $id): array {
        $relation = $this->getUserRelation($id);

        if (!$this->canView($relation)) {
            return ['permission' => __('Anda tidak memiliki akses untuk melihat customer ini.', 'wp-customer')];
        }

        return [];
    }

    /**
     * Validate delete operation
     *
     * @param int $id Entity ID
     * @return array Errors (empty if valid)
     */
    protected function validateDeleteOperation(int $id): array {
        return $this->validateDelete($id);
    }

    /**
     * Check if user can create
     *
     * @return bool
     */
    protected function canCreate(): bool {
        return current_user_can('add_customer');
    }

    /**
     * Check if user can update
     *
     * @param int $id Entity ID
     * @return bool
     */
    protected function canUpdateEntity(int $id): bool {
        $relation = $this->getUserRelation($id);
        return $this->canUpdate($relation);
    }

    /**
     * Check if user can view
     *
     * @param int $id Entity ID
     * @return bool
     */
    protected function canViewEntity(int $id): bool {
        $relation = $this->getUserRelation($id);
        return $this->canView($relation);
    }

    /**
     * Check if user can delete
     *
     * @param int $id Entity ID
     * @return bool
     */
    protected function canDeleteEntity(int $id): bool {
        $relation = $this->getUserRelation($id);
        return $this->canDelete($relation);
    }

    /**
     * Check if user can list
     *
     * @return bool
     */
    protected function canList(): bool {
        return current_user_can('view_customer_list');
    }

    // ========================================
    // CUSTOM VALIDATION METHODS
    // ========================================

    /**
     * Validate form fields (implements abstract method)
     *
     * @param array $data Data to validate
     * @param int|null $id Entity ID (for update)
     * @return array Errors (empty if valid)
     */
    protected function validateFormFields(array $data, ?int $id = null): array {
        $errors = [];

        // Name validation
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            $errors['name'] = __('Nama customer wajib diisi.', 'wp-customer');
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = __('Nama customer maksimal 100 karakter.', 'wp-customer');
        } elseif ($this->model->existsByName($name, $id)) {
            $errors['name'] = __('Nama customer sudah ada.', 'wp-customer');
        }

        // NPWP validation (optional)
        if (!empty($data['npwp'])) {
            $npwp = trim($data['npwp']);
            if (!$this->validateNpwpFormat($npwp)) {
                $errors['npwp'] = __('Format NPWP tidak valid. Format: XX.XXX.XXX.X-XXX.XXX', 'wp-customer');
            } elseif ($this->model->existsByNPWP($data['npwp'], $id)) {
                $errors['npwp'] = __('NPWP sudah terdaftar.', 'wp-customer');
            }
        }

        // NIB validation (optional)
        if (!empty($data['nib'])) {
            $nib = trim($data['nib']);
            if (!$this->validateNibFormat($nib)) {
                $errors['nib'] = __('Format NIB tidak valid. Harus 13 digit.', 'wp-customer');
            } elseif ($this->model->existsByNIB($data['nib'], $id)) {
                $errors['nib'] = __('NIB sudah terdaftar.', 'wp-customer');
            }
        }

        // Province validation (required)
        if (empty($data['province_id'])) {
            $errors['province_id'] = __('Province is required', 'wp-customer');
        }

        // Regency validation (required)
        if (empty($data['regency_id'])) {
            $errors['regency_id'] = __('City/Regency is required', 'wp-customer');
        }

        return $errors;
    }

    /**
     * Validate delete operation
     *
     * @param int $id Entity ID
     * @return array Errors (empty if valid)
     */
    public function validateDelete(int $id): array {
        $errors = [];

        // Check permission
        if (!current_user_can('delete_customer')) {
            $errors[] = __('Anda tidak memiliki izin untuk menghapus customer', 'wp-customer');
            return $errors;
        }

        // Check if customer exists
        $customer = $this->model->find($id);
        if (!$customer) {
            $errors[] = __('Customer tidak ditemukan', 'wp-customer');
            return $errors;
        }

        // Check relation permission
        if (!$this->canDelete($this->getUserRelation($id))) {
            $errors[] = __('Anda tidak memiliki izin untuk menghapus customer ini', 'wp-customer');
            return $errors;
        }

        // Check if customer has branches
        $branch_count = $this->model->getBranchCount($id);
        if ($branch_count > 0) {
            $errors[] = sprintf(
                __('Customer tidak dapat dihapus karena masih memiliki %d cabang', 'wp-customer'),
                $branch_count
            );
        }

        // Check if customer has employees
        global $wpdb;
        $employee_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees WHERE customer_id = %d",
            $id
        ));

        if ($employee_count > 0) {
            $errors[] = sprintf(
                __('Customer tidak dapat dihapus karena masih memiliki %d karyawan', 'wp-customer'),
                $employee_count
            );
        }

        return $errors;
    }

    /**
     * Validate admin fields for customer creation
     *
     * @param array $data Data containing admin_name and admin_email
     * @return array Errors (empty if valid)
     */
    public function validateAdminFields(array $data): array {
        $errors = [];

        // Admin name validation
        $admin_name = trim($data['admin_name'] ?? '');
        if (empty($admin_name)) {
            $errors['admin_name'] = __('Admin name is required', 'wp-customer');
        }

        // Admin email validation
        $admin_email = trim($data['admin_email'] ?? '');
        if (empty($admin_email)) {
            $errors['admin_email'] = __('Admin email is required', 'wp-customer');
        } elseif (!is_email($admin_email)) {
            $errors['admin_email'] = __('Invalid email format', 'wp-customer');
        } elseif (email_exists($admin_email)) {
            $errors['admin_email'] = __('Admin email already exists', 'wp-customer');
        }

        return $errors;
    }

    /**
     * Validate access for customer
     *
     * @param int $customer_id Customer ID
     * @return array Access info
     */
    public function validateAccess(int $customer_id): array {
        $relation = $this->getUserRelation($customer_id);

        return [
            'has_access' => $this->canView($relation),
            'access_type' => $relation['access_type'],
            'relation' => $relation,
            'customer_id' => $customer_id
        ];
    }

    // ========================================
    // FORMAT & VALIDATION HELPERS
    // ========================================

    /**
     * Format NPWP to standard format: XX.XXX.XXX.X-XXX.XXX
     *
     * @param string $npwp Raw NPWP
     * @return string Formatted NPWP
     */
    public function formatNpwp(string $npwp): string {
        $numbers = preg_replace('/\D/', '', $npwp);

        if (strlen($numbers) === 15) {
            return substr($numbers, 0, 2) . '.' .
                   substr($numbers, 2, 3) . '.' .
                   substr($numbers, 5, 3) . '.' .
                   substr($numbers, 8, 1) . '-' .
                   substr($numbers, 9, 3) . '.' .
                   substr($numbers, 12, 3);
        }

        return $npwp;
    }

    /**
     * Validate NPWP format
     *
     * @param string $npwp NPWP to validate
     * @return bool
     */
    public function validateNpwpFormat(string $npwp): bool {
        return (bool) preg_match('/^\d{2}\.\d{3}\.\d{3}\.\d{1}\-\d{3}\.\d{3}$/', $npwp);
    }

    /**
     * Format NIB to clean 13 digits
     *
     * @param string $nib Raw NIB
     * @return string Formatted NIB
     */
    public function formatNib(string $nib): string {
        $numbers = preg_replace('/\D/', '', $nib);
        return substr($numbers, 0, 13);
    }

    /**
     * Validate NIB format
     *
     * @param string $nib NIB to validate
     * @return bool
     */
    public function validateNibFormat(string $nib): bool {
        return (bool) preg_match('/^\d{13}$/', $nib);
    }

    // ========================================
    // PERMISSION CHECKING (User Relation)
    // ========================================

    /**
     * Get user relation with customer
     *
     * This method moved from CustomerModel to CustomerValidator
     * because it's primarily used for permission checking.
     *
     * @param int $customer_id Customer ID
     * @return array Relation data
     */
    public function getUserRelation(int $customer_id): array {
        $current_user_id = get_current_user_id();

        // Check in-memory cache
        if (isset($this->relationCache[$customer_id])) {
            return $this->relationCache[$customer_id];
        }

        // Check persistent cache
        $cache_key = "customer_relation_{$customer_id}_{$current_user_id}";
        $cached_relation = $this->cache->get('customer_relation', $cache_key);

        // TODO-2192 FIXED: Cache now returns false on miss (not null)
        if ($cached_relation !== false) {
            $this->relationCache[$customer_id] = $cached_relation;
            return $cached_relation;
        }

        // Build relation from database
        $relation = $this->buildUserRelation($customer_id, $current_user_id);

        // Cache result
        $this->cache->set('customer_relation', $relation, 120, $cache_key);
        $this->relationCache[$customer_id] = $relation;

        return $relation;
    }

    /**
     * Build user relation from database
     *
     * @param int $customer_id Customer ID
     * @param int $user_id User ID
     * @return array Relation data
     */
    private function buildUserRelation(int $customer_id, int $user_id): array {
        global $wpdb;

        $is_admin = current_user_can('edit_all_customers');

        // Initialize relation
        $relation = [
            'is_admin' => $is_admin,
            'is_customer_admin' => false,
            'is_customer_branch_admin' => false,
            'is_customer_employee' => false,
            'access_type' => 'none'
        ];

        if (!$is_admin) {
            // Single query to check all relations
            $query = $wpdb->prepare("
                SELECT
                    CASE WHEN c.user_id IS NOT NULL THEN 1 ELSE 0 END as is_customer_admin,
                    CASE WHEN b.user_id IS NOT NULL THEN 1 ELSE 0 END as is_customer_branch_admin,
                    CASE WHEN ce.user_id IS NOT NULL AND c.user_id IS NULL AND b.user_id IS NULL THEN 1 ELSE 0 END as is_customer_employee,
                    c.id as owner_of_customer_id,
                    b.customer_id as branch_admin_of_customer_id,
                    ce.customer_id as employee_of_customer_id
                FROM (SELECT %d as uid, %d as cust_id) u
                LEFT JOIN {$wpdb->prefix}app_customers c ON c.user_id = u.uid AND (u.cust_id = 0 OR c.id = u.cust_id) AND c.status = 'active'
                LEFT JOIN {$wpdb->prefix}app_customer_branches b ON b.user_id = u.uid AND (u.cust_id = 0 OR b.customer_id = u.cust_id) AND b.status = 'active'
                LEFT JOIN {$wpdb->prefix}app_customer_employees ce ON ce.user_id = u.uid AND (u.cust_id = 0 OR ce.customer_id = u.cust_id) AND ce.status = 'active'
                LIMIT 1
            ", $user_id, $customer_id);

            $result = $wpdb->get_row($query, ARRAY_A);

            if ($result) {
                $relation['is_customer_admin'] = (bool) $result['is_customer_admin'];
                $relation['is_customer_branch_admin'] = (bool) $result['is_customer_branch_admin'];
                $relation['is_customer_employee'] = (bool) $result['is_customer_employee'];

                if ($result['owner_of_customer_id']) {
                    $relation['owner_of_customer_id'] = (int) $result['owner_of_customer_id'];
                }
                if ($result['branch_admin_of_customer_id']) {
                    $relation['customer_branch_admin_of_customer_id'] = (int) $result['branch_admin_of_customer_id'];
                }
                if ($result['employee_of_customer_id']) {
                    $relation['employee_of_customer_id'] = (int) $result['employee_of_customer_id'];
                }
            }
        }

        // Determine access type
        if ($is_admin) {
            $relation['access_type'] = 'admin';
        } elseif ($relation['is_customer_admin']) {
            $relation['access_type'] = 'customer_admin';
        } elseif ($relation['is_customer_branch_admin']) {
            $relation['access_type'] = 'customer_branch_admin';
        } elseif ($relation['is_customer_employee']) {
            $relation['access_type'] = 'customer_employee';
        } elseif (current_user_can('view_customer_detail')) {
            // Platform users (wp-app-core integration)
            $relation['access_type'] = 'platform';
        }

        // Apply filters for external plugin integration
        $relation = apply_filters('wp_customer_user_relation', $relation, $customer_id, $user_id);
        $relation['access_type'] = apply_filters('wp_customer_access_type', $relation['access_type'], $relation);

        return $relation;
    }

    /**
     * Check if user can view
     *
     * @param array $relation User relation
     * @return bool
     */
    public function canView(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_customer_admin'] && current_user_can('view_own_customer')) return true;
        if ($relation['is_customer_branch_admin'] && current_user_can('view_own_customer')) return true;
        if ($relation['is_customer_employee'] && current_user_can('view_own_customer')) return true;
        if (current_user_can('view_customer_detail')) return true;

        return false;
    }

    /**
     * Check if user can update
     *
     * @param array $relation User relation
     * @return bool
     */
    public function canUpdate(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_customer_admin'] && current_user_can('edit_own_customer')) return true;
        if (current_user_can('edit_all_customers')) return true;

        return false;
    }

    /**
     * Check if user can delete
     *
     * @param array $relation User relation
     * @return bool
     */
    public function canDelete(array $relation): bool {
        if ($relation['is_admin'] && current_user_can('delete_customer')) return true;
        if (current_user_can('delete_customer')) return true;

        return false;
    }

    /**
     * Check view permission (implements abstract method)
     *
     * @param array $relation User relation
     * @return bool
     */
    protected function checkViewPermission(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_customer_admin'] && current_user_can('view_own_customer')) return true;
        if ($relation['is_customer_branch_admin'] && current_user_can('view_own_customer')) return true;
        if ($relation['is_customer_employee'] && current_user_can('view_own_customer')) return true;
        if (current_user_can('view_customer_detail')) return true;

        return false;
    }

    /**
     * Check update permission (implements abstract method)
     *
     * @param array $relation User relation
     * @return bool
     */
    protected function checkUpdatePermission(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_customer_admin'] && current_user_can('edit_own_customer')) return true;
        if (current_user_can('edit_all_customers')) return true;

        return false;
    }

    /**
     * Check delete permission (implements abstract method)
     *
     * @param array $relation User relation
     * @return bool
     */
    protected function checkDeletePermission(array $relation): bool {
        if ($relation['is_admin'] && current_user_can('delete_customer')) return true;
        if (current_user_can('delete_customer')) return true;

        return false;
    }

    /**
     * Clear relation cache
     *
     * @param int|null $customer_id Customer ID (null for all)
     * @return void
     */
    public function clearCache(?int $customer_id = null): void {
        if ($customer_id) {
            unset($this->relationCache[$customer_id]);
        } else {
            $this->relationCache = [];
        }

        $this->cache->clearCache('customer_relation');
    }
}
