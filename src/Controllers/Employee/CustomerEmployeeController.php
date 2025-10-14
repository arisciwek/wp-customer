<?php
/**
 * Customer Employee Controller Class
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Employee
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Employee/CustomerEmployeeController.php
 *
 * Description: Controller untuk mengelola data karyawan customer.
 *              Menangani operasi CRUD dengan integrasi cache.
 *              Includes validasi input, permission checks,
 *              dan response formatting untuk panel kanan.
 *              Menyediakan endpoints untuk DataTables server-side.
 *
 * Changelog:
 * 1.0.0 - 2024-01-12
 * - Initial release
 * - Added CRUD operations
 * - Added DataTable integration
 * - Added permission handling
 */

namespace WPCustomer\Controllers\Employee;

use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Models\Employee\CustomerEmployeeModel;
use WPCustomer\Validators\Employee\CustomerEmployeeValidator;
use WPCustomer\Cache\CustomerCacheManager;

class CustomerEmployeeController {
    private CustomerModel $customerModel;
    private CustomerEmployeeModel $model;
    private CustomerEmployeeValidator $validator;
    private CustomerCacheManager $cache;
    private string $log_file;

    /**
     * Default log file path
     */
    private const DEFAULT_LOG_FILE = 'logs/employee.log';

    public function __construct() {
        $this->model = new CustomerEmployeeModel();
        $this->customerModel = new CustomerModel();
        $this->validator = new CustomerEmployeeValidator();
        $this->cache = new CustomerCacheManager();

        // Initialize log file in plugin directory
        $this->log_file = WP_CUSTOMER_PATH . self::DEFAULT_LOG_FILE;

        // Ensure logs directory exists
        $this->initLogDirectory();

        // Register AJAX endpoints
        add_action('wp_ajax_handle_customer_employee_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_get_customer_employee', [$this, 'show']);
        add_action('wp_ajax_create_customer_employee', [$this, 'store']);
        add_action('wp_ajax_update_customer_employee', [$this, 'update']);
        add_action('wp_ajax_delete_customer_employee', [$this, 'delete']);
        add_action('wp_ajax_change_customer_employee_status', [$this, 'changeStatus']);
        add_action('wp_ajax_create_customer_employee_button', [$this, 'createEmployeeButton']);

        // Hook untuk menampilkan field tambahan di profil user
        add_action('show_user_profile', [$this, 'showProfileExtras']);
        add_action('edit_user_profile', [$this, 'showProfileExtras']);        
    }

    /**
     * Initialize log directory if it doesn't exist
     */
    private function initLogDirectory(): void {
        // Get WordPress uploads directory information
        $upload_dir = wp_upload_dir();
        $customer_base_dir = $upload_dir['basedir'] . '/wp-customer';
        $customer_log_dir = $customer_base_dir . '/logs';
        
        // Update log file path with monthly rotation
        $this->log_file = $customer_log_dir . '/employee-' . date('Y-m') . '.log';

        // Create directories if needed
        if (!file_exists($customer_base_dir)) {
            wp_mkdir_p($customer_base_dir);
        }

        if (!file_exists($customer_log_dir)) {
            wp_mkdir_p($customer_log_dir);
        }

        // Create log file if it doesn't exist
        if (!file_exists($this->log_file)) {
            touch($this->log_file);
            chmod($this->log_file, 0644);
        }
    }

    /**
     * Log debug messages
     */
    private function debug_log($message): void {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $timestamp = current_time('mysql');
        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }

        $log_message = "[{$timestamp}] {$message}\n";
        error_log($log_message, 3, $this->log_file);
    }

    /**
     * Handle DataTable AJAX request (cache handled in Model)
     */

	public function handleDataTableRequest() {
	    try {
		if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
		    wp_send_json_error(['message' => 'Security check failed']);
		    return;
		}

		// Get and validate parameters
		$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
		$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
		$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
		$search = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';
		$customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
		
		if (!$customer_id) {
		    throw new \Exception('Customer ID is required');
		}

		$orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
		$orderDir = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'asc';

		// Map numeric column index to column name
		$columns = ['name', 'department', 'branch_name', 'status', 'actions'];
		$orderColumnName = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'name';

		// Get data from model (cache handled in model)
		$result = $this->model->getDataTableData(
		    $customer_id,
		    $start,
		    $length,
		    $search,
		    $orderColumnName,
		    $orderDir
		);

		if (!$result) {
		    throw new \Exception('No data returned from model');
		}

		// Format data with validation
		$data = [];
		foreach ($result['data'] as $employee) {
		    // Get customer for permission check
		    $customer = $this->customerModel->find($employee->customer_id);
		    if (!$this->validator->canViewEmployee($employee, $customer)) {
		        continue;
		    }

		    $data[] = [
		        'id' => $employee->id,
		        'name' => esc_html($employee->name),
		        'position' => esc_html($employee->position),
		        'department' => $this->generateDepartmentsBadges([
		            'finance' => (bool)$employee->finance,
		            'operation' => (bool)$employee->operation,
		            'legal' => (bool)$employee->legal,
		            'purchase' => (bool)$employee->purchase
		        ]),
		        'email' => esc_html($employee->email),
		        'branch_name' => esc_html($employee->branch_name),
		        'status' => $employee->status,
		        'actions' => $this->generateActionButtons($employee)
		    ];
		}

		$response = [
		    'draw' => $draw,
		    'recordsTotal' => $result['total'],
		    'recordsFiltered' => $result['filtered'],
		    'data' => $data
		];

		wp_send_json($response);

	    } catch (\Exception $e) {
		wp_send_json_error([
		    'message' => $e->getMessage()
		], 400);
	    }
	}
    /**
     * Generate HTML for department badges
     */
    private function generateDepartmentsBadges(array $departments): string {
        // Check if any department is true
        $has_departments = array_filter($departments);
        if (empty($has_departments)) {
            return '<div class="department-badges-container empty">-</div>';
        }

        $badges = [];
        foreach ($departments as $dept => $active) {
            if ($active) {
                $label = ucfirst($dept); // Convert finance to Finance, etc.
                $badges[] = sprintf(
                    '<span class="department-badge %s">%s</span>',
                    esc_attr($dept),
                    esc_html($label)
                );
            }
        }

        return sprintf(
            '<div class="department-badges-container">%s</div>',
            implode('', $badges)
        );
    }

    /**
     * Generate HTML for status badge
     */
    private function generateStatusBadge(string $status): string {
        $label = $status === 'active' ? __('Aktif', 'wp-customer') : __('Non-aktif', 'wp-customer');
        return sprintf(
            '<span class="status-badge status-%s">%s</span>',
            esc_attr($status),
            esc_html($label)
        );
    }

    public function createEmployeeButton() {
        try {
            if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
                wp_send_json_error(['message' => 'Security check failed']);
                return;
            }

            $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
            $branch_id = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : 0;

            if (!$customer_id) {
                throw new \Exception('ID Customer tidak valid');
            }

            $validator = new CustomerEmployeeValidator();
            $canCreate = $validator->canCreateEmployee($customer_id, $branch_id);

            if ($canCreate) {
                $button = '<button type="button" class="button button-primary" id="add-employee-btn">';
                $button .= '<span class="dashicons dashicons-plus-alt"></span>';
                $button .= __('Tambah Karyawan', 'wp-customer');
                $button .= '</button>';
            } else {
                $button = '';
            }

            wp_send_json_success([
                'button' => $button
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generate action buttons HTML
     */
    private function generateActionButtons($employee) {
        $actions = '';
        $current_user_id = get_current_user_id();
        
        // Get customer untuk validasi
        $customer = $this->customerModel->find($employee->customer_id);
        if (!$customer) return $actions;

        // View Button
        if ($this->validator->canViewEmployee($employee, $customer)) {
            $actions .= sprintf(
                '<button type="button" class="button view-employee" data-id="%d" title="%s">
                    <i class="dashicons dashicons-visibility"></i>
                </button> ',
                $employee->id,
                __('Lihat', 'wp-customer')
            );
        }

        // Edit Button
        if ($this->validator->canEditEmployee($employee, $customer)) {
            $actions .= sprintf(
                '<button type="button" class="button edit-employee" data-id="%d" title="%s">
                    <i class="dashicons dashicons-edit"></i>
                </button> ',
                $employee->id,
                __('Edit', 'wp-customer')
            );
        }

        // Delete Button
        if ($this->validator->canDeleteEmployee($employee, $customer)) {
            $actions .= sprintf(
                '<button type="button" class="button delete-employee" data-id="%d" title="%s">
                    <i class="dashicons dashicons-trash"></i>
                </button>',
                $employee->id,
                __('Hapus', 'wp-customer')
            );
        }

        // Status Toggle Button
        if ($this->validator->canEditEmployee($employee, $customer)) {
            $newStatus = $employee->status === 'active' ? 'inactive' : 'active';
            $statusTitle = $employee->status === 'active' ? 
                __('Nonaktifkan', 'wp-customer') : 
                __('Aktifkan', 'wp-customer');
            $statusIcon = $employee->status === 'active' ? 'remove' : 'yes';
            
            $actions .= sprintf(
                '<button type="button" class="button toggle-status" data-id="%d" data-status="%s" title="%s">
                    <i class="dashicons dashicons-%s"></i>
                </button>',
                $employee->id,
                $newStatus,
                $statusTitle,
                $statusIcon
            );
        }

        return $actions;

    }
    /**
     * Show employee dengan cache
     */
    public function show() {
       try {
           error_log('WP Customer Employee Debug - show called for ID: ' . ($_POST['id'] ?? 'not set'));

           if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
               wp_send_json_error(['message' => 'Security check failed']);
               return;
           }

           $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
           if (!$id) throw new \Exception('Invalid employee ID');

            $employee = $this->model->find($id);
            if (!$employee) throw new \Exception('Employee not found');

            $customer = $this->customerModel->find($employee->customer_id);
            if (!$customer) throw new \Exception('Customer not found');

            // Tambahkan pengecekan permission
            $canView = $this->validator->canViewEmployee($employee, $customer);
            error_log('WP Customer Employee Debug - canViewEmployee result: ' . ($canView ? 'true' : 'false'));

            if (!$canView) {
                throw new \Exception('Anda tidak memiliki izin untuk melihat detail karyawan ini.');
            }

           // Validate view permission
           $errors = $this->validator->validateView($id);
           if (!empty($errors)) {
               throw new \Exception(reset($errors));
           }

           // Check cache
           $employee = $this->cache->get("employee_{$id}");
           if (!$employee) {
               $employee = $this->model->find($id);
               if (!$employee) throw new \Exception('Employee not found');
               $this->cache->set("employee_{$id}", $employee);
           }

           error_log('WP Customer Employee Debug - show success for employee ID: ' . $id);
           wp_send_json_success($employee);

       } catch (\Exception $e) {
           error_log('WP Customer Employee Debug - show error: ' . $e->getMessage());
           wp_send_json_error(['message' => $e->getMessage()]);
       }
    }

    /**
     * Store employee (cache invalidation handled by Model)
     */
    public function store() {
       try {
           if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
               wp_send_json_error(['message' => 'Security check failed']);
               return;
           }

           $data = [
               'customer_id' => isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0,
               'branch_id' => isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : 0,
               'name' => sanitize_text_field($_POST['name'] ?? ''),
               'position' => sanitize_text_field($_POST['position'] ?? ''),
               'finance' => isset($_POST['finance']) && $_POST['finance'] === "1",
               'operation' => isset($_POST['operation']) && $_POST['operation'] === "1", 
               'legal' => isset($_POST['legal']) && $_POST['legal'] === "1",
               'purchase' => isset($_POST['purchase']) && $_POST['purchase'] === "1",
               'keterangan' => sanitize_text_field($_POST['keterangan'] ?? ''),
               'email' => sanitize_email($_POST['email'] ?? ''),
               'phone' => sanitize_text_field($_POST['phone'] ?? ''),
               'status' => isset($_POST['status']) && in_array($_POST['status'], ['active', 'inactive']) 
                   ? $_POST['status'] 
                   : 'active'
           ];

            if (!$this->validator->canCreateEmployee($data['customer_id'], $data['branch_id'])) {
                throw new \Exception('Anda tidak memiliki izin untuk menambah karyawan.');
            }

           $errors = $this->validator->validateCreate($data);
           if (!empty($errors)) throw new \Exception(implode(', ', $errors));

           $user_data = [
               'user_login' => strstr($data['email'], '@', true) ?: sanitize_user(strtolower(str_replace(' ', '', $data['name']))),
               'user_email' => $data['email'],
               'first_name' => explode(' ', $data['name'], 2)[0],
               'last_name' => explode(' ', $data['name'], 2)[1] ?? '',
               'user_pass' => wp_generate_password(),
               'role' => 'customer'
           ];

           $user_id = wp_insert_user($user_data);
           if (is_wp_error($user_id)) throw new \Exception($user_id->get_error_message());

           $data['user_id'] = $user_id;
           $id = $this->model->create($data);
           if (!$id) {
               wp_delete_user($user_id);
               throw new \Exception('Failed to create employee');
           }

           wp_new_user_notification($user_id, null, 'user');

           // Cache invalidation now handled by Model
           $employee = $this->model->find($id);
           wp_send_json_success([
               'message' => __('Karyawan berhasil ditambahkan dan email aktivasi telah dikirim', 'wp-customer'),
               'employee' => $employee
           ]);

       } catch (\Exception $e) {
           wp_send_json_error(['message' => $e->getMessage()]);
       }
    }


    /**
     * Update employee (cache invalidation handled by Model)
     */
    public function update() {
       try {
           if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
               wp_send_json_error(['message' => 'Security check failed']);
               return;
           }

           $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
           if (!$id) throw new \Exception('Invalid employee ID');

            $employee = $this->model->find($id);
            if (!$employee) throw new \Exception('Employee not found');

            $customer = $this->customerModel->find($employee->customer_id);
            if (!$customer) throw new \Exception('Customer not found');

            if (!$this->validator->canEditEmployee($employee, $customer)) {
                throw new \Exception('Anda tidak memiliki izin untuk mengedit karyawan ini.');
            }

           $data = [
               'name' => sanitize_text_field($_POST['name'] ?? ''),
               'position' => sanitize_text_field($_POST['position'] ?? ''),
               'email' => sanitize_email($_POST['email'] ?? ''),
               'phone' => sanitize_text_field($_POST['phone'] ?? ''),
               'branch_id' => isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : 0,
               'finance' => isset($_POST['finance']) && $_POST['finance'] === "1",
               'operation' => isset($_POST['operation']) && $_POST['operation'] === "1",
               'legal' => isset($_POST['legal']) && $_POST['legal'] === "1",
               'purchase' => isset($_POST['purchase']) && $_POST['purchase'] === "1",
               'keterangan' => sanitize_text_field($_POST['keterangan'] ?? ''),
               'status' => isset($_POST['status']) && in_array($_POST['status'], ['active', 'inactive']) 
                   ? $_POST['status'] 
                   : 'active'
           ];

           $errors = $this->validator->validateUpdate($data, $id);
           if (!empty($errors)) throw new \Exception(implode(', ', $errors));

           if (!$this->model->update($id, $data)) {
               throw new \Exception('Failed to update employee');
           }

           // Cache invalidation now handled by Model
           $employee = $this->model->find($id);

           wp_send_json_success([
               'message' => __('Data karyawan berhasil diperbarui', 'wp-customer'),
               'employee' => $employee
           ]);

       } catch (\Exception $e) {
           wp_send_json_error(['message' => $e->getMessage()]);
       }
    }

    /**
     * Delete employee (cache invalidation handled by Model)
     */
    public function delete() {
       try {
           if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
               wp_send_json_error(['message' => 'Security check failed']);
               return;
           }

           $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

           if (!$id) throw new \Exception('Invalid employee ID');

            $employee = $this->model->find($id);
            if (!$employee) throw new \Exception('Employee not found');

            $customer = $this->customerModel->find($employee->customer_id);
            if (!$customer) throw new \Exception('Customer not found');

            if (!$this->validator->canDeleteEmployee($employee, $customer)) {
                throw new \Exception('Anda tidak memiliki izin untuk menghapus karyawan ini.');
            }

           $errors = $this->validator->validateDelete($id);
           if (!empty($errors)) throw new \Exception(reset($errors));

           // Store customer_id before deletion for cache invalidation
           $customer_id = $employee->customer_id;

           if (!$this->model->delete($id)) {
               throw new \Exception('Failed to delete employee');
           }

           // Cache invalidation now handled by Model
           wp_send_json_success([
               'message' => __('Karyawan berhasil dihapus', 'wp-customer')
           ]);

       } catch (\Exception $e) {
           wp_send_json_error(['message' => $e->getMessage()]);
       }
    }

    /**
     * Change employee status (cache invalidation handled by Model)
     */
    public function changeStatus() {
       try {
           if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
               wp_send_json_error(['message' => 'Security check failed']);
               return;
           }

           $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
           if (!$id) throw new \Exception('Invalid employee ID');

            $employee = $this->model->find($id);
            if (!$employee) throw new \Exception('Employee not found');

            $customer = $this->customerModel->find($employee->customer_id);
            if (!$customer) throw new \Exception('Customer not found');

            if (!$this->validator->canEditEmployee($employee, $customer)) {
                throw new \Exception('Anda tidak memiliki izin untuk mengubah status karyawan ini.');
            }

           $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
           if (!in_array($status, ['active', 'inactive'])) {
               throw new \Exception('Invalid status');
           }

           $employee = $this->model->find($id);
           if (!$employee) throw new \Exception('Employee not found');

           if (!$this->model->changeStatus($id, $status)) {
               throw new \Exception('Failed to update employee status');
           }

           // Cache invalidation now handled by Model
           $employee = $this->model->find($id);
           wp_send_json_success([
               'message' => __('Status karyawan berhasil diperbarui', 'wp-customer'),
               'employee' => $employee
           ]);

       } catch (\Exception $e) {
           wp_send_json_error(['message' => $e->getMessage()]);
       }
    }
    
    /**
     * Khusus untuk membuat demo data employee
     */
    public function createDemoEmployee(array $data): ?int {
        try {
            // Debug log
            $this->debug_log('Creating demo employee: ' . print_r($data, true));

            // Buat employee via model
            $employee_id = $this->model->create($data);
            
            if (!$employee_id) {
                throw new \Exception('Gagal membuat demo employee');
            }

            // Clear semua cache yang terkait
            $this->cache->delete('employee', $employee_id);
            $this->cache->delete('employee_total_count', get_current_user_id());
            
            // Cache untuk relasi dengan customer
            $this->cache->delete('customer_employee', $data['customer_id']);
            $this->cache->delete('customer_employee_list', $data['customer_id']);
            
            // Cache untuk relasi dengan branch
            $this->cache->delete('branch_employee', $data['branch_id']);
            $this->cache->delete('branch_employee_list', $data['branch_id']);
            
            // Invalidate DataTable cache
            $this->cache->invalidateDataTableCache('customer_employee_list', [
                'customer_id' => $data['customer_id']
            ]);

            // Invalidate cache customer dan branch
            $this->cache->invalidateCustomerCache($data['customer_id']);
            $this->cache->invalidateDataTableCache('branch_list', [
                'customer_id' => $data['customer_id']
            ]);

            return $employee_id;

        } catch (\Exception $e) {
            $this->debug_log('Error creating demo employee: ' . $e->getMessage());
            throw $e;
        }
    }



    public function showProfileExtras($user) {
        $employeeData = $this->model->getByUserId($user->ID);

        // Debug ke error_log
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log('=== [CustomerEmployeeController] showProfileExtras() ===');
            error_log('User ID: ' . $user->ID);
            error_log('Employee Data: ' . print_r($employeeData, true));
        }

        $user_roles = $user->roles;
        $user_capabilities = array_keys(array_filter($user->allcaps));

        require_once WP_CUSTOMER_PATH . 'src/Views/templates/customer-employee/partials/_customer_employee_profile_fields.php';
    }    
}
