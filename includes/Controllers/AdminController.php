<?php
namespace CustomerManagement\Controllers;

class AdminController extends BaseController {
    protected function register_ajax_handlers() {
        add_action('wp_ajax_get_cities', [$this, 'handle_get_cities']);
        add_action('wp_ajax_export_customers', [$this, 'handle_export_customers']);
        add_action('wp_ajax_get_locations', [$this, 'handle_get_locations']);
    }

    public function __construct() {
        add_action('init', [$this, 'register_post_status']);
        add_action('admin_head', [$this, 'add_menu_icon_styles']);
        add_filter('plugin_action_links_' . CUSTOMER_PLUGIN_BASENAME, [$this, 'add_plugin_action_links']);
    }

    public function register_post_status() {
        register_post_status('inactive', [
            'label' => _x('Inactive', 'customer-management'),
            'public' => false,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => false,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>')
        ]);
    }

    public function add_menu_icon_styles() {
        echo '<style>
            #toplevel_page_customer-management .wp-menu-image::before {
                content: "\f307";
            }
        </style>';
    }

    public function add_plugin_action_links($links) {
        $plugin_links = [
            '<a href="' . admin_url('admin.php?page=customer-management') . '">' . __('Settings', 'customer-management') . '</a>'
        ];
        return array_merge($plugin_links, $links);
    }

    public function handle_get_cities() {
        $this->verify_nonce();
        $this->verify_capability('read_customers');

        $province_id = isset($_POST['province_id']) ? intval($_POST['province_id']) : 0;
        if (!$province_id) {
            $this->send_error('Invalid province ID');
        }

        // Here you would typically fetch cities from your data source
        // For this example, we'll return some dummy data
        $cities = $this->get_cities_by_province($province_id);
        $this->send_success($cities);
    }

    public function handle_export_customers() {
        $this->verify_nonce();
        $this->verify_capability('export_customers');

        $membership_type = isset($_REQUEST['membership_type']) ? sanitize_text_field($_REQUEST['membership_type']) : '';
        $branch_id = isset($_REQUEST['branch_id']) ? intval($_REQUEST['branch_id']) : 0;

        // Build query conditions
        $conditions = [];
        if ($membership_type) {
            $conditions['membership_type'] = $membership_type;
        }
        if ($branch_id) {
            $conditions['branch_id'] = $branch_id;
        }

        // Get data
        $customers = $this->model->get_all(['where' => $conditions]);

        // Generate CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=customers-export-' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');

        // Add headers
        fputcsv($output, [
            'Name',
            'Email',
            'Phone',
            'Address',
            'Branch',
            'Employee',
            'Membership',
            'Province',
            'City',
            'Created At'
        ]);

        // Add data rows
        foreach ($customers as $customer) {
            fputcsv($output, [
                $customer->name,
                $customer->email,
                $customer->phone,
                $customer->address,
                $customer->branch_name,
                $customer->employee_name,
                $customer->membership_type,
                $customer->province_name,
                $customer->city_name,
                $customer->created_at
            ]);
        }

        fclose($output);
        exit;
    }

    public function handle_get_locations() {
        $this->verify_nonce();
        $this->verify_capability('read_customers');

        // Here you would typically fetch provinces and cities from your data source
        // For this example, we'll return some dummy data
        $locations = [
            'provinces' => $this->get_provinces(),
            'cities' => $this->get_cities()
        ];

        $this->send_success($locations);
    }

    private function get_provinces() {
        // Dummy data - replace with actual data source
        return [
            ['id' => 1, 'name' => 'DKI Jakarta'],
            ['id' => 2, 'name' => 'Jawa Barat'],
            ['id' => 3, 'name' => 'Jawa Tengah'],
            ['id' => 4, 'name' => 'Jawa Timur']
        ];
    }

    private function get_cities() {
        // Dummy data - replace with actual data source
        return [
            ['id' => 1, 'province_id' => 1, 'name' => 'Jakarta Pusat'],
            ['id' => 2, 'province_id' => 1, 'name' => 'Jakarta Selatan'],
            ['id' => 3, 'province_id' => 2, 'name' => 'Bandung'],
            ['id' => 4, 'province_id' => 2, 'name' => 'Bogor']
        ];
    }

    private function get_cities_by_province($province_id) {
        $all_cities = $this->get_cities();
        return array_filter($all_cities, function($city) use ($province_id) {
            return $city['province_id'] == $province_id;
        });
    }
}
