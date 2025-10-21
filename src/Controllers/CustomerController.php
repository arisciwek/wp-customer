<?php
/**
* Customer Controller Class
*
* @package     WP_Customer
* @subpackage  Controllers
* @version     1.0.2
* @author      arisciwek
*
* Path: /wp-customer/src/Controllers/CustomerController.php
*
* Description: Controller untuk mengelola data customer.
*              Menangani operasi CRUD dengan integrasi cache.
*              Includes validasi input, permission checks,
*              dan response formatting untuk panel kanan.
*              Menyediakan endpoints untuk DataTables server-side.
*
* Changelog:
* 1.0.2 - 2025-01-21
* - Added username field to admin create form (instead of auto-generate from email)
* - Admin can now input friendly username (e.g., "test dua" instead of "test_02")
* - Password still auto-generated and displayed (Option B)
* - Consistent with public register form (both have username field)
* - Improved UX: more control over username creation
*
* 1.0.1 - 2024-12-08
* - Added view_own_customer permission check in show method
* - Enhanced permission validation
* - Improved error handling for permission checks
*
* 1.0.0 - 2024-12-03 14:30:00
* - Refactor CRUD responses untuk panel kanan
* - Added cache integration di semua endpoints
* - Added konsisten response format
* - Added validasi dan permission di semua endpoints
* - Improved error handling dan feedback
*/

namespace WPCustomer\Controllers;

use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Models\Branch\BranchModel;
use WPCustomer\Models\Employee\CustomerEmployeeModel;
use WPCustomer\Validators\CustomerValidator;
use WPCustomer\Cache\CustomerCacheManager;

class CustomerController {
    private $error_messages;
    private CustomerModel $model;
    private CustomerValidator $validator;
    private CustomerCacheManager $cache;
    private BranchModel $branchModel;
    private CustomerEmployeeModel $employeeModel;

    private string $log_file;

    private function logPermissionCheck($action, $user_id, $customer_id, $result, $branch_id = null) {
        // $this->debug_log(sprintf(
        //    'Permission check for %s - User: %d, Customer: %d, Branch: %s, Result: %s',
        //    $action,
        //    $user_id,
        //    $customer_id,
        //    $branch_id ?? 'none',  // Gunakan null coalescing untuk handle null branch_id
        //    $result ? 'granted' : 'denied'
        // ));
    }

    /**
     * Default log file path
     */
    private const DEFAULT_LOG_FILE = 'logs/customer.log';

    public function __construct() {
        $this->model = new CustomerModel();
        $this->branchModel = new BranchModel();
        $this->employeeModel = new CustomerEmployeeModel();
        $this->validator = new CustomerValidator();
        $this->cache = new CustomerCacheManager();

        // Inisialisasi error messages
        $this->error_messages = [
            'insufficient_permissions' => __('Anda tidak memiliki izin untuk melakukan operasi ini', 'wp-customer'),
            'view_denied' => __('Anda tidak memiliki izin untuk melihat data ini', 'wp-customer'),
            'edit_denied' => __('Anda tidak memiliki izin untuk mengubah data ini', 'wp-customer'),
            'delete_denied' => __('Anda tidak memiliki izin untuk menghapus data ini', 'wp-customer'),
        ];


        // Inisialisasi log file di dalam direktori plugin
        $this->log_file = WP_CUSTOMER_PATH . self::DEFAULT_LOG_FILE;

        // Pastikan direktori logs ada
        $this->initLogDirectory();

        // Register AJAX handlers
        add_action('wp_ajax_handle_customer_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_nopriv_handle_customer_datatable', [$this, 'handleDataTableRequest']);

        // Register endpoint untuk update
        add_action('wp_ajax_update_customer', [$this, 'update']);

        // Register endpoint lain yang diperlukan
        add_action('wp_ajax_get_customer', [$this, 'show']);
        add_action('wp_ajax_create_customer', [$this, 'store']);
        add_action('wp_ajax_delete_customer', [$this, 'delete']);
        add_action('wp_ajax_validate_customer_access', [$this, 'validateCustomerAccess']);
        //add_action('wp_ajax_get_current_customer_id', [$this, 'getCurrentCustomerId']);
        add_action('wp_ajax_generate_customer_pdf', [$this, 'generate_customer_pdf']);
        add_action('wp_ajax_generate_wp_docgen_customer_detail_document', [$this, 'generate_wp_docgen_customer_detail_document']);
        add_action('wp_ajax_generate_wp_docgen_customer_detail_pdf', [$this, 'generate_wp_docgen_customer_detail_pdf']);
        add_action('wp_ajax_create_customer_button', [$this, 'createCustomerButton']);

        add_action('wp_ajax_create_customer_pdf_button', [$this, 'createPdfButton']);
        add_action('wp_ajax_get_customer_stats', [$this, 'getStats']);

        // Debug cache di folder uploads 
        /*
        $upload_dir = wp_upload_dir();
        $debug_file = $upload_dir['basedir'] . '/wp-customer/cache-debug.txt';

        // Capture cache state
        global $wp_object_cache;
        $cache_data = print_r($wp_object_cache->cache, true);

        // Append with timestamp
        $debug_content = "[" . date('Y-m-d H:i:s') . "]\n";
        $debug_content .= $cache_data . "\n\n";

        // Save to file
        file_put_contents($debug_file, $debug_content, FILE_APPEND);
        */

    }

public function createPdfButton() {
    try {
        check_ajax_referer('wp_customer_nonce', 'nonce');
        
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if (!$id) {
            throw new \Exception('Invalid customer ID');
        }

        // Tambahkan logging untuk debug
        $access = $this->validator->validateAccess($id);
        error_log('PDF Button Access Check - User: ' . get_current_user_id());
        error_log('Access Result: ' . print_r($access, true));

        if (!$access['has_access']) {
            wp_send_json_success(['button' => '']);
            return;
        }

        $button = '<button type="button" class="button wp-mpdf-customer-detail-export-pdf">';
        $button .= '<span class="dashicons dashicons-pdf"></span>';
        $button .= __('Generate PDF', 'wp-customer');
        $button .= '</button>';

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
     * Generate DOCX document
     */
    public function generate_wp_docgen_customer_detail_document() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');
            
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid customer ID');
            }

            // Validate access
            $access = $this->validator->validateAccess($id);
            if (!$access['has_access']) {
                throw new \Exception('You do not have permission to view this customer');
            }

            // Get customer data
            $customer = $this->model->find($id);
            if (!$customer) {
                throw new \Exception('Customer not found');
            }

            // Initialize WP DocGen
            //$docgen = new \WPDocGen\Generator();
            $docgen = wp_docgen();

            // Set template variables
            $variables = [
                'customer_name' => $customer->name,
                'customer_code' => $customer->code,
                'total_branches' => $customer->branch_count,
                'created_date' => date('d F Y H:i', strtotime($customer->created_at)),
                'updated_date' => date('d F Y H:i', strtotime($customer->updated_at)),
                'npwp' => $customer->npwp ?? '-',
                'nib' => $customer->nib ?? '-',
                'generated_date' => date('d F Y H:i')
            ];

            // Get template path
            $template_path = WP_CUSTOMER_PATH . 'templates/docx/customer-detail.docx';

            // Generate DOCX
            $output_path = wp_upload_dir()['path'] . '/customer-' . $customer->code . '.docx';
            $docgen->generateFromTemplate($template_path, $variables, $output_path);

            // Prepare download response
            $file_url = wp_upload_dir()['url'] . '/customer-' . $customer->code . '.docx';
            wp_send_json_success([
                'file_url' => $file_url,
                'filename' => 'customer-' . $customer->code . '.docx'
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Generate PDF from DOCX
     */
    public function generate_wp_docgen_customer_detail_pdf() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');
            
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid customer ID');
            }

            // Similar validation as DOCX generation
            $access = $this->validator->validateAccess($id);
            if (!$access['has_access']) {
                throw new \Exception('You do not have permission to view this customer');
            }

            $customer = $this->model->find($id);
            if (!$customer) {
                throw new \Exception('Customer not found');
            }

            // Initialize WP DocGen
            $docgen = new \WPDocGen\Generator();

            // Generate DOCX first (similar to generate_wp_docgen_customer_detail_document)
            $variables = [
                'customer_name' => $customer->name,
                'customer_code' => $customer->code,
                'total_branches' => $customer->branch_count,
                'created_date' => date('d F Y H:i', strtotime($customer->created_at)),
                'updated_date' => date('d F Y H:i', strtotime($customer->updated_at)),
                'npwp' => $customer->npwp ?? '-',
                'nib' => $customer->nib ?? '-',
                'generated_date' => date('d F Y H:i')
            ];

            $template_path = WP_CUSTOMER_PATH . 'templates/docx/customer-detail.docx';
            $docx_path = wp_upload_dir()['path'] . '/customer-' . $customer->code . '.docx';
            
            // Generate DOCX first
            $docgen->generateFromTemplate($template_path, $variables, $docx_path);

            // Convert DOCX to PDF
            $pdf_path = wp_upload_dir()['path'] . '/customer-' . $customer->code . '.pdf';
            $docgen->convertToPDF($docx_path, $pdf_path);

            // Clean up DOCX file
            unlink($docx_path);

            // Send PDF URL back
            $pdf_url = wp_upload_dir()['url'] . '/customer-' . $customer->code . '.pdf';
            wp_send_json_success([
                'file_url' => $pdf_url,
                'filename' => 'customer-' . $customer->code . '.pdf'
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function createCustomerButton() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');
            
            if (!current_user_can('add_customer')) {
                wp_send_json_success(['button' => '']);
                return;
            }

            $button = '<button type="button" class="button button-primary" id="add-customer-btn">';
            $button .= '<span class="dashicons dashicons-plus-alt"></span>';
            $button .= __('Tambah Customer', 'wp-customer');
            $button .= '</button>';

            wp_send_json_success([
                'button' => $button
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function generate_customer_pdf() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');
            
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid customer ID');
            }

            // Cek akses
            $access = $this->validator->validateAccess($id);
            if (!$access['has_access']) {
                throw new \Exception('You do not have permission to view this customer');
            }

            // Load wp-mpdf jika ada
            if (!function_exists('wp_mpdf_load')) {
                throw new \Exception('PDF generator plugin tidak ditemukan');
            }

            if (!wp_mpdf_load()) {
                throw new \Exception('Gagal memuat PDF generator plugin');
            }

            if (!wp_mpdf_init()) {
                throw new \Exception('Gagal menginisialisasi PDF generator');
            }
            
            // Ambil data customer
            $customer = $this->model->find($id);
            if (!$customer) {
                throw new \Exception('Customer not found');
            }

            // Generate PDF menggunakan WP mPDF
            ob_start();
            include WP_CUSTOMER_PATH . 'src/Views/templates/customer/pdf/customer-detail-pdf.php';
            $html = ob_get_clean();

            $mpdf = wp_mpdf()->generate_pdf($html, [
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 16,
                'margin_bottom' => 16
            ]);

            // Output PDF untuk download
            $mpdf->Output('customer-' . $customer->code . '.pdf', \Mpdf\Output\Destination::DOWNLOAD);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => 'pdf_generation_error'
            ]);
        }
    }
    /**
     * Validate customer access - public endpoint untuk AJAX
     * @since 1.0.0
     */
    public function validateCustomerAccess() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $customer_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$customer_id) {
                throw new \Exception('Invalid customer ID');
            }

            // Gunakan validator langsung
            $access = $this->validator->validateAccess($customer_id);
            
            if (!$access['has_access']) {
                wp_send_json_error([
                    'message' => __('Anda tidak memiliki akses ke customer ini', 'wp-customer'),
                    'code' => 'access_denied'
                ]);
                return;
            }

            wp_send_json_success([
                'message' => 'Akses diberikan',
                'customer_id' => $customer_id,
                'access_type' => $access['access_type']
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => 'error'
            ]);
        }
    }

    /**
     * Initialize log directory if it doesn't exist
     */
    private function initLogDirectory(): void {
        // Get WordPress uploads directory information
        $upload_dir = wp_upload_dir();
        $customer_base_dir = $upload_dir['basedir'] . '/wp-customer';
        $customer_log_dir = $customer_base_dir . '/logs';
        
        // Update log file path with monthly rotation format
        $this->log_file = $customer_log_dir . '/customer-' . date('Y-m') . '.log';

        // Create base wp-customer directory if it doesn't exist
        if (!file_exists($customer_base_dir)) {
            if (!wp_mkdir_p($customer_base_dir)) {
                $this->log_file = rtrim(sys_get_temp_dir(), '/') . '/wp-customer.log';
                error_log('Failed to create base directory in uploads: ' . $customer_base_dir);
                return;
            }
            
            // Add .htaccess to base directory
            $base_htaccess_content = "# Protect Directory\n";
            $base_htaccess_content .= "<FilesMatch \"^.*$\">\n";
            $base_htaccess_content .= "Order Deny,Allow\n";
            $base_htaccess_content .= "Deny from all\n";
            $base_htaccess_content .= "</FilesMatch>\n";
            $base_htaccess_content .= "\n";
            $base_htaccess_content .= "# Allow specific file types if needed\n";
            $base_htaccess_content .= "<FilesMatch \"\.(jpg|jpeg|png|gif|css|js)$\">\n";
            $base_htaccess_content .= "Order Allow,Deny\n";
            $base_htaccess_content .= "Allow from all\n";
            $base_htaccess_content .= "</FilesMatch>";
            
            @file_put_contents($customer_base_dir . '/.htaccess', $base_htaccess_content);
            @chmod($customer_base_dir, 0755);
        }

        // Create logs directory if it doesn't exist
        if (!file_exists($customer_log_dir)) {
            if (!wp_mkdir_p($customer_log_dir)) {
                $this->log_file = rtrim(sys_get_temp_dir(), '/') . '/wp-customer.log';
                error_log('Failed to create log directory in uploads: ' . $customer_log_dir);
                return;
            }

            // Add .htaccess to logs directory with strict rules
            $logs_htaccess_content = "# Deny access to all files\n";
            $logs_htaccess_content .= "Order deny,allow\n";
            $logs_htaccess_content .= "Deny from all\n\n";
            $logs_htaccess_content .= "# Deny access to log files specifically\n";
            $logs_htaccess_content .= "<Files ~ \"\.log$\">\n";
            $logs_htaccess_content .= "Order allow,deny\n";
            $logs_htaccess_content .= "Deny from all\n";
            $logs_htaccess_content .= "</Files>\n\n";
            $logs_htaccess_content .= "# Extra protection\n";
            $logs_htaccess_content .= "<IfModule mod_php.c>\n";
            $logs_htaccess_content .= "php_flag engine off\n";
            $logs_htaccess_content .= "</IfModule>";
            
            @file_put_contents($customer_log_dir . '/.htaccess', $logs_htaccess_content);
            @chmod($customer_log_dir, 0755);
        }

        // Create log file if it doesn't exist
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
     * Log debug messages ke file
     *
     * @param mixed $message Pesan yang akan dilog
     * @return void
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

        // Gunakan error_log bawaan WordPress dengan custom log file
        error_log($log_message, 3, $this->log_file);
    }

    private function getCustomerTableData($start = 0, $length = 10, $search = '', $orderColumn = 'code', $orderDir = 'asc') {
        try {
            // Validasi permission yang sudah bekerja di handleDataTableRequest()
            $hasPermission = current_user_can('view_customer_list');
            $this->logPermissionCheck(
                'view_customer_list',
                get_current_user_id(),
                0,
                null,
                $hasPermission
            );

            if (!$hasPermission) {
                return null;
            }

            // Get data using model
            $result = $this->model->getDataTableData($start, $length, $search, $orderColumn, $orderDir);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->debug_log('Error getting customer table data: ' . $e->getMessage());
            return null;
        }
    }

    // Untuk AJAX request
    public function handleDataTableRequest() {
        try {
            // Verify nonce
            if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
                throw new \Exception('Security check failed');
            }

            // Get parameters with safe defaults
            $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
            $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
            $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
            $search = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';
            
            // Get order parameters
            $orderColumn = isset($_POST['order'][0]['column']) && isset($_POST['columns'][$_POST['order'][0]['column']]['data']) 
                ? sanitize_text_field($_POST['columns'][$_POST['order'][0]['column']]['data'])
                : 'name';
            $orderDir = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'asc';

            // Additional parameters if needed
            $additionalParams = [];
            
            // If filtering by specific parameters
            if (isset($_POST['status'])) {
                $additionalParams['status'] = sanitize_text_field($_POST['status']);
            }
            if (isset($_POST['type'])) {
                $additionalParams['type'] = sanitize_text_field($_POST['type']);
            }

            // Get fresh data if no cache
            $result = $this->getCustomerTableData($start, $length, $search, $orderColumn, $orderDir);
            if (!$result) {
                throw new \Exception('Failed to fetch customer data');
            }

            // Format data for response
            $data = [];
            foreach ($result['data'] as $customer) {
                $data[] = [
                    'id' => $customer->id,
                    'code' => esc_html($customer->code),
                    'name' => esc_html($customer->name),
                    'owner_name' => esc_html($customer->owner_name ?? '-'),
                    'branch_count' => intval($customer->branch_count),
                    'actions' => $this->generateActionButtons($customer)
                ];
            }

            $response = [
                'draw' => $draw,
                'recordsTotal' => $result['total'],
                'recordsFiltered' => $result['filtered'],
                'data' => $data,
            ];

            wp_send_json($response);

        } catch (\Exception $e) {
            $this->debug_log('DataTable error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generate action buttons untuk DataTable row
     * 
     * @param object $customer Data customer dari row
     * @return string HTML button actions
     */
    private function generateActionButtons($customer) {
        $actions = '';
        
        // Debug logging
        // $this->debug_log("==== Generating Action Buttons for Customer ID: {$customer->id} ====");
        
        // Dapatkan relasi user dengan customer ini
        $relation = $this->validator->getUserRelation($customer->id);
        
        // Log relasi untuk debugging
        // $this->debug_log("User Relation for buttons:", $relation);

        // View Button - selalu tampilkan jika punya akses view
        if ($this->validator->canView($relation)) {
            $actions .= sprintf(
                '<button type="button" class="button small-button view-customer" data-id="%d">' .
                '<i class="dashicons dashicons-visibility"></i></button> ',
                $customer->id
            );
        }

        // Edit Button - tampilkan jika punya akses edit
        if ($this->validator->canUpdate($relation)) {
            $actions .= sprintf(
                '<button type="button" class="button small-button edit-customer" data-id="%d">' .
                '<i class="dashicons dashicons-edit"></i></button> ',
                $customer->id
            );
        }

        // Delete Button - hanya untuk admin
        if ($this->validator->canDelete($relation)) {
            $actions .= sprintf(
                '<button type="button" class="button small-button delete-customer" data-id="%d">' .
                '<i class="dashicons dashicons-trash"></i></button>',
                $customer->id
            );
        }
        return $actions;
    }

    /**
     * Create customer with WordPress user (Task-2165: Shared method)
     * Can be called from store() or CustomerRegistrationHandler
     *
     * @param array $data Customer data (name, email, npwp, nib, provinsi_id, regency_id, status)
     *                    Optional: username, password (for self-register)
     *                    Optional: user_id (if user already created)
     * @param int|null $created_by User ID who creates (null = self-created)
     * @return array ['customer_id' => X, 'user_id' => Y, 'message' => 'Success']
     * @throws \Exception on failure
     */
    public function createCustomerWithUser(array $data, ?int $created_by = null): array {
        // Validate email
        $email = isset($data['email']) ? sanitize_email($data['email']) : '';
        if (empty($email)) {
            throw new \Exception(__('Email wajib diisi', 'wp-customer'));
        }

        // Track if credentials were auto-generated
        $credentials_generated = false;
        $generated_username = null;
        $generated_password = null;

        // Check if user_id already provided (user already created)
        if (isset($data['user_id']) && $data['user_id']) {
            $user_id = (int)$data['user_id'];
        } else {
            // Check if email already exists
            if (email_exists($email)) {
                throw new \Exception(__('Email sudah terdaftar', 'wp-customer'));
            }

            // Check if username and password provided
            if (isset($data['username']) && !empty($data['username'])) {
                // Username provided (self-register OR admin create with username field)
                $username = sanitize_user($data['username']);

                // Validate username
                if (empty($username)) {
                    throw new \Exception(__('Username tidak valid', 'wp-customer'));
                }

                // Check if username already exists
                $original_username = $username;
                $counter = 1;
                while (username_exists($username)) {
                    $username = $original_username . $counter;
                    $counter++;
                }

                // Check if password provided (self-register) or auto-generate (admin create)
                if (isset($data['password']) && !empty($data['password'])) {
                    $password = $data['password'];
                } else {
                    $password = wp_generate_password(12, true, true);

                    // Mark that credentials were auto-generated (password only)
                    $credentials_generated = true;
                    $generated_username = $username;
                    $generated_password = $password;
                }
            } else {
                // No username provided - should not happen with current forms
                throw new \Exception(__('Username wajib diisi', 'wp-customer'));
            }

            // Create WordPress user
            $user_id = wp_create_user($username, $password, $email);

            if (is_wp_error($user_id)) {
                throw new \Exception($user_id->get_error_message());
            }

            // Set roles
            $user = new \WP_User($user_id);
            $user->set_role('customer');
            $user->add_role('customer_admin');

            // Send notification email
            wp_new_user_notification($user_id, null, 'user');
        }

        // Prepare customer data
        $customer_data = [
            'name' => sanitize_text_field($data['name']),
            'npwp' => isset($data['npwp']) ? sanitize_text_field($data['npwp']) : null,
            'nib' => isset($data['nib']) ? sanitize_text_field($data['nib']) : null,
            'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'active',
            'provinsi_id' => isset($data['provinsi_id']) ? (int)$data['provinsi_id'] : null,
            'regency_id' => isset($data['regency_id']) ? (int)$data['regency_id'] : null,
            'user_id' => $user_id,
            'reg_type' => isset($data['reg_type']) ? sanitize_text_field($data['reg_type']) : ($created_by ? 'by_admin' : 'self'),
            'created_by' => $created_by ?? $user_id // If null, self-created
        ];

        // Validate form data
        $form_errors = $this->validator->validateForm($customer_data);
        if (!empty($form_errors)) {
            // Rollback: delete user if validation fails
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            wp_delete_user($user_id);
            throw new \Exception(implode(', ', $form_errors));
        }

        // Create customer via model (triggers hooks)
        $customer_id = $this->model->create($customer_data);
        if (!$customer_id) {
            // Rollback: delete user if customer creation fails
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            wp_delete_user($user_id);
            throw new \Exception('Failed to create customer');
        }

        $result = [
            'customer_id' => $customer_id,
            'user_id' => $user_id,
            'message' => __('Customer berhasil ditambahkan. Email aktivasi telah dikirim.', 'wp-customer')
        ];

        // Include generated credentials if they were auto-generated
        if ($credentials_generated) {
            $result['credentials_generated'] = true;
            $result['username'] = $generated_username;
            $result['password'] = $generated_password;
        }

        return $result;
    }

    /**
     * Handle customer creation request (AJAX endpoint)
     * Endpoint: wp_ajax_create_customer
     * Task-2165: Refactored to use shared createCustomerWithUser method
     */
    public function store() {
        try {
            error_log('Store method called'); // Debug
            check_ajax_referer('wp_customer_nonce', 'nonce');

            // Check permission
            $permission_errors = $this->validator->validatePermission('create');
            if (!empty($permission_errors)) {
                wp_send_json_error(['message' => reset($permission_errors)]);
                return;
            }

            // Prepare data for shared method
            $data = [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'name' => $_POST['name'] ?? '',
                'npwp' => !empty($_POST['npwp']) ? $this->validator->formatNpwp($_POST['npwp']) : null,
                'nib' => !empty($_POST['nib']) ? $this->validator->formatNib($_POST['nib']) : null,
                'provinsi_id' => $_POST['provinsi_id'] ?? null,
                'regency_id' => $_POST['regency_id'] ?? null,
                'status' => $_POST['status'] ?? 'active'
            ];

            // Call shared method (created_by = current admin)
            $result = $this->createCustomerWithUser($data, get_current_user_id());

            error_log('Created customer ID: ' . $result['customer_id']); // Debug

            // Get customer data for response
            $customer = $this->model->find($result['customer_id']);

            // Prepare response
            $response = [
                'message' => $result['message'],
                'data' => $customer
            ];

            // Include generated credentials if available (Option B)
            if (isset($result['credentials_generated']) && $result['credentials_generated']) {
                $response['credentials'] = [
                    'username' => $result['username'],
                    'password' => $result['password'],
                    'email' => $_POST['email'] ?? ''
                ];
            }

            wp_send_json_success($response);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle customer update request
     * Endpoint: wp_ajax_update_customer
     */
    public function update() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if (!$id) {
                throw new \Exception('Invalid customer ID');
            }

            // 1. Permission validation
            $permission_errors = $this->validator->validatePermission('update', $id);
            if (!empty($permission_errors)) {
                wp_send_json_error([
                    'message' => reset($permission_errors)
                ]);
                return;
            }

            // 2. Prepare update data
            $data = [
                'name' => sanitize_text_field($_POST['name']),
                'npwp' => !empty($_POST['npwp']) ? sanitize_text_field($_POST['npwp']) : null,
                'nib' => !empty($_POST['nib']) ? sanitize_text_field($_POST['nib']) : null,
                'status' => !empty($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active',
                'provinsi_id' => !empty($_POST['provinsi_id']) ? intval($_POST['provinsi_id']) : null,
                'regency_id' => !empty($_POST['regency_id']) ? intval($_POST['regency_id']) : null
            ];

            // Add validation for status field
            if (empty($data['status'])) {
                $data['status'] = 'active'; // Default value
            }

            // Validate status is one of allowed values
            if (!in_array($data['status'], ['active', 'inactive'])) {
                throw new \Exception('Invalid status value');
            }

            // Handle user_id if present and user has permission
            if (isset($_POST['user_id']) && current_user_can('edit_all_customers')) {
                $data['user_id'] = !empty($_POST['user_id']) ? intval($_POST['user_id']) : null;
            }

            // Debug log
            error_log('Update data received: ' . print_r($data, true));
            error_log('Raw POST data: ' . print_r($_POST, true));

            // 3. Form validation
            $form_errors = $this->validator->validateForm($data, $id);
            if (!empty($form_errors)) {
                wp_send_json_error([
                    'message' => implode(', ', $form_errors)
                ]);
                return;
            }

            // 4. Perform update
            $updated = $this->model->update($id, $data);
            if (!$updated) {
                throw new \Exception('Failed to update customer');
            }

            // Clear relevant caches
            $this->cache->invalidateCustomerCache($id);

            // 5. Get updated data for response
            $customer = $this->model->find($id);
            if (!$customer) {
                throw new \Exception('Failed to retrieve updated customer');
            }

            // Get additional data for response
            $branch_count = $this->model->getBranchCount($id);
            $access = $this->validator->validateAccess($id);

            // 6. Return success response with complete data
            wp_send_json_success([
                'message' => __('Customer berhasil diperbarui', 'wp-customer'),
                'data' => [
                    'customer' => array_merge((array)$customer, [
                        'access_type' => $access['access_type'],
                        'has_access' => $access['has_access']
                    ]),
                    'branch_count' => $branch_count,
                    'access_type' => $access['access_type']
                ]
            ]);

        } catch (\Exception $e) {
            $this->debug_log('Update error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function show() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $this->debug_log("=== Start show() ===");

            // Get and validate ID
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid customer ID');
            }

            // Get customer data first (single find() call)
            $customer = $this->model->find($id);
            if (!$customer) {
                throw new \Exception('Customer not found');
            }

            // Validate access (will use cached customer data in getUserRelation)
            $access = $this->validator->validateAccess($id);
            if (!$access['has_access']) {
                throw new \Exception('You do not have permission to view this customer');
            }

            // Get membership data if needed
            $membership = $this->model->getMembershipData($id);

            // Prepare response data
            $response_data = [
                'customer' => $customer,
                'membership' => $membership,
                'access_type' => $access['access_type']
            ];

            $this->debug_log("Sending response: " . print_r($response_data, true));

            wp_send_json_success($response_data);

        } catch (\Exception $e) {
            $this->debug_log("Error in show(): " . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    private function enrichCustomerData($customer, $access, $branch_count) {
        return [
            'customer' => array_merge((array)$customer, [
                'access_type' => $access['access_type'],
                'has_access' => $access['has_access']
            ]),
            'branch_count' => $branch_count,
            'access_type' => $access['access_type']
        ];
    }

    private function logCustomerAccess($data) {
        $this->debug_log('=== Customer Access Debug ===');
        $this->debug_log('Customer ID: ' . $data['customer']['id']);
        $this->debug_log('Access Type: ' . $data['access_type']);
        $this->debug_log('Customer Data: ' . print_r($data['customer'], true));
    }

    public function delete() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid customer ID');
            }

            // Add this check
            if (!current_user_can('delete_customer')) {
                throw new \Exception('Insufficient permissions');
            }

            // Validate delete operation
            $errors = $this->validator->validateDelete($id);
            if (!empty($errors)) {
                throw new \Exception(reset($errors));
            }

            // Perform delete
            if (!$this->model->delete($id)) {
                throw new \Exception('Failed to delete customer');
            }

            // Clear cache
            $this->cache->invalidateCustomerCache($id);

            wp_send_json_success([
                'message' => __('Data Customer berhasil dihapus', 'wp-customer')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function getStats() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            // Get customer_id from query param
            $customer_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

            // Validate access if customer_id provided
            if ($customer_id) {
                $access = $this->validator->validateAccess($customer_id);
                if (!$access['has_access']) {
                    throw new \Exception('You do not have permission to view this customer');
                }
            }

            // Cache key based on customer_id and user access
            $cache_key = 'customer_stats_' . $customer_id . '_' . get_current_user_id();
            $cache_group = 'wp_customer';

            // Try to get from cache
            $stats = wp_cache_get($cache_key, $cache_group);

            if (false === $stats) {
                // Cache miss - get fresh data
                $stats = [
                    'total_customers' => $this->model->getTotalCount(),
                    'total_branches' => $this->branchModel->getTotalCount($customer_id),
                    'total_employees' => $this->employeeModel->getTotalCount($customer_id)
                ];

                // Cache for 5 minutes
                wp_cache_set($cache_key, $stats, $cache_group, 300);
            }

            wp_send_json_success($stats);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function renderMainPage() {
        // Render template
        require_once WP_CUSTOMER_PATH . 'src/Views/templates/customer-dashboard.php';
    }    
}
