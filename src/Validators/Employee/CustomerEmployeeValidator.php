<?php
/**
 * Customer Employee Validator Class
 *
 * @package     WP_Customer
 * @subpackage  Validators/Employee
 * @version     1.0.11
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

   /**
    * Find customer - using direct query to avoid cache contract issue
    * TODO: Remove this workaround when wp-app-core cache contract is fixed
    *
    * @param int $customer_id Customer ID
    * @return object|null Customer object or null
    */
   private function findCustomer(int $customer_id): ?object {
       global $wpdb;
       return $wpdb->get_row($wpdb->prepare(
           "SELECT * FROM {$wpdb->prefix}app_customers WHERE id = %d",
           $customer_id
       ));
   }

   public function canViewEmployee($employee, $customer): bool {
       $current_user_id = get_current_user_id();

       // Customer Owner Check
       if ((int)$customer->user_id === (int)$current_user_id) {
           return true;
       }

       // Customer Branch Admin Check
       if ($this->isCustomerBranchAdmin($current_user_id, $employee->branch_id)) {
           return true;
       }

       // Staff Check (dari CustomerEmployees)
       if ($this->isStaffMember($current_user_id, $employee->branch_id)) {
           return true;
       }

       // System Admin Check
       if (current_user_can('view_customer_employee_detail')) {
           return true;
       }

       return apply_filters('wp_customer_can_view_customer_employee', false, $employee, $customer, $current_user_id);
   }

   public function canCreateEmployee($customer_id, $branch_id): bool {
       $current_user_id = get_current_user_id();

       // Customer Owner Check
       $customer = $this->findCustomer($customer_id);
       if ($customer && (int)$customer->user_id === (int)$current_user_id) {
           return true;
       }

       // Customer Branch Admin Check dengan add_customer_employee capability
       if ($this->isCustomerBranchAdmin($current_user_id, $branch_id) && current_user_can('add_customer_employee')) {
           return true;
       }

       // System Admin Check
       if (current_user_can('add_customer_employee')) {
           return true;
       }

       return apply_filters('wp_customer_can_create_customer_employee', false, $customer_id, $branch_id, $current_user_id);
   }

   public function canEditEmployee($employee, $customer): bool {
       $current_user_id = get_current_user_id();

       // Customer Owner Check
       if ((int)$customer->user_id === (int)$current_user_id) {
           return true;
       }

       // Customer Branch Admin Check
       if ($this->isCustomerBranchAdmin($current_user_id, $employee->branch_id) &&
           current_user_can('edit_own_customer_employee')) {
           return true;
       }

       // Creator Check
       if ((int)$employee->created_by === (int)$current_user_id &&
           current_user_can('edit_own_customer_employee')) {
           return true;
       }

       // System Admin Check
       if (current_user_can('edit_all_customer_employees')) {
           return true;
       }

       return apply_filters('wp_customer_can_edit_customer_employee', false, $employee, $customer, $current_user_id);
   }

   public function canDeleteEmployee($employee, $customer): bool {
       $current_user_id = get_current_user_id();

       // Customer Owner Check
       if ((int)$customer->user_id === (int)$current_user_id) {
           return true;
       }

       // Customer Branch Admin Check
       if ($this->isCustomerBranchAdmin($current_user_id, $employee->branch_id) &&
           current_user_can('delete_customer_employee')) {
           return true;
       }

       // Creator Check
       if ((int)$employee->created_by === (int)$current_user_id &&
           current_user_can('delete_customer_employee')) {
           return true;
       }

       // System Admin Check
       if (current_user_can('delete_customer_employee')) {
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
           $customer = $this->findCustomer($data['customer_id']);
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
       $customer = $this->findCustomer($employee->customer_id);
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
       $customer = $this->findCustomer($employee->customer_id);
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
       $customer = $this->findCustomer($employee->customer_id);
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

    /**
     * Get access type from relation
     * 
     * Determines the user's access level based on their relationship with the employee/customer.
     * Priority order: admin > customer_admin > customer_branch_admin > staff > none
     *
     * @param array $relation User relation array with boolean flags
     * @return string Access type (admin, customer_admin, customer_branch_admin, staff, or none)
     */
    private function getAccessType(array $relation): string {
        // Check in priority order
        if ($relation['is_admin'] ?? false) {
            return 'admin';
        }

        if ($relation['is_customer_admin'] ?? false) {
            return 'customer_admin';
        }

        if ($relation['is_customer_branch_admin'] ?? false) {
            return 'customer_branch_admin';
        }

        if ($relation['is_customer_employee'] ?? false) {
            return 'staff';
        }

        return 'none';
    }

    /**
     * Validate form data for employee
     * 
     * Performs comprehensive validation of employee form fields including:
     * - Name (required, max 100 chars)
     * - Email (required, valid format, unique per customer)
     * - Position (required, max 100 chars)
     * - Phone (optional, max 20 chars, valid format)
     * - Branch ID (required, exists)
     * - Customer ID (required, exists)
     * - At least one department selected
     * 
     * @param array $data Employee data to validate
     * @param int|null $id Employee ID (null for create, set for update)
     * @return array Array of validation errors (empty if valid)
     */
    public function validateForm(array $data, ?int $id = null): array {
        $errors = [];

        // Name validation
        $name = trim($data['name'] ?? '');
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
        } elseif ($this->employee_model->existsByEmail($email, $id)) {
            $errors['email'] = __('Email sudah digunakan.', 'wp-customer');
        }

        // Position validation
        $position = trim($data['position'] ?? '');
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

        // Branch ID validation
        if (empty($data['branch_id'])) {
            $errors['branch_id'] = __('Cabang wajib dipilih.', 'wp-customer');
        } else {
            global $wpdb;
            $branch_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_branches WHERE id = %d",
                $data['branch_id']
            ));
            if (!$branch_exists) {
                $errors['branch_id'] = __('Cabang tidak ditemukan.', 'wp-customer');
            }
        }

        // Customer ID validation
        if (empty($data['customer_id'])) {
            $errors['customer_id'] = __('Customer wajib dipilih.', 'wp-customer');
        } else {
            $customer = $this->findCustomer($data['customer_id']);
            if (!$customer) {
                $errors['customer_id'] = __('Customer tidak ditemukan.', 'wp-customer');
            }
        }

        // Department validation - at least one must be selected
        if (!$this->hasAtLeastOneDepartment($data)) {
            $errors['department'] = __('Minimal satu departemen harus dipilih.', 'wp-customer');
        }

        return $errors;
    }

    /**
     * Validate permission for employee action
     * 
     * Checks if current user has permission to perform the specified action.
     * Handles both creation (without ID) and other actions (with ID).
     * Validates action-specific permissions and checks employee/customer existence.
     * 
     * @param string $action Action to validate (create, view, update, delete)
     * @param int|null $id Employee ID (required for all actions except create)
     * @return array Array of validation errors (empty if valid)
     */
    public function validatePermission(string $action, ?int $id = null): array {
        $errors = [];

        // Handle create action separately
        if (!$id && $action === 'create') {
            if (isset($_POST['customer_id']) && isset($_POST['branch_id'])) {
                $customer_id = (int)$_POST['customer_id'];
                $branch_id = (int)$_POST['branch_id'];
                
                if (!$this->canCreateEmployee($customer_id, $branch_id)) {
                    $errors['permission'] = __('Anda tidak memiliki izin untuk menambah karyawan.', 'wp-customer');
                }
            } else {
                if (!isset($_POST['customer_id'])) {
                    $errors['customer_id'] = __('Customer ID wajib dipilih.', 'wp-customer');
                }
                if (!isset($_POST['branch_id'])) {
                    $errors['branch_id'] = __('Branch ID wajib dipilih.', 'wp-customer');
                }
            }
            return $errors;
        }

        // For other actions, require employee ID
        if (!$id) {
            $errors['id'] = __('ID Karyawan tidak valid.', 'wp-customer');
            return $errors;
        }

        // Get employee and customer data
        $employee = $this->employee_model->find($id);
        if (!$employee) {
            $errors['id'] = __('Karyawan tidak ditemukan.', 'wp-customer');
            return $errors;
        }

        $customer = $this->findCustomer($employee->customer_id);
        if (!$customer) {
            $errors['customer'] = __('Customer tidak ditemukan.', 'wp-customer');
            return $errors;
        }

        // Validate based on action
        switch ($action) {
            case 'view':
                if (!$this->canViewEmployee($employee, $customer)) {
                    $errors['permission'] = __('Anda tidak memiliki akses untuk melihat karyawan ini.', 'wp-customer');
                }
                break;

            case 'update':
                if (!$this->canEditEmployee($employee, $customer)) {
                    $errors['permission'] = __('Anda tidak memiliki akses untuk mengubah karyawan ini.', 'wp-customer');
                }
                break;

            case 'delete':
                if (!$this->canDeleteEmployee($employee, $customer)) {
                    $errors['permission'] = __('Anda tidak memiliki akses untuk menghapus karyawan ini.', 'wp-customer');
                }
                break;

            default:
                $errors['action'] = __('Aksi tidak valid.', 'wp-customer');
        }

        return $errors;
    }

    /**
     * Validate access for given customer and branch
     * 
     * FIXED: Now properly checks admin capabilities FIRST before checking other relations.
     * This ensures admin users are always detected as 'admin' access_type.
     *
     * @param int $customer_id Customer ID (0 for general access validation)
     * @param int $branch_id Branch ID (0 if no specific branch)
     * @return array Access information [has_access, access_type, relation, customer_id, branch_id]
     */
    public function validateAccess(int $customer_id, int $branch_id = 0): array {
        $current_user_id = get_current_user_id();

        // CRITICAL FIX: Check admin capability FIRST
        $is_admin = current_user_can('view_customer_employee_list') || current_user_can('edit_all_customer_employees');

        // Handle special case for customer_id = 0 (general validation)
        if ($customer_id === 0) {
            $relation = [
                'is_admin' => $is_admin,
                'is_customer_admin' => false,
                'is_customer_branch_admin' => false,
                'is_customer_employee' => false,
                'access_type' => $is_admin ? 'admin' : 'none'
            ];

            return [
                'has_access' => current_user_can('view_customer_employee_list') || $is_admin,
                'access_type' => $is_admin ? 'admin' : 'none',
                'relation' => $relation,
                'customer_id' => 0,
                'branch_id' => 0
            ];
        }

        // If admin, immediately return admin access without checking other relations
        if ($is_admin) {
            $relation = [
                'is_admin' => true,
                'is_customer_admin' => false,
                'is_customer_branch_admin' => false,
                'is_customer_employee' => false,
                'access_type' => 'admin'
            ];

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("CustomerEmployeeValidator: User $current_user_id detected as ADMIN for customer $customer_id");
            }

            return [
                'has_access' => true,
                'access_type' => 'admin',
                'relation' => $relation,
                'customer_id' => $customer_id,
                'branch_id' => $branch_id
            ];
        }

        // Get customer data
        $customer = $this->findCustomer($customer_id);
        if (!$customer) {
            return [
                'has_access' => false,
                'access_type' => 'none',
                'relation' => [
                    'is_admin' => false,
                    'is_customer_admin' => false,
                    'is_customer_branch_admin' => false,
                    'is_customer_employee' => false,
                    'access_type' => 'none'
                ],
                'customer_id' => $customer_id,
                'branch_id' => $branch_id,
                'error' => 'Customer not found'
            ];
        }

        // Check other relations only if not admin
        $is_customer_admin = (int)$customer->user_id === (int)$current_user_id;
        $is_customer_branch_admin = $branch_id > 0 ? $this->isCustomerBranchAdmin($current_user_id, $branch_id) : false;
        $is_customer_employee = $this->isStaffMemberOfCustomer($current_user_id, $customer_id);

        // Build relation array
        $relation = [
            'is_admin' => false, // Already checked above
            'is_customer_admin' => $is_customer_admin,
            'is_customer_branch_admin' => $is_customer_branch_admin,
            'is_customer_employee' => $is_customer_employee
        ];

        // Determine access_type using helper method
        $access_type = $this->getAccessType($relation);
        $relation['access_type'] = $access_type;

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("CustomerEmployeeValidator: User $current_user_id access_type=$access_type for customer $customer_id");
        }

        return [
            'has_access' => $access_type !== 'none',
            'access_type' => $access_type,
            'relation' => $relation,
            'customer_id' => $customer_id,
            'branch_id' => $branch_id
        ];
    }

   private function hasAtLeastOneDepartment(array $data): bool {
       return ($data['finance'] ?? false) || 
              ($data['operation'] ?? false) || 
              ($data['legal'] ?? false) || 
              ($data['purchase'] ?? false);
   }

   private function isCustomerBranchAdmin($user_id, $branch_id): bool {
       global $wpdb;
       return (bool)$wpdb->get_var($wpdb->prepare(
           "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_branches 
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

   /**
    * Check if user is staff member of any branch in given customer
    *
    * @param int $user_id User ID to check
    * @param int $customer_id Customer ID to check against
    * @return bool True if user is staff member of this customer
    */
   private function isStaffMemberOfCustomer($user_id, $customer_id): bool {
       global $wpdb;
       return (bool)$wpdb->get_var($wpdb->prepare(
           "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees
            WHERE user_id = %d AND customer_id = %d AND status = 'active'",
           $user_id, $customer_id
       ));
   }
   
   
}
