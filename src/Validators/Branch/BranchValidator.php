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
use WPCustomer\Models\CustomerModel;

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
        
        // Code validation
        $code = trim(sanitize_text_field($data['code'] ?? ''));
        if (empty($code)) {
            $errors['code'] = __('Kode cabang wajib diisi.', 'wp-customer');
        } elseif (!preg_match('/^\d{4}$/', $code)) {
            $errors['code'] = __('Kode cabang harus berupa 4 digit angka.', 'wp-customer');
        } elseif ($this->branch_model->existsByCode($code)) {
            $errors['code'] = __('Kode cabang sudah ada.', 'wp-customer');
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
        } elseif (!in_array($type, ['kabupaten', 'kota'])) {
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
            if (!in_array($type, ['kabupaten', 'kota'])) {
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
            $errors['id'] = __('Kabupaten/kota tidak ditemukan.', 'wp-customer');
            return $errors;
        }

        // Permission check
        if (!current_user_can('delete_branch') &&
            (!current_user_can('delete_own_branch') || $branch->created_by !== get_current_user_id())) {
            $errors['permission'] = __('Anda tidak memiliki izin untuk menghapus cabang ini.', 'wp-customer');
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
}
