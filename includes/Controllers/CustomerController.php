<?php
namespace CustomerManagement\Controllers;

use CustomerManagement\Models\CustomerModel;

class CustomerController extends BaseController {
    public function __construct() {
        $this->model = new CustomerModel();
    }

    protected function register_ajax_handlers() {
        add_action('wp_ajax_get_customers', [$this, 'handle_get_customers']);
        add_action('wp_ajax_get_customer', [$this, 'handle_get_customer']);
        add_action('wp_ajax_create_customer', [$this, 'handle_create_customer']);
        add_action('wp_ajax_update_customer', [$this, 'handle_update_customer']);
        add_action('wp_ajax_delete_customer', [$this, 'handle_delete_customer']);
    }


    public function handle_get_customers() {
        $this->verify_nonce();
        $this->verify_capability('read_customers');

        $params = $this->get_datatable_params();
        $data = $this->model->get_for_datatable($params);
        $total_records = $this->model->count();
        $filtered_records = $total_records;

        // Format the response for DataTables
        $response = [
            'draw' => isset($_REQUEST['draw']) ? intval($_REQUEST['draw']) : 0,
            'recordsTotal' => $total_records,
            'recordsFiltered' => $filtered_records,
            'data' => array_map(function($row) {
                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'email' => $row->email,
                    'phone' => $row->phone ?? '-',
                    'membership_type' => $row->membership_level_name ?? 'Regular',
                    'branch_name' => $row->branch_name ?? '-',
                    'employee_name' => $row->employee_name ?? '-'
                ];
            }, $data)
        ];

        wp_send_json($response);
    }
    
    public function handle_get_customer() {
        $this->verify_nonce();
        $this->verify_capability('read_customers');

        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if (!$id) {
            $this->send_error('Invalid customer ID');
        }

        $customer = $this->model->get_with_relations($id);
        if (!$customer) {
            $this->send_error('Customer not found');
        }

        // Format customer data
        $formatted_customer = [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'address' => $customer->address,
            'membership_type' => $customer->membership_level_name,
            'branch_name' => $customer->branch_name,
            'employee_name' => $customer->employee_name,
            'province_name' => $customer->province_name,
            'city_name' => $customer->city_name
        ];

        $this->send_success($formatted_customer);
    }

    public function handle_create_customer() {
        $this->verify_nonce();
        $this->verify_capability('create_customers');

        $data = $this->validate_customer_data($_POST);
        $data['created_by'] = $this->get_current_user_id();

        $customer_id = $this->model->create($data);
        if (!$customer_id) {
            $this->send_error('Failed to create customer');
        }

        $customer = $this->model->get_with_relations($customer_id);
        $this->send_success($customer, 'Customer created successfully');
    }

    public function handle_update_customer() {
        $this->verify_nonce();
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id) {
            $this->send_error('Invalid customer ID');
        }

        $customer = $this->model->get($id);
        if (!$customer) {
            $this->send_error('Customer not found');
        }

        // Check permissions
        if (!current_user_can('update_customers') && 
            (!current_user_can('edit_own_customers') || 
            ($customer->created_by !== $this->get_current_user_id() && 
             $customer->assigned_to !== $this->get_current_user_id()))) {
            $this->send_error('You do not have permission to update this customer');
        }

        $data = $this->validate_customer_data($_POST);
        $updated = $this->model->update($id, $data);
        
        if ($updated === false) {
            $this->send_error('Failed to update customer');
        }

        $customer = $this->model->get_with_relations($id);
        $this->send_success($customer, 'Customer updated successfully');
    }

    public function handle_delete_customer() {
        $this->verify_nonce();
        $this->verify_capability('delete_customers');

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id) {
            $this->send_error('Invalid customer ID');
        }

        $deleted = $this->model->delete($id);
        if (!$deleted) {
            $this->send_error('Failed to delete customer');
        }

        $this->send_success(null, 'Customer deleted successfully');
    }

    private function validate_customer_data($data) {
        $validated = [];
        
        // Required fields
        if (empty($data['name'])) {
            $this->send_error('Customer name is required');
        }
        $validated['name'] = $this->sanitize_text_field($data['name']);

        if (empty($data['email']) || !is_email($data['email'])) {
            $this->send_error('Valid email address is required');
        }
        $validated['email'] = sanitize_email($data['email']);

        // Optional fields
        if (!empty($data['phone'])) {
            $validated['phone'] = $this->sanitize_text_field($data['phone']);
        }

        if (!empty($data['address'])) {
            $validated['address'] = sanitize_textarea_field($data['address']);
        }

        if (!empty($data['provinsi_id'])) {
            $validated['provinsi_id'] = intval($data['provinsi_id']);
        }

        if (!empty($data['kabupaten_id'])) {
            $validated['kabupaten_id'] = intval($data['kabupaten_id']);
        }

        if (!empty($data['employee_id'])) {
            $validated['employee_id'] = intval($data['employee_id']);
        }

        if (!empty($data['branch_id'])) {
            $validated['branch_id'] = intval($data['branch_id']);
        }

        if (!empty($data['assigned_to'])) {
            $validated['assigned_to'] = intval($data['assigned_to']);
        }

        $membership_types = ['regular', 'priority', 'utama'];
        if (!empty($data['membership_type']) && in_array($data['membership_type'], $membership_types)) {
            $validated['membership_type'] = $data['membership_type'];
        }

        return $validated;
    }
}
