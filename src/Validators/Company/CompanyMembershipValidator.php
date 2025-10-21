<?php
/**
 * Company Membership Validator
 *
 * @package     WP_Customer
 * @subpackage  Validators/Membership
 * @version     1.0.10
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Validators/Company/CompanyMembershipValidator.php
 *
 * Description: Validator untuk memvalidasi operasi terkait Company membership
 *              - Validasi upgrade eligibility
 *              - Validasi payment details
 *              - Validasi extension requests
 *              
 * Dependencies:
 * - CompanyMembershipModel untuk check data
 * - MembershipLevelModel untuk validasi level
 */

namespace WPCustomer\Validators\Company;

use WPCustomer\Models\Company\CompanyMembershipModel;
use WPCustomer\Models\Membership\MembershipLevelModel;

class CompanyMembershipValidator {
    private $membership_model;
    private $level_model;
    
    public function __construct() {
        $this->membership_model = new CompanyMembershipModel();
        $this->level_model = new MembershipLevelModel();
    }
    
    /**
     * Validate upgrade eligibility
     * 
     * @param int $company_id Customer ID
     * @param int $target_level_id Target level ID
     * @return bool|WP_Error True if eligible or WP_Error with reason
     */
    public function validateUpgradeEligibility($company_id, $target_level_id) {
        // Check if customer exists
        $customer = $this->membership_model->getCustomerData($company_id);
        if (!$customer) {
            return new \WP_Error(
                'invalid_customer',
                __('Customer tidak ditemukan', 'wp-customer')
            );
        }
        
        // Check if customer has active membership
        $current = $this->membership_model->findByCompany($company_id);
        if (!$current) {
            return new \WP_Error(
                'no_membership',
                __('Tidak ditemukan data membership aktif', 'wp-customer')
            );
        }
        
        // Check membership status
        if ($current->status === 'pending_upgrade') {
            return new \WP_Error(
                'pending_upgrade',
                __('Sudah ada permintaan upgrade yang sedang diproses', 'wp-customer')
            );
        }
        
        // Check target level existence
        $target_level = $this->level_model->get_level($target_level_id);
        if (!$target_level) {
            return new \WP_Error(
                'invalid_level',
                __('Level target tidak ditemukan', 'wp-customer')
            );
        }
        
        // Check if target level is higher than current
        $current_level = $this->level_model->get_level($current->level_id);
        if (!$current_level) {
            return new \WP_Error(
                'invalid_current_level',
                __('Level saat ini tidak valid', 'wp-customer')
            );
        }
        
        if ($target_level['sort_order'] <= $current_level['sort_order']) {
            return new \WP_Error(
                'invalid_upgrade_path',
                __('Tidak dapat upgrade ke level yang sama atau lebih rendah', 'wp-customer')
            );
        }
        
        // Check resource compatibility
        $resource_validation = $this->validateResourceCompatibility($company_id, $target_level);
        if (is_wp_error($resource_validation)) {
            return $resource_validation;
        }
        
        // Check payment status
        $payment_validation = $this->validatePaymentStatus($company_id);
        if (is_wp_error($payment_validation)) {
            return $payment_validation;
        }
        
        return true;
    }
    
    /**
     * Validate if current resource usage is compatible with target level
     * 
     * @param int $company_id Customer ID
     * @param array $target_level Target level data
     * @return bool|WP_Error True if compatible or WP_Error with reason
     */
    private function validateResourceCompatibility($company_id, $target_level) {
        // Get current resource usage
        $employee_count = $this->membership_model->getActiveEmployeeCount($company_id);
        $branch_count = $this->membership_model->getActiveBranchCount($company_id);
        
        // Parse target level capabilities
        $capabilities = json_decode($target_level['capabilities'], true);
        
        // Check staff limit
        if (isset($capabilities['resources']['max_staff']['value'])) {
            $max_staff = $capabilities['resources']['max_staff']['value'];
            
            // If not unlimited and current usage exceeds limit
            if ($max_staff >= 0 && $employee_count > $max_staff) {
                return new \WP_Error(
                    'exceeds_staff_limit',
                    sprintf(
                        __('Jumlah staff saat ini (%d) melebihi batas level target (%d)', 'wp-customer'),
                        $employee_count,
                        $max_staff
                    )
                );
            }
        }
        
        // Check branch limit
        if (isset($capabilities['resources']['max_branches']['value'])) {
            $max_branches = $capabilities['resources']['max_branches']['value'];
            
            // If not unlimited and current usage exceeds limit
            if ($max_branches >= 0 && $branch_count > $max_branches) {
                return new \WP_Error(
                    'exceeds_branch_limit',
                    sprintf(
                        __('Jumlah cabang saat ini (%d) melebihi batas level target (%d)', 'wp-customer'),
                        $branch_count,
                        $max_branches
                    )
                );
            }
        }
        
        return true;
    }
    
    /**
     * Validate customer payment status
     * 
     * @param int $company_id Customer ID
     * @return bool|WP_Error True if ok or WP_Error with reason
     */
    private function validatePaymentStatus($company_id) {
        // Check for unpaid invoices
        $unpaid_count = $this->membership_model->getUnpaidInvoiceCount($company_id);
        
        if ($unpaid_count > 0) {
            return new \WP_Error(
                'unpaid_invoices',
                sprintf(
                    __('Terdapat %d invoice yang belum dibayar. Harap selesaikan pembayaran sebelum mengajukan upgrade.', 'wp-customer'),
                    $unpaid_count
                )
            );
        }
        
        return true;
    }
    
    /**
     * Validate payment details for upgrade
     * 
     * @param array $payment_data Payment data
     * @return bool|WP_Error True if valid or WP_Error with reason
     */
    public function validatePaymentDetails($payment_data) {
        // Check required fields
        $required_fields = ['payment_method', 'amount', 'company_id'];
        
        foreach ($required_fields as $field) {
            if (empty($payment_data[$field])) {
                return new \WP_Error(
                    'missing_field',
                    sprintf(__('Field %s harus diisi', 'wp-customer'), $field)
                );
            }
        }
        
        // Validate payment method
        $allowed_methods = ['transfer_bank', 'virtual_account', 'credit_card'];
        
        if (!in_array($payment_data['payment_method'], $allowed_methods)) {
            return new \WP_Error(
                'invalid_payment_method',
                __('Metode pembayaran tidak valid', 'wp-customer')
            );
        }
        
        // Validate amount
        if (!is_numeric($payment_data['amount']) || $payment_data['amount'] <= 0) {
            return new \WP_Error(
                'invalid_amount',
                __('Jumlah pembayaran tidak valid', 'wp-customer')
            );
        }
        
        return true;
    }
    
    /**
     * Validate membership extension request
     * 
     * @param int $company_id Customer ID
     * @param int $months Extension period in months
     * @return bool|WP_Error True if valid or WP_Error with reason
     */
    public function validateExtensionRequest($company_id, $months) {
        // Check if customer exists
        $customer = $this->membership_model->getCustomerData($company_id);
        if (!$customer) {
            return new \WP_Error(
                'invalid_customer',
                __('Customer tidak ditemukan', 'wp-customer')
            );
        }
        
        // Check if customer has active membership
        $current = $this->membership_model->findByCustomer($company_id);
        if (!$current) {
            return new \WP_Error(
                'no_membership',
                __('Tidak ditemukan data membership aktif', 'wp-customer')
            );
        }
        
        // Validate period
        if ($months < 1 || $months > 36) {
            return new \WP_Error(
                'invalid_period',
                __('Periode perpanjangan harus antara 1-36 bulan', 'wp-customer')
            );
        }
        
        return true;
    }
}

