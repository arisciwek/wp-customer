<?php
namespace CustomerManagement\Controllers;

use CustomerManagement\Models\EmployeeModel;

class EmployeeController extends BaseController {
    public function __construct() {
        $this->model = new EmployeeModel();
    }

    protected function register_ajax_handlers() {
        add_action('wp_ajax_get_employees', [$this, 'handle_get_employees']);
        add_action('wp_ajax_get_employee', [$this, 'handle_get_employee']);
        add_action('wp_ajax_create_employee', [$this, 'handle_create_employee']);
        add_action('wp_ajax_update_employee', [$this, 'handle_update_employee']);
        add_action('wp_ajax_delete_employee', [$this, 'handle_delete_employee']);
    }

    public function handle_get_employees() {
        $this->verify_nonce();
        $this->verify_capability('read_customers');

        $params = $this->get_datatable_params();
        $data = $this->model->get_for_datatable($params);
        $total_records = $this->model->count();
        $filtered_records = count($data);

        $response = $this->prepare_datatable_response($data, $total_records, $filtered_records);
        $this->send_success($response);
    }

    public function handle_get_employee() {
        $this->verify_nonce();
        $this->verify_capability('read_customers');

        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if (!$id) {
            $this->send_error('Invalid employee ID');
        }

        $employee = $this->model->get_with_customers($id);
        if (!$employee) {
            $this->send_error('Employee not found');
        }

        $this->send_success($employee);
    }

    public function handle_create_employee() {
        $this->verify_nonce();
        $this->verify_capability('create_customers');

        $data = $this->validate_employee_data($_POST);
        $employee_id = $this->model->create($data);

        if (!$employee_id) {
            $this->send_error('Failed to create employee');
        }

        $employee = $this->model->get($employee_id);
        $this->send_success($employee, 'Employee created successfully');
    }

    public function handle_update_employee() {
        $this->verify_nonce();
        $this->verify_capability('update_customers');

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id) {
            $this->send_error('Invalid employee ID');
        }

        $data = $this->validate_employee_data($_POST);
        $updated = $this->model->update($id, $data);

        if ($updated === false) {
            $this->send_error('Failed to update employee');
        }

        $employee = $this->model->get($id);
        $this->send_success($employee, 'Employee updated successfully');
    }

    public function handle_delete_employee() {
        $this->verify_nonce();
        $this->verify_capability('delete_customers');

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id) {
            $this->send_error('Invalid employee ID');
        }

        // Check if employee has associated customers
        $employee = $this->model->get_with_customers($id);
        if ($employee && $employee->customer_count > 0) {
            $this->send_error('Cannot delete employee with associated customers');
        }

        $deleted = $this->model->delete($id);
        if (!$deleted) {
            $this->send_error('Failed to delete employee');
        }

        $this->send_success(null, 'Employee deleted successfully');
    }

    private function validate_employee_data($data) {
        $validated = [];

        // Required fields
        if (empty($data['name'])) {
            $this->send_error('Employee name is required');
        }
        $validated['name'] = $this->sanitize_text_field($data['name']);

        // Optional fields
        if (!empty($data['position'])) {
            $validated['position'] = $this->sanitize_text_field($data['position']);
        }

        return $validated;
    }
}
