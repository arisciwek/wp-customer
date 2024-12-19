<?php
namespace CustomerManagement\Controllers;

class AdminController extends BaseController {
    protected function register_ajax_handlers() {
        add_action('wp_ajax_get_cities', [$this, 'handle_get_cities']);
        add_action('wp_ajax_export_customers', [$this, 'handle_export_customers']);
        add_action('wp_ajax_get_locations', [$this, 'handle_get_locations']);
        add_action('wp_ajax_get_dashboard_data', [$this, 'handle_get_dashboard_data']);
    }

    // ... (kode lain tetap sama)

    public function handle_get_dashboard_data() {
        $this->verify_nonce();
        $this->verify_capability('read_customers');

        try {
            // Get total customers
            $total_customers = $this->get_total_customers();

            // Get membership distribution
            $membership_distribution = $this->get_membership_distribution();

            // Get recent activities
            $recent_activities = $this->get_recent_activities();

            // Get branch distribution
            $branch_distribution = $this->get_branch_distribution();

            $data = array(
                'total_customers' => $total_customers,
                'membership_distribution' => $membership_distribution,
                'recent_activities' => $recent_activities,
                'branch_distribution' => $branch_distribution
            );

            $this->send_success($data);

        } catch (\Exception $e) {
            $this->send_error('Failed to fetch dashboard data: ' . $e->getMessage());
        }
    }

    private function get_total_customers() {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}customers WHERE status = 'active'");
    }

    private function get_membership_distribution() {
        global $wpdb;
        $results = $wpdb->get_results("
            SELECT 
                ml.slug,
                COUNT(c.id) as count
            FROM {$wpdb->prefix}customer_membership_levels ml
            LEFT JOIN {$wpdb->prefix}customers c ON ml.id = c.membership_level_id AND c.status = 'active'
            GROUP BY ml.id, ml.slug
        ");

        $distribution = array();
        foreach ($results as $row) {
            $distribution[$row->slug] = (int) $row->count;
        }

        return $distribution;
    }

    private function get_recent_activities() {
        global $wpdb;
        // Contoh sederhana, sesuaikan dengan struktur tabel aktivitas Anda
        return array();
    }

    private function get_branch_distribution() {
        global $wpdb;
        $results = $wpdb->get_results("
            SELECT 
                b.name,
                COUNT(c.id) as count
            FROM {$wpdb->prefix}customer_branches b
            LEFT JOIN {$wpdb->prefix}customers c ON b.id = c.branch_id AND c.status = 'active'
            GROUP BY b.id, b.name
            ORDER BY count DESC
        ");

        $distribution = array();
        foreach ($results as $row) {
            $distribution[$row->name] = (int) $row->count;
        }

        return $distribution;
    }
}
