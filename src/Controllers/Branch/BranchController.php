<?php
/**
 * Branch Controller Class
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Branch
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Branch/BranchController.php
 *
 * Description: Controller untuk mengelola data cabang.
 *              Extends AbstractCrudController dari wp-app-core.
 *              Handles HTTP requests, delegates to Model/Validator.
 *
 * Business Rules:
 * - agency_id, division_id, inspector_id ONLY set via "assign inspector" feature
 * - Create/Update branch does NOT auto-assign these fields
 * - If location changes on branch with inspector, assignment cleared (set to NULL)
 *
 * Changelog:
 * 2.0.0 - 2025-11-09 (TODO-2194: Refactor to AbstractCrudController)
 * - BREAKING: Refactored to extend AbstractCrudController
 * - Code reduction: ~900 lines → ~600 lines (33% reduction)
 * - CRUD methods (store, update, delete, show) INHERITED from AbstractCrudController
 * - Implements 9 abstract methods
 * - Custom AJAX handlers preserved: handle_get_branch_form(), handle_save_branch(), handle_delete_branch()
 * - Business logic preserved: User creation, role assignment, inspector clearing
 * - All custom endpoints preserved: DataTable, branch validation, etc
 *
 * Previous version: BranchController-OLD-*.php (backup)
 */

namespace WPCustomer\Controllers\Branch;

use WPAppCore\Controllers\Abstract\AbstractCrudController;
use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Models\Branch\BranchModel;
use WPCustomer\Validators\Branch\BranchValidator;
use WPCustomer\Cache\BranchCacheManager;

class BranchController extends AbstractCrudController {
    private CustomerModel $customerModel;
    private BranchModel $model;
    private BranchValidator $validator;
    private BranchCacheManager $cache;
    private string $log_file;

    /**
     * Default log file path
     */
    private const DEFAULT_LOG_FILE = 'logs/branch.log';

    public function __construct() {
        $this->customerModel = new CustomerModel();
        $this->model = new BranchModel();
        $this->validator = new BranchValidator();
        $this->cache = BranchCacheManager::getInstance();

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
        add_action('wp_ajax_validate_branch_type_change', [$this, 'validateBranchTypeChange']);
        add_action('wp_ajax_create_branch_button', [$this, 'createBranchButton']);

        add_action('wp_ajax_validate_branch_access', [$this, 'validateBranchAccess']);
        add_action('wp_ajax_get_available_regencies', [$this, 'getAvailableRegencies']);

        // Modal integration (TODO-2190)
        add_action('wp_ajax_get_branch_form', [$this, 'handle_get_branch_form']);
        add_action('wp_ajax_save_branch', [$this, 'handle_save_branch']);
        add_action('wp_ajax_delete_branch_modal', [$this, 'handle_delete_branch']);
    }

    // ========================================
    // IMPLEMENT ABSTRACT METHODS (9 required)
    // ========================================

    /**
     * Get entity name
     *
     * @return string
     */
    protected function getEntityName(): string {
        return 'branch';
    }

    /**
     * Get entity name plural
     *
     * @return string
     */
    protected function getEntityNamePlural(): string {
        return 'branches';
    }

    /**
     * Get nonce action
     *
     * @return string
     */
    protected function getNonceAction(): string {
        return 'wp_customer_nonce';
    }

    /**
     * Get text domain
     *
     * @return string
     */
    protected function getTextDomain(): string {
        return 'wp-customer';
    }

    /**
     * Get validator instance
     *
     * @return BranchValidator
     */
    protected function getValidator() {
        return $this->validator;
    }

    /**
     * Get model instance
     *
     * @return BranchModel
     */
    protected function getModel() {
        return $this->model;
    }

    /**
     * Get cache group
     *
     * @return string
     */
    protected function getCacheGroup(): string {
        return 'wp_customer_branch';
    }

    /**
     * Prepare data from $_POST for create operation
     *
     * @return array Sanitized data
     */
    protected function prepareCreateData(): array {
        $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
        if (!$customer_id) {
            throw new \Exception('ID Customer tidak valid');
        }

        // Cek permission
        if (!$this->validator->canCreateBranch($customer_id)) {
            throw new \Exception('Anda tidak memiliki izin untuk menambah cabang');
        }

        // Sanitasi input
        $data = [
            'customer_id' => $customer_id,
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'type' => 'cabang', // Default cabang karena pusat sudah auto-created via AutoEntityCreator
            'nitku' => sanitize_text_field($_POST['nitku'] ?? ''),
            'postal_code' => sanitize_text_field($_POST['postal_code'] ?? ''),
            'latitude' => (float)($_POST['latitude'] ?? 0),
            'longitude' => (float)($_POST['longitude'] ?? 0),
            'address' => sanitize_text_field($_POST['address'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'province_id' => isset($_POST['province_id']) ? (int)$_POST['province_id'] : null,
            'regency_id' => isset($_POST['regency_id']) ? (int)$_POST['regency_id'] : null,
            'created_by' => get_current_user_id(),
            'status' => 'active'
        ];

        // NOTE: agency_id dan division_id TIDAK di-set saat create
        // Agency/Division akan di-assign nanti saat assign inspector
        // getAgencyAndDivisionIds() digunakan untuk assign inspector, bukan create branch

        // Validate branch creation data
        $create_errors = $this->validator->validateCreate($data);
        if (!empty($create_errors)) {
            throw new \Exception(reset($create_errors));
        }

        // Buat user untuk admin branch jika data admin diisi
        if (!empty($_POST['admin_email'])) {
            // NOTE: Password di-generate menggunakan wp_generate_password()
            // Password akan dikirim via email oleh wp_new_user_notification()
            // User harus mengganti password saat login pertama kali

            // Generate secure random password
            $generated_password = wp_generate_password(12, true, true);

            $user_data = [
                'user_login' => sanitize_user($_POST['admin_username']),
                'user_email' => sanitize_email($_POST['admin_email']),
                'first_name' => sanitize_text_field($_POST['admin_firstname']),
                'last_name' => sanitize_text_field($_POST['admin_lastname'] ?? ''),
                'user_pass' => $generated_password,  // Random secure password
                'role' => 'customer'  // Base role for all plugin users
            ];

            /**
             * Filter user data before creating WordPress user for branch admin
             *
             * Allows modification of user data before wp_insert_user() call.
             *
             * Use cases:
             * - Demo data: Force static IDs for predictable test data
             * - Migration: Import users with preserved IDs from external system
             * - Testing: Unit tests with predictable user IDs
             * - Custom user data: Add custom fields or metadata
             *
             * @param array $user_data User data for wp_insert_user()
             * @param array $data Original branch data from controller
             * @param string $context Context identifier ('branch_admin')
             * @return array Modified user data
             *
             * @since 1.0.0
             */
            $user_data = apply_filters(
                'wp_customer_branch_user_before_insert',
                $user_data,
                $data,
                'branch_admin'
            );

            // Handle static ID if requested (e.g., by demo data)
            $static_user_id = null;
            if (isset($user_data['ID'])) {
                $static_user_id = $user_data['ID'];
                unset($user_data['ID']); // wp_insert_user() doesn't accept ID parameter
            }

            $user_id = wp_insert_user($user_data);
            if (is_wp_error($user_id)) {
                throw new \Exception($user_id->get_error_message());
            }

            // If static ID was requested, update the user ID
            if ($static_user_id !== null && $static_user_id != $user_id) {
                global $wpdb;

                // Check if target ID already exists
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT ID FROM {$wpdb->users} WHERE ID = %d",
                    $static_user_id
                ));

                if (!$existing) {
                    // Update to static ID
                    $wpdb->query('SET FOREIGN_KEY_CHECKS=0');

                    $result_users = $wpdb->update(
                        $wpdb->users,
                        ['ID' => $static_user_id],
                        ['ID' => $user_id],
                        ['%d'],
                        ['%d']
                    );

                    $result_meta = $wpdb->update(
                        $wpdb->usermeta,
                        ['user_id' => $static_user_id],
                        ['user_id' => $user_id],
                        ['%d'],
                        ['%d']
                    );

                    $wpdb->query('SET FOREIGN_KEY_CHECKS=1');

                    if ($result_users !== false && $result_meta !== false) {
                        $user_id = $static_user_id;
                        error_log("[BranchController] Updated user ID to static ID: {$static_user_id}");
                    } else {
                        error_log("[BranchController] Failed to update to static ID: " . $wpdb->last_error);
                    }
                } else {
                    error_log("[BranchController] Static ID {$static_user_id} already exists, using auto ID {$user_id}");
                }
            }

            // Add customer roles (multi-role pattern)
            // User dari branch mendapat 3 role:
            // 1. customer - base role untuk akses customer area
            // 2. customer_branch_admin - admin level untuk branch management
            // 3. customer_employee - sebagai employee dari customer
            $user = get_user_by('ID', $user_id);
            if ($user) {
                $user->add_role('customer');
                $user->add_role('customer_branch_admin');
                $user->add_role('customer_employee');
            }

            // Force password reset on first login
            // User akan diminta mengganti password saat login pertama kali
            update_user_meta($user_id, 'default_password_nag', true);

            $data['user_id'] = $user_id;

            // Kirim email aktivasi
            wp_new_user_notification($user_id, null, 'user');
        }

        return $data;
    }

    /**
     * Prepare data from $_POST for update operation
     *
     * @param int $id Entity ID
     * @return array Sanitized data
     */
    protected function prepareUpdateData(int $id): array {
        // Get existing branch data
        $branch = $this->model->find($id);
        if (!$branch) {
            throw new \Exception('Branch not found');
        }

        // Get customer data untuk permission check
        $customer = $this->customerModel->find($branch->customer_id);
        if (!$customer) {
            throw new \Exception('Customer not found');
        }

        // Permission check di awal
        if (!$this->validator->canUpdateBranch($branch, $customer)) {
            throw new \Exception('Anda tidak memiliki izin untuk mengedit cabang ini.');
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
            'province_id' => isset($_POST['province_id']) ? (int)$_POST['province_id'] : null,
            'regency_id' => isset($_POST['regency_id']) ? (int)$_POST['regency_id'] : null,
            'user_id' => isset($_POST['user_id']) ? (int)$_POST['user_id'] : null,
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : null
        ], function($value) { return $value !== null; });

        // NOTE: agency_id and division_id NOT auto-updated when location changes
        // These fields are ONLY set via "assign inspector" feature
        // If location changes and branch has inspector, the assignment becomes invalid
        // User must manually re-assign inspector for the new location

        // If province or regency changed and branch has inspector, clear the assignment
        if ((isset($data['province_id']) || isset($data['regency_id'])) && !empty($branch->inspector_id)) {
            // Location changed - clear inspector assignment
            $data['agency_id'] = null;
            $data['division_id'] = null;
            $data['inspector_id'] = null;
            error_log("Branch {$id}: Location changed, clearing inspector assignment (was inspector_id={$branch->inspector_id})");
        }

        // Business logic validation
        $errors = $this->validator->validateUpdate($id, $data);
        if (!empty($errors)) {
            throw new \Exception(reset($errors));
        }

        return $data;
    }

    // ========================================
    // OVERRIDE PARENT METHODS (Custom implementation)
    // ========================================

    /**
     * Override store() to handle user creation rollback on failure
     *
     * @return void
     */
    public function store(): void {
        try {
            // Verify nonce
            $this->verifyNonce();

            // Prepare data (includes user creation logic)
            $data = $this->prepareCreateData();

            // Create via model
            $branch_id = $this->model->create($data);
            if (!$branch_id) {
                // Rollback user creation if branch creation failed
                if (!empty($data['user_id'])) {
                    wp_delete_user($data['user_id']);
                }
                throw new \Exception('Gagal menambah cabang');
            }

            // Send success response
            wp_send_json_success([
                'message' => 'Cabang berhasil ditambahkan',
                'branch' => $this->model->find($branch_id)
            ]);

        } catch (\Exception $e) {
            $this->handleError($e, 'create');
        }
    }

    /**
     * Override show() to include customer data validation
     *
     * @return void
     */
    public function show(): void {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid branch ID');
            }

            // Get branch data
            $branch = $this->model->find($id);
            if (!$branch) {
                throw new \Exception('Branch not found');
            }

            // Get customer data
            $customer = $this->customerModel->find($branch->customer_id);
            if (!$customer) {
                throw new \Exception('Customer not found');
            }

            // Permission check di awal
            if (!$this->validator->canViewBranch($branch, $customer)) {
                throw new \Exception('Anda tidak memiliki izin untuk melihat detail cabang ini.');
            }

            // Business logic validation dengan data yang sudah ada
            $errors = $this->validator->validateView($branch, $customer);
            if (!empty($errors)) {
                throw new \Exception(reset($errors));
            }

            wp_send_json_success([
                'branch' => $branch
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Override delete() to include branch type validation
     *
     * @return void
     */
    public function delete(): void {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid branch ID');
            }

            // Get branch and customer for permission check
            $branch = $this->model->find($id);
            $customer = $this->customerModel->find($branch->customer_id);

            // Check edit permission first (since delete requires edit)
            if (!$this->validator->canUpdateBranch($branch, $customer)) {
                throw new \Exception('Permission denied');
            }

            // Validate branch type deletion
            $type_validation = $this->validator->validateBranchTypeDelete($id);
            if (!$type_validation['valid']) {
                throw new \Exception($type_validation['message']);
            }

            // Proceed with deletion
            if (!$this->model->delete($id)) {
                throw new \Exception('Failed to delete branch');
            }

            wp_send_json_success([
                'message' => __('Branch deleted successfully', 'wp-customer')
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    // ========================================
    // CUSTOM AJAX HANDLERS (Entity-specific)
    // ========================================

    /**
     * Validate customer access - public endpoint untuk AJAX
     * @since 1.0.0
     */
    public function validateBranchAccess() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $branch_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$branch_id) {
                throw new \Exception('Invalid branch ID');
            }

            // Gunakan validator langsung
            $access = $this->validator->validateAccess($branch_id);

            if (!$access['has_access']) {
                wp_send_json_error([
                    'message' => __('Anda tidak memiliki akses ke cabang customer ini', 'wp-customer'),
                    'code' => 'access_denied'
                ]);
                return;
            }

            wp_send_json_success([
                'message' => 'Akses diberikan',
                'customer_id' => $access['customer_id'],
                'access_type' => $access['access_type']
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => 'error'
            ]);
        }
    }

    public function createBranchButton() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;

            if (!$customer_id) {
                throw new \Exception('ID Customer tidak valid');
            }

            $customer = $this->customerModel->find($customer_id);
            if (!$customer) {
                throw new \Exception('Customer tidak ditemukan');
            }

            if (!$this->validator->canCreateBranch($customer_id)) {
                wp_send_json_success(['button' => '']);
                return;
            }

            $button = '<button type="button" class="button button-primary" id="add-branch-btn">';
            $button .= '<span class="dashicons dashicons-plus-alt"></span>';
            $button .= __('Tambah Cabang', 'wp-customer');
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

    public function validateBranchTypeChange() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $branch_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $new_type = isset($_POST['new_type']) ? sanitize_text_field($_POST['new_type']) : '';

            if (!$branch_id || !$new_type) {
                throw new \Exception('Missing required parameters');
            }

            // Get current branch data
            $branch = $this->model->find($branch_id);
            if (!$branch) {
                throw new \Exception('Branch not found');
            }

            // Get all branches for this customer
            $existing_pusat = $this->model->findPusatByCustomer($branch->customer_id);

            // If changing to 'pusat' and there's already a pusat branch
            if ($new_type === 'pusat' && $existing_pusat && $existing_pusat->id !== $branch_id) {
                wp_send_json_error([
                    'message' => sprintf(
                        'Customer sudah memiliki kantor pusat: %s. Tidak dapat mengubah cabang ini menjadi kantor pusat.',
                        $existing_pusat->name
                    ),
                    'original_type' => $branch->type
                ]);
            }

            // If changing from 'pusat' to 'cabang', check if this is the only pusat
            if ($branch->type === 'pusat' && $new_type === 'cabang') {
                $pusat_count = $this->model->countPusatByCustomer($branch->customer_id);
                if ($pusat_count <= 1) {
                    wp_send_json_error([
                        'message' => 'Tidak dapat mengubah tipe menjadi cabang karena ini adalah satu-satunya kantor pusat.',
                        'original_type' => $branch->type
                    ]);
                }
            }

            wp_send_json_success(['message' => 'Valid']);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle DataTable request
     */
    public function handleDataTableRequest() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
            if (!$customer_id) {
                throw new \Exception('Invalid customer ID');
            }

            $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
            $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
            $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
            $search = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';

            $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
            $orderDir = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'asc';

            $columns = ['name', 'type', 'actions'];
            $orderBy = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'name';
            if ($orderBy === 'actions') {
                $orderBy = 'name';
            }
            // Get fresh data
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
                // Validate access
                $customer = $this->customerModel->find($branch->customer_id);
                if (!$this->validator->canViewBranch($branch, $customer)) {
                    continue;
                }

                // Get admin name
                $admin_name = '-';
                if (!empty($branch->user_id)) {
                    $user = get_userdata($branch->user_id);
                    if ($user) {
                        $first_name = $user->first_name;
                        $last_name = $user->last_name;
                        if ($first_name || $last_name) {
                            $admin_name = trim($first_name . ' ' . $last_name);
                        } else {
                            $admin_name = $user->display_name;
                        }
                    }
                }

                $data[] = [
                    'id' => $branch->id,
                    'code' => esc_html($branch->code),
                    'name' => esc_html($branch->name),
                    'admin_name' => esc_html($admin_name),
                    'type' => esc_html($branch->type),
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

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ], 400);
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
            if (!current_user_can('view_customer_branch_list') && !current_user_can('view_own_customer_branch')) {
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

    /**
     * Get available regencies for branch creation
     * Only returns regencies that have divisions assigned through jurisdictions
     *
     * @return void Sends JSON response with available regencies
     */
    public function getAvailableRegencies() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $province_id = isset($_POST['province_id']) ? (int) $_POST['province_id'] : 0;
            if (!$province_id) {
                throw new \Exception('ID Provinsi tidak valid');
            }

            // Get regencies that have jurisdictions (and thus divisions) for the selected province
            global $wpdb;

            $regencies = $wpdb->get_results($wpdb->prepare("
                SELECT DISTINCT r.id, r.name, r.code
                FROM {$wpdb->prefix}wi_regencies r
                INNER JOIN {$wpdb->prefix}app_agency_jurisdictions j ON r.code = j.jurisdiction_code
                INNER JOIN {$wpdb->prefix}app_agency_divisions d ON j.division_id = d.id
                INNER JOIN {$wpdb->prefix}app_agencies a ON d.agency_id = a.id
                WHERE r.province_id = %d
                AND d.status = 'active'
                AND a.status = 'active'
                ORDER BY r.name ASC
            ", $province_id));

            if ($regencies === null) {
                throw new \Exception('Gagal mengambil data regencies');
            }

            wp_send_json_success($regencies);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle get branch form for modal (TODO-2190)
     * Loads create or edit form based on parameters
     *
     * @since 1.1.0
     * @return void Outputs form HTML and dies
     */
    public function handle_get_branch_form() {
        try {
            // Auto-wire system sends wpdt_nonce
            $nonce = $_GET['nonce'] ?? $_POST['nonce'] ?? '';
            error_log('Branch Form - Nonce received: ' . $nonce);
            error_log('Branch Form - Verify result: ' . wp_verify_nonce($nonce, 'wpdt_nonce'));

            if (!wp_verify_nonce($nonce, 'wpdt_nonce')) {
                error_log('Branch Form - NONCE VERIFICATION FAILED');
                wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
                return;
            }
            error_log('Branch Form - Nonce verified successfully');

            $branch_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            $customer_id = isset($_GET['customer_id']) ? (int) $_GET['customer_id'] : 0;

            if ($branch_id) {
                // Edit mode
                $branch = $this->model->find($branch_id);
                if (!$branch) {
                    wp_send_json_error(['message' => __('Branch not found', 'wp-customer')]);
                    return;
                }

                $customer = $this->customerModel->find($branch->customer_id);
                if (!$customer) {
                    wp_send_json_error(['message' => __('Customer not found', 'wp-customer')]);
                    return;
                }

                // Permission check
                if (!$this->validator->canUpdateBranch($branch, $customer)) {
                    wp_send_json_error(['message' => __('You do not have permission to edit this branch', 'wp-customer')]);
                    return;
                }

                // Load edit form and capture output for auto-wire system
                ob_start();
                include WP_CUSTOMER_PATH . 'src/Views/admin/branch/forms/edit-branch-form.php';
                $html = ob_get_clean();

                wp_send_json_success(['html' => $html]);
            } else {
                // Create mode
                if (!$customer_id) {
                    wp_send_json_error(['message' => __('Customer ID required', 'wp-customer')]);
                    return;
                }

                // Permission check
                if (!$this->validator->canCreateBranch($customer_id)) {
                    wp_send_json_error(['message' => __('You do not have permission to create branch', 'wp-customer')]);
                    return;
                }

                $customer = $this->customerModel->find($customer_id);
                if (!$customer) {
                    wp_send_json_error(['message' => __('Customer not found', 'wp-customer')]);
                    return;
                }

                // Load create form and capture output for auto-wire system
                ob_start();
                include WP_CUSTOMER_PATH . 'src/Views/admin/branch/forms/create-branch-form.php';
                $html = ob_get_clean();

                wp_send_json_success(['html' => $html]);
            }

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle save branch for modal (TODO-2190)
     * Wrapper for create and update operations
     *
     * @since 1.1.0
     * @return void Sends JSON response
     */
    public function handle_save_branch() {
        try {
            // Auto-wire system sends wpdt_nonce
            $nonce = $_POST['nonce'] ?? $_GET['nonce'] ?? '';
            error_log('Branch Save - Nonce received: ' . $nonce);
            error_log('Branch Save - POST data: ' . print_r($_POST, true));
            error_log('Branch Save - Verify result: ' . wp_verify_nonce($nonce, 'wpdt_nonce'));

            if (!wp_verify_nonce($nonce, 'wpdt_nonce')) {
                error_log('Branch Save - NONCE VERIFICATION FAILED');
                throw new \Exception(__('Security check failed', 'wp-customer'));
            }

            error_log('Branch Save - Nonce verified successfully');

            $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'create';
            error_log('Branch Save - Mode: ' . $mode);

            if ($mode === 'edit') {
                // Get ID from POST
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                if (!$id) {
                    throw new \Exception('Branch ID is required');
                }

                // Prepare and validate data
                $data = $this->prepareUpdateData($id);

                // Update via model
                $result = $this->getModel()->update($id, $data);

                // Clear cache
                wp_cache_delete('branch_' . $id, $this->getCacheGroup());

                // Send success response
                wp_send_json_success([
                    'message' => sprintf(
                        __('%s updated successfully', $this->getTextDomain()),
                        ucfirst($this->getEntityName())
                    ),
                    'data' => $result
                ]);
            } else {
                // Prepare data (includes user creation logic)
                $data = $this->prepareCreateData();

                // Create via model
                $branch_id = $this->getModel()->create($data);
                if (!$branch_id) {
                    // Rollback user creation if branch creation failed
                    if (!empty($data['user_id'])) {
                        wp_delete_user($data['user_id']);
                    }
                    throw new \Exception('Gagal menambah cabang');
                }

                // Send success response
                wp_send_json_success([
                    'message' => 'Cabang berhasil ditambahkan',
                    'branch' => $this->getModel()->find($branch_id)
                ]);
            }

        } catch (\Exception $e) {
            error_log('[branch] update error: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle delete branch for modal
     *
     * @return void Sends JSON response
     */
    public function handle_delete_branch() {
        try {
            // Delegate to parent delete() method
            $this->delete();

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    // ========================================
    // UTILITY METHODS (Private helpers)
    // ========================================

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

    private function generateActionButtons($branch) {
        $current_user_id = get_current_user_id();
        $customer = $this->customerModel->find($branch->customer_id);
        $actions = '<div class="wpapp-actions">';

        // View button - gunakan canViewBranch
        if ($this->validator->canViewBranch($branch, $customer)) {
            $actions .= sprintf(
                '<button type="button" class="button view-branch" data-id="%d" title="%s">
                    <i class="dashicons dashicons-visibility"></i>
                </button> ',
                $branch->id,
                __('Lihat', 'wp-customer')
            );
        }

        // Edit button - gunakan canUpdateBranch
        if ($this->validator->canUpdateBranch($branch, $customer)) {
            $actions .= sprintf(
                '<button type="button" class="button edit-branch" data-id="%d" title="%s">
                    <i class="dashicons dashicons-edit"></i>
                </button> ',
                $branch->id,
                __('Edit', 'wp-customer')
            );
        }

        // Delete button - gunakan canDeleteBranch
        if ($this->validator->canDeleteBranch($branch, $customer)) {
            $type_validation = $this->validator->validateBranchTypeDelete($branch->id);
            if ($type_validation['valid']) {
                $actions .= sprintf(
                    '<button type="button" class="button delete-branch" data-id="%d" title="%s">
                        <i class="dashicons dashicons-trash"></i>
                    </button>',
                    $branch->id,
                    __('Hapus', 'wp-customer')
                );
            }
        }

        $actions .= '</div>'; // Close wpapp-actions wrapper
        return $actions;
    }
}
