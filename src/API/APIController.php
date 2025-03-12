 <?php
/**
 * WP Customer REST API Controller
 *
 * @package     WP_Customer
 * @subpackage  API
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/API/APIController.php
 *
 * Description: Controller untuk mengelola REST API WP Customer.
 *              Handles endpoint registration dan request processing.
 *              Includes authentication, validation dan response formatting.
 *              
 * Dependencies:
 * - WordPress REST API
 * - WPCustomer\Models\Customer\CustomerModel
 * - WPCustomer\Models\Membership\MembershipLevelModel
 *
 * Changelog:
 * 1.0.0 - 2024-02-16
 * - Initial version
 * - Added CRUD endpoints for customers
 * - Added membership level endpoints
 * - Added CORS support
 */

namespace WPCustomer\API;

class APIController {
    private const API_NAMESPACE = 'wp-customer/v1';
    
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        // Customer endpoints
        register_rest_route(self::API_NAMESPACE, '/customers', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_customers'],
                'permission_callback' => [$this, 'check_customer_list_permission'],
                'args' => [
                    'page' => [
                        'default' => 1,
                        'sanitize_callback' => 'absint'
                    ],
                    'per_page' => [
                        'default' => 10,
                        'sanitize_callback' => 'absint'
                    ]
                ]
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_customer'],
                'permission_callback' => [$this, 'check_create_permission'],
                'args' => [
                    'name' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'npwp' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'nib' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'  
                    ]
                ]
            ]
        ]);

        // Single customer endpoint
        register_rest_route(self::API_NAMESPACE, '/customers/(?P<id>\d+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_customer'],
                'permission_callback' => [$this, 'check_read_permission'],
                'args' => [
                    'id' => [
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ]
                ]
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_customer'],
                'permission_callback' => [$this, 'check_update_permission'],
                'args' => [
                    'id' => [
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ],
                    'name' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ]
                ]
            ],
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_customer'],
                'permission_callback' => [$this, 'check_delete_permission']
            ]
        ]);

        // Membership level endpoints
        register_rest_route(self::API_NAMESPACE, '/membership-levels', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_customer_membership_levels'],
                'permission_callback' => [$this, 'check_membership_permission']
            ]
        ]);
    }

    /**
     * Permission checks
     */
    public function check_customer_list_permission(\WP_REST_Request $request) {
        return current_user_can('view_customer_list');
    }

    public function check_read_permission(\WP_REST_Request $request) {
        $customer_id = $request->get_param('id');
        
        // Check if user can view all customers
        if (current_user_can('view_customer_detail')) {
            return true;
        }

        // Check if user owns this customer
        if (current_user_can('view_own_customer')) {
            $customer = new \WPCustomer\Models\Customer\CustomerModel();
            return $customer->isOwner(get_current_user_id(), $customer_id);
        }

        return false;
    }

    public function check_create_permission() {
        return current_user_can('add_customer');
    }

    public function check_update_permission(\WP_REST_Request $request) {
        $customer_id = $request->get_param('id');
        
        if (current_user_can('edit_all_customers')) {
            return true;
        }

        if (current_user_can('edit_own_customer')) {
            $customer = new \WPCustomer\Models\Customer\CustomerModel();
            return $customer->isOwner(get_current_user_id(), $customer_id);
        }

        return false;
    }

    public function check_delete_permission(\WP_REST_Request $request) {
        return current_user_can('delete_customer');
    }

    public function check_membership_permission() {
        return is_user_logged_in();
    }

    /**
     * Endpoint handlers
     */
    public function get_customers(\WP_REST_Request $request) {
        try {
            $page = $request->get_param('page');
            $per_page = $request->get_param('per_page');
            
            $model = new \WPCustomer\Models\Customer\CustomerModel();
            $customers = $model->paginate($page, $per_page);
            
            return new \WP_REST_Response([
                'success' => true,
                'data' => $customers
            ], 200);
            
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function get_customer(\WP_REST_Request $request) {
        try {
            $id = $request->get_param('id');
            
            $model = new \WPCustomer\Models\Customer\CustomerModel();
            $customer = $model->find($id);
            
            if (!$customer) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            return new \WP_REST_Response([
                'success' => true,
                'data' => $customer
            ], 200);
            
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function create_customer(\WP_REST_Request $request) {
        try {
            $data = [
                'name' => $request->get_param('name'),
                'npwp' => $request->get_param('npwp'),
                'nib' => $request->get_param('nib'),
                'created_by' => get_current_user_id()
            ];

            $model = new \WPCustomer\Models\Customer\CustomerModel();
            $id = $model->create($data);

            if (!$id) {
                throw new \Exception('Failed to create customer');
            }

            $customer = $model->find($id);

            return new \WP_REST_Response([
                'success' => true,
                'data' => $customer,
                'message' => 'Customer created successfully'
            ], 201);

        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update_customer(\WP_REST_Request $request) {
        try {
            $id = $request->get_param('id');
            $data = [];

            // Only update provided fields
            foreach (['name', 'npwp', 'nib'] as $field) {
                if ($request->has_param($field)) {
                    $data[$field] = $request->get_param($field);
                }
            }

            $model = new \WPCustomer\Models\Customer\CustomerModel();
            $updated = $model->update($id, $data);

            if (!$updated) {
                throw new \Exception('Failed to update customer');
            }

            $customer = $model->find($id);

            return new \WP_REST_Response([
                'success' => true,
                'data' => $customer,
                'message' => 'Customer updated successfully'
            ], 200);

        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function delete_customer(\WP_REST_Request $request) {
        try {
            $id = $request->get_param('id');
            
            $model = new \WPCustomer\Models\Customer\CustomerModel();
            $deleted = $model->delete($id);

            if (!$deleted) {
                throw new \Exception('Failed to delete customer');
            }

            return new \WP_REST_Response([
                'success' => true,
                'message' => 'Customer deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function get_customer_membership_levels() {
        try {
            $model = new \WPCustomer\Models\Membership\MembershipLevelModel();
            $levels = $model->get_all_levels();

            return new \WP_REST_Response([
                'success' => true,
                'data' => $levels
            ], 200);

        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
