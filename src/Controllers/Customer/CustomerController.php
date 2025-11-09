<?php
/**
 * Customer Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Customer
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Customer/CustomerController.php
 *
 * Description: CRUD controller untuk Customer entity.
 *              Extends AbstractCrudController dari wp-app-core.
 *              Handles customer creation with WordPress user integration.
 *
 * Changelog:
 * 2.0.0 - 2025-01-08 (Task-2191: CRUD Refactoring)
 * - BREAKING: Complete refactor to extend AbstractCrudController
 * - Code reduction: Focus on CRUD core operations
 * - Implements 9 abstract methods
 * - Custom: createCustomerWithUser() for WordPress integration
 * - AJAX hooks preserved for CRUD operations
 * - DataTable handling will be refactored separately (future)
 */

namespace WPCustomer\Controllers\Customer;

use WPAppCore\Controllers\Abstract\AbstractCrudController;
use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Validators\CustomerValidator;
use WPCustomer\Cache\CustomerCacheManager;

defined('ABSPATH') || exit;

class CustomerController extends AbstractCrudController {

    /**
     * @var CustomerModel
     */
    private $model;

    /**
     * @var CustomerValidator
     */
    private $validator;

    /**
     * @var CustomerCacheManager
     */
    private $cache;

    /**
     * Constructor
     */
    public function __construct() {
        $this->model = new CustomerModel();
        $this->validator = new CustomerValidator();
        $this->cache = CustomerCacheManager::getInstance();

        // Register AJAX hooks
        $this->registerAjaxHooks();
    }

    /**
     * Register AJAX hooks
     *
     * @return void
     */
    private function registerAjaxHooks(): void {
        // CRUD hooks
        add_action('wp_ajax_create_customer', [$this, 'store']);
        add_action('wp_ajax_get_customer', [$this, 'show']);
        add_action('wp_ajax_update_customer', [$this, 'update']);
        add_action('wp_ajax_delete_customer', [$this, 'delete']);
        add_action('wp_ajax_validate_customer_access', [$this, 'validateCustomerAccess']);
    }

    // ========================================
    // IMPLEMENT ABSTRACT METHODS (9 required)
    // ========================================

    /**
     * Get entity name (singular)
     *
     * @return string
     */
    protected function getEntityName(): string {
        return 'customer';
    }

    /**
     * Get entity name (plural)
     *
     * @return string
     */
    protected function getEntityNamePlural(): string {
        return 'customers';
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
     * @return CustomerValidator
     */
    protected function getValidator() {
        return $this->validator;
    }

    /**
     * Get model instance
     *
     * @return CustomerModel
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
        return 'wp_customer';
    }

    /**
     * Prepare data for create operation
     *
     * @return array Sanitized data
     */
    protected function prepareCreateData(): array {
        return [
            'username' => sanitize_user($_POST['username'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'npwp' => !empty($_POST['npwp']) ? $this->validator->formatNpwp($_POST['npwp']) : null,
            'nib' => !empty($_POST['nib']) ? $this->validator->formatNib($_POST['nib']) : null,
            'provinsi_id' => isset($_POST['provinsi_id']) ? (int) $_POST['provinsi_id'] : null,
            'regency_id' => isset($_POST['regency_id']) ? (int) $_POST['regency_id'] : null,
            'status' => sanitize_text_field($_POST['status'] ?? 'active')
        ];
    }

    /**
     * Prepare data for update operation
     *
     * @param int $id Customer ID
     * @return array Sanitized data
     */
    protected function prepareUpdateData(int $id): array {
        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'npwp' => !empty($_POST['npwp']) ? sanitize_text_field($_POST['npwp']) : null,
            'nib' => !empty($_POST['nib']) ? sanitize_text_field($_POST['nib']) : null,
            'status' => !empty($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active',
            'provinsi_id' => !empty($_POST['provinsi_id']) ? (int) $_POST['provinsi_id'] : null,
            'regency_id' => !empty($_POST['regency_id']) ? (int) $_POST['regency_id'] : null
        ];

        // Validate status
        if (!in_array($data['status'], ['active', 'inactive'])) {
            throw new \Exception('Invalid status value');
        }

        // Handle user_id if present and user has permission
        if (isset($_POST['user_id']) && current_user_can('edit_all_customers')) {
            $data['user_id'] = !empty($_POST['user_id']) ? (int) $_POST['user_id'] : null;
        }

        return $data;
    }

    // ========================================
    // OVERRIDE CRUD METHODS FOR CUSTOM LOGIC
    // ========================================

    /**
     * Override store() to use createCustomerWithUser
     *
     * @return void
     */
    public function store(): void {
        try {
            $this->verifyNonce();
            $this->checkPermission('create');

            // Prepare data
            $data = $this->prepareCreateData();

            // Create customer with WordPress user
            $result = $this->createCustomerWithUser($data, get_current_user_id());

            // Get customer data for response
            $customer = $this->model->find($result['customer_id']);

            // Prepare response
            $response = [
                'message' => $result['message'],
                'data' => $customer
            ];

            // Include generated credentials if available
            if (isset($result['credentials_generated']) && $result['credentials_generated']) {
                $response['credentials'] = [
                    'username' => $result['username'],
                    'password' => $result['password'],
                    'email' => $data['email']
                ];
            }

            $this->sendSuccess($response['message'], $response);

        } catch (\Exception $e) {
            $this->handleError($e, 'create');
        }
    }

    /**
     * Override show() to include membership data
     *
     * @return void
     */
    public function show(): void {
        try {
            $this->verifyNonce();

            $id = $this->getId();

            // Get customer data
            $customer = $this->model->find($id);
            if (!$customer) {
                throw new \Exception('Customer not found');
            }

            // Validate access
            $access = $this->validator->validateAccess($id);
            if (!$access['has_access']) {
                throw new \Exception('You do not have permission to view this customer');
            }

            // Get membership data
            $membership = $this->model->getMembershipData($id);

            // Response
            wp_send_json_success([
                'customer' => $customer,
                'membership' => $membership,
                'access_type' => $access['access_type']
            ]);

        } catch (\Exception $e) {
            $this->handleError($e, 'view');
        }
    }

    /**
     * Override update() to include enriched response
     *
     * @return void
     */
    public function update(): void {
        try {
            $this->verifyNonce();

            $id = $this->getId();
            $this->checkPermission('update', $id);

            // Prepare and validate
            $data = $this->prepareUpdateData($id);
            $this->validate($data, $id);

            // Update
            $updated = $this->model->update($id, $data);
            if (!$updated) {
                throw new \Exception('Failed to update customer');
            }

            // Clear cache
            $this->cache->invalidateCustomerCache($id);

            // Get updated data
            $customer = $this->model->find($id);
            $access = $this->validator->validateAccess($id);

            wp_send_json_success([
                'message' => __('Customer berhasil diperbarui', 'wp-customer'),
                'data' => [
                    'customer' => array_merge((array) $customer, [
                        'access_type' => $access['access_type'],
                        'has_access' => $access['has_access']
                    ]),
                    'access_type' => $access['access_type']
                ]
            ]);

        } catch (\Exception $e) {
            $this->handleError($e, 'update');
        }
    }

    /**
     * Override delete() for custom validation
     *
     * @return void
     */
    public function delete(): void {
        try {
            $this->verifyNonce();

            $id = $this->getId();

            // Validate delete
            $errors = $this->validator->validateDelete($id);
            if (!empty($errors)) {
                throw new \Exception(reset($errors));
            }

            // Delete
            if (!$this->model->delete($id)) {
                throw new \Exception('Failed to delete customer');
            }

            // Clear cache
            $this->cache->invalidateCustomerCache($id);

            $this->sendSuccess(__('Data Customer berhasil dihapus', 'wp-customer'));

        } catch (\Exception $e) {
            $this->handleError($e, 'delete');
        }
    }

    // ========================================
    // CUSTOM METHODS
    // ========================================

    /**
     * Create customer with WordPress user
     *
     * @param array $data Customer data
     * @param int|null $created_by Creator user ID
     * @return array Result with customer_id, user_id, message
     * @throws \Exception
     */
    public function createCustomerWithUser(array $data, ?int $created_by = null): array {
        // Validate email
        $email = isset($data['email']) ? sanitize_email($data['email']) : '';
        if (empty($email)) {
            throw new \Exception(__('Email wajib diisi', 'wp-customer'));
        }

        // Track credentials
        $credentials_generated = false;
        $generated_username = null;
        $generated_password = null;

        // Check if user_id already provided
        if (isset($data['user_id']) && $data['user_id']) {
            $user_id = (int) $data['user_id'];
        } else {
            // Check email exists
            if (email_exists($email)) {
                throw new \Exception(__('Email sudah terdaftar', 'wp-customer'));
            }

            // Check username
            if (isset($data['username']) && !empty($data['username'])) {
                $username = sanitize_user($data['username']);

                if (empty($username)) {
                    throw new \Exception(__('Username tidak valid', 'wp-customer'));
                }

                // Make unique
                $original_username = $username;
                $counter = 1;
                while (username_exists($username)) {
                    $username = $original_username . $counter;
                    $counter++;
                }

                // Auto-generate password
                if (isset($data['password']) && !empty($data['password'])) {
                    $password = $data['password'];
                } else {
                    $password = wp_generate_password(12, true, true);
                    $credentials_generated = true;
                    $generated_username = $username;
                    $generated_password = $password;
                }
            } else {
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

            // Send notification
            wp_new_user_notification($user_id, null, 'user');
        }

        // Prepare customer data
        $customer_data = [
            'name' => sanitize_text_field($data['name']),
            'npwp' => isset($data['npwp']) ? sanitize_text_field($data['npwp']) : null,
            'nib' => isset($data['nib']) ? sanitize_text_field($data['nib']) : null,
            'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'active',
            'provinsi_id' => isset($data['provinsi_id']) ? (int) $data['provinsi_id'] : null,
            'regency_id' => isset($data['regency_id']) ? (int) $data['regency_id'] : null,
            'user_id' => $user_id,
            'reg_type' => isset($data['reg_type']) ? sanitize_text_field($data['reg_type']) : ($created_by ? 'by_admin' : 'self'),
            'created_by' => $created_by ?? $user_id
        ];

        // Validate
        $form_errors = $this->validator->validateForm($customer_data);
        if (!empty($form_errors)) {
            // Rollback: delete user
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            wp_delete_user($user_id);
            throw new \Exception(implode(', ', $form_errors));
        }

        // Create customer
        $customer_id = $this->model->create($customer_data);
        if (!$customer_id) {
            // Rollback: delete user
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            wp_delete_user($user_id);
            throw new \Exception('Failed to create customer');
        }

        $result = [
            'customer_id' => $customer_id,
            'user_id' => $user_id,
            'message' => __('Customer berhasil ditambahkan. Email aktivasi telah dikirim.', 'wp-customer')
        ];

        // Include credentials if generated
        if ($credentials_generated) {
            $result['credentials_generated'] = true;
            $result['username'] = $generated_username;
            $result['password'] = $generated_password;
        }

        return $result;
    }

    /**
     * Validate customer access (AJAX endpoint)
     *
     * @return void
     */
    public function validateCustomerAccess(): void {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $customer_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$customer_id) {
                throw new \Exception('Invalid customer ID');
            }

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
            wp_send_json_error(['message' => $e->getMessage(), 'code' => 'error']);
        }
    }
}
