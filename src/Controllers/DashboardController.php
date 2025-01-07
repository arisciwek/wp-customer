<?php
/**
 * Dashboard Controller Class
 *
 * @package     WP_Customer
 * @subpackage  Controllers
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/DashboardController.php
 *
 * Description: Controller untuk mengelola statistik dashboard.
 *              Menyediakan endpoint untuk mendapatkan total branch
 *              secara global untuk tampilan statistik.
 */

namespace WPCustomer\Controllers;

use WPCustomer\Models\Branch\BranchModel;

class DashboardController {
    private BranchModel $branch_model;

    public function __construct() {
        $this->branch_model = new BranchModel();

        // Register AJAX endpoint
        add_action('wp_ajax_get_dashboard_stats', [$this, 'getDashboardStats']);
        add_action('wp_ajax_nopriv_get_dashboard_stats', [$this, 'getDashboardStats']);
    }

    public function getDashboardStats() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $stats = [
                'total_branches' => $this->branch_model->getTotalCount()
            ];

            wp_send_json_success($stats);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}
