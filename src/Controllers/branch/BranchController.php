<?php
/**
 * Branch Controller Class
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Branch
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Branch/BranchController.php
 *
 * Description: Controller untuk mengelola data cabang.
 *              Menangani operasi CRUD dengan integrasi cache.
 *              Includes validasi input, permission checks,
 *              dan response formatting untuk DataTables.
 *
 * Changelog:
 * 1.0.0 - 2024-12-10
 * - Initial implementation
 * - Added CRUD endpoints
 * - Added DataTables integration
 * - Added permission checks
 * - Added cache support
 */

namespace WPCustomer\Controllers\Branch;

use WPCustomer\Models\CustomerModel;
use WPCustomer\Models\Branch\BranchModel;
use WPCustomer\Validators\Branch\BranchValidator;
use WPCustomer\Cache\CacheManager;

class BranchController {
    private CustomerModel $customerModel;
    private BranchModel $model;
    private BranchValidator $validator;
    private CacheManager $cache;
    private string $log_file;

    /**
     * Default log file path
     */
    private const DEFAULT_LOG_FILE = 'logs/branch.log';

    public function __construct() {
        $this->customerModel = new CustomerModel();
        $this->model = new BranchModel();
        $this->validator = new BranchValidator();
        $this->cache = new CacheManager();

        // Initialize log file inside plugin directory
        $this->log_file = WP_CUSTOMER_PATH . self::DEFAULT_LOG_FILE;

        // Ensure logs directory exists
        $this->initLogDirectory();

        // Register AJAX handlers
        add_action('wp_ajax_handle_branch_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_nopriv_handle_branch_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_get_customer_branches', [$this, 'getCustomerBranches']);

        // Register other endpoints
        add_action('wp_ajax_get_branch', [$this, 'show']);
        add_action('wp_ajax_create_branch', [$this, 'store']);
        add_action('wp_ajax_update_branch', [$this, 'update']);
        add_action('wp_ajax_delete_branch', [$this, 'delete']);
    }

    /**
     * Initialize log directory if it doesn't exist
     */
    private function initLogDirectory(): void {
        // Gunakan wp_upload_dir() untuk mendapatkan writable directory
        $upload_dir = wp_upload_dir();
        $plugin_log_dir = $upload_dir['basedir'] . '/wp-customer/logs';
        
        // Update log file path dengan format yang lebih informatif
        $this->log_file = $plugin_log_dir . '/customer-' . date('Y-m') . '.log';

        // Buat direktori jika belum ada
        if (!file_exists($plugin_log_dir)) {
            if (!wp_mkdir_p($plugin_log_dir)) {
                // Jika gagal, gunakan sys_get_temp_dir sebagai fallback
                $this->log_file = rtrim(sys_get_temp_dir(), '/') . '/wp-customer.log';
                error_log('Failed to create log directory in uploads: ' . $plugin_log_dir);
                return;
            }

            // Protect directory dengan .htaccess
            file_put_contents($plugin_log_dir . '/.htaccess', 'deny from all');
            chmod($plugin_log_dir, 0755);
        }

        // Buat file log jika belum ada
        if (!file_exists($this->log_file)) {
            if (@touch($this->log_file)) {
                chmod($this->log_file, 0644);
            } else {
                error_log('Failed to create log file: ' . $this->log_file);
                $this->log_file = rtrim(sys_get_temp_dir(), '/') . '/wp-customer.log';
                return;
            }
        }

        // Double check writability
        if (!is_writable($this->log_file)) {
            error_log('Log file not writable: ' . $this->log_file);
            $this->log_file = rtrim(sys_get_temp_dir(), '/') . '/wp-customer.log';
        }
    }



    /**
     * Log debug messages to file
     */
    private function debug_log($message): void {
        // Hanya jalankan jika WP_DEBUG aktif
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $timestamp = current_time('mysql');

        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }

        $log_message = "[{$timestamp}] {$message}\n";

        // Coba tulis ke file
        $written = @error_log($log_message, 3, $this->log_file);
        
        // Jika gagal, log ke default WordPress debug log
        if (!$written) {
            error_log('WP Customer Plugin: ' . $log_message);
        }
    }
    /*
public function getCheckCustomerAccess($customer_id) {
    global $wpdb;
    $current_user_id = get_current_user_id();

    // 1. Admin Check
    if (current_user_can('edit_all_customers')) {
        error_log("User has admin access");
        return [
            'has_access' => true,
            'access_type' => 'admin'
        ];
    }

    // 2. Owner Check
    $is_owner = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}app_customers 
         WHERE id = %d AND user_id = %d",
        $customer_id, $current_user_id
    ));

    if ($is_owner && current_user_can('view_own_customer')) {
        error_log("User is owner");
        return [
            'has_access' => true,
            'access_type' => 'owner'
        ];
    }

    // 3. Employee Check
    $is_employee = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees 
         WHERE customer_id = %d AND user_id = %d AND status = 'active'",
        $customer_id, $current_user_id
    ));

    if ($is_employee && current_user_can('view_own_customer')) {
        error_log("User is employee");
        return [
            'has_access' => true,
            'access_type' => 'employee'
        ];
    }

    error_log("No access found");
    return [
        'has_access' => false,
        'access_type' => null
    ];
}
*/

    public function handleDataTableRequest() {
        try {
            // Verify nonce
            if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
                throw new \Exception('Security check failed');
            }

            // Get and validate customer_id
            $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
            if (!$customer_id) {
                throw new \Exception('Invalid customer ID');
            }

            // Get and validate parameters
            $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
            $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
            $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
            $search = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';

            // Get order parameters
            $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
            $orderDir = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'asc';

            // Map column index to column name
            $columns = ['name', 'type', 'actions'];
            $orderBy = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'name';

            if ($orderBy === 'actions') {
                $orderBy = 'name'; // Default sort if actions column
            }

            try {
                $result = $this->model->getDataTableData(
                    $customer_id,
                    $start,
                    $length,
                    $search,
                    $orderBy,
                    $orderDir
                );

                if (!$result) {
                    throw new \Exception('No data returned from model');
                }

                $data = [];
                foreach ($result['data'] as $branch) {
                    $data[] = [
                        'id' => $branch->id,
                        'code' => esc_html($branch->code),
                        'name' => esc_html($branch->name),
                        'type' => esc_html($branch->type),
                        'customer_name' => esc_html($branch->customer_name),
                        'actions' => $this->generateActionButtons($branch)
                    ];
                }

                $response = [
                    'draw' => $draw,
                    'recordsTotal' => $result['total'],
                    'recordsFiltered' => $result['filtered'],
                    'data' => $data,
                ];

                wp_send_json($response);

            } catch (\Exception $modelException) {
                throw new \Exception('Database error: ' . $modelException->getMessage());
            }

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ], 400);
        }
    }

    // Contoh implementasi yang lebih sesuai untuk tombol tambah
    private function generateAddBranchButton($customer) {
        $current_user_id = get_current_user_id();

        // Debug logging
        $this->debug_log("=== Add Branch Button Permission Check ===");
        $this->debug_log([
            'customer_id' => (int)$customer->id,
            'customer_owner_id' => (int)$customer->user_id,
            'current_user_id' => $current_user_id
        ]);

        // 1. Check if user is owner
        $is_owner = ((int)$customer->user_id === $current_user_id);

        // 2. Permission check
        $can_add = current_user_can('add_branch') && $is_owner;

        $this->debug_log([
            'is_owner' => $is_owner,
            'has_add_permission' => current_user_can('add_branch'),
            'final_decision' => $can_add
        ]);

        if ($can_add) {
            return sprintf(
                '<button type="button" class="button button-primary" id="add-branch-btn">
                    <span class="dashicons dashicons-plus-alt"></span>
                    %s
                </button>',
                __('Tambah Cabang', 'wp-customer')
            );
        }

        return '';
    }

    private function generateActionButtons($branch) {
            $actions = '';
            $current_user_id = get_current_user_id();

            // Debug logging untuk transparansi
            $this->debug_log("==== Generating Action Buttons for Branch ID: {$branch->id} ====");
            
            // 1. Dapatkan data customer untuk cek ownership
            global $wpdb;
            $customer = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}app_customers WHERE id = %d",
                $branch->customer_id
            ));

            // Log customer data
            $this->debug_log([
                'branch_id' => (int)$branch->id,
                'customer_id' => (int)$branch->customer_id,
                'customer_owner_id' => $customer ? (int)$customer->user_id : 'not found',
                'current_user_id' => (int)$current_user_id,
                'branch_created_by' => (int)$branch->created_by
            ]);

            // 2. Cek apakah user adalah owner
            $is_owner = $customer && ((int)$customer->user_id === (int)$current_user_id);
            
            // 3. Cek apakah user adalah staff
            $is_staff = (bool)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees 
                 WHERE customer_id = %d AND user_id = %d",
                $branch->customer_id, 
                $current_user_id
            ));

            // Log permission context
            $this->debug_log("Permission Context:");
            $this->debug_log([
                'is_owner' => $is_owner,
                'is_staff' => $is_staff,
                'is_creator' => ((int)$branch->created_by === (int)$current_user_id),
                'has_view_detail' => current_user_can('view_branch_detail'),
                'has_edit_all' => current_user_can('edit_all_branches'),
                'has_edit_own' => current_user_can('edit_own_branch'),
                'has_delete' => current_user_can('delete_branch')
            ]);

            // 4. View Button Logic
            // - Owner selalu bisa lihat
            // - Staff bisa lihat semua dalam customernya
            // - Admin dengan view_branch_detail bisa lihat semua
            if ($is_owner || $is_staff || current_user_can('view_branch_detail')) {
                $actions .= sprintf(
                    '<button type="button" class="button view-branch" data-id="%d" title="%s">
                        <i class="dashicons dashicons-visibility"></i>
                    </button> ',
                    $branch->id,
                    __('Lihat', 'wp-customer')
                );
                $this->debug_log("Added View Button");
            }

            // 5. Edit Button Logic
            // - Owner bisa edit semua cabang dalam customernya
            // - Staff hanya bisa edit cabang yang dia buat
            // - Admin dengan edit_all_branches bisa edit semua
            if (current_user_can('edit_all_branches') || 
                $is_owner || 
                (current_user_can('edit_own_branch') && (int)$branch->created_by === (int)$current_user_id)) {
                
                $actions .= sprintf(
                    '<button type="button" class="button edit-branch" data-id="%d" title="%s">
                        <i class="dashicons dashicons-edit"></i>
                    </button> ',
                    $branch->id,
                    __('Edit', 'wp-customer')
                );
                $this->debug_log("Added Edit Button");
            }

            // 6. Delete Button Logic
            // - Owner bisa hapus semua cabang dalam customernya
            // - Staff hanya bisa hapus cabang yang dia buat
            // - Admin dengan delete_branch bisa hapus semua
            if (current_user_can('delete_branch') || 
                $is_owner || 
                (current_user_can('delete_branch') && (int)$branch->created_by === (int)$current_user_id)) {
                
                $actions .= sprintf(
                    '<button type="button" class="button delete-branch" data-id="%d" title="%s">
                        <i class="dashicons dashicons-trash"></i>
                    </button>',
                    $branch->id,
                    __('Hapus', 'wp-customer')
                );
                $this->debug_log("Added Delete Button");
            }

            // Log final buttons
            $this->debug_log("Final action buttons HTML: " . $actions);
            $this->debug_log("==== End Action Buttons Generation ====\n");

            return $actions;
        }

        public function store() {
            try {
                check_ajax_referer('wp_customer_nonce', 'nonce');

                if (!current_user_can('add_branch')) {
                    throw new \Exception('Insufficient permissions');
                }

                // Create WP User first
                $userdata = [
                    'user_login'    => sanitize_text_field($_POST['admin_username']),
                    'user_email'    => sanitize_email($_POST['admin_email']),
                    'first_name'    => sanitize_text_field($_POST['admin_firstname']),
                    'last_name'     => sanitize_text_field($_POST['admin_lastname']),
                    'user_pass'     => wp_generate_password(),
                    'role'          => 'customer'
                ];

                $user_id = wp_insert_user($userdata);
                if (is_wp_error($user_id)) {
                    throw new \Exception($user_id->get_error_message());
                }

                // Create branch with user_id
                $branch_data = [
                    'customer_id' => (int)$_POST['customer_id'],
                    'name' => sanitize_text_field($_POST['name']),
                    'type' => sanitize_text_field($_POST['type']),
                    'user_id' => $user_id,
                    // Other branch data
                    'nitku' => sanitize_text_field($_POST['nitku']),
                    'postal_code' => sanitize_text_field($_POST['postal_code']),
                    'latitude' => (float)$_POST['latitude'],
                    'longitude' => (float)$_POST['longitude'],
                    'address' => sanitize_text_field($_POST['address']),
                    'phone' => sanitize_text_field($_POST['phone']),
                    'email' => sanitize_email($_POST['email']),
                    'provinsi_id' => (int)$_POST['provinsi_id'],
                    'regency_id' => (int)$_POST['regency_id'],
                    'created_by' => get_current_user_id(),
                    'status' => 'active'
                ];

                $branch_id = $this->model->create($branch_data);
                if (!$branch_id) {
                    // Rollback user creation if branch fails
                    wp_delete_user($user_id);
                    throw new \Exception('Failed to create branch');
                }

                // Send password reset email
                wp_new_user_notification($user_id, null, 'user');

                wp_send_json_success([
                    'message' => 'Branch dan admin berhasil dibuat',
                    'branch_id' => $branch_id
                ]);

            } catch (\Exception $e) {
                wp_send_json_error(['message' => $e->getMessage()]);
            }
        }

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

private function canEditBranch($branch, $customer) {
    $current_user_id = get_current_user_id();
    
    // Debug logging
    $this->debug_log("=== Branch Edit Permission Check ===");
    $this->debug_log([
        'branch_id' => (int)$branch->id,
        'customer_id' => (int)$branch->customer_id,
        'customer_owner_id' => (int)$customer->user_id,
        'current_user_id' => (int)$current_user_id,
        'branch_created_by' => (int)$branch->created_by
    ]);

    // 1. Customer Owner Check - highest priority
    $is_customer_owner = ((int)$customer->user_id === (int)$current_user_id);
    if ($is_customer_owner) {
        $this->debug_log("Permission granted: User is customer owner");
        return true;
    }

    // 2. System Admin Check - for super admins
    if (current_user_can('edit_all_branches')) {
        $this->debug_log("Permission granted: User has edit_all_branches capability");
        return true;
    }

    // 3. Regular User Check - for branch creators
    if (current_user_can('edit_own_branch') && (int)$branch->created_by === (int)$current_user_id) {
        $this->debug_log("Permission granted: User created this branch");
        return true;
    }

    $this->debug_log("Permission denied: No matching criteria");
    return false;
}

    /**
     * Implementation in update() method
     */

    public function update() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid branch ID');
            }

            // Get existing branch data
            $branch = $this->model->find($id);
            if (!$branch) {
                throw new \Exception('Branch not found');
            }

            // Get customer data untuk check ownership
            $customer = $this->customerModel->find($branch->customer_id);
            if (!$customer) {
                throw new \Exception('Customer not found');
            }

            // Check edit permission
            if (!$this->canEditBranch($branch, $customer)) {
                throw new \Exception('Anda tidak memiliki izin untuk mengedit cabang ini');
            }

            $current_user_id = get_current_user_id();

            // Debug log for permission checking
            $this->debug_log("=== Branch Update Permission Check ===");
            $this->debug_log([
                'branch_id' => $id,
                'customer_id' => $branch->customer_id,
                'customer_owner_id' => $customer->user_id,
                'current_user_id' => $current_user_id,
                'branch_created_by' => $branch->created_by
            ]);

            // Check if user is customer owner
            $is_customer_owner = ((int)$customer->user_id === (int)$current_user_id);
            
            $this->debug_log("Permission Context:");
            $this->debug_log([
                'is_customer_owner' => $is_customer_owner,
                'edit_all_branches' => current_user_can('edit_all_branches'),
                'edit_own_branch' => current_user_can('edit_own_branch')
            ]);

            // Permission check
            $can_edit = $is_customer_owner || 
                       current_user_can('edit_all_branches') ||
                       (current_user_can('edit_own_branch') && (int)$branch->created_by === (int)$current_user_id);

            $this->debug_log("Final Edit Permission: " . ($can_edit ? 'Granted' : 'Denied'));

            if (!$can_edit) {
                throw new \Exception('Anda tidak memiliki izin untuk mengedit cabang ini');
            }

            // Validate input

            $data = array_filter([
                'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : null,
                'type' => isset($_POST['type']) ? sanitize_text_field($_POST['type']) : null,
                'nitku' => isset($_POST['nitku']) ? sanitize_text_field($_POST['nitku']) : null,
                'postal_code' => isset($_POST['postal_code']) ? sanitize_text_field($_POST['postal_code']) : null,
                'latitude' => isset($_POST['latitude']) ? (float)$_POST['latitude'] : null,
                'longitude' => isset($_POST['longitude']) ? (float)$_POST['longitude'] : null,
                'address' => isset($_POST['address']) ? sanitize_text_field($_POST['address']) : null,
                'phone' => isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : null,
                'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : null,
                'provinsi_id' => isset($_POST['provinsi_id']) ? (int)$_POST['provinsi_id'] : null,
                'regency_id' => isset($_POST['regency_id']) ? (int)$_POST['regency_id'] : null,
                'user_id' => isset($_POST['user_id']) ? (int)$_POST['user_id'] : null,
                'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : null
            ], function($value) { return $value !== null; });
            

            $errors = $this->validator->validateUpdate($data, $id);
            if (!empty($errors)) {
                throw new \Exception(implode(', ', $errors));
            }

            // Update data
            $updated = $this->model->update($id, $data);
            if (!$updated) {
                throw new \Exception('Failed to update branch');
            }

            // Get updated branch data
            $branch = $this->model->find($id);
            if (!$branch) {
                throw new \Exception('Failed to retrieve updated branch');
            }

            wp_send_json_success([
                'message' => __('Branch updated successfully', 'wp-customer'),
                'branch' => $branch
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function show() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid branch ID');
            }

            $branch = $this->model->find($id);
            if (!$branch) {
                throw new \Exception('Branch not found');
            }

            // Add permission check
            if (!current_user_can('view_branch_detail') &&
                (!current_user_can('view_own_branch') || $branch->created_by !== get_current_user_id())) {
                throw new \Exception('You do not have permission to view this branch');
            }

            wp_send_json_success([
                'branch' => $branch
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function delete() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid branch ID');
            }

            // Validate delete operation
            $errors = $this->validator->validateDelete($id);
            if (!empty($errors)) {
                throw new \Exception(reset($errors));
            }

            // Perform delete
            if (!$this->model->delete($id)) {
                throw new \Exception('Failed to delete branch');
            }

            wp_send_json_success([
                'message' => __('Data Cabang berhasil dihapus', 'wp-customer')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function getCustomerBranches() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
            if (!$customer_id) {
                throw new \Exception('ID Customer tidak valid');
            }

            // Periksa permission
            if (!current_user_can('view_branch_list') && !current_user_can('view_own_branch')) {
                throw new \Exception('Anda tidak memiliki akses untuk melihat data cabang');
            }

            $branches = $this->model->getByCustomer($customer_id);

            wp_send_json_success($branches);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

}

