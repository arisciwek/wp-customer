<?php
/**
 * Customer Membership Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Membership
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Membership/CustomerMembershipController.php
 *
 * Description: Controller untuk mengelola operasi terkait membership customer:
 *              - View status membership
 *              - Upgrade/downgrade requests  
 *              - Period management
 *              - Grace period handling
 *              Includes permission validation dan error handling.
 */

namespace WPCustomer\Controllers\Membership;

use WPCustomer\Models\Customer\CustomerMembershipModel;
use WPCustomer\Models\Customer\CustomerMembershipLevelModel;
use WPCustomer\Cache\CustomerCacheManager;

class CustomerMembershipController {
    private CustomerMembershipModel $membership_model;
    private CustomerMembershipLevelModel $level_model;
    private CustomerCacheManager $cache;

    public function __construct() {
        $this->membership_model = new CustomerMembershipModel();
        $this->level_model = new CustomerMembershipLevelModel();
        $this->cache = new CustomerCacheManager();

        // Register all AJAX endpoints
        add_action('wp_ajax_get_membership_status', [$this, 'getMembershipStatus']);
        add_action('wp_ajax_upgrade_membership', [$this, 'upgradeMembership']);
        add_action('wp_ajax_extend_membership', [$this, 'extendMembership']); 
        add_action('wp_ajax_get_upgrade_options', [$this, 'getUpgradeOptions']);
    }

    /**
     * Get current membership status
     */
    public function getMembershipStatus() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
            if (!$customer_id) {
                throw new \Exception('Invalid customer ID');
            }

            // Get membership data
            $membership = $this->membership_model->findByCustomer($customer_id);
            if (!$membership) {
                throw new \Exception('No active membership found');
            }

            // Get level details
            $level = $this->level_model->getLevel($membership->level_id);
            if (!$level) {
                throw new \Exception('Invalid membership level');
            }

            // Format response data
            $response = [
                'status' => $membership->status,
                'level' => [
                    'id' => $level->id,
                    'name' => $level->name,
                    'max_staff' => $level->max_staff,
                    'max_departments' => $level->max_departments,
                    'capabilities' => json_decode($level->capabilities, true)
                ],
                'period' => [
                    'start_date' => $membership->start_date,
                    'end_date' => $membership->end_date,
                    'trial_end_date' => $membership->trial_end_date,
                    'grace_period_end_date' => $membership->grace_period_end_date
                ],
                'payment' => [
                    'status' => $membership->payment_status,
                    'method' => $membership->payment_method,
                    'price_paid' => $membership->price_paid
                ]
            ];

            wp_send_json_success($response);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process membership upgrade request
     */
    public function upgradeMembership() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
            $new_level_id = isset($_POST['level_id']) ? (int)$_POST['level_id'] : 0;

            if (!$customer_id || !$new_level_id) {
                throw new \Exception('Invalid parameters');
            }

            // Validate upgrade possibility
            if (!$this->membership_model->canUpgrade($customer_id, $new_level_id)) {
                throw new \Exception('Invalid upgrade path');
            }

            // Get current membership
            $current = $this->membership_model->findByCustomer($customer_id);
            if (!$current) {
                throw new \Exception('No active membership found');
            }

            // Calculate upgrade price
            $price = $this->membership_model->calculateUpgradePrice(
                $current->id,
                $new_level_id
            );

            // Update membership status to pending_upgrade
            $updated = $this->membership_model->upgradeMembership($current->id, $new_level_id);
            if (!$updated) {
                throw new \Exception('Failed to initiate upgrade');
            }

            // Clear relevant caches
            $this->cache->delete('membership', $current->id);
            $this->cache->delete('customer_membership', $customer_id);
			$this->cache->delete('customer_active_employee_count', $customer_id);

            wp_send_json_success([
                'message' => 'Upgrade initiated successfully',
                'upgrade_price' => $price
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Extend membership period
     */
    public function extendMembership() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
            $months = isset($_POST['months']) ? (int)$_POST['months'] : 0;

            if (!$customer_id || !$months) {
                throw new \Exception('Invalid parameters');
            }

            // Get current membership
            $current = $this->membership_model->findByCustomer($customer_id);
            if (!$current) {
                throw new \Exception('No active membership found');
            }

            // Extend the period
            $extended = $this->membership_model->extendPeriod($current->id, $months);
            if (!$extended) {
                throw new \Exception('Failed to extend membership');
            }

            // Clear caches
            $this->cache->delete('membership', $current->id);
            $this->cache->delete('customer_membership', $customer_id);

            // Get updated membership
            $updated = $this->membership_model->findByCustomer($customer_id);

            wp_send_json_success([
                'message' => 'Membership extended successfully',
                'new_end_date' => $updated->end_date
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

	/**
	 * Render membership tab content
	 * 
	 * @param int $customer_id Customer ID
	 * @return void
	 */
	public function renderMembershipTab($customer_id) {
	    try {
	        global $wpdb;

	        // Validate access
	        if (!$this->validator->validateAccess($customer_id)) {
	            throw new \Exception(__('You do not have permission to view this membership', 'wp-customer'));
	        }

	        // Get current membership data
	        $membership = $this->membership_model->findByCustomer($customer_id);
	        
	        // Get current level if membership exists
	        $current_level = null;
	        if ($membership) {
	            $current_level = $this->level_model->getLevel($membership->level_id);
	            if (!$current_level) {
	                throw new \Exception('Invalid membership level');
	            }
	        }

	        // Get employee count for staff usage
			$employee_count = $this->membership_model->getActiveEmployeeCount($customer_id);

	        // Get all available levels for upgrade options
	        $available_levels = $this->level_model->getAllLevels();

	        // Add trial info and price calculation for each level
	        foreach ($available_levels as &$level) {
	            // Add trial info
	            $level->trial_info = $this->getTrialInfo($membership, $level);
	            
	            // Calculate upgrade price if upgrading
	            if ($membership) {
	                $level->upgrade_price = $this->calculatePrice(
	                    $membership,
	                    $level,
	                    1 // Default to 1 month for initial display
	                );
	            }
	        }

	        // Prepare view data
	        $data = [
	            'customer_id' => $customer_id,
	            'membership' => $membership,
	            'current_level' => $current_level,
	            'employee_count' => (int)$employee_count,
	            'available_levels' => $available_levels,
	            'can_upgrade' => $this->validator->canPerformAction($customer_id, 'upgrade_membership'),
	            'i18n' => [
	                'loading' => __('Loading...', 'wp-customer'),
	                'confirm_upgrade' => __('Are you sure you want to upgrade to %s?', 'wp-customer'),
	                'upgrade_success' => __('Membership upgrade initiated successfully', 'wp-customer'),
	                'upgrade_error' => __('Failed to process upgrade', 'wp-customer')
	            ]
	        ];

	        // Load view template
	        require_once WP_CUSTOMER_PATH . 'src/Views/templates/customer/partials/_customer_membership.php';

	    } catch (\Exception $e) {
	        echo '<div class="notice notice-error"><p>' . esc_html($e->getMessage()) . '</p></div>';
	    }
	}
	
	/**
	 * Get available upgrade options including price calculations
	 * 
	 * @return void Response dikirim sebagai JSON
	 */
	public function getUpgradeOptions() {
	    try {
	        check_ajax_referer('wp_customer_nonce', 'nonce');

	        $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
	        $period_months = isset($_POST['period_months']) ? (int)$_POST['period_months'] : 1;
	        
	        if (!$customer_id) {
	            throw new \Exception('ID Customer tidak valid');
	        }

	        // Dapatkan membership saat ini
	        $current = $this->membership_model->findByCustomer($customer_id);
	        if (!$current) {
	            throw new \Exception('Tidak ditemukan membership aktif');
	        }

	        // Dapatkan semua level yang tersedia
	        $levels = $this->level_model->getAllLevels();

	        // Filter dan format opsi upgrade dengan perhitungan harga
	        $options = [];
	        foreach ($levels as $level) {
	            // Lewati level yang bukan upgrade
	            if (!$this->membership_model->canUpgrade($customer_id, $level->id)) {
	                continue;
	            }

	            // Hitung harga dasar
	            $base_price = $level->price_per_month * $period_months;
	            
	            // Hitung harga prorata untuk upgrade
	            $price = $this->calculatePrice(
	                $current,
	                $level,
	                $period_months
	            );

	            // Dapatkan informasi trial
	            $trial_info = $this->getTrialInfo($current, $level);

	            // Siapkan detail capabilities
	            $capabilities = json_decode($level->capabilities, true);

	            $options[] = [
	                'id' => $level->id,
	                'name' => $level->name,
	                'description' => $level->description,
	                'max_staff' => $level->max_staff,
	                'max_departments' => $level->max_departments,
	                'price_per_month' => $level->price_per_month,
	                'price_details' => [
	                    'base_price' => $base_price,
	                    'period_months' => $period_months,
	                    'total_price' => $price,
	                    'is_prorated' => $current !== null,
	                    'has_trial' => $trial_info['has_trial'],
	                    'trial_days' => $trial_info['trial_days'],
	                    'discount' => $trial_info['discount']
	                ],
	                'capabilities' => $capabilities,
	                'trial_info' => $trial_info
	            ];
	        }

	        wp_send_json_success([
	            'current_level' => $current->level_id,
	            'upgrade_options' => $options
	        ]);

	    } catch (\Exception $e) {
	        wp_send_json_error([
	            'message' => $e->getMessage()
	        ]);
	    }
	}

    /**
     * Helper to calculate price with prorating and trial considerations
     */
    private function calculatePrice($current_membership, $new_level, $period_months) {
        if ($current_membership) {
            // Calculate prorated price for upgrade
            $remaining_days = (strtotime($current_membership->end_date) - time()) / (60 * 60 * 24);
            if ($remaining_days <= 0) {
                return $new_level->price_per_month * $period_months;
            }

            $current_level = $this->level_model->getLevel($current_membership->level_id);
            $price_difference = $new_level->price_per_month - $current_level->price_per_month;
            $prorated_amount = ($price_difference / 30) * $remaining_days;

            return $prorated_amount + ($new_level->price_per_month * $period_months);
        } else {
            // Calculate new subscription price
            $base_price = $new_level->price_per_month * $period_months;

            // Apply trial discount if available
            if ($new_level->is_trial_available) {
                $trial_discount = ($new_level->trial_days / 30) * $new_level->price_per_month;
                return $base_price - $trial_discount;
            }

            return $base_price;
        }
    }

    /**
     * Helper to get trial availability and info
     */
    private function getTrialInfo($current_membership, $level) {
        if ($current_membership || !$level->is_trial_available) {
            return [
                'has_trial' => false,
                'trial_days' => 0,
                'discount' => 0
            ];
        }

        $trial_discount = ($level->trial_days / 30) * $level->price_per_month;
        
        return [
            'has_trial' => true,
            'trial_days' => $level->trial_days,
            'discount' => $trial_discount
        ];
    }

}
