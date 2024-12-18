<?php
namespace CustomerManagement\Controllers;

use CustomerManagement\Models\BranchModel;

class BranchController extends BaseController {
    public function __construct() {
        $this->model = new BranchModel();
    }

    protected function register_ajax_handlers() {
        add_action('wp_ajax_get_branches', [$this, 'handle_get_branches']);
        add_action('wp_ajax_get_branch', [$this, 'handle_get_branch']);
        add_action('wp_ajax_create_branch', [$this, 'handle_create_branch']);
        add_action('wp_ajax_update_branch', [$this, 'handle_update_branch']);
        add_action('wp_ajax_delete_branch', [$this, 'handle_delete_branch']);
    }

    public function handle_get_branches() {
        $this->verify_nonce();
        $this->verify_capability('read_customers');

        $params = $this->get_datatable_params();
        $data = $this->model->get_for_datatable($params);
        $total_records = $this->model->count();
        $filtered_records = count($data);

        $response = $this->prepare_datatable_response($data, $total_records, $filtered_records);
        $this->send_success($response);
    }

    public function handle_get_branch() {
        $this->verify_nonce();
        $this->verify_capability('read_customers');

        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if (!$id) {
            $this->send_error('Invalid branch ID');
        }

        $branch = $this->model->get_with_customers($id);
        if (!$branch) {
            $this->send_error('Branch not found');
        }

        $this->send_success($branch);
    }

    public function handle_create_branch() {
        $this->verify_nonce();
        $this->verify_capability('create_customers');

        $data = $this->validate_branch_data($_POST);
        $branch_id = $this->model->create($data);

        if (!$branch_id) {
            $this->send_error('Failed to create branch');
        }

        $branch = $this->model->get($branch_id);
        $this->send_success($branch, 'Branch created successfully');
    }

    public function handle_update_branch() {
        $this->verify_nonce();
        $this->verify_capability('update_customers');

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id) {
            $this->send_error('Invalid branch ID');
        }

        $data = $this->validate_branch_data($_POST);
        $updated = $this->model->update($id, $data);

        if ($updated === false) {
            $this->send_error('Failed to update branch');
        }

        $branch = $this->model->get($id);
        $this->send_success($branch, 'Branch updated successfully');
    }

    public function handle_delete_branch() {
        $this->verify_nonce();
        $this->verify_capability('delete_customers');

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id) {
            $this->send_error('Invalid branch ID');
        }

        // Check if branch has associated customers
        $branch = $this->model->get_with_customers($id);
        if ($branch && $branch->customer_count > 0) {
            $this->send_error('Cannot delete branch with associated customers');
        }

        $deleted = $this->model->delete($id);
        if (!$deleted) {
            $this->send_error('Failed to delete branch');
        }

        $this->send_success(null, 'Branch deleted successfully');
    }

    private function validate_branch_data($data) {
        $validated = [];

        // Required fields
        if (empty($data['name'])) {
            $this->send_error('Branch name is required');
        }
        $validated['name'] = $this->sanitize_text_field($data['name']);

        // Optional fields
        if (!empty($data['location'])) {
            $validated['location'] = $this->sanitize_text_field($data['location']);
        }

        return $validated;
    }
}
