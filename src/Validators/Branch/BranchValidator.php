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

    public function validateCreate(array $data): array {
        $errors = [];

        // Permission check
        if (!current_user_can('add_branch')) {
            $errors['permission'] = __('Anda tidak memiliki izin untuk menambah cabang.', 'wp-customer');
            return $errors;
        }

        // Customer exists check
        $customer_id = intval($data['customer_id'] ?? 0);
        if (!$customer_id || !$this->customer_model->find($customer_id)) {
            $errors['customer_id'] = __('Customer tidak valid.', 'wp-customer');
            return $errors;
        }
        
        // Name validation
        $name = trim(sanitize_text_field($data['name'] ?? ''));
        if (empty($name)) {
            $errors['name'] = __('Nama cabang wajib diisi.', 'wp-customer');
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = __('Nama cabang maksimal 100 karakter.', 'wp-customer');
        } elseif ($this->branch_model->existsByNameInCustomer($name, $customer_id)) {
            $errors['name'] = __('Nama cabang sudah ada di customer ini.', 'wp-customer');
        }

        // Type validation
        $type = trim(sanitize_text_field($data['type'] ?? ''));
        if (empty($type)) {
            $errors['type'] = __('Tipe cabang wajib diisi.', 'wp-customer');
        } elseif (!in_array($type, ['cabang', 'pusat'])) {
            $errors['type'] = __('Tipe cabang tidak valid.', 'wp-customer');
        }

        return $errors;
    }

    public function validateUpdate(array $data, int $id): array {
        $errors = [];

        // Check if branch exists
        $branch = $this->branch_model->find($id);
        if (!$branch) {
            $errors['id'] = __('Kabupaten/kota tidak ditemukan.', 'wp-customer');
            return $errors;
        }

        // Permission check
        if (!current_user_can('edit_all_branches') &&
            (!current_user_can('edit_own_branch') || $branch->created_by !== get_current_user_id())) {
            $errors['permission'] = __('Anda tidak memiliki izin untuk mengedit cabang ini.', 'wp-customer');
            return $errors;
        }

        // Basic validation
        $name = trim(sanitize_text_field($data['name'] ?? ''));
        if (empty($name)) {
            $errors['name'] = __('Nama cabang wajib diisi.', 'wp-customer');
        }

        // Length check
        if (mb_strlen($name) > 100) {
            $errors['name'] = __('Nama cabang maksimal 100 karakter.', 'wp-customer');
        }

        // Unique check excluding current ID
        if ($this->branch_model->existsByNameInCustomer($name, $branch->customer_id, $id)) {
            $errors['name'] = __('Nama cabang sudah ada di customer ini.', 'wp-customer');
        }

        // Type validation if provided
        if (isset($data['type'])) {
            $type = trim(sanitize_text_field($data['type']));
            if (!in_array($type, ['cabang', 'pusat'])) {
                $errors['type'] = __('Tipe cabang tidak valid.', 'wp-customer');
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

        // Permission check
        if (!current_user_can('delete_branch') &&
            (!current_user_can('delete_own_branch') || $branch->created_by !== get_current_user_id())) {
            $errors['permission'] = __('Anda tidak memiliki izin untuk menghapus cabang ini.', 'wp-customer');
        }

        // Branch type deletion validation
        $type_validation = $this->validateBranchTypeDelete($id);
        if (!$type_validation['valid']) {
            $errors['type'] = $type_validation['message'];
        }

        return $errors;
    }

    /**
     * Validate view operation
     */
    public function validateView(int $id): array {
        $errors = [];

        // Check if branch exists
        $branch = $this->branch_model->find($id);
        if (!$branch) {
            $errors['id'] = __('Kabupaten/kota tidak ditemukan.', 'wp-customer');
            return $errors;
        }

        // Permission check
        if (!current_user_can('view_branch_detail') &&
            (!current_user_can('view_own_branch') || $branch->created_by !== get_current_user_id())) {
            $errors['permission'] = __('Anda tidak memiliki izin untuk melihat detail cabang ini.', 'wp-customer');
        }

        return $errors;
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
