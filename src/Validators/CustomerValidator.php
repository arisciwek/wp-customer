<?php
/**
 * Customer Validator Class
 *
 * @package     WP_Customer
 * @subpackage  Validators
 * @version     2.0.0
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
 * 2.0.0 - 2024-01-20
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

use WPCustomer\Models\CustomerModel;

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
            if (!preg_match('/^\d{2}\.\d{3}\.\d{3}\.\d{1}-\d{3}\.\d{3}$/', $npwp)) {
                $errors['npwp'] = __('Format NPWP tidak valid.', 'wp-customer');
            }
        }

        // NIB validation (optional)
        if (!empty($data['nib'])) {
            $nib = trim($data['nib']);
            if (!preg_match('/^\d{13}$/', $nib)) {
                $errors['nib'] = __('Format NIB tidak valid.', 'wp-customer');
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
     * Get user relation with customer
     *
     * @param int $customer_id
     * @return array Array containing is_admin, is_owner, is_employee flags
     */
    public function getUserRelation(int $customer_id): array {
        // Check cache first
        if (isset($this->relationCache[$customer_id])) {
            return $this->relationCache[$customer_id];
        }

        global $wpdb;
        $current_user_id = get_current_user_id();

        $relation = [
            'is_admin' => current_user_can('edit_all_customers'),
            'is_owner' => false,
            'is_employee' => false
        ];

        // Check if user is owner
        $customer = $this->model->find($customer_id);
        if ($customer) {
            $relation['is_owner'] = ((int)$customer->user_id === $current_user_id);
        }

        // Check if user is employee
        $is_employee = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees 
             WHERE customer_id = %d 
             AND user_id = %d 
             AND status = 'active'",
            $customer_id, 
            $current_user_id
        ));
        
        $relation['is_employee'] = (int)$is_employee > 0;

        // Save to cache
        $this->relationCache[$customer_id] = $relation;

        return $relation;
    }

    /**
     * Check if user can view customer
     */
    public function canView(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_owner'] && current_user_can('view_own_customer')) return true;
        if ($relation['is_employee'] && current_user_can('view_own_customer')) return true;
        return false;
    }

    /**
     * Check if user can update customer
     */
    public function canUpdate(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_owner'] && current_user_can('edit_own_customer')) return true;
        return false;
    }

    /**
     * Check if user can delete customer
     */
    public function canDelete(array $relation): bool {
        return $relation['is_admin'] && current_user_can('delete_customer');
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
}
