<?php
namespace CustomerManagement\Controllers;

abstract class BaseController {
    protected $model;

    public function register() {
        $this->register_ajax_handlers();
    }

    abstract protected function register_ajax_handlers();

    protected function verify_nonce() {
        if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'customer_management_nonce')) {
            $this->send_error('Invalid security token');
        }
    }

    protected function verify_capability($capability) {
        if (!current_user_can($capability)) {
            $this->send_error('You do not have permission to perform this action');
        }
    }

    protected function send_success($data = null, $message = '') {
        wp_send_json_success([
            'data' => $data,
            'message' => $message
        ]);
    }

    protected function send_error($message = '', $data = null) {
        wp_send_json_error([
            'message' => $message,
            'data' => $data
        ]);
    }

    protected function get_current_user_id() {
        return get_current_user_id();
    }

    protected function sanitize_text_field($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize_text_field'], $data);
        }
        return sanitize_text_field($data);
    }

    protected function get_datatable_params() {
        return [
            'start' => isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0,
            'length' => isset($_REQUEST['length']) ? intval($_REQUEST['length']) : 10,
            'search' => isset($_REQUEST['search']['value']) ? sanitize_text_field($_REQUEST['search']['value']) : '',
            'order_column' => isset($_REQUEST['order'][0]['column']) ? sanitize_text_field($_REQUEST['order'][0]['column']) : 'id',
            'order_dir' => isset($_REQUEST['order'][0]['dir']) ? strtoupper(sanitize_text_field($_REQUEST['order'][0]['dir'])) : 'DESC'
        ];
    }

    protected function prepare_datatable_response($data, $total_records, $filtered_records) {
        return [
            'draw' => isset($_REQUEST['draw']) ? intval($_REQUEST['draw']) : 1,
            'recordsTotal' => $total_records,
            'recordsFiltered' => $filtered_records,
            'data' => $data
        ];
    }
}
