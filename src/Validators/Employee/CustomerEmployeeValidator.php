<?php
/**
 * Customer Employee Validator Class
 *
 * @package     WP_Customer
 * @subpackage  Validators/Employee
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Validators/Employee/CustomerEmployeeValidator.php
 *
 * Description: Validator untuk operasi CRUD Employee.
 *              Memastikan semua input data valid sebelum diproses model.
 *              Menyediakan validasi untuk create, update, dan delete.
 *              Includes validasi permission dan ownership.
 *
 * Changelog:
 * 1.0.0 - 2024-01-12
 * - Initial release
 * - Added create validation
 * - Added update validation
 * - Added delete validation
 * - Added permission validation
 */

namespace WPCustomer\Validators\Employee;

use WPCustomer\Models\Employee\CustomerEmployeeModel;

class CustomerEmployeeValidator {
    private $employee_model;

    public function __construct() {
        $this->employee_model = new CustomerEmployeeModel();
    }

    /**
     * Validate create operation
     */
    public function validateCreate(array $data): array {
        $errors = [];

        // Permission check
        if (!current_user_can('add_employee')) {
            $errors['permission'] = __('Anda tidak memiliki izin untuk menambah karyawan.', 'wp-customer');
            return $errors;
        }

        // Basic data validation
        $errors = array_merge($errors, $this->validateBasicData($data));

        // Customer ID validation
        if (empty($data['customer_id'])) {
            $errors['customer_id'] = __('ID Customer wajib diisi.', 'wp-customer');
        }

        // Branch ID validation
        if (empty($data['branch_id'])) {
            $errors['branch_id'] = __('ID Cabang wajib diisi.', 'wp-customer');
        }

        // Email uniqueness
        if (!empty($data['email']) && $this->employee_model->existsByEmail($data['email'])) {
            $errors['email'] = __('Email sudah digunakan.', 'wp-customer');
        }

        return $errors;
    }

    /**
     * Validate update operation
     */
    public function validateUpdate(array $data, int $id): array {
        $errors = [];

        // Check if employee exists
        $employee = $this->employee_model->find($id);
        if (!$employee) {
            $errors['id'] = __('Karyawan tidak ditemukan.', 'wp-customer');
            return $errors;
        }

        // Permission check
        if (!current_user_can('edit_employee')) {
            $errors['permission'] = __('Anda tidak memiliki izin untuk mengedit karyawan.', 'wp-customer');
            return $errors;
        }

        // Basic data validation
        $errors = array_merge($errors, $this->validateBasicData($data));

        // Email uniqueness (excluding current ID)
        if (!empty($data['email']) && $this->employee_model->existsByEmail($data['email'], $id)) {
            $errors['email'] = __('Email sudah digunakan.', 'wp-customer');
        }

        return $errors;
    }

    /**
     * Validate delete operation
     */
    public function validateDelete(int $id): array {
        $errors = [];

        // Check if employee exists
        $employee = $this->employee_model->find($id);
        if (!$employee) {
            $errors['id'] = __('Karyawan tidak ditemukan.', 'wp-customer');
            return $errors;
        }

        // Permission check
        if (!current_user_can('delete_employee')) {
            $errors['permission'] = __('Anda tidak memiliki izin untuk menghapus karyawan.', 'wp-customer');
            return $errors;
        }

        return $errors;
    }

    /**
     * Validate basic data common to create and update
     */
    private function validateBasicData(array $data): array {
        $errors = [];

        // Name validation
        $name = trim(sanitize_text_field($data['name'] ?? ''));
        if (empty($name)) {
            $errors['name'] = __('Nama karyawan wajib diisi.', 'wp-customer');
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = __('Nama karyawan maksimal 100 karakter.', 'wp-customer');
        }

        // Email validation
        $email = sanitize_email($data['email'] ?? '');
        if (empty($email)) {
            $errors['email'] = __('Email wajib diisi.', 'wp-customer');
        } elseif (!is_email($email)) {
            $errors['email'] = __('Format email tidak valid.', 'wp-customer');
        }

        // Position validation
        $position = trim(sanitize_text_field($data['position'] ?? ''));
        if (empty($position)) {
            $errors['position'] = __('Jabatan wajib diisi.', 'wp-customer');
        } elseif (mb_strlen($position) > 100) {
            $errors['position'] = __('Jabatan maksimal 100 karakter.', 'wp-customer');
        }

        // Keterangan validation
        $keterangan = trim(sanitize_text_field($data['keterangan'] ?? ''));
        if (empty($keterangan)) {
            $errors['keterangan'] = __('Keterangan wajib diisi.', 'wp-customer');
        } elseif (mb_strlen($keterangan) > 100) {
            $errors['keterangan'] = __('Keterangan maksimal 100 karakter.', 'wp-customer');
        }

        // Phone validation (optional)
        if (!empty($data['phone'])) {
            $phone = trim(sanitize_text_field($data['phone']));
            if (mb_strlen($phone) > 20) {
                $errors['phone'] = __('Nomor telepon maksimal 20 karakter.', 'wp-customer');
            } elseif (!preg_match('/^[0-9\+\-\(\)\s]*$/', $phone)) {
                $errors['phone'] = __('Format nomor telepon tidak valid.', 'wp-customer');
            }
        }

        return $errors;
    }

    /**
     * Validate view operation
     */
    public function validateView(int $id): array {
        $errors = [];

        // Check if employee exists
        $employee = $this->employee_model->find($id);
        if (!$employee) {
            $errors['id'] = __('Karyawan tidak ditemukan.', 'wp-customer');
            return $errors;
        }

        // Permission check
        if (!current_user_can('view_employee_detail') && 
            (!current_user_can('view_own_employee') || $employee->created_by !== get_current_user_id())) {
            $errors['permission'] = __('Anda tidak memiliki izin untuk melihat detail karyawan ini.', 'wp-customer');
        }

        return $errors;
    }
}
