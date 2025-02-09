<?php
/**
 * Membership Demo Data Generator
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Demo/MembershipDemoData.php
 *
 * Description: Generate membership demo data untuk setiap branch.
 *              Masing-masing branch mendapat membership dengan:
 *              - Random level (Regular/Priority/Utama)
 *              - Random trial status
 *              - Random payment status (paid/pending)
 *              Menggunakan transaction untuk konsistensi data
 *              dan cache invalidation untuk performa.
 *              
 * Dependencies:
 * - CustomerMembershipModel     : Handle membership operations
 * - CustomerMembershipLevelModel: Get level data
 * - BranchModel                 : Get branch data
 * - CustomerModel               : Validate customer data
 *
 * Order of operations:
 * 1. Validate prerequisites (development mode, tables, data)
 * 2. Get all active branches
 * 3. For each branch:
 *    - Get random level
 *    - Generate dates based on level settings
 *    - Set random trial/payment status
 *    - Create membership record
 *
 * Changelog:
 * 1.0.0 - 2024-02-09
 * - Initial version
 * - Added membership generation for branches
 * - Added random level assignment
 * - Added trial and payment handling
 */

namespace WPCustomer\Database\Demo;

use WPCustomer\Models\Customer\CustomerMembershipModel;
use WPCustomer\Models\Customer\CustomerMembershipLevelModel;
use WPCustomer\Models\Branch\BranchModel;
use WPCustomer\Models\Customer\CustomerModel;

defined('ABSPATH') || exit;

class MembershipDemoData extends AbstractDemoData {
    use CustomerDemoDataHelperTrait;

    private $membershipModel;
    private $levelModel;
    protected $branchModel;
    protected $customerModel;
    
    private $membership_ids = [];
    private $levels_data = [];

    public function __construct() {
        parent::__construct();
        $this->membershipModel = new CustomerMembershipModel();
        $this->levelModel = new CustomerMembershipLevelModel();
        $this->branchModel = new BranchModel();
        $this->customerModel = new CustomerModel();
    }

    /**
     * Validate prerequisites before generation
     */
    protected function validate(): bool {
        try {
            if (!$this->isDevelopmentMode()) {
                throw new \Exception('Development mode is not enabled');
            }

            // Check tables exist
            global $wpdb;
            $tables = [
                'app_customer_memberships',
                'app_customer_membership_levels',
                'app_branches'
            ];

            foreach ($tables as $table) {
                if (!$wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$table}'")) {
                    throw new \Exception("Table {$table} not found");
                }
            }

            // Get and validate membership levels
            $this->levels_data = $this->levelModel->getAllLevels();
            if (empty($this->levels_data)) {
                throw new \Exception('No membership levels found');
            }

            // Check for existing branches
            $branch_count = $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->prefix}app_branches 
                WHERE status = 'active'
            ");
            if ($branch_count == 0) {
                throw new \Exception('No active branches found');
            }

            return true;

        } catch (\Exception $e) {
            $this->debug('Validation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate membership data
     */
    protected function generate(): void {
        if ($this->shouldClearData()) {
            $this->clearExistingData();
        }

        global $wpdb;
        $current_date = current_time('mysql');

        try {
            // Get all active branches
            $branches = $wpdb->get_results("
                SELECT b.*, c.user_id as customer_user_id 
                FROM {$wpdb->prefix}app_branches b
                JOIN {$wpdb->prefix}app_customers c ON b.customer_id = c.id
                WHERE b.status = 'active'
            ");

            foreach ($branches as $branch) {
                // Get random membership level
                $level = $this->getRandomLevel();
                
                // Determine if trial should be used
                $use_trial = $level->is_trial_available && (bool)rand(0, 1);

				// Get random period_months (1, 3, 6, atau 12 bulan)
				$period_months = [1, 3, 6, 12][array_rand([1, 3, 6, 12])];
                
				// Random payment_date dalam 14 hari ke belakang
				$random_days = rand(0, 14);
				$payment_date = date('Y-m-d H:i:s', strtotime("-{$random_days} days"));

				// Random start_date 2,5,20 menit setelah payment
				$minutes_after_payment = [2, 5, 20][array_rand([2, 5, 20])];
				$start_date = date('Y-m-d H:i:s', strtotime($payment_date . " +{$minutes_after_payment} minutes"));

				// Calculate end_date based on period_months and start_date
				$end_date = date('Y-m-d H:i:s', strtotime($start_date . " +{$period_months} months"));
                
                // Set trial dates if applicable
                $trial_end_date = null;
                if ($use_trial) {
                    $trial_end_date = date('Y-m-d H:i:s', 
                        strtotime($start_date . ' +' . $level->trial_days . ' days')
                    );
                }

                // Random payment status
                $is_paid = (bool)rand(0, 1);
                $payment_status = $is_paid ? 'paid' : 'pending';
                $payment_date = $is_paid ? $current_date : null;
                $price_paid = $is_paid ? $level->price_per_month : 0;

				// Prepare membership data
				$membership_data = [
				    'customer_id' => $branch->customer_id,
				    'branch_id' => $branch->id,
				    'level_id' => $level->id,
				    'status' => $use_trial ? 'active' : ($is_paid ? 'active' : 'pending_payment'),
				    'period_months' => $period_months,  // Random period
				    'start_date' => $start_date,        // Random start date
				    'end_date' => $end_date,           // Calculated end date
				    'trial_end_date' => $trial_end_date,
				    'grace_period_end_date' => null,
				    'price_paid' => $is_paid ? ($level->price_per_month * $period_months) : 0, // Price adjusted for period
				    'payment_method' => $is_paid ? 'bank_transfer' : null,
				    'payment_status' => $payment_status,
				    'payment_date' => $payment_date,
				    'created_by' => $branch->customer_user_id,
				    'created_at' => $current_date
				];

                // Create membership
                $membership_id = $this->membershipModel->create($membership_data);
                if (!$membership_id) {
                    throw new \Exception("Failed to create membership for branch: {$branch->id}");
                }

                $this->membership_ids[] = $membership_id;
                $this->debug("Created membership {$membership_id} for branch {$branch->id} with level {$level->name}");
            }

            $this->debug('Membership generation completed. Total: ' . count($this->membership_ids));

        } catch (\Exception $e) {
            $this->debug('Error in membership generation: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get random membership level
     */
    private function getRandomLevel(): object {
        return $this->levels_data[array_rand($this->levels_data)];
    }

    /**
     * Clear existing membership data
     */
    private function clearExistingData(): void {
        global $wpdb;
        
        try {
            $wpdb->query("DELETE FROM {$wpdb->prefix}app_customer_memberships WHERE id > 0");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}app_customer_memberships AUTO_INCREMENT = 1");
            $this->debug('Existing membership data cleared');
        } catch (\Exception $e) {
            $this->debug('Error clearing existing data: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get generated membership IDs
     */
    public function getMembershipIds(): array {
        return $this->membership_ids;
    }
}
