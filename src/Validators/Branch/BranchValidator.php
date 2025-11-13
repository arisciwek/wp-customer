<?php
/**
 * Branch Validator
 *
 * @package     WP_Customer
 * @subpackage  Validators/Branch
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Validators/Branch/BranchValidator.php
 *
 * Description: Validator untuk Branch CRUD operations.
 *              Extends AbstractValidator dari wp-app-core.
 *              Handles form validation dan permission checks.
 *
 * Changelog:
 * 2.0.0 - 2025-11-09 (Task-2193: CRUD Refactoring)
 * - BREAKING: Refactored to extend AbstractValidator
 * - Code reduction: 594 lines → ~450 lines (24% reduction)
 * - Implements 13 abstract methods
 * - getUserRelation() now from BranchModel (not CustomerValidator)
 * - Custom validation: Phone format, branch name uniqueness, type validation
 * - Custom validation: Pusat branch duplicate check, location-based inspector
 * - Permission logic: Owner-based + capability-based hierarchy
 */

namespace WPCustomer\Validators\Branch;

use WPAppCore\Validators\Abstract\AbstractValidator;
use WPCustomer\Models\Branch\BranchModel;
use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Cache\BranchCacheManager;

defined('ABSPATH') || exit;

class BranchValidator extends AbstractValidator {

    /**
     * @var BranchModel
     */
    private $model;

    /**
     * @var CustomerModel
     */
    private $customer_model;

    /**
     * @var BranchCacheManager
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
        $this->model = new BranchModel();
        $this->customer_model = new CustomerModel();
        $this->cache = BranchCacheManager::getInstance();
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
        return 'branch';
    }

    /**
     * Get entity display name
     *
     * @return string
     */
    protected function getEntityDisplayName(): string {
        return 'Branch';
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
     * @return BranchModel
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
        return 'add_customer_branch';
    }

    /**
     * Get view capabilities
     *
     * @return array
     */
    protected function getViewCapabilities(): array {
        return ['view_customer_branch_list', 'view_own_customer_branch'];
    }

    /**
     * Get update capabilities
     *
     * @return array
     */
    protected function getUpdateCapabilities(): array {
        return ['edit_customer_branch', 'edit_own_customer_branch'];
    }

    /**
     * Get delete capability
     *
     * @return string
     */
    protected function getDeleteCapability(): string {
        return 'delete_customer_branch';
    }

    /**
     * Get list capability
     *
     * @return string
     */
    protected function getListCapability(): string {
        return 'view_customer_branch_list';
    }

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
            $errors['name'] = __('Nama cabang wajib diisi.', 'wp-customer');
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = __('Nama cabang maksimal 100 karakter.', 'wp-customer');
        } elseif (isset($data['customer_id']) && $this->model->existsByNameInCustomer($name, $data['customer_id'], $id)) {
            $errors['name'] = __('Nama cabang sudah ada dalam customer ini.', 'wp-customer');
        }

        // Customer ID validation
        if (empty($data['customer_id'])) {
            $errors['customer_id'] = __('Customer wajib dipilih.', 'wp-customer');
        } else {
            // Direct query to avoid cache contract issue
            global $wpdb;
            $customer = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}app_customers WHERE id = %d",
                $data['customer_id']
            ));
            if (!$customer) {
                $errors['customer_id'] = __('Customer tidak ditemukan.', 'wp-customer');
            }
        }

        // Type validation (simplified - type hardcoded as 'cabang' in controller)
        if (empty($data['type'])) {
            $errors['type'] = __('Tipe cabang wajib diisi.', 'wp-customer');
        } elseif (!in_array($data['type'], ['pusat', 'cabang'])) {
            $errors['type'] = __('Tipe cabang tidak valid.', 'wp-customer');
        }

        // Province validation (required for AutoEntityCreator)
        if (empty($data['province_id'])) {
            $errors['province_id'] = __('Provinsi wajib dipilih.', 'wp-customer');
        }

        // Regency validation (required for AutoEntityCreator)
        if (empty($data['regency_id'])) {
            $errors['regency_id'] = __('Kabupaten/Kota wajib dipilih.', 'wp-customer');
        }

        // Phone validation (optional, but must be 08xxxxxxxxxx format if provided)
        if (!empty($data['phone'])) {
            $phone = trim($data['phone']);
            // Format: 08 followed by 8-13 digits (total 10-15 digits)
            if (!preg_match('/^08[0-9]{8,13}$/', $phone)) {
                $errors['phone'] = __('Format telepon tidak valid. Gunakan format 08xxxxxxxxxx (08 diikuti 8-13 digit angka).', 'wp-customer');
            }
        }

        return $errors;
    }

    /**
     * Check view permission (implements abstract method)
     *
     * @param array $relation User relation
     * @return bool
     */
    protected function checkViewPermission(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_customer_admin']) return true;
        if ($relation['is_customer_branch_admin']) return true;
        if ($relation['is_customer_employee'] && current_user_can('view_own_customer_branch')) return true;

        // Platform role check (wp-app-core integration)
        if (current_user_can('view_customer_branch_list')) return true;

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
        if ($relation['is_customer_admin']) return true;
        if ($relation['is_customer_branch_admin'] && current_user_can('edit_own_customer_branch')) return true;

        // Platform role check (wp-app-core integration)
        if (current_user_can('edit_customer_branch')) return true;

        return false;
    }

    /**
     * Check delete permission (implements abstract method)
     *
     * @param array $relation User relation
     * @return bool
     */
    protected function checkDeletePermission(array $relation): bool {
        if ($relation['is_admin'] && current_user_can('delete_customer_branch')) return true;
        if ($relation['is_customer_admin']) return true;

        // Platform role check (wp-app-core integration)
        if (current_user_can('delete_customer_branch')) return true;

        return apply_filters('wp_customer_can_delete_customer_branch', false, $relation);
    }

    // ========================================
    // CUSTOM VALIDATION METHODS
    // ========================================

    /**
     * Validate create operation
     *
     * NOTE: agency_id and division_id NOT validated during create
     * Agency/Division assigned later when assigning inspector
     *
     * @param array $data Data to validate
     * @return array Errors (empty if valid)
     */
    public function validateCreate(array $data): array {
        $errors = [];

        // Run general form validation
        $form_errors = $this->validateFormFields($data);
        if (!empty($form_errors)) {
            $errors = array_merge($errors, $form_errors);
        }

        // NOTE: agency_id and division_id validation REMOVED
        // Agency/Division not part of branch creation
        // Will be assigned later via assign inspector functionality

        return $errors;
    }

    /**
     * Validate update operation
     *
     * @param int $id Entity ID
     * @param array $data Data to validate
     * @return array Errors (empty if valid)
     */
    public function validateUpdate(int $id, array $data): array {
        $errors = [];

        // Check if branch exists
        $branch = $this->model->find($id);
        if (!$branch) {
            $errors['id'] = __('Cabang tidak ditemukan.', 'wp-customer');
            return $errors;
        }

        // Validate type change if present
        if ($data['type'] ?? false) {
            $type_validation = $this->validateBranchTypeChange(
                $id,
                $data['type'],
                $branch->customer_id
            );

            if (!$type_validation['valid']) {
                $errors['type'] = $type_validation['message'];
            }
        }

        return $errors;
    }

    /**
     * Validate view operation
     *
     * @param object $branch Branch object
     * @param object $customer Customer object
     * @return array Errors (empty if valid)
     */
    public function validateView($branch, $customer): array {
        $errors = [];

        // Only validate that required data exists
        if (!$branch || !$customer) {
            $errors['data'] = __('Data tidak valid.', 'wp-customer');
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

        // Validate permission
        $permission_errors = $this->validatePermission('delete', $id);
        if (!empty($permission_errors)) {
            return $permission_errors;
        }

        // Validate branch type
        $type_validation = $this->validateBranchTypeDelete($id);
        if (!$type_validation['valid']) {
            $errors['type'] = $type_validation['message'];
        }

        // Validate dependencies (employees)
        global $wpdb;
        $employee_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees WHERE branch_id = %d",
            $id
        ));

        if ($employee_count > 0) {
            $errors['dependency'] = sprintf(
                __('Cabang tidak dapat dihapus karena masih memiliki %d karyawan.', 'wp-customer'),
                $employee_count
            );
        }

        return $errors;
    }

    /**
     * Validate access for given branch
     *
     * @param int $branch_id Branch ID (0 for general access validation)
     * @return array Access information [has_access, access_type, relation, branch_id]
     */
    public function validateAccess(int $branch_id): array {
        // Handle special case for branch_id = 0 (general validation)
        if ($branch_id === 0) {
            // Get relation from model which includes platform access_type
            $relation = $this->model->getUserRelation(0, get_current_user_id());
            $access_type = $relation['access_type'] ?? 'none';

            return [
                'has_access' => current_user_can('view_customer_branch_list'),
                'access_type' => $access_type,
                'relation' => $relation,
                'branch_id' => 0,
                'customer_id' => 0
            ];
        }

        // Get user relation with this branch
        $relation = $this->getUserRelation($branch_id);

        // Get branch data to retrieve customer_id
        $branch = $this->model->find($branch_id);
        $customer_id = $branch ? $branch->customer_id : null;

        // If branch not found, return access denied
        if (!$branch) {
            return [
                'has_access' => false,
                'access_type' => 'none',
                'relation' => $relation,
                'branch_id' => $branch_id,
                'customer_id' => null,
                'error' => 'Branch not found'
            ];
        }

        // Get customer if customer_id is valid
        $customer = null;
        if ($customer_id) {
            $customer = $this->customer_model->find($customer_id);
        }

        $has_access = $this->canViewBranch($branch, $customer);
        $access_type = $relation['access_type'] ?? 'none';

        return [
            'has_access' => $has_access,
            'access_type' => $access_type,
            'relation' => $relation,
            'branch_id' => $branch_id,
            'customer_id' => $customer_id
        ];
    }

    // ========================================
    // PERMISSION CHECKING METHODS
    // ========================================

    /**
     * Get user relation with branch
     *
     * Overrides parent method to use BranchModel's getUserRelation
     *
     * @param int $branch_id Branch ID
     * @return array Relation data
     */
    public function getUserRelation(int $branch_id): array {
        $current_user_id = get_current_user_id();

        // Check memory cache first
        if (isset($this->relationCache[$branch_id])) {
            $cached = $this->relationCache[$branch_id];
            $cached['from_cache'] = true;
            return $cached;
        }

        // Get relation from model (handles both branch_id 0 and specific IDs)
        $relation = $this->model->getUserRelation($branch_id, $current_user_id);

        // Store in memory cache for this request
        $this->relationCache[$branch_id] = $relation;

        return $relation;
    }

    /**
     * Check if user can view branch
     *
     * @param object $branch Branch object
     * @param object $customer Customer object
     * @return bool
     */
    public function canViewBranch($branch, $customer): bool {
        // Get user relation with this branch
        $relation = $this->getUserRelation($branch->id);

        if ($relation['is_admin']) return true;
        if ($relation['is_customer_admin']) return true;
        if ($relation['is_customer_branch_admin']) return true;
        if ($relation['is_customer_employee'] && current_user_can('view_own_customer_branch')) return true;

        // Platform role check (wp-app-core integration)
        if (current_user_can('view_customer_branch_list')) return true;

        return false;
    }

    /**
     * Check if user can create branch
     *
     * @param int $customer_id Customer ID
     * @return bool
     */
    public function canCreateBranch(int $customer_id): bool {
        $current_user_id = get_current_user_id();

        // Get relation with customer (from CustomerModel via BranchModel)
        $customer_relation = $this->model->getUserRelation($customer_id, $current_user_id);

        if ($customer_relation['is_admin']) return true;
        if ($customer_relation['is_customer_admin']) return true;
        if (current_user_can('add_customer_branch')) return true;

        return apply_filters('wp_customer_can_create_branch', false, $customer_id, $current_user_id);
    }

    /**
     * Check if user can update branch
     *
     * @param object $branch Branch object
     * @param object $customer Customer object
     * @return bool
     */
    public function canUpdateBranch($branch, $customer): bool {
        // Get user relation with this branch
        $relation = $this->getUserRelation($branch->id);

        if ($relation['is_admin']) return true;
        if ($relation['is_customer_admin']) return true;
        if ($relation['is_customer_branch_admin'] && current_user_can('edit_own_customer_branch')) return true;

        // Platform role check (wp-app-core integration)
        if (current_user_can('edit_customer_branch')) return true;

        return false;
    }

    /**
     * Check if user can delete branch
     *
     * @param object $branch Branch object
     * @param object $customer Customer object
     * @return bool
     */
    public function canDeleteBranch($branch, $customer): bool {
        // Get user relation with branch
        $relation = $this->getUserRelation($branch->id);

        if ($relation['is_admin'] && current_user_can('delete_customer_branch')) return true;
        if ($relation['is_customer_admin']) return true;

        // Platform role check (wp-app-core integration)
        if (current_user_can('delete_customer_branch')) return true;

        return apply_filters('wp_customer_can_delete_customer_branch', false, $relation);
    }

    // ========================================
    // BRANCH TYPE VALIDATION
    // ========================================

    /**
     * Validate branch type during creation
     *
     * @param string $type Branch type
     * @param int $customer_id Customer ID
     * @return array Validation result
     */
    public function validateBranchTypeCreate(string $type, int $customer_id): array {
        global $wpdb;

        $branch_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_branches WHERE customer_id = %d",
            $customer_id
        ));

        if ($branch_count === '0' && $type !== 'pusat') {
            return [
                'valid' => false,
                'message' => 'Cabang pertama harus bertipe kantor pusat'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate branch type change
     *
     * @param int $branch_id Branch ID
     * @param string $new_type New branch type
     * @param int $customer_id Customer ID
     * @return array Validation result
     */
    public function validateBranchTypeChange(int $branch_id, string $new_type, int $customer_id): array {
        global $wpdb;

        // If not changing to 'cabang', no validation needed
        if ($new_type !== 'cabang') {
            return ['valid' => true];
        }

        // Get current branch type
        $current_branch = $wpdb->get_row($wpdb->prepare(
            "SELECT type FROM {$wpdb->prefix}app_customer_branches
             WHERE id = %d AND customer_id = %d",
            $branch_id, $customer_id
        ));

        // If current type is not 'pusat', no validation needed
        if (!$current_branch || $current_branch->type !== 'pusat') {
            return ['valid' => true];
        }

        // Count remaining 'pusat' branches excluding current branch
        $pusat_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_branches
             WHERE customer_id = %d AND type = 'pusat' AND id != %d",
            $customer_id, $branch_id
        ));

        if ($pusat_count === '0') {
            return [
                'valid' => false,
                'message' => 'Minimal harus ada 1 kantor pusat. Tidak bisa mengubah tipe kantor pusat terakhir.'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate branch type before deletion
     *
     * @param int $branch_id Branch ID
     * @return array Validation result
     */
    public function validateBranchTypeDelete(int $branch_id): array {
        global $wpdb;

        // Get branch details including customer_id and type
        $branch = $wpdb->get_row($wpdb->prepare(
            "SELECT type, customer_id FROM {$wpdb->prefix}app_customer_branches WHERE id = %d",
            $branch_id
        ));

        if (!$branch) {
            return ['valid' => false, 'message' => 'Branch tidak ditemukan'];
        }

        // If not pusat, no validation needed
        if ($branch->type !== 'pusat') {
            return ['valid' => true];
        }

        // Count active non-pusat branches
        $active_branches = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_branches
             WHERE customer_id = %d
             AND type = 'cabang'
             AND status = 'active'
             AND id != %d",
            $branch->customer_id,
            $branch_id
        ));

        if ($active_branches > 0) {
            return [
                'valid' => false,
                'message' => 'Tidak dapat menghapus kantor pusat karena masih ada cabang aktif'
            ];
        }

        return ['valid' => true];
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Sanitize input data
     *
     * @param array $data Input data
     * @return array Sanitized data
     */
    public function sanitizeInput(array $data): array {
        $sanitized = [];

        if (isset($data['name'])) {
            $sanitized['name'] = trim(sanitize_text_field($data['name']));
        }

        if (isset($data['type'])) {
            $sanitized['type'] = trim(sanitize_text_field($data['type']));
        }

        if (isset($data['customer_id'])) {
            $sanitized['customer_id'] = intval($data['customer_id']);
        }

        return $sanitized;
    }

    /**
     * Clear relation cache
     *
     * @param int|null $branch_id Branch ID (null for all)
     * @return void
     */
    public function clearCache(?int $branch_id = null): void {
        if ($branch_id) {
            unset($this->relationCache[$branch_id]);
        } else {
            $this->relationCache = [];
        }

        $this->cache->clearCache('branch_relation');
    }
}
