<?php
/**
 * Companies Controller
 *
 * Handles HTTP requests and AJAX for companies management
 * Uses wp-app-core DataTable system
 *
 * @package WPCustomer
 * @subpackage Controllers\Companies
 * @since 1.1.0
 * @author arisciwek
 */

namespace WPCustomer\Controllers\Companies;

use WPCustomer\Models\Companies\CompaniesModel;
use WPCustomer\Models\Companies\CompaniesDataTableModel;
use WPCustomer\Validators\Companies\CompaniesValidator;

defined('ABSPATH') || exit;

/**
 * CompaniesController class
 *
 * Manages companies (branches) CRUD operations and DataTable display
 *
 * @since 1.1.0
 */
class CompaniesController {

    /**
     * Model instance
     *
     * @var CompaniesModel
     */
    private $model;

    /**
     * Validator instance
     *
     * @var CompaniesValidator
     */
    private $validator;

    /**
     * Constructor
     */
    public function __construct() {
        $this->model = new CompaniesModel();
        $this->validator = new CompaniesValidator();

        $this->init();
    }

    /**
     * Initialize controller
     */
    private function init() {
        // Register AJAX handlers - delayed to ensure wp-app-core is loaded
        add_action('init', [$this, 'register_ajax_handlers'], 20);

        // Enqueue assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Register AJAX handlers
     */
    public function register_ajax_handlers() {
        // NOTE: DataTable AJAX handler now registered via AbstractDataTable
        // No need for manual registration via DataTableController

        // Register other AJAX endpoints
        add_action('wp_ajax_get_company_details', [$this, 'ajax_get_details']);
        add_action('wp_ajax_load_company_detail_panel', [$this, 'ajax_load_detail_panel']);
        add_action('wp_ajax_create_company', [$this, 'ajax_create']);
        add_action('wp_ajax_update_company', [$this, 'ajax_update']);
        add_action('wp_ajax_delete_company', [$this, 'ajax_delete']);
        add_action('wp_ajax_get_companies_stats', [$this, 'ajax_get_stats']);
    }

    /**
     * Enqueue assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_assets($hook) {
        // Only load on companies page (top-level menu)
        if ($hook !== 'toplevel_page_wp-customer-companies') {
            return;
        }

        // Enqueue DataTables library
        wp_enqueue_script(
            'datatables-js',
            'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
            ['jquery'],
            '1.13.7',
            true
        );

        wp_enqueue_style(
            'datatables-css',
            'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
            [],
            '1.13.7'
        );

        // Enqueue companies DataTable script
        wp_enqueue_script(
            'wp-customer-companies-datatable',
            plugins_url('assets/js/companies/companies-datatable.js', \WP_CUSTOMER_FILE),
            ['jquery', 'datatables-js'],
            \WP_CUSTOMER_VERSION,
            true
        );

        // Enqueue companies styles
        wp_enqueue_style(
            'wp-customer-companies',
            plugins_url('assets/css/companies/companies.css', \WP_CUSTOMER_FILE),
            [],
            \WP_CUSTOMER_VERSION
        );

        // Localize script with data
        wp_localize_script('wp-customer-companies-datatable', 'wpCustomerCompanies', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_customer_companies_nonce'),
            'datatableNonce' => wp_create_nonce('wpapp_datatable_nonce'),
            'canFilterStatus' => current_user_can('edit_all_customers'),
            'strings' => [
                'confirmDelete' => __('Are you sure you want to delete this company?', 'wp-customer'),
                'deleteSuccess' => __('Company deleted successfully', 'wp-customer'),
                'deleteError' => __('Error deleting company', 'wp-customer'),
                'loadError' => __('Error loading data', 'wp-customer'),
            ]
        ]);
    }

    /**
     * Render companies page
     */
    public function render_page() {
        // Check permission via filter
        if (!$this->validator->can_access_page()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wp-customer'));
        }

        // Get action from URL
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

        // Route to appropriate view
        switch ($action) {
            case 'view':
                $this->render_view();
                break;

            case 'edit':
                $this->render_edit();
                break;

            case 'create':
                $this->render_create();
                break;

            default:
                $this->render_list();
                break;
        }
    }

    /**
     * Render list view
     */
    private function render_list() {
        $view_file = WP_CUSTOMER_PATH . 'src/Views/companies/list.php';

        if (!file_exists($view_file)) {
            echo '<div class="wrap"><h1>Companies List</h1><p>View file not found.</p></div>';
            return;
        }

        include $view_file;
    }

    /**
     * Render view page
     */
    private function render_view() {
        $company_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if (!$company_id) {
            wp_die(__('Invalid company ID', 'wp-customer'));
        }

        // Check permission
        if (!$this->validator->can_view_company($company_id)) {
            wp_die(__('You do not have permission to view this company.', 'wp-customer'));
        }

        $company = $this->model->find($company_id);

        if (!$company) {
            wp_die(__('Company not found', 'wp-customer'));
        }

        $view_file = WP_CUSTOMER_PATH . 'src/Views/companies/view.php';

        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo '<div class="wrap"><h1>Company View</h1><p>View file not found.</p></div>';
        }
    }

    /**
     * Render edit page
     */
    private function render_edit() {
        $company_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if (!$company_id) {
            wp_die(__('Invalid company ID', 'wp-customer'));
        }

        // Check permission
        if (!$this->validator->can_edit_company($company_id)) {
            wp_die(__('You do not have permission to edit this company.', 'wp-customer'));
        }

        $company = $this->model->find($company_id);

        if (!$company) {
            wp_die(__('Company not found', 'wp-customer'));
        }

        $view_file = WP_CUSTOMER_PATH . 'src/Views/companies/edit.php';

        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo '<div class="wrap"><h1>Edit Company</h1><p>View file not found.</p></div>';
        }
    }

    /**
     * Render create page
     */
    private function render_create() {
        // Check permission
        if (!$this->validator->can_create_company()) {
            wp_die(__('You do not have permission to create companies.', 'wp-customer'));
        }

        $view_file = WP_CUSTOMER_PATH . 'src/Views/companies/create.php';

        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo '<div class="wrap"><h1>Create Company</h1><p>View file not found.</p></div>';
        }
    }

    /**
     * AJAX: Get company details
     */
    public function ajax_get_details() {
        try {
            // Verify nonce
            check_ajax_referer('wp_customer_companies_nonce', 'nonce');

            // Get company ID
            $company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;

            if (!$company_id) {
                throw new \Exception(__('Invalid company ID', 'wp-customer'));
            }

            // Check permission
            if (!$this->validator->can_view_company($company_id)) {
                throw new \Exception(__('Permission denied', 'wp-customer'));
            }

            // Get company data
            $company = $this->model->find($company_id);

            if (!$company) {
                throw new \Exception(__('Company not found', 'wp-customer'));
            }

            // Format response
            $response = [
                'id' => $company->id,
                'code' => $company->code,
                'name' => $company->name,
                'type' => $company->type,
                'status' => $company->status,
                'address' => $company->address,
                'phone' => $company->phone,
                'email' => $company->email,
                'customer_id' => $company->customer_id,
                'agency_id' => $company->agency_id,
                'created_at' => date_i18n('d/m/Y H:i', strtotime($company->created_at)),
            ];

            wp_send_json_success($response);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX: Load company detail panel (for sliding panel)
     *
     * Returns HTML for the detail panel with tabs
     *
     * @since 1.1.0
     */
    public function ajax_load_detail_panel() {
        try {
            // Verify nonce
            check_ajax_referer('wp_customer_companies_nonce', 'nonce');

            // Get company ID
            $company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;

            if (!$company_id) {
                throw new \Exception(__('Invalid company ID', 'wp-customer'));
            }

            // Check permission
            if (!$this->validator->can_view_company($company_id)) {
                throw new \Exception(__('Permission denied', 'wp-customer'));
            }

            // Get company (branch) data
            $company = $this->model->find($company_id);

            if (!$company) {
                throw new \Exception(__('Company not found', 'wp-customer'));
            }

            // Get customer data
            global $wpdb;
            $customer = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}app_customers WHERE id = %d",
                $company->customer_id
            ));

            if (!$customer) {
                throw new \Exception(__('Customer not found', 'wp-customer'));
            }

            /**
             * Filter: Modify company data before rendering detail panel
             *
             * @param object $company Company data
             * @param int $company_id Company ID
             *
             * @since 1.1.0
             */
            $company = apply_filters('wp_customer_company_detail_data', $company, $company_id);

            /**
             * Filter: Modify customer data before rendering detail panel
             *
             * @param object $customer Customer data
             * @param int $company_id Company ID
             *
             * @since 1.1.0
             */
            $customer = apply_filters('wp_customer_company_customer_data', $customer, $company_id);

            // Render detail view
            ob_start();
            include WP_CUSTOMER_PATH . 'src/Views/companies/detail.php';
            $html = ob_get_clean();

            wp_send_json_success([
                'html' => $html,
                'company_id' => $company_id,
                'company_name' => $company->name
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX: Create company
     */
    public function ajax_create() {
        try {
            // Verify nonce
            check_ajax_referer('wp_customer_companies_nonce', 'nonce');

            // Check permission
            if (!$this->validator->can_create_company()) {
                throw new \Exception(__('Permission denied', 'wp-customer'));
            }

            // Validate data
            $data = $this->validator->validate_create_data($_POST);

            if (is_wp_error($data)) {
                throw new \Exception($data->get_error_message());
            }

            // Create company
            $company_id = $this->model->create($data);

            if (!$company_id) {
                throw new \Exception(__('Failed to create company', 'wp-customer'));
            }

            wp_send_json_success([
                'message' => __('Company created successfully', 'wp-customer'),
                'company_id' => $company_id
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX: Update company
     */
    public function ajax_update() {
        try {
            // Verify nonce
            check_ajax_referer('wp_customer_companies_nonce', 'nonce');

            // Get company ID
            $company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;

            if (!$company_id) {
                throw new \Exception(__('Invalid company ID', 'wp-customer'));
            }

            // Check permission
            if (!$this->validator->can_edit_company($company_id)) {
                throw new \Exception(__('Permission denied', 'wp-customer'));
            }

            // Validate data
            $data = $this->validator->validate_update_data($_POST);

            if (is_wp_error($data)) {
                throw new \Exception($data->get_error_message());
            }

            // Update company
            $result = $this->model->update($company_id, $data);

            if (!$result) {
                throw new \Exception(__('Failed to update company', 'wp-customer'));
            }

            wp_send_json_success([
                'message' => __('Company updated successfully', 'wp-customer')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX: Delete company
     */
    public function ajax_delete() {
        try {
            // Verify nonce
            check_ajax_referer('wp_customer_companies_nonce', 'nonce');

            // Get company ID
            $company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;

            if (!$company_id) {
                throw new \Exception(__('Invalid company ID', 'wp-customer'));
            }

            // Check permission
            if (!$this->validator->can_delete_company($company_id)) {
                throw new \Exception(__('Permission denied', 'wp-customer'));
            }

            // Delete company (soft delete by default)
            $hard_delete = isset($_POST['hard_delete']) && $_POST['hard_delete'] === 'true';
            $result = $this->model->delete($company_id, $hard_delete);

            if (!$result) {
                throw new \Exception(__('Failed to delete company', 'wp-customer'));
            }

            wp_send_json_success([
                'message' => __('Company deleted successfully', 'wp-customer')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX: Get statistics
     */
    public function ajax_get_stats() {
        try {
            // Verify nonce
            check_ajax_referer('wp_customer_companies_nonce', 'nonce');

            // Check permission
            if (!$this->validator->can_access_page()) {
                throw new \Exception(__('Permission denied', 'wp-customer'));
            }

            // Get statistics
            $stats = $this->model->get_statistics();

            wp_send_json_success($stats);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}
