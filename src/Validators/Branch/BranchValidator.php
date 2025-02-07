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
 * - If user has edit_all_branches: System administrator with full access
 */

namespace WPCustomer\Validators\Branch;

use WPCustomer\Models\Branch\BranchModel;
use WPCustomer\Models\Customer\CustomerModel;

class BranchValidator {
    private $branch_model;
    private $customer_model;

    public function __construct() {
        $this->branch_model = new BranchModel();
        $this->customer_model = new CustomerModel();
    }

    public function canViewBranch($branch, $customer): bool {
        $current_user_id = get_current_user_id();

        // 1. Customer Owner Check - highest priority
        if ((int)$customer->user_id === (int)$current_user_id) {
            return true;
        }

        // 2. Branch Admin Check
        if ((int)$branch->user_id === (int)$current_user_id) {
            return true;
        }

        // 3. Staff Check (dari CustomerEmployees)
        if ($this->isStaffMember($current_user_id, $branch->id)) {
            return true;
        }

        // 4. System Admin Check
        if (current_user_can('view_branch_detail')) {
            return true;
        }

        return false;
    }

    public function canEditBranch($branch, $customer) {
        $current_user_id = get_current_user_id();

        // 1. Customer Owner Check - highest priority
        $is_customer_owner = ((int)$customer->user_id === (int)$current_user_id);
        if ($is_customer_owner) {
            return true;
        }

        // 2. Branch Admin Check
        if ((int)$branch->user_id === (int)$current_user_id && current_user_can('edit_own_branch')) {
            return true;
        }

        // 3. System Admin Check  
        if (current_user_can('edit_all_branches')) {
            return true;
        }

        return false;
    }

    public function canDeleteBranch($branch, $customer): bool {
        $current_user_id = get_current_user_id();

        // 1. Customer Owner Check - highest priority
        if ((int)$customer->user_id === (int)$current_user_id) {
            return true;
        }

        // 2. Branch Admin Check
        // Harus admin branch DAN punya capability delete_branch
        if ((int)$branch->user_id === (int)$current_user_id && 
            current_user_can('edit_own_branch')) {
            return true;
        }

        // 3. Staff TIDAK bisa delete
        // 4. System Admin dengan delete_branch bisa delete semua
        if (current_user_can('delete_branch')) {
            return true;
        }

        return false;
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

        // Check if branch exists
        $branch = $this->branch_model->find($id);
        if (!$branch) {
            $errors['id'] = __('Cabang tidak ditemukan.', 'wp-customer');
            return $errors;
        }

        // Get customer for permission check
        $customer = $this->customer_model->find($branch->customer_id);
        if (!$customer) {
            $errors['id'] = __('Customer tidak ditemukan.', 'wp-customer');
            return $errors;
        }

        // Branch type deletion validation
        $type_validation = $this->validateBranchTypeDelete($id);
        if (!$type_validation['valid']) {
            $errors['type'] = $type_validation['message'];
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
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_branches WHERE customer_id = %d",
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
            "SELECT type FROM {$wpdb->prefix}app_branches 
             WHERE id = %d AND customer_id = %d",
            $branch_id, $customer_id
        ));

        // If current type is not 'pusat', no validation needed
        if (!$current_branch || $current_branch->type !== 'pusat') {
            return ['valid' => true];
        }

        // Count remaining 'pusat' branches excluding current branch
        $pusat_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_branches 
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
            "SELECT type, customer_id FROM {$wpdb->prefix}app_branches WHERE id = %d",
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
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_branches 
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
