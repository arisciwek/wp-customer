<?php
/**
* Branch Validator Class
*
* @package     WP_Customer
* @subpackage  Validators/Branch
* @version     1.0.0
* @author      arisciwek
*
* Path: src/Validators/Branch/BranchValidator.php
*
* Description: Validator untuk operasi CRUD Cabang.
*              Memastikan semua input data valid sebelum diproses model.
*              Menyediakan validasi untuk create, update, dan delete.
*              Includes validasi permission dan ownership.
*
* Changelog:
* 1.0.0 - 2024-12-10
* - Initial release
* - Added create validation
* - Added update validation
* - Added delete validation
* - Added permission validation
* 
* 
*/

/**
 * Branch Permission Logic
 * 
 * Permission hierarchy for branch management follows these rules:
 * 
 * 1. Customer Owner Rights:
 *    - Owner (user_id in customers table) has full control of ALL entities under their customer
 *    - No need for *_all_* capabilities
 *    - Can edit/delete any branch within their customer scope
 *    - This is ownership-based permission, not capability-based
 * 
 * 2. Regular User Rights:
 *    - Users with edit_own_branch can only edit branches they created
 *    - Created_by field determines ownership for regular users
 * 
 * 3. Staff Rights:
 *    - Staff members (in customer_employees table) can view but not edit
 *    - View rights are automatic for customer scope
 * 
 * 4. Administrator Rights:
 *    - Only administrators use edit_all_branches capability
 *    - This is for system-wide access, not customer-scope access
 *    
 * Example:
 * - If user is customer owner: Can edit all branches under their customer
 * - If user has edit_own_branch: Can only edit branches where created_by matches
 * - If user has edit_branch: System administrator with full access
 */

namespace WPCustomer\Validators\Branch;

use WPCustomer\Models\Branch\BranchModel;
use WPCustomer\Models\Customer\CustomerModel;

class BranchValidator {
    private $branch_model;
    private $customer_model;
    private $relationCache = [];

    public function __construct() {
        $this->branch_model = new BranchModel();
        $this->customer_model = new CustomerModel();
    }

    public function getUserRelation(int $branch_id): array {
        $current_user_id = get_current_user_id();

        // Check class memory cache first (for single request performance)
        if (isset($this->relationCache[$branch_id])) {
            return $this->relationCache[$branch_id];
        }
        
        // Handle special case for branch_id 0 (general validation)
        if ($branch_id === 0) {
            $relation = [
                'is_admin' => current_user_can('edit_all_branches'),
                'is_customer_owner' => false,
                'is_branch_admin' => false, 
                'is_customer_employee' => false,
                'access_type' => current_user_can('edit_all_branches') ? 'admin' : 'none'
            ];
            
            // Cache the result
            $this->relationCache[$branch_id] = $relation;
            return $relation;
        }
        
        // Get relation from model
        $relation = $this->branch_model->getUserRelation($branch_id, $current_user_id);
        
        // Store in class memory cache for this request
        $this->relationCache[$branch_id] = $relation;
        
        return $relation;
    }

    /**
     * Validate access for given branch
     * 
     * @param int $branch_id Branch ID (0 for general access validation)
     * @return array Access information [has_access, access_type, relation, branch_id]
     */
    public function validateAccess(int $branch_id): array {
        // Tangani kasus khusus untuk branch_id = 0 (validasi umum)
        if ($branch_id === 0) {
            // Untuk validasi umum, kita tidak perlu data spesifik branch
            $relation = [
                'is_admin' => current_user_can('edit_all_branches'),
                'is_customer_owner' => false,
                'is_branch_admin' => false,
                'is_customer_employee' => false,
                'access_type' => current_user_can('edit_all_branches') ? 'admin' : 'none'
            ];
            
            return [
                'has_access' => current_user_can('view_branch_list'),
                'access_type' => $relation['is_admin'] ? 'admin' : 'none',
                'relation' => $relation,
                'branch_id' => 0,
                'customer_id' => 0
            ];
        }
        
        // Dapatkan relasi user dengan branch ini
        $relation = $this->getUserRelation($branch_id);
        
        // Get branch data untuk mendapatkan customer_id
        $branch = $this->branch_model->find($branch_id);
        $customer_id = $branch ? $branch->customer_id : null;
        
        // Jika branch tidak ditemukan, kembalikan akses ditolak
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
        
        // Dapatkan customer jika customer_id valid
        $customer = null;
        if ($customer_id) {
            $customer = $this->customer_model->find($customer_id);
        }
        
        return [
            'has_access' => $this->canViewBranch($branch, $customer),
            'access_type' => $relation['access_type'] ?? 'none',
            'relation' => $relation,
            'branch_id' => $branch_id,
            'customer_id' => $customer_id
        ];

    }
    
    /**
     * Get access type from relation
     * 
     * @param array $relation User relation array
     * @return string Access type (admin, customer_owner, branch_admin, staff, or none)
     */
    private function getAccessType(array $relation): string {
        return $relation['access_type'] ?? 'none';
    }

    public function canViewBranch($branch, $customer): bool {
        // Dapatkan relasi user dengan branch ini
        $relation = $this->getUserRelation($branch->id);
        
        if ($relation['is_admin']) return true;
        if ($relation['is_customer_owner']) return true;
        if ($relation['is_branch_admin']) return true;
        if ($relation['is_customer_employee'] && current_user_can('view_own_branch')) return true;
    }

    public function canCreateBranch(int $customer_id): bool {
        $current_user_id = get_current_user_id();
        
        // Dapatkan relasi user dengan customer
        $customer_relation = $this->customer_model->getUserRelation($customer_id);
        
        if ($customer_relation['is_admin']) return true;
        if ($customer_relation['is_customer_owner']) return true;
        if (current_user_can('add_branch')) return true;
        
        return apply_filters('wp_customer_can_create_branch', false, $customer_id, $current_user_id);
    }

    public function canUpdateBranch($branch, $customer): bool {
        // Dapatkan relasi user dengan branch ini
        $relation = $this->getUserRelation($branch->id);
        
        if ($relation['is_admin']) return true;
        if ($relation['is_customer_owner']) return true;
        if ($relation['is_branch_admin'] && current_user_can('edit_own_branch')) return true;

    }

    public function canDeleteBranch($branch, $customer): bool {
        // Dapatkan relasi user dengan branch
        $relation = $this->getUserRelation($branch->id);
        
        if ($relation['is_admin'] && current_user_can('delete_branch')) return true;
        if ($relation['is_customer_owner']) return true;
        
        return apply_filters('wp_customer_can_delete_branch', false, $relation);
    }

    /**
     * Validate branch data for creation
     * Ensures agency_id and division_id are properly assigned based on province and regency
     *
     * @param array $data Branch data to validate
     * @return array Array of validation errors
     */
    public function validateCreate(array $data): array {
        $errors = [];

        // First run general form validation
        $form_errors = $this->validateForm($data);
        if (!empty($form_errors)) {
            $errors = array_merge($errors, $form_errors);
        }

        // Validate that agency_id and division_id are not null/empty for create
        // This ensures the automatic assignment from province/regency worked
        if (empty($data['agency_id'])) {
            $errors['agency_id'] = __('Agency ID wajib diisi. Pastikan provinsi yang dipilih memiliki agency.', 'wp-customer');
        }

        if (empty($data['division_id'])) {
            $errors['division_id'] = __('Division ID wajib diisi. Pastikan regency yang dipilih memiliki division dalam agency.', 'wp-customer');
        }

        return $errors;
    }

    public function validateForm(array $data, ?int $id = null): array {
        $errors = [];

        // Validasi name
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            $errors['name'] = __('Nama cabang wajib diisi.', 'wp-customer');
        }
        elseif (mb_strlen($name) > 100) {
            $errors['name'] = __('Nama cabang maksimal 100 karakter.', 'wp-customer');
        }
        elseif (isset($data['customer_id']) && $this->branch_model->existsByNameInCustomer($name, $data['customer_id'], $id)) {
            $errors['name'] = __('Nama cabang sudah ada dalam customer ini.', 'wp-customer');
        }

        // Validasi customer_id
        if (empty($data['customer_id'])) {
            $errors['customer_id'] = __('Customer wajib dipilih.', 'wp-customer');
        }
        else {
            $customer = $this->customer_model->find($data['customer_id']);
            if (!$customer) {
                $errors['customer_id'] = __('Customer tidak ditemukan.', 'wp-customer');
            }
        }

        // Validasi type
        if (empty($data['type'])) {
            $errors['type'] = __('Tipe cabang wajib dipilih.', 'wp-customer');
        }
        elseif (!in_array($data['type'], ['pusat', 'cabang'])) {
            $errors['type'] = __('Tipe cabang tidak valid.', 'wp-customer');
        }
        elseif ($data['type'] === 'pusat' && !$id) {
            // Untuk cabang baru dengan tipe pusat, periksa apakah sudah ada pusat
            $existing_pusat = $this->branch_model->findPusatByCustomer($data['customer_id']);
            if ($existing_pusat) {
                $errors['type'] = __('Customer ini sudah memiliki kantor pusat.', 'wp-customer');
            }
        }

        // Validasi lainnya sesuai kebutuhan

        return $errors;
    }

    public function validateView($branch, $customer): array {
        $errors = [];
        
        // Hanya validasi bahwa data yang dibutuhkan ada
        if (!$branch || !$customer) {
            $errors['data'] = __('Data tidak valid.', 'wp-customer');
        }

        return $errors;
    }

    public function validateUpdate(array $data, int $id): array {
        $errors = [];

        // Check if branch exists
        $branch = $this->branch_model->find($id);
        if (!$branch) {
            $errors['id'] = __('Cabang tidak ditemukan.', 'wp-customer');
            return $errors;
        }

        // Validasi type change jika ada
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

    public function validateDelete(int $id): array {
        $errors = [];

        // Validasi permission
        $permission_errors = $this->validatePermission('delete', $id);
        if (!empty($permission_errors)) {
            return $permission_errors;
        }

        // Validasi branch type
        $type_validation = $this->validateBranchTypeDelete($id);
        if (!$type_validation['valid']) {
            $errors['type'] = $type_validation['message'];
        }

        // Validasi dependensi lain (misalnya employee)
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
    
    public function validatePermission(string $action, ?int $id = null): array {
        $errors = [];

        if (!$id && $action === 'create') {
            if (isset($_POST['customer_id'])) {
                $customer_id = (int)$_POST['customer_id'];
                if (!$this->canCreateBranch($customer_id)) {
                    $errors['permission'] = __('Anda tidak memiliki izin untuk menambah cabang.', 'wp-customer');
                }
            } else {
                $errors['customer_id'] = __('Customer ID wajib dipilih.', 'wp-customer');
            }
            return $errors;
        }

        // Untuk action selain create yang memerlukan ID
        $branch = $this->branch_model->find($id);
        if (!$branch) {
            $errors['id'] = __('Cabang tidak ditemukan.', 'wp-customer');
            return $errors;
        }
        
        $customer = $this->customer_model->find($branch->customer_id);
        if (!$customer) {
            $errors['id'] = __('Customer tidak ditemukan.', 'wp-customer');
            return $errors;
        }
        
        // Validasi berdasarkan action
        switch ($action) {
            case 'view':
                if (!$this->canViewBranch($branch, $customer)) {
                    $errors['permission'] = __('Anda tidak memiliki akses untuk melihat cabang ini.', 'wp-customer');
                }
                break;

            case 'update':
                if (!$this->canUpdateBranch($branch, $customer)) {
                    $errors['permission'] = __('Anda tidak memiliki akses untuk mengubah cabang ini.', 'wp-customer');
                }
                break;

            case 'delete':
                if (!$this->canDeleteBranch($branch, $customer)) {
                    $errors['permission'] = __('Anda tidak memiliki akses untuk menghapus cabang ini.', 'wp-customer');
                }
                // Validasi tambahan untuk delete
                $type_validation = $this->validateBranchTypeDelete($id);
                if (!$type_validation['valid']) {
                    $errors['type'] = $type_validation['message'];
                }
                break;
                
            default:
                $errors['action'] = __('Aksi tidak valid.', 'wp-customer');
        }

        return $errors;
    }

    private function isStaffMember($user_id, $branch_id) {
        global $wpdb;
        return (bool)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees 
             WHERE user_id = %d AND branch_id = %d AND status = 'active'",
            $user_id, $branch_id
        ));
    }

    /**
     * Helper function to sanitize input data
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

}
