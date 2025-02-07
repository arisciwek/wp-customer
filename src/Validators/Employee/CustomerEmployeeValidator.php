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
use WPCustomer\Models\Customer\CustomerModel;

class CustomerEmployeeValidator {
   private $employee_model;
   private $customer_model;

   public function __construct() {
       $this->employee_model = new CustomerEmployeeModel();
       $this->customer_model = new CustomerModel(); 
   }

   public function canViewEmployee($employee, $customer): bool {
       $current_user_id = get_current_user_id();

       // Customer Owner Check
       if ((int)$customer->user_id === (int)$current_user_id) {
           return true;
       }

       // Branch Admin Check
       if ($this->isBranchAdmin($current_user_id, $employee->branch_id)) {
           return true;
       }

       // Staff Check (dari CustomerEmployees)
       if ($this->isStaffMember($current_user_id, $employee->branch_id)) {
           return true;
       }

       // System Admin Check
       if (current_user_can('view_employee_detail')) {
           return true;
       }

       return apply_filters('wp_customer_can_view_employee', false, $employee, $customer, $current_user_id);
   }

   public function canCreateEmployee($customer_id, $branch_id): bool {
       $current_user_id = get_current_user_id();

       // Customer Owner Check
       $customer = $this->customer_model->find($customer_id);
       if ($customer && (int)$customer->user_id === (int)$current_user_id) {
           return true;
       }

       // Branch Admin Check dengan add_employee capability
       if ($this->isBranchAdmin($current_user_id, $branch_id) && current_user_can('add_employee')) {
           return true;
       }

       // System Admin Check
       if (current_user_can('add_employee')) {
           return true;
       }

       return apply_filters('wp_customer_can_create_employee', false, $customer_id, $branch_id, $current_user_id);
   }

   public function canEditEmployee($employee, $customer): bool {
       $current_user_id = get_current_user_id();

       // Customer Owner Check
       if ((int)$customer->user_id === (int)$current_user_id) {
           return true;
       }

       // Branch Admin Check
       if ($this->isBranchAdmin($current_user_id, $employee->branch_id) && 
           current_user_can('edit_own_employee')) {
           return true;
       }

       // Creator Check
       if ((int)$employee->created_by === (int)$current_user_id && 
           current_user_can('edit_own_employee')) {
           return true;
       }

       // System Admin Check
       if (current_user_can('edit_all_employees')) {
           return true;
       }

       return apply_filters('wp_customer_can_edit_employee', false, $employee, $customer, $current_user_id);
   }

   public function canDeleteEmployee($employee, $customer): bool {
       $current_user_id = get_current_user_id();

       // Customer Owner Check
       if ((int)$customer->user_id === (int)$current_user_id) {
           return true;
       }

       // Branch Admin Check
       if ($this->isBranchAdmin($current_user_id, $employee->branch_id) && 
           current_user_can('delete_employee')) {
           return true;
       }

       // Creator Check
       if ((int)$employee->created_by === (int)$current_user_id && 
           current_user_can('delete_employee')) {
           return true;
       }

       // System Admin Check
       if (current_user_can('delete_employee')) {
           return true;
       }

       return false;
   }

   public function validateCreate(array $data): array {
       $errors = [];

       // Permission check
       if (!$this->canCreateEmployee($data['customer_id'], $data['branch_id'])) {
           $errors['permission'] = __('Anda tidak memiliki izin untuk menambah karyawan.', 'wp-customer');
           return $errors;
       }

       // Basic data validation
       $errors = array_merge($errors, $this->validateBasicData($data));

       // Customer ID validation
       if (empty($data['customer_id'])) {
           $errors['customer_id'] = __('ID Customer wajib diisi.', 'wp-customer');
       } else {
           $customer = $this->customer_model->find($data['customer_id']);
           if (!$customer) {
               $errors['customer_id'] = __('Customer tidak ditemukan.', 'wp-customer');
           }
       }

       // Branch ID validation
       if (empty($data['branch_id'])) {
           $errors['branch_id'] = __('ID Cabang wajib diisi.', 'wp-customer');
       }

       // Email uniqueness
       if (!empty($data['email']) && $this->employee_model->existsByEmail($data['email'])) {
           $errors['email'] = __('Email sudah digunakan.', 'wp-customer');
       }

       // Department validation
       if (!$this->hasAtLeastOneDepartment($data)) {
           $errors['department'] = __('Minimal satu departemen harus dipilih.', 'wp-customer');
       }

       return $errors;
   }

   public function validateUpdate(array $data, int $id): array {
       $errors = [];

       // Check if employee exists
       $employee = $this->employee_model->find($id);
       if (!$employee) {
           $errors['id'] = __('Karyawan tidak ditemukan.', 'wp-customer');
           return $errors;
       }

       // Get customer for permission check
       $customer = $this->customer_model->find($employee->customer_id);
       if (!$customer) {
           $errors['customer'] = __('Customer tidak ditemukan.', 'wp-customer');
           return $errors;
       }

       // Permission check
       if (!$this->canEditEmployee($employee, $customer)) {
           $errors['permission'] = __('Anda tidak memiliki izin untuk mengedit karyawan ini.', 'wp-customer');
           return $errors;
       }

       // Basic data validation
       $errors = array_merge($errors, $this->validateBasicData($data));

       // Email uniqueness (excluding current ID)
       if (!empty($data['email']) && $this->employee_model->existsByEmail($data['email'], $id)) {
           $errors['email'] = __('Email sudah digunakan.', 'wp-customer');
       }

       // Department validation on update
       if (!$this->hasAtLeastOneDepartment($data)) {
           $errors['department'] = __('Minimal satu departemen harus dipilih.', 'wp-customer');
       }

       return $errors;
   }

   public function validateDelete(int $id): array {
       $errors = [];

       // Check if employee exists
       $employee = $this->employee_model->find($id);
       if (!$employee) {
           $errors['id'] = __('Karyawan tidak ditemukan.', 'wp-customer');
           return $errors;
       }

       // Get customer for permission check
       $customer = $this->customer_model->find($employee->customer_id);
       if (!$customer) {
           $errors['customer'] = __('Customer tidak ditemukan.', 'wp-customer');
           return $errors;
       }

       // Permission check
       if (!$this->canDeleteEmployee($employee, $customer)) {
           $errors['permission'] = __('Anda tidak memiliki izin untuk menghapus karyawan ini.', 'wp-customer');
       }

       return $errors;
   }

   public function validateView(int $id): array {
       $errors = [];

       // Check if employee exists
       $employee = $this->employee_model->find($id);
       if (!$employee) {
           $errors['id'] = __('Karyawan tidak ditemukan.', 'wp-customer');
           return $errors;
       }

       // Get customer for permission check
       $customer = $this->customer_model->find($employee->customer_id);
       if (!$customer) {
           $errors['customer'] = __('Customer tidak ditemukan.', 'wp-customer');
           return $errors;
       }

       // Permission check
       if (!$this->canViewEmployee($employee, $customer)) {
           $errors['permission'] = __('Anda tidak memiliki izin untuk melihat detail karyawan ini.', 'wp-customer');
       }

       return $errors;
   }

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

   private function hasAtLeastOneDepartment(array $data): bool {
       return ($data['finance'] ?? false) || 
              ($data['operation'] ?? false) || 
              ($data['legal'] ?? false) || 
              ($data['purchase'] ?? false);
   }

   private function isBranchAdmin($user_id, $branch_id): bool {
       global $wpdb;
       return (bool)$wpdb->get_var($wpdb->prepare(
           "SELECT COUNT(*) FROM {$wpdb->prefix}app_branches 
            WHERE id = %d AND user_id = %d",
           $branch_id, $user_id
       ));
   }

   private function isStaffMember($user_id, $branch_id): bool {
       global $wpdb;
       return (bool)$wpdb->get_var($wpdb->prepare(
           "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees 
            WHERE user_id = %d AND branch_id = %d AND status = 'active'",
           $user_id, $branch_id
       ));
   }
}
