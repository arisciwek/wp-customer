<?php
/**
 * Company Membership Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Company
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Company/CompanyMembershipController.php
 *
 * Description: Controller untuk mengelola operasi terkait membership customer:
 *              - View status membership
 *              - Upgrade/downgrade requests  
 *              - Period management
 *              - Grace period handling
 *              Includes permission validation dan error handling.
 * 
 * Changelog:
 * v2.0.0 - 2025-03-12
 * - Added customer-facing endpoints
 * - Added upgrade eligibility checks
 * - Added price calculation for upgrades
 * - Improved error handling and validation
 * - Added caching integration
 */

namespace WPCustomer\Controllers\Company;

use WPCustomer\Models\Company\CompanyMembershipModel;
use WPCustomer\Models\Membership\MembershipLevelModel;
use WPCustomer\Cache\CustomerCacheManager;
use WPCustomer\Validators\Company\CompanyMembershipValidator;

class CompanyMembershipController {
    private $membership_model;
    private $level_model;
    private $cache;
    private $validator;

    public function __construct() {
        $this->membership_model = new CompanyMembershipModel();
        $this->level_model = new MembershipLevelModel();
        $this->cache = new CustomerCacheManager();
        $this->validator = new CompanyMembershipValidator();

        // Register company-facing AJAX endpoints
        add_action('wp_ajax_get_company_membership_status', [$this, 'getMembershipStatus']);
        add_action('wp_ajax_get_company_upgrade_options', [$this, 'getUpgradeOptions']);
        add_action('wp_ajax_request_upgrade_company_membership', [$this, 'requestUpgradeMembership']);
        add_action('wp_ajax_check_upgrade_eligibility_company_membership', [$this, 'checkUpgradeEligibility']);
        add_action('wp_ajax_get_all_membership_levels', [$this, 'getAllMembershipLevels']);

        // Register admin/staff AJAX endpoints
        add_action('wp_ajax_save_membership_level', [$this, 'saveMembershipLevel']);
        add_action('wp_ajax_get_company_membership_level_data', [$this, 'getMembershipLevelData']);
        add_action('wp_ajax_extend_company_membership', [$this, 'extendMembership']);
    }

    /**
     * Validate common request parameters and permissions
     * 
     * @param string $nonce_action The nonce action to verify
     * @param string $permission The permission to check (optional)
     * @return int|WP_Error Company ID if valid, WP_Error otherwise
     */
    private function validateRequest($nonce_action = 'wp_customer_nonce', $permission = '') {
        // Verify nonce
        check_ajax_referer($nonce_action, 'nonce');
        
        // Get customer ID from request
        $company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;
        if (!$company_id) {
            return new \WP_Error('invalid_params', __('ID Company tidak valid', 'wp-customer'));
        }
        
        // Check permissions if specified
        if (!empty($permission) && !current_user_can($permission)) {
            if (!$this->userCanAccessCustomer($company_id)) {
                return new \WP_Error('access_denied', __('Anda tidak memiliki izin untuk mengakses data ini', 'wp-customer'));
            }
        }
        
        return $company_id;
    }
    
    /**
     * Check if current user can access customer data
     * 
     * @param int $company_id The customer ID to check
     * @return bool True if user can access, false otherwise
     */
    private function userCanAccessCustomer($company_id) {
        // Admin can access any customer
        if (current_user_can('manage_options')) {
            return true;
        }
        
        // Get current user ID
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            return false;
        }
        
        // Check if user is owner of the customer
        $customer = $this->membership_model->getCustomerOwner($company_id);
        if ($customer && $customer->user_id == $current_user_id) {
            return true;
        }
        
        return false;
    }

    /**
     * Get membership status dengan informasi fitur yang lebih lengkap
     */
    public function getMembershipStatus() {
        try {
            // Check if nonce is valid
            if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
                wp_send_json_error([
                    'message' => __('Validasi keamanan gagal', 'wp-customer'),
                    'code' => 'invalid_nonce'
                ]);
                return;
            }
            
            // Get company ID
            $company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;
            error_log("getMembershipStatus - Received company_id: " . $company_id);

            if (!$company_id) {
                wp_send_json_error([
                    'message' => __('ID Company tidak valid', 'wp-customer'),
                    'code' => 'invalid_company_id',
                    'received' => $_POST
                ]);
                return;
            }
            
            // User permission check (modify as needed)
            if (!current_user_can('manage_options') && !$this->userCanAccessCustomer($company_id)) {
                wp_send_json_error([
                    'message' => __('Anda tidak memiliki izin untuk mengakses data ini', 'wp-customer'),
                    'code' => 'access_denied'
                ]);
                return;
            }

            // Try to get from cache first
            $cache_key = "membership_status_{$company_id}";
            $cached_data = $this->cache->get('company_membership', $cache_key);
            if ($cached_data !== null) {
                wp_send_json_success($cached_data);
                return;
            }

            // Get membership data from model
            $membership = $this->membership_model->findByCompany($company_id);
            if (!$membership) {
                error_log("getMembershipStatus - No membership found for company_id: " . $company_id);

                // Cari level default
                $default_level = $this->level_model->get_level(1); // Ambil level dengan ID 1 (biasanya Regular)
                if (!$default_level) {
                    wp_send_json_error([
                        'message' => __('Tidak ditemukan data membership aktif dan level default', 'wp-customer'),
                        'code' => 'no_membership'
                    ]);
                    return;
                }

                // Buat response dengan level default
                $default_response = [
                    'id' => 0,
                    'company_id' => $company_id,
                    'level_id' => $default_level['id'],
                    'level_name' => $default_level['name'],
                    'level_slug' => $default_level['slug'],
                    'status' => 'inactive',
                    'is_active' => false,
                    'in_grace_period' => false,
                    'resource_usage' => [
                        'employees' => [
                            'current' => 0,
                            'limit' => isset($default_level['max_staff']) ? $default_level['max_staff'] : 2,
                            'percentage' => 0
                        ]
                    ],
                    'period' => [
                        'start_date' => date_i18n(get_option('date_format')),
                        'end_date' => date_i18n(get_option('date_format'), strtotime('+1 month')),
                        'remaining_days' => 30
                    ],
                    'active_features' => $this->getDefaultActiveFeatures($default_level),
                    'price_per_month' => $default_level['price_per_month']
                ];

                // Cache the response
                $this->cache->set('company_membership', $default_response, 3600, $cache_key);
                wp_send_json_success($default_response);
                return;
            }

            // Jika membership ditemukan, lanjutkan seperti normal
            // Get level details
            $level = $this->level_model->getLevel($membership->level_id);
            if (!$level) {
                throw new \Exception(__('Level membership tidak valid', 'wp-customer'));
            }

            // Get usage data
            $employee_count = $this->membership_model->getActiveEmployeeCount($company_id);
            
            // Get capabilities
            $capabilities = json_decode($level->capabilities, true);
            $active_features = $this->getFormattedCapabilities($capabilities);

            // Format dates
            $start_date = date_i18n(get_option('date_format'), strtotime($membership->start_date));
            $end_date = date_i18n(get_option('date_format'), strtotime($membership->end_date));
            
            // Calculate remaining days
            $now = time();
            $end = strtotime($membership->end_date);
            $remaining_days = ceil(($end - $now) / (60 * 60 * 24));
            
            // Format response data
            $response = [
                'id' => $membership->id,
                'company_id' => $company_id,
                'level_id' => $level->id,
                'level_name' => $level->name,
                'level_slug' => $level->slug,
                'status' => $membership->status,
                'is_active' => $membership->status === 'active',
                'in_grace_period' => $membership->status === 'grace',
                'resource_usage' => [
                    'employees' => [
                        'current' => $employee_count,
                        'limit' => isset($capabilities['resources']['max_staff']['value']) 
                            ? $capabilities['resources']['max_staff']['value'] : 0,
                        'percentage' => isset($capabilities['resources']['max_staff']['value']) && $capabilities['resources']['max_staff']['value'] > 0 
                            ? round(($employee_count / $capabilities['resources']['max_staff']['value']) * 100) : 0
                    ]
                ],
                'period' => [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'remaining_days' => $remaining_days
                ],
                'active_features' => $active_features,
                'price_per_month' => $level->price_per_month
            ];

            // Cache the response
            $this->cache->set('customer_membership', $response, 3600, $cache_key);

            wp_send_json_success($response);

        } catch (\Exception $e) {
            error_log("getMembershipStatus - Exception: " . $e->getMessage());

            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => 'exception',
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Get default active features for level
     * 
     * @param array $level Level data
     * @return array Default active features
     */
    private function getDefaultActiveFeatures($level) {
        $capabilities = json_decode($level['capabilities'], true);
        return $this->getFormattedCapabilities($capabilities);
    }


    /**
     * Format capabilities for frontend display
     * 
     * @param array $capabilities Raw capabilities from database
     * @return array Formatted capabilities for display
     */
    private function getFormattedCapabilities($capabilities) {
        $formatted = [];
        
        // Process features
        if (isset($capabilities['features'])) {
            foreach ($capabilities['features'] as $key => $feature) {
                if (!empty($feature['value'])) {
                    $formatted[] = [
                        'key' => $key,
                        'label' => $feature['label'] ?? $this->getCapabilityLabel($key),
                        'type' => 'feature',
                        'value' => true
                    ];
                }
            }
        }
        
        // Process limits
        if (isset($capabilities['limits'])) {
            foreach ($capabilities['limits'] as $key => $limit) {
                $formatted[] = [
                    'key' => $key,
                    'label' => $limit['label'] ?? $this->getLimitLabel($key),
                    'type' => 'limit',
                    'value' => $limit['value']
                ];
            }
        }
        
        return $formatted;
    }
    
    /**
     * Get human-readable label for capability
     * 
     * @param string $capability_key The capability key
     * @return string Human-readable label
     */
    private function getCapabilityLabel($capability_key) {
        $labels = [
            'can_add_staff' => __('Dapat menambah staff', 'wp-customer'),
            'can_export' => __('Dapat export data', 'wp-customer'),
            'can_bulk_import' => __('Dapat melakukan import massal', 'wp-customer'),
            'can_manage_departments' => __('Dapat mengelola departemen', 'wp-customer'),
            'can_customize_invoice' => __('Dapat mengkustomisasi invoice', 'wp-customer'),
            'can_access_api' => __('Dapat mengakses API', 'wp-customer')
        ];
        
        return $labels[$capability_key] ?? $capability_key;
    }
    
    /**
     * Get human-readable label for limit
     * 
     * @param string $limit_key The limit key
     * @return string Human-readable label
     */
    private function getLimitLabel($limit_key) {
        $labels = [
            'max_staff' => __('Maksimal staff', 'wp-customer'),
            'max_branches' => __('Maksimal cabang', 'wp-customer'),
            'max_departments' => __('Maksimal departemen', 'wp-customer'),
            'max_projects' => __('Maksimal proyek aktif', 'wp-customer')
        ];
        
        return $labels[$limit_key] ?? $limit_key;
    }

    /**
     * Get upgrade options dengan informasi lengkap untuk SEMUA LEVEL
     */
    public function getUpgradeOptions() {
        try {
            $result = $this->validateRequest();
            if (is_wp_error($result)) {
                throw new \Exception($result->get_error_message());
            }
            
            $company_id = $result;
            $period_months = isset($_POST['period_months']) ? intval($_POST['period_months']) : 1;
            
            // Validate period_months
            if ($period_months < 1 || $period_months > 12) {
                throw new \Exception(__('Periode tidak valid. Silakan pilih antara 1-12 bulan.', 'wp-customer'));
            }

            // Try to get from cache first
            $cache_key = "upgrade_options_{$company_id}_{$period_months}";
            $cached_data = $this->cache->get('customer_membership', $cache_key);
            if ($cached_data !== null) {
                wp_send_json_success($cached_data);
                return;
            }

            // Get current membership
            $current = $this->membership_model->findByCompany($company_id);
            
            // Get current level ID (default to 1 if no membership)
            $current_level_id = $current ? $current->level_id : 1;
            
            // Get current level details
            $current_level = $this->level_model->get_level($current_level_id);
            if (!$current_level) {
                throw new \Exception(__('Level membership saat ini tidak ditemukan', 'wp-customer'));
            }

            // Get all available levels
            $all_levels = $this->level_model->get_all_levels();
            if (empty($all_levels)) {
                throw new \Exception(__('Tidak ada level membership yang tersedia', 'wp-customer'));
            }
            
            // Debug: Log struktur lengkap dari all_levels
            error_log('ALL LEVELS STRUCTURE: ' . print_r($all_levels, true));
            
            // Transform ALL levels to match expected format
            $formatted_levels = [];
            $upgrade_options = [];
            
            foreach ($all_levels as $level) {            
                // Format capabilities for frontend
                $capabilities = json_decode($level['capabilities'], true);
                
                // Debug: Log struktur capabilities per level
                error_log('LEVEL ' . $level['name'] . ' CAPABILITIES: ' . print_r($capabilities, true));
                
                // Siapkan data resource limits
                $resource_limits = [];
                if (isset($capabilities['resources'])) {
                    foreach ($capabilities['resources'] as $key => $resource) {
                        $resource_limits[] = [
                            'key' => $key,
                            'label' => $resource['label'] ?? $this->getLimitLabel($key),
                            'value' => $resource['value']
                        ];
                    }
                }
                
                // Siapkan data key features
                $key_features = [];
                
                // Staff features
                if (isset($capabilities['features'])) {
                    foreach ($capabilities['features'] as $key => $feature) {
                        if ($feature['value']) {
                            $key_features[] = [
                                'key' => $key,
                                'label' => $feature['label'] ?? $this->getCapabilityLabel($key),
                                'type' => 'feature'
                            ];
                        }
                    }
                }
                
                // Price calculation
                $base_price = floatval($level['price_per_month']) * $period_months;
                $upgrade_price = $this->calculateUpgradePrice($current, $level, $period_months);
                
                // Calculate savings compared to monthly payments
                $monthly_total = floatval($level['price_per_month']) * $period_months;
                $discount_percentage = 0;
                
                if ($period_months > 1) {
                    $discount_percentage = ($monthly_total - $upgrade_price) / $monthly_total * 100;
                }
                
                $formatted_level = [
                    'id' => $level['id'],
                    'name' => $level['name'],
                    'slug' => $level['slug'],
                    'description' => $level['description'],
                    'price_per_month' => floatval($level['price_per_month']),
                    'price_details' => [
                        'base_price' => $base_price,
                        'upgrade_price' => $upgrade_price,
                        'period_months' => $period_months,
                        'discount_percentage' => round($discount_percentage, 1),
                        'monthly_equivalent' => round($upgrade_price / $period_months)
                    ],
                    'key_features' => $key_features,
                    'resource_limits' => $resource_limits,
                    'capabilities' => $capabilities, // Full capabilities data for frontend
                    'is_current' => ($level['id'] == $current_level_id),
                    'is_recommended' => $level['sort_order'] == $current_level['sort_order'] + 1,
                    'upgrade_url' => $this->getUpgradeUrl($company_id, $level['id'], $period_months),
                    'is_trial_available' => !empty($level['is_trial_available']),
                    'trial_days' => intval($level['trial_days'])
                ];
                
                // Add to appropriate array
                $formatted_levels[] = $formatted_level;
                
                // Add to upgrade options if it's higher than current level
                if ($level['id'] != $current_level_id && $level['sort_order'] > $current_level['sort_order']) {
                    $upgrade_options[] = $formatted_level;
                }
            }

            // Sort options by sort_order
            usort($formatted_levels, function($a, $b) use ($all_levels) {
                $a_order = 0;
                $b_order = 0;
                
                foreach ($all_levels as $level) {
                    if ($level['id'] == $a['id']) $a_order = $level['sort_order'];
                    if ($level['id'] == $b['id']) $b_order = $level['sort_order'];
                }
                
                return $a_order - $b_order;
            });
            
            // Sort upgrade options too
            usort($upgrade_options, function($a, $b) use ($all_levels) {
                $a_order = 0;
                $b_order = 0;
                
                foreach ($all_levels as $level) {
                    if ($level['id'] == $a['id']) $a_order = $level['sort_order'];
                    if ($level['id'] == $b['id']) $b_order = $level['sort_order'];
                }
                
                return $a_order - $b_order;
            });
            
            $response = [
                'current_level' => [
                    'id' => $current_level['id'],
                    'name' => $current_level['name'],
                    'slug' => $current_level['slug'],
                    'price_per_month' => floatval($current_level['price_per_month'])
                ],
                'all_levels' => $formatted_levels,  // NEW: All levels with complete data
                'upgrade_options' => $upgrade_options,
                'available_periods' => [1, 3, 6, 12],
                'selected_period' => $period_months
            ];
            
            // Cache the response
            $this->cache->set('customer_membership', $response, 3600, $cache_key);

            wp_send_json_success($response);

        } catch (\Exception $e) {
            error_log("getUpgradeOptions Exception: " . $e->getMessage());
            error_log($e->getTraceAsString());
            
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get key features from capabilities for display
     * 
     * @param array $capabilities Raw capabilities from database
     * @return array List of key features
     */
    private function getKeyFeatures($capabilities) {
        $features = [];
        
        if (isset($capabilities['features'])) {
            foreach ($capabilities['features'] as $key => $feature) {
                if (!empty($feature['value'])) {
                    $features[] = [
                        'key' => $key,
                        'label' => $feature['label'] ?? $this->getCapabilityLabel($key)
                    ];
                }
            }
        }
        
        return $features;
    }
    
    /**
     * Get resource limits from capabilities for display
     * 
     * @param array $capabilities Raw capabilities from database
     * @return array List of resource limits
     */
    private function getResourceLimits($capabilities) {
        $limits = [];
        
        if (isset($capabilities['resources'])) {
            foreach ($capabilities['resources'] as $key => $limit) {
                $limits[] = [
                    'key' => $key,
                    'label' => $limit['label'] ?? $this->getLimitLabel($key),
                    'value' => $limit['value'],
                    'is_unlimited' => $limit['value'] < 0
                ];
            }
        }
        
        return $limits;
    }
    
    /**
     * Calculate upgrade price based on current membership and target level
     * 
     * @param object $current_membership Current membership object
     * @param array $target_level Target level data
     * @param int $period_months Subscription period in months
     * @return float Calculated upgrade price
     */
    private function calculateUpgradePrice($current_membership, $target_level, $period_months) {
        // Base price for selected period
        $base_price = floatval($target_level['price_per_month']) * $period_months;
        
        // If no current membership or expired, return base price
        $now = time();
        $end_date = strtotime($current_membership->end_date);
        if ($end_date <= $now) {
            return $base_price;
        }
        
        // Get current level
        $current_level = $this->level_model->get_level($current_membership->level_id);
        if (!$current_level) {
            return $base_price;
        }
        
        // Calculate remaining days in current subscription
        $remaining_days = max(0, ceil(($end_date - $now) / (60 * 60 * 24)));
        
        // Calculate daily rates
        $current_daily_rate = floatval($current_level['price_per_month']) / 30;
        $new_daily_rate = floatval($target_level['price_per_month']) / 30;
        
        // Credit for remaining time on current plan
        $remaining_credit = $current_daily_rate * $remaining_days;
        
        // Cost for remaining time on new plan
        $remaining_cost = $new_daily_rate * $remaining_days;
        
        // Additional cost for upgrade (difference for remaining period)
        $upgrade_cost = $remaining_cost - $remaining_credit;
        
        // Total price: base price for new period + upgrade cost for remaining days
        $total_price = $base_price + $upgrade_cost;
        
        // Apply discount for longer periods
        if ($period_months >= 12) {
            $total_price *= 0.9; // 10% discount for annual
        } else if ($period_months >= 6) {
            $total_price *= 0.95; // 5% discount for 6+ months
        }
        
        return round($total_price);
    }
    
    /**
     * Get upgrade URL for checkout
     * 
     * @param int $company_id Company ID
     * @param int $level_id Target level ID
     * @param int $period_months Subscription period in months
     * @return string URL for checkout
     */
    private function getUpgradeUrl($company_id, $level_id, $period_months) {
        // Generate upgrade token
        $token = wp_generate_password(12, false);
        
        // Store token in transient
        set_transient(
            "wp_customer_upgrade_{$token}", 
            [
                'company_id' => $company_id,
                'level_id' => $level_id,
                'period_months' => $period_months,
                'timestamp' => time()
            ],
            HOUR_IN_SECONDS // Token valid for 1 hour
        );
        
        // Generate URL
        $url = add_query_arg(
            [
                'action' => 'customer_membership_upgrade',
                'customer' => $company_id,
                'level' => $level_id,
                'period' => $period_months,
                'token' => $token
            ],
            site_url('/checkout/')
        );
        
        return $url;
    }

    /**
     * Check upgrade eligibility
     * 
     * @return void JSON response
     */
    public function checkUpgradeEligibility() {
        try {
            $result = $this->validateRequest();
            if (is_wp_error($result)) {
                throw new \Exception($result->get_error_message());
            }
            
            $company_id = $result;
            $target_level_id = isset($_POST['level_id']) ? intval($_POST['level_id']) : 0;
            
            if (!$target_level_id) {
                throw new \Exception(__('Level target tidak valid', 'wp-customer'));
            }

            // Validate with membership validator
            $validation_result = $this->validator->validateUpgradeEligibility($company_id, $target_level_id);
            
            if (is_wp_error($validation_result)) {
                throw new \Exception($validation_result->get_error_message());
            }
            
            wp_send_json_success([
                'can_upgrade' => true,
                'message' => __('Customer eligible untuk upgrade ke level ini', 'wp-customer')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'can_upgrade' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process membership upgrade request
     * 
     * @return void JSON response
     */
    public function requestUpgradeMembership() {
        try {
            $result = $this->validateRequest();
            if (is_wp_error($result)) {
                throw new \Exception($result->get_error_message());
            }
            
            $company_id = $result;
            $target_level_id = isset($_POST['level_id']) ? intval($_POST['level_id']) : 0;
            $period_months = isset($_POST['period_months']) ? intval($_POST['period_months']) : 1;
            $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';
            
            // Validate parameters
            if (!$target_level_id) {
                throw new \Exception(__('Level target tidak valid', 'wp-customer'));
            }
            
            if ($period_months < 1 || $period_months > 12) {
                throw new \Exception(__('Periode tidak valid. Silakan pilih antara 1-12 bulan.', 'wp-customer'));
            }
            
            if (empty($payment_method)) {
                throw new \Exception(__('Metode pembayaran harus dipilih', 'wp-customer'));
            }

            // Check eligibility
            $validation_result = $this->validator->validateUpgradeEligibility($company_id, $target_level_id);
            if (is_wp_error($validation_result)) {
                throw new \Exception($validation_result->get_error_message());
            }
            
            // Get current membership
            $current = $this->membership_model->findByCompany($company_id);
            if (!$current) {
                throw new \Exception(__('Tidak ditemukan data membership aktif', 'wp-customer'));
            }
            
            // Get levels data
            $current_level = $this->level_model->get_level($current->level_id);
            $target_level = $this->level_model->get_level($target_level_id);
            
            if (!$target_level) {
                throw new \Exception(__('Level target tidak ditemukan', 'wp-customer'));
            }
            
            // Calculate upgrade price
            $upgrade_price = $this->calculateUpgradePrice($current, $target_level, $period_months);
            
            // Create payment request
            $payment_data = [
                'company_id' => $company_id,
                'amount' => $upgrade_price,
                'payment_method' => $payment_method,
                'description' => sprintf(
                    __('Upgrade membership dari %s ke %s untuk %d bulan', 'wp-customer'),
                    $current_level['name'],
                    $target_level['name'],
                    $period_months
                ),
                'metadata' => [
                    'type' => 'membership_upgrade',
                    'current_level_id' => $current->level_id,
                    'target_level_id' => $target_level_id,
                    'period_months' => $period_months
                ]
            ];
            
            // Process payment request
            $payment_result = $this->processPayment($payment_data);
            
            if (is_wp_error($payment_result)) {
                throw new \Exception($payment_result->get_error_message());
            }
            
            // Update membership status to pending_upgrade
            $update_data = [
                'status' => 'pending_upgrade',
                'upgrade_to_level_id' => $target_level_id,
                'upgrade_period_months' => $period_months,
                'upgrade_payment_id' => $payment_result['payment_id'],
                'upgrade_requested_at' => current_time('mysql')
            ];
            
            $update_result = $this->membership_model->update($current->id, $update_data);
            
            if (!$update_result) {
                throw new \Exception(__('Gagal mengupdate status membership', 'wp-customer'));
            }
            
            // Clear cache
            $this->cache->delete('customer_membership', "membership_status_{$company_id}");
            
            // Send notification
            $this->sendUpgradeNotification($company_id, $current_level, $target_level, $upgrade_price);
            
            wp_send_json_success([
                'message' => __('Permintaan upgrade berhasil diproses', 'wp-customer'),
                'upgrade_price' => $upgrade_price,
                'payment_url' => $payment_result['payment_url'],
                'payment_id' => $payment_result['payment_id']
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Process payment for membership upgrade
     * 
     * @param array $payment_data Payment data
     * @return array|WP_Error Payment result or error
     */
    private function processPayment($payment_data) {
        // This is a placeholder for actual payment processing
        // In a real implementation, you would integrate with a payment gateway
        
        try {
            // Simulate payment processing
            $payment_id = 'PAY-' . time() . '-' . mt_rand(1000, 9999);
            
            // Create payment record in database
            global $wpdb;
            $table = $wpdb->prefix . 'app_customer_payments';
            
            $result = $wpdb->insert(
                $table,
                [
                    'payment_id' => $payment_id,
                    'company_id' => $payment_data['company_id'],
                    'amount' => $payment_data['amount'],
                    'payment_method' => $payment_data['payment_method'],
                    'description' => $payment_data['description'],
                    'metadata' => json_encode($payment_data['metadata']),
                    'status' => 'pending',
                    'created_at' => current_time('mysql')
                ]
            );
            
            if (!$result) {
                return new \WP_Error('payment_failed', __('Gagal membuat record pembayaran', 'wp-customer'));
            }
            
            // For testing/development, return success with dummy payment URL
            // In production, this would be the URL from your payment gateway
            $payment_url = add_query_arg(
                [
                    'payment_id' => $payment_id,
                    'amount' => $payment_data['amount'],
                    'description' => urlencode($payment_data['description'])
                ],
                site_url('/checkout/payment/')
            );
            
            return [
                'success' => true,
                'payment_id' => $payment_id,
                'payment_url' => $payment_url
            ];
            
        } catch (\Exception $e) {
            return new \WP_Error('payment_failed', $e->getMessage());
        }
    }
    
    /**
     * Send notification for upgrade request
     * 
     * @param int $company_id Company ID
     * @param array $current_level Current level data
     * @param array $target_level Target level data
     * @param float $upgrade_price Upgrade price
     */
    private function sendUpgradeNotification($company_id, $current_level, $target_level, $upgrade_price) {
        // Get customer data
        $customer = $this->membership_model->getCustomerData($company_id);
        if (!$customer) {
            return;
        }
        
        // Get admin email
        $admin_email = get_option('admin_email');
        
        // Send email to customer
        $customer_subject = sprintf(
            __('Permintaan Upgrade Membership %s', 'wp-customer'),
            get_bloginfo('name')
        );
        
                    $customer_message = sprintf(
            __('Halo %s,

Permintaan upgrade membership Anda dari %s ke %s telah kami terima.
Jumlah pembayaran: Rp %s

Kami akan memproses upgrade setelah pembayaran Anda terverifikasi.
Terima kasih telah menggunakan layanan kami.

Salam,
Tim %s', 'wp-customer'),
            $customer->name,
            $current_level['name'],
            $target_level['name'],
            number_format($upgrade_price, 0, ',', '.'),
            get_bloginfo('name')
        );
        
        wp_mail($customer->email, $customer_subject, $customer_message);
        
        // Send notification to admin
        $admin_subject = sprintf(
            __('Permintaan Upgrade Membership: %s', 'wp-customer'),
            $customer->name
        );
        
        $admin_message = sprintf(
            __('Permintaan upgrade membership baru:

Customer: %s (ID: %d)
Level saat ini: %s
Level target: %s
Jumlah pembayaran: Rp %s

Silakan verifikasi pembayaran di dashboard admin.', 'wp-customer'),
            $customer->name,
            $company_id,
            $current_level['name'],
            $target_level['name'],
            number_format($upgrade_price, 0, ',', '.')
        );
        
        wp_mail($admin_email, $admin_subject, $admin_message);
    }

    /**
     * Extend membership period
     * 
     * @return void JSON response
     */
    public function extendMembership() {
        try {
            // Only admin or staff with permissions can extend memberships
            if (!current_user_can('manage_options') && !current_user_can('extend_customer_membership')) {
                throw new \Exception(__('Anda tidak memiliki izin untuk melakukan operasi ini', 'wp-customer'));
            }
            
            check_ajax_referer('wp_customer_nonce', 'nonce');
            
            $company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;
            $months = isset($_POST['months']) ? intval($_POST['months']) : 0;
            
            if (!$company_id || $months < 1) {
                throw new \Exception(__('Parameter tidak valid', 'wp-customer'));
            }
            
            // Get current membership
            $membership = $this->membership_model->findByCompany($company_id);
            if (!$membership) {
                throw new \Exception(__('Tidak ditemukan data membership aktif', 'wp-customer'));
            }
            
            // Calculate new end date
            $current_end = new \DateTime($membership->end_date);
            $current_end->modify("+{$months} months");
            $new_end_date = $current_end->format('Y-m-d H:i:s');
            
            // Update membership
            $update_data = [
                'end_date' => $new_end_date,
                'updated_at' => current_time('mysql')
            ];
            
            // If membership is inactive or in grace period, activate it
            if ($membership->status != 'active') {
                $update_data['status'] = 'active';
            }
            
            $result = $this->membership_model->update($membership->id, $update_data);
            
            if (!$result) {
                throw new \Exception(__('Gagal mengupdate data membership', 'wp-customer'));
            }
            
            // Clear cache
            $this->cache->delete('customer_membership', "membership_status_{$company_id}");
            
            // Log extension
            $this->logMembershipExtension($company_id, $membership->id, $months);
            
            // Get updated membership for response
            $updated = $this->membership_model->findByCompany($company_id);
            
            wp_send_json_success([
                'message' => sprintf(
                    __('Membership berhasil diperpanjang selama %d bulan', 'wp-customer'),
                    $months
                ),
                'new_end_date' => date_i18n(get_option('date_format'), strtotime($updated->end_date))
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Log membership extension
     * 
     * @param int $company_id Company ID
     * @param int $membership_id Membership ID
     * @param int $months Extension period in months
     */
    private function logMembershipExtension($company_id, $membership_id, $months) {
        global $wpdb;
        $table = $wpdb->prefix . 'app_customer_membership_logs';
        
        $wpdb->insert(
            $table,
            [
                'company_id' => $company_id,
                'membership_id' => $membership_id,
                'action' => 'extend',
                'description' => sprintf(
                    __('Membership diperpanjang selama %d bulan oleh %s', 'wp-customer'),
                    $months,
                    wp_get_current_user()->display_name
                ),
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ]
        );
    }

    /**
     * Handle getting membership level data for customer view
     */
    public function getMembershipLevelData() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');
            
            $company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;
            if (!$company_id) {
                throw new \Exception(__('Invalid customer ID', 'wp-customer'));
            }

            // Check if user has permission to view this customer's data
            if (!current_user_can('manage_options') && !$this->userCanAccessCustomer($company_id)) {
                throw new \Exception(__('You do not have permission to view this customer data', 'wp-customer'));
            }

            // Get current membership
            $current = $this->membership_model->findByCompany($company_id);
            
            // Get all membership levels
            $levels = $this->level_model->get_all_levels();
            
            // Format data for each level
            $formatted_levels = [];
            $employee_count = $this->membership_model->getActiveEmployeeCount($company_id);
            
            foreach ($levels as $level) {
                // Get capabilities
                $capabilities = json_decode($level['capabilities'], true);
                
                // Get staff limit
                $max_staff = isset($capabilities['resources']['max_staff']['value']) 
                    ? $capabilities['resources']['max_staff']['value'] 
                    : 0;
                
                // If unlimited, set to -1
                if ($max_staff === -1) {
                    $max_staff = __('Unlimited', 'wp-customer');
                }
                
                // Format key features
                $key_features = $this->getKeyFeatures($capabilities);
                
                $data = [
                    'id' => $level['id'],
                    'name' => $level['name'],
                    'slug' => $level['slug'],
                    'description' => $level['description'],
                    'price_per_month' => floatval($level['price_per_month']),
                    'max_staff' => $max_staff,
                    'key_features' => $key_features,
                    'is_trial_available' => !empty($level['is_trial_available']),
                    'trial_days' => intval($level['trial_days'])
                ];
                
                // Check if current user can upgrade to this level
                $can_upgrade = false;
                if ($current) {
                    $can_upgrade = $current->level_id != $level['id'] && $this->canUpgrade($company_id, $level['id']);
                    
                    // If can upgrade, add upgrade button
                    if ($can_upgrade) {
                        $data['upgrade_button'] = sprintf(
                            '<button type="button" class="button button-primary upgrade-membership-btn" data-level="%s" data-level-id="%d">%s</button>',
                            esc_attr($level['slug']),
                            intval($level['id']),
                            sprintf(__('Upgrade ke %s', 'wp-customer'), $level['name'])
                        );
                    }
                }
                
                $formatted_levels[$level['slug']] = $data;
            }

            wp_send_json_success([
                'current_level' => $current ? $current->level_id : null,
                'employee_count' => $employee_count,
                'levels' => $formatted_levels
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Check if customer can upgrade to specified level
     * 
     * @param int $company_id Company ID
     * @param int $level_id Target level ID
     * @return bool True if can upgrade, false otherwise
     */
    private function canUpgrade($company_id, $level_id) {
        try {
            // Get current membership
            $membership = $this->membership_model->findByCompany($company_id);
            if (!$membership) {
                return false;
            }
            
            // Get current and target levels
            $current_level = $this->level_model->get_level($membership->level_id);
            $target_level = $this->level_model->get_level($level_id);
            
            if (!$current_level || !$target_level) {
                return false;
            }
            
            // Compare sort_order (higher order = higher tier)
            return $target_level['sort_order'] > $current_level['sort_order'];
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Save membership level (Admin only)
     */
    public function saveMembershipLevel() {
        try {
            // Only admin can save membership levels
            if (!current_user_can('manage_options')) {
                throw new \Exception(__('You do not have permission to perform this action.', 'wp-customer'));
            }
            
            check_ajax_referer('wp_customer_nonce', 'nonce');
        
            // Forward to MembershipLevelController
            $level_controller = new MembershipLevelController();
            $level_controller->saveMembershipLevel();
            
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Menambahkan fungsi baru untuk mendapatkan semua level membership
     * Endpoint ini mirip dengan yang digunakan di SettingsController
     */
    public function getAllMembershipLevels() {
        try {
            // Verifikasi nonce dan permissions
            check_ajax_referer('wp_customer_nonce', 'nonce');
            
            // Get all membership levels
            $levels = $this->level_model->get_all_levels();
            
            // Debug: Log struktur data
            error_log('Get All Membership Levels: ' . print_r($levels, true));
            
            if (empty($levels)) {
                throw new \Exception(__('Tidak ada level membership yang tersedia', 'wp-customer'));
            }
            
            // Format response to match SettingsController structure
            $response = [
                'levels' => $levels,
                'grouped_features' => $this->getFeaturesGroupedByCategory()
            ];
            
            wp_send_json_success($response);
            
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Helper function untuk mendapatkan fitur yang dikelompokkan berdasarkan kategori
     */
    private function getFeaturesGroupedByCategory() {
        // Implement this if you need it, similar to how it's done in SettingsController
        // You might need to use MembershipFeatureModel->get_all_features_by_group()
        
        $feature_model = new \WPCustomer\Models\Membership\MembershipFeatureModel();
        return $feature_model->get_all_features_by_group();
    }

}
