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
use WPCustomer\Models\Membership\MembershipLevelModel;
use WPCustomer\Cache\CustomerCacheManager;

class CustomerMembershipController {
    private CustomerMembershipModel $membership_model;
    private CustomerMembershipLevelModel $level_model;
    private CustomerCacheManager $cache;

    public function __construct() {
        
        //$this->membership_model = new CustomerMembershipModel();
        //$this->level_model = new MembershipLevelModel();
        //$this->cache = new CustomerCacheManager();

        // Register all AJAX endpoints
        add_action('wp_ajax_get_membership_status', [$this, 'getMembershipStatus']);
        add_action('wp_ajax_upgrade_membership', [$this, 'upgradeMembership']);
        add_action('wp_ajax_extend_membership', [$this, 'extendMembership']); 
        add_action('wp_ajax_get_upgrade_options', [$this, 'getUpgradeOptions']);
/*
    	add_action('wp_ajax_get_membership_level', [$this, 'getMembershipLevel']);
		add_action('wp_ajax_save_membership_level', [$this, 'saveMembershipLevel']);
        add_action('wp_ajax_get_membership_level_data', [$this, 'getMembershipLevelData']);
*/
		$this->membership_model = new CustomerMembershipModel();
		$this->cache = new CustomerCacheManager();
        return;
    }
/*
	public function saveMembershipLevel() {
	   try {
	       check_ajax_referer('wp_customer_nonce', 'nonce');

	       $levelModel = new CustomerMembershipLevelModel();
	       $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
	       
	       $result = $levelModel->save($_POST, $id);

	       if (!$result) {
	           throw new \Exception('Failed to save membership level');
	       }

	       wp_send_json_success(['message' => 'Level saved successfully']);

	   } catch (\Exception $e) {
	       wp_send_json_error(['message' => $e->getMessage()]);
	   }
	}

	public function getMembershipFields() {
	   try {
	       check_ajax_referer('wp_customer_nonce', 'nonce');
	       
	       $featureModel = new MembershipFeatureModel();
	       $fields = $featureModel->get_all_features_by_group();
	       
	       wp_send_json_success($fields);
	       
	   } catch (\Exception $e) {
	       wp_send_json_error(['message' => $e->getMessage()]);
	   }
	}

	public function getMembershipLevel() {
	    try {
	        check_ajax_referer('wp_customer_nonce', 'nonce');
	        
	        $level_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
	        error_log('Getting level data for ID: ' . $level_id);

	        if (!$level_id) {
	            throw new \Exception('Invalid level ID');
	        }

	        // Get level data
	        $level = $this->level_model->getLevel($level_id);
	        error_log('Raw level data: ' . print_r($level, true));

	        if (!$level) {
	            throw new \Exception('Level not found');
	        }

	        // Format response data
	        $response = [
	            'id' => $level->id,
	            'name' => $level->name,
	            'description' => $level->description,
	            'price_per_month' => $level->price_per_month,
	            'max_staff' => $level->max_staff,
	            'max_departments' => $level->max_departments,
	            'is_trial_available' => $level->is_trial_available,
	            'trial_days' => $level->trial_days,
	            'grace_period_days' => $level->grace_period_days,
	            'capabilities' => is_string($level->capabilities) ? $level->capabilities : json_encode($level->capabilities)
	        ];
	        
	        error_log('Formatted response: ' . print_r($response, true));
	        wp_send_json_success($response);

	    } catch (\Exception $e) {
	        error_log('Error in get_membership_level: ' . $e->getMessage());
	        wp_send_json_error([
	            'message' => $e->getMessage()
	        ]);
	    }

	}

	// Rename dari createUpgradeButton menjadi getMembershipLevelData
	public function getMembershipLevelData() {
	    try {
	        check_ajax_referer('wp_customer_nonce', 'nonce');
	        
	        $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
	        if (!$customer_id) {
	            throw new \Exception('Invalid customer ID');
	        }

	        // Get current membership
	        $current = $this->membership_model->findByCustomer($customer_id);
	        
	        error_log('$customer_id ' . $customer_id);

	        // Get membership levels data
	        $levels = $this->level_model->getAllLevels();
	        
	        // Format data untuk setiap level
	        $formatted_levels = [];
	        foreach ($levels as $level) {
	            $data = [
	                'name' => $level->name,
	                'slug' => $level->slug,
	                'description' => $level->description,
	                'price_per_month' => $level->price_per_month,
	                'max_staff' => $level->max_staff,
	                'capabilities' => $level->capabilities,
	                'is_trial_available' => $level->is_trial_available,
	                'trial_days' => $level->trial_days
	            ];

	            // Add upgrade button if eligible
	            if ($this->membership_model->canUpgrade($customer_id, $level->id)) {
	                $data['upgrade_button'] = sprintf(
	                    '<button type="button" class="button button-primary" id="upgrade-%s-btn">%s</button>',
	                    esc_attr($level->slug),
	                    sprintf(__('Upgrade ke %s', 'wp-customer'), $level->name)
	                );
	            }

	            $formatted_levels[$level->slug] = $data;
	        }

	        wp_send_json_success([
	            'current_level' => $current ? $current->level_id : null,
	            'levels' => $formatted_levels
	        ]);

	    } catch (\Exception $e) {
	        wp_send_json_error([
	            'message' => $e->getMessage()
	        ]);
	    }
	}
*/
	public function createMembership(array $membership_data): int {
	    try {
	        // Validate data structure
	        if (empty($membership_data['customer_id']) || empty($membership_data['level_id'])) {
	            throw new \Exception('Invalid membership data');
	        }

	        // Create via model
	        $membership_id = $this->membership_model->create($membership_data);
	        
	        if (!$membership_id) {
	            throw new \Exception('Failed to create membership');
	        }

	        // Clear related caches
	        $this->cache->delete('membership', $membership_id);
	        $this->cache->delete('customer_membership', $membership_data['customer_id']);
	        $this->cache->invalidateDataTableCache('membership_list', [
	            'customer_id' => $membership_data['customer_id']
	        ]);

	        return $membership_id;

	    } catch (\Exception $e) {
	        $this->debug_log('Error creating membership: ' . $e->getMessage());
	        throw $e;
	    }
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
			    'current_staff' => $employee_count,
			    'period' => [
			        'start_date' => $membership->start_date,
			        'end_date' => $membership->end_date
			    ],
			    // Get data untuk semua level
			    'regular' => $this->level_model->getFormattedLevelData('regular'),
			    'priority' => $this->level_model->getFormattedLevelData('priority'),
			    'utama' => $this->level_model->getFormattedLevelData('utama')
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

            $membershipLevel = $this->membershipLevelModel->getAllLevels();

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
