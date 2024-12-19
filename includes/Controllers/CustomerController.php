<?php
namespace CustomerManagement\Controllers;

use CustomerManagement\Models\CustomerModel;

class CustomerController extends BaseController {
    protected $model;

    public function __construct() {
        $this->model = new CustomerModel();
    }

    protected function register_ajax_handlers() {
        add_action('wp_ajax_get_customers', [$this, 'handle_get_customers']);
        add_action('wp_ajax_get_customer', [$this, 'handle_get_customer']);
        add_action('wp_ajax_get_customer_tab_content', [$this, 'handle_get_customer_tab_content']);
        add_action('wp_ajax_create_customer', [$this, 'handle_create_customer']);
        add_action('wp_ajax_update_customer', [$this, 'handle_update_customer']);
        add_action('wp_ajax_delete_customer', [$this, 'handle_delete_customer']);
    }

    public function handle_get_customers() {
        try {
            $this->verify_nonce();
            $this->verify_capability('read_customers');

            $params = $this->get_datatable_params();
            $data = $this->model->get_for_datatable($params);
            $total_records = $this->model->count();
            $filtered_records = $total_records;

            // Format for DataTables
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
                        'employee_name' => $row->employee_name ?? '-',
                        'actions' => $this->get_row_actions($row)
                    ];
                }, $data)
            ];

            wp_send_json($response);
        } catch (\Exception $e) {
            $this->send_error($e->getMessage());
        }
    }
    
    public function handle_get_customer() {
        try {
            $this->verify_nonce();
            $this->verify_capability('read_customers');

            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            if (!$id) {
                throw new \Exception('Invalid customer ID');
            }

            $customer = $this->model->get_with_relations($id);
            if (!$customer) {
                throw new \Exception('Customer not found');
            }

            // Format customer data for panel display
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
                'city_name' => $customer->city_name,
                'tabs' => [
                    'info' => [
                        'loaded' => true,
                        'data' => $this->get_customer_info_tab($customer)
                    ],
                    'activity' => [
                        'loaded' => false,
                        'load_url' => add_query_arg([
                            'action' => 'get_customer_tab_content',
                            'tab' => 'activity',
                            'id' => $customer->id
                        ], admin_url('admin-ajax.php'))
                    ],
                    'notes' => [
                        'loaded' => false,
                        'load_url' => add_query_arg([
                            'action' => 'get_customer_tab_content',
                            'tab' => 'notes',
                            'id' => $customer->id
                        ], admin_url('admin-ajax.php'))
                    ]
                ]
            ];

            $this->send_success($formatted_customer);
        } catch (\Exception $e) {
            $this->send_error($e->getMessage());
        }
    }

    public function handle_get_customer_tab_content() {
        try {
            $this->verify_nonce();
            $this->verify_capability('read_customers');

            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $tab = isset($_REQUEST['tab']) ? sanitize_key($_REQUEST['tab']) : '';

            if (!$id || !$tab) {
                throw new \Exception('Invalid request parameters');
            }

            $customer = $this->model->get_with_relations($id);
            if (!$customer) {
                throw new \Exception('Customer not found');
            }

            $content = '';
            switch ($tab) {
                case 'activity':
                    $content = $this->get_customer_activity_tab($customer);
                    break;
                case 'notes':
                    $content = $this->get_customer_notes_tab($customer);
                    break;
                default:
                    throw new \Exception('Invalid tab requested');
            }

            $this->send_success(['content' => $content]);
        } catch (\Exception $e) {
            $this->send_error($e->getMessage());
        }
    }

    public function handle_create_customer() {
        try {
            $this->verify_nonce();
            $this->verify_capability('create_customers');

            $data = $this->validate_customer_data($_POST);
            $data['created_by'] = $this->get_current_user_id();
            
            $customer_id = $this->model->create($data);
            if (!$customer_id) {
                throw new \Exception('Failed to create customer');
            }

            $customer = $this->model->get_with_relations($customer_id);
            $this->send_success([
                'customer' => $customer,
                'message' => 'Customer created successfully',
                'redirect_hash' => $customer_id
            ]);
        } catch (\Exception $e) {
            $this->send_error($e->getMessage());
        }
    }

    public function handle_update_customer() {
        try {
            $this->verify_nonce();
            
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if (!$id) {
                throw new \Exception('Invalid customer ID');
            }

            $customer = $this->model->get($id);
            if (!$customer) {
                throw new \Exception('Customer not found');
            }

            // Permission check
            if (!current_user_can('update_customers') && 
                (!current_user_can('edit_own_customers') || 
                ($customer->created_by !== $this->get_current_user_id() && 
                $customer->assigned_to !== $this->get_current_user_id()))) {
                throw new \Exception('You do not have permission to update this customer');
            }

            $data = $this->validate_customer_data($_POST);
            $updated = $this->model->update($id, $data);
            
            if ($updated === false) {
                throw new \Exception('Failed to update customer');
            }

            $customer = $this->model->get_with_relations($id);
            $this->send_success([
                'customer' => $customer,
                'message' => 'Customer updated successfully',
                'redirect_hash' => $id
            ]);
        } catch (\Exception $e) {
            $this->send_error($e->getMessage());
        }
    }

    public function handle_delete_customer() {
        try {
            $this->verify_nonce();
            $this->verify_capability('delete_customers');

            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if (!$id) {
                throw new \Exception('Invalid customer ID');
            }

            $deleted = $this->model->delete($id);
            if (!$deleted) {
                throw new \Exception('Failed to delete customer');
            }

            $this->send_success([
                'message' => 'Customer deleted successfully',
                'clear_hash' => true
            ]);
        } catch (\Exception $e) {
            $this->send_error($e->getMessage());
        }
    }

    private function get_row_actions($customer) {
        $actions = '<div class="row-actions">';
        
        if (current_user_can('read_customers')) {
            $actions .= sprintf(
                '<span class="view"><a href="#" class="view-customer" data-id="%d">View</a></span>',
                $customer->id
            );
        }

        if (current_user_can('update_customers')) {
            $actions .= ' | ';
            $actions .= sprintf(
                '<span class="edit"><a href="#" class="edit-customer" data-id="%d">Edit</a></span>',
                $customer->id
            );
        }

        if (current_user_can('delete_customers')) {
            $actions .= ' | ';
            $actions .= sprintf(
                '<span class="delete"><a href="#" class="delete-customer" data-id="%d">Delete</a></span>',
                $customer->id
            );
        }

        $actions .= '</div>';
        return $actions;
    }

    private function validate_customer_data($data) {
        $validated = [];
        
        if (empty($data['name'])) {
            throw new \Exception('Customer name is required');
        }
        $validated['name'] = $this->sanitize_text_field($data['name']);

        if (empty($data['email']) || !is_email($data['email'])) {
            throw new \Exception('Valid email address is required');
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

    private function get_customer_info_tab($customer) {
        // Return formatted info tab data
        return [
            'basic_info' => [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address
            ],
            'membership' => [
                'type' => $customer->membership_level_name,
                'since' => $customer->created_at
            ],
            'assignment' => [
                'branch' => $customer->branch_name,
                'employee' => $customer->employee_name
            ],
            'location' => [
                'province' => $customer->province_name,
                'city' => $customer->city_name
            ]
        ];
    }

    private function get_customer_activity_tab($customer) {
        // Get activity data from model/database
        return [
            'activities' => [] // Implement actual activity fetching
        ];
    }

    private function get_customer_notes_tab($customer) {
        // Get notes data from model/database
        return [
            'notes' => [] // Implement actual notes fetching
        ];
    }
}
