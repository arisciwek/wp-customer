<?php
/**
 * Customer Validator Class
 *
 * @package     WP_Customer
 * @subpackage  Validators
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: src/Validators/CustomerValidator.php
 *
 * Description: Validator untuk memvalidasi operasi CRUD Customer.
 *              Menangani validasi form dan permission check.
 *              Mendukung multiple user roles: admin, owner, employee.
 *              Terintegrasi dengan WP Capability System.
 *
 * Dependencies:
 * - WPCustomer\Models\CustomerModel untuk data checks
 * - WordPress Capability API
 *
 * Changelog:
 * 1.0.7 - 2025-01-21 (Task-2165 Form Sync)
 * - Added formatNpwp() public method (moved from CustomerRegistrationHandler)
 * - Added validateNpwpFormat() public method (refactored from private validation)
 * - Added formatNib() public method for NIB formatting
 * - Added validateNibFormat() public method for NIB format validation
 * - Centralized NPWP/NIB formatting and validation (Single Source of Truth)
 *
 * 1.0.6 - 2025-10-19 (TODO-2165 Refactor)
 * - Simplified permission checks using direct capability validation (Opsi 1)
 * - Removed hook filters (wp_customer_user_can_view_customer, etc.) - not needed
 * - Added direct current_user_can() checks for platform role integration
 * - More secure and maintainable approach using WordPress capability system
 * - Breaking change: External plugins should use capability system instead of hooks
 *
 * 1.0.5 - 2025-10-19 (TODO-2165)
 * - Refactored hook names for better clarity and WordPress convention compliance
 * - Changed wp_customer_can_view → wp_customer_user_can_view_customer
 * - Changed wp_customer_can_update → wp_customer_user_can_edit_customer
 * - Changed wp_customer_can_delete → wp_customer_user_can_delete_customer
 * - Fixed unreachable code bug in canDelete() method (filter was never called)
 * - Improved extensibility for plugin integrations (wp-app-core, etc.)
 *
 * 1.0.3 - 2024-01-20
 * - Separated form and permission validation
 * - Added role-based permission system
 * - Added relation caching for better performance
 * - Added support for multiple user types (admin, owner, employee)
 * - Improved error handling and messages
 *
 * 1.0.0 - 2024-12-02
 * - Initial release
 */

namespace WPCustomer\Validators;

use WPCustomer\Models\Customer\CustomerModel;

class CustomerValidator {
    private CustomerModel $model;
    private array $relationCache = [];

    private array $action_capabilities = [
        'create' => 'add_customer',
        'update' => ['edit_all_customers', 'edit_own_customer'],
        'view' => ['view_customer_detail', 'view_own_customer'],
        'delete' => 'delete_customer',
        'list' => 'view_customer_list'
    ];

    public function __construct() {
        $this->model = new CustomerModel();
    }

    /**
     * Validasi form input
     *
     * @param array $data Data yang akan divalidasi
     * @param int|null $id ID customer untuk update (optional)
     * @return array Array of errors, empty jika valid
     */
    public function validateForm(array $data, ?int $id = null): array {
        $errors = [];

        // Name validation
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            $errors['name'] = __('Nama customer wajib diisi.', 'wp-customer');
        } 
        elseif (mb_strlen($name) > 100) {
            $errors['name'] = __('Nama customer maksimal 100 karakter.', 'wp-customer');
        }
        elseif ($this->model->existsByName($name, $id)) {
            $errors['name'] = __('Nama customer sudah ada.', 'wp-customer');
        }

        // NPWP validation (optional)
        if (!empty($data['npwp'])) {
            $npwp = trim($data['npwp']);
            if (!$this->validateNpwpFormat($npwp)) {
                $errors['npwp'] = __('Format NPWP tidak valid. Format: XX.XXX.XXX.X-XXX.XXX', 'wp-customer');
            }
            if ($this->model->existsByNPWP($data['npwp'], $id)) {
                $errors['npwp'] = __('NPWP sudah terdaftar.', 'wp-customer');
            }
        }

        // NIB validation (optional)
        if (!empty($data['nib'])) {
            $nib = trim($data['nib']);
            if (!$this->validateNibFormat($nib)) {
                $errors['nib'] = __('Format NIB tidak valid. Harus 13 digit.', 'wp-customer');
            }
            if ($this->model->existsByNIB($data['nib'], $id)) {
                $errors['nib'] = __('NIB sudah terdaftar.', 'wp-customer');
            }
        }

        return $errors;
    }

    /**
     * Validasi permission untuk suatu action
     *
     * @param string $action Action yang akan divalidasi (create|update|view|delete|list)
     * @param int|null $id ID customer (optional)
     * @return array Array of errors, empty jika valid
     * @throws \Exception Jika action tidak valid
     */
    public function validatePermission(string $action, ?int $id = null): array {
        $errors = [];

        if (!$id) {
            // Untuk action yang tidak memerlukan ID (misal: create)
            return $this->validateBasicPermission($action);
        }

        // Dapatkan relasi user dengan customer
        $relation = $this->getUserRelation($id);
        
        // Validasi berdasarkan relasi dan action
        switch ($action) {
            case 'view':
                if (!$this->canView($relation)) {
                    $errors['permission'] = __('Anda tidak memiliki akses untuk melihat customer ini.', 'wp-customer');
                }
                break;

            case 'update':
                if (!$this->canUpdate($relation)) {
                    $errors['permission'] = __('Anda tidak memiliki akses untuk mengubah customer ini.', 'wp-customer');
                }
                break;

            case 'delete':
                if (!$this->canDelete($relation)) {
                    $errors['permission'] = __('Anda tidak memiliki akses untuk menghapus customer ini.', 'wp-customer');
                }
                break;

            default:
                throw new \Exception('Invalid action specified');
        }

        return $errors;
    }

    /**
     * Get user relation with customer (using model implementation with memory caching)
     *
     * @param int $customer_id Customer ID
     * @return array Array containing relation information (is_admin, is_customer_admin, is_customer_employee, access_type)
     */
    public function getUserRelation(int $customer_id): array {
        $current_user_id = get_current_user_id();

        // Check class memory cache first (for single request performance)
        if (isset($this->relationCache[$customer_id])) {
            return $this->relationCache[$customer_id];
        }
        
        // Get relation from model (with persistent cache and access_type already included)
        $relation = $this->model->getUserRelation($customer_id, $current_user_id);
        
        // Store in class memory cache for this request
        $this->relationCache[$customer_id] = $relation;
        
        return $relation;
    }

    /**
     * Validate access for given customer
     * 
     * @param int $customer_id Customer ID (0 for general access validation)
     * @return array Access information [has_access, access_type, relation, customer_id]
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

    /**
     * Get access type from relation
     * This method is kept for backward compatibility
     * 
     * @param array $relation User relation array
     * @return string Access type (admin, owner, employee, or custom from plugins)
     */
    private function getAccessType(array $relation): string {
        return $relation['access_type'] ?? 'none';
    }

    /**
     * Check if user can view customer
     *
     * Uses direct capability checks for simplicity and security.
     * Platform roles (from wp-app-core) are handled via WordPress capability system.
     */
    public function canView(array $relation): bool {
        // Standard role checks
        if ($relation['is_admin']) return true;
        if ($relation['is_customer_admin'] && current_user_can('view_own_customer')) return true;
        if ($relation['is_customer_branch_admin'] && current_user_can('view_own_customer')) return true;
        if ($relation['is_customer_employee'] && current_user_can('view_own_customer')) return true;

        // Platform role check (wp-app-core integration)
        // If user has view_customer_detail capability, grant access
        // This covers platform_finance, platform_admin, platform_analyst, platform_viewer
        if (current_user_can('view_customer_detail')) return true;

        return false;
    }

    /**
     * Check if user can update customer
     *
     * Uses direct capability checks for simplicity and security.
     * Platform roles (from wp-app-core) are handled via WordPress capability system.
     */
    public function canUpdate(array $relation): bool {
        // Standard role checks
        if ($relation['is_admin']) return true;
        if ($relation['is_customer_admin'] && current_user_can('edit_own_customer')) return true;

        // Platform role check (wp-app-core integration)
        // If user has edit_all_customers capability, grant access
        // This covers platform_admin, platform_super_admin
        if (current_user_can('edit_all_customers')) return true;

        return false;
    }

    /**
     * Check if user can delete customer
     *
     * Uses direct capability checks for simplicity and security.
     * Platform roles (from wp-app-core) are handled via WordPress capability system.
     */
    public function canDelete(array $relation): bool {
        // Check admin permission first
        if ($relation['is_admin'] && current_user_can('delete_customer')) {
            return true;
        }

        // Platform role check (wp-app-core integration)
        // If user has delete_customer capability, grant access
        // This covers platform_super_admin only (most restrictive)
        if (current_user_can('delete_customer')) {
            return true;
        }

        return false;
    }

    /**
     * Validate basic permissions that don't require customer ID
     */
    private function validateBasicPermission(string $action): array {
        $errors = [];
        $required_cap = $this->action_capabilities[$action] ?? null;

        if (!$required_cap) {
            throw new \Exception('Invalid action specified');
        }

        if (!current_user_can($required_cap)) {
            $errors['permission'] = __('Anda tidak memiliki izin untuk operasi ini.', 'wp-customer');
        }

        return $errors;
    }

    /**
     * Clear relation cache
     *
     * @param int|null $customer_id If provided, only clear cache for specific customer
     */
    public function clearCache(?int $customer_id = null): void {
        if ($customer_id) {
            unset($this->relationCache[$customer_id]);
        } else {
            $this->relationCache = [];
        }
    }

    public function validateDelete(int $id): array {
        $errors = [];

        // 1. Validasi permission dasar
        if (!current_user_can('delete_customer')) {
            $errors[] = __('Anda tidak memiliki izin untuk menghapus customer', 'wp-customer');
            return $errors;
        }

        // 2. Cek apakah customer ada
        $customer = $this->model->find($id);
        if (!$customer) {
            $errors[] = __('Customer tidak ditemukan', 'wp-customer');
            return $errors;
        }

        // 3. Cek relasi dengan User
        if (!$this->canDelete($this->getUserRelation($id))) {
            $errors[] = __('Anda tidak memiliki izin untuk menghapus customer ini', 'wp-customer');
            return $errors;
        }

        // 4. Cek apakah customer memiliki branch
        $branch_count = $this->model->getBranchCount($id);
        if ($branch_count > 0) {
            $errors[] = sprintf(
                __('Customer tidak dapat dihapus karena masih memiliki %d cabang', 'wp-customer'),
                $branch_count
            );
        }

        // 5. Cek apakah customer memiliki employee
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
     * Format NPWP to standard format: XX.XXX.XXX.X-XXX.XXX
     *
     * @param string $npwp Raw NPWP (15 digits)
     * @return string Formatted NPWP
     */
    public function formatNpwp(string $npwp): string {
        // Remove non-digits
        $numbers = preg_replace('/\D/', '', $npwp);

        // Format to XX.XXX.XXX.X-XXX.XXX
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
     * @return bool True if valid format
     */
    public function validateNpwpFormat(string $npwp): bool {
        // Check if NPWP matches the format: XX.XXX.XXX.X-XXX.XXX
        return (bool) preg_match('/^\d{2}\.\d{3}\.\d{3}\.\d{1}\-\d{3}\.\d{3}$/', $npwp);
    }

    /**
     * Format NIB to clean 13 digits
     *
     * @param string $nib Raw NIB
     * @return string Formatted NIB (13 digits only)
     */
    public function formatNib(string $nib): string {
        // Remove non-digits
        $numbers = preg_replace('/\D/', '', $nib);

        // Return first 13 digits
        return substr($numbers, 0, 13);
    }

    /**
     * Validate NIB format
     *
     * @param string $nib NIB to validate
     * @return bool True if valid format (13 digits)
     */
    public function validateNibFormat(string $nib): bool {
        // Check if NIB is exactly 13 digits
        return (bool) preg_match('/^\d{13}$/', $nib);
    }

}
