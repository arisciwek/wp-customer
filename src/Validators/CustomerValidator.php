<?php
/**
* Customer Validator Class
*
* @package     WP_Customer
* @subpackage  Validators
* @version     1.0.0
* @author      arisciwek
*
* Path: src/Validators/CustomerValidator.php
*
* Description: Validator untuk operasi CRUD Customer.
*              Memastikan semua input data valid sebelum diproses model.
*              Menyediakan validasi untuk create, update, dan delete.
*              Includes validasi permission dan ownership.
*
* Changelog:
* 1.0.1 - 2024-12-08
* - Added view_own_customer validation in validateView method
* - Updated permission validation messages
* - Enhanced error handling for permission checks
*
* Changelog:
* 1.0.0 - 2024-12-02 15:00:00
* - Initial release
* - Added create validation
* - Added update validation
* - Added delete validation
* - Added permission validation
*
* Dependencies:
* - WPCustomer\Models\CustomerModel for data checks
* - WordPress sanitization functions
*/

namespace WPCustomer\Validators;

use WPCustomer\Models\CustomerModel;

class CustomerValidator {
   private $customer_model;

   public function __construct() {
       $this->customer_model = new CustomerModel();
   }

   /**
    * Validate create operation
    */
    public function validateCreate(array $data): array {
        $errors = [];

        // Permission check
        if (!current_user_can('add_customer')) {
            $errors['permission'] = __('Anda tidak memiliki izin untuk menambah customer.', 'wp-customer');
            return $errors;
        }

        // Code validation
        $code = trim(sanitize_text_field($data['code'] ?? ''));
        if (empty($code)) {
            $errors['code'] = __('Kode customer wajib diisi.', 'wp-customer');
        } elseif (!preg_match('/^\d{2}$/', $code)) {
            $errors['code'] = __('Kode customer harus berupa 2 digit angka.', 'wp-customer');
        } elseif ($this->customer_model->existsByCode($code)) {
            $errors['code'] = __('Kode customer sudah ada.', 'wp-customer');
        }

        // Name validation
        $name = trim(sanitize_text_field($data['name'] ?? ''));
        if (empty($name)) {
            $errors['name'] = __('Nama customer wajib diisi.', 'wp-customer');
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = __('Nama customer maksimal 100 karakter.', 'wp-customer');
        } elseif ($this->customer_model->existsByName($name)) {
            $errors['name'] = __('Nama customer sudah ada.', 'wp-customer');
        }

        return $errors;
    }

   /**
    * Validate update operation
    */

    public function validateUpdate(array $data, int $id): array {
        $errors = [];

        // Check if customer exists
        $customer = $this->customer_model->find($id);
        if (!$customer) {
            $errors['id'] = __('Customer tidak ditemukan.', 'wp-customer');
            return $errors;
        }

        // Permission check
        if (!current_user_can('edit_all_customers') &&
            (!current_user_can('edit_own_customer') || $customer->created_by !== get_current_user_id())) {
            $errors['permission'] = __('Anda tidak memiliki izin untuk mengedit customer ini.', 'wp-customer');
            return $errors;
        }

        // Basic validation
        $name = trim(sanitize_text_field($data['name'] ?? ''));
        if (empty($name)) {
            $errors['name'] = __('Nama customer wajib diisi.', 'wp-customer');
        }

        // Validate code
        $code = trim(sanitize_text_field($data['code'] ?? ''));
        if (empty($code)) {
            $errors['code'] = __('Kode customer wajib diisi.', 'wp-customer');
        } elseif (!preg_match('/^[0-9]{2}$/', $code)) {
            $errors['code'] = __('Kode customer harus 2 digit angka.', 'wp-customer');
        }

        // Length check
        if (mb_strlen($name) > 100) {
            $errors['name'] = __('Nama customer maksimal 100 karakter.', 'wp-customer');
        }

        // Unique check excluding current ID
        if ($this->customer_model->existsByName($name, $id)) {
            $errors['name'] = __('Nama customer sudah ada.', 'wp-customer');
        }

        // Check if code is unique (excluding current customer)
        if ($this->customer_model->existsByCode($code, $id)) {
            $errors['code'] = __('Kode customer sudah digunakan.', 'wp-customer');
        }

        return $errors;
    }

   /**
    * Validate delete operation
    */
   public function validateDelete(int $id): array {
       $errors = [];

       // Check if customer exists
       $customer = $this->customer_model->find($id);
       if (!$customer) {
           $errors['id'] = __('Customer tidak ditemukan.', 'wp-customer');
           return $errors;
       }

       // Permission check
       if (!current_user_can('delete_customer') &&
           (!current_user_can('delete_own_customer') || $customer->created_by !== get_current_user_id())) {
           $errors['permission'] = __('Anda tidak memiliki izin untuk menghapus customer ini.', 'wp-customer');
           return $errors;
       }

       // Check for existing branches
       if ($this->customer_model->getBranchCount($id) > 0) {
           $errors['dependencies'] = __('Customer tidak dapat dihapus karena masih memiliki kabupaten/kota.', 'wp-customer');
       }

       return $errors;
   }

   /**
    * Validate view operation
    */
    public function validateView(int $id): array {
        $errors = [];

        // Check if customer exists
        $customer = $this->customer_model->find($id);
        if (!$customer) {
            $errors['id'] = __('Customer tidak ditemukan.', 'wp-customer');
            return $errors;
        }

        // Permission check - update ini
        if (!current_user_can('view_customer_detail') &&
            (!current_user_can('view_own_customer') || $customer->created_by !== get_current_user_id())) {
            $errors['permission'] = __('Anda tidak memiliki izin untuk melihat detail customer ini.', 'wp-customer');
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

       return $sanitized;
   }
}
