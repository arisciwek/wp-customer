<?php
/**
 * Membership Levels Demo Data Generator
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Demo/MembershipLevelsDemoData.php
 *
 * Description: Generates demo data for membership levels.
 *              First component to run in demo data generation.
 *              Sets up base configuration for:
 *              - Regular membership (2 staff max)
 *              - Priority membership (5 staff max)
 *              - Utama membership (unlimited staff)
 *              
 * Dependencies:
 * - WPCustomer\Models\Customer\CustomerMembershipLevelModel
 * - WordPress database ($wpdb)
 * - WordPress Options API
 * - CustomerDemoDataHelperTrait
 *
 * Order of Operations:
 * 1. Check development mode
 * 2. Clean existing membership data if in development mode
 * 3. Setup membership defaults
 * 4. Insert membership levels
 *
 * Changelog:
 * 1.1.0 - 2024-02-08
 * - Added CustomerDemoDataHelperTrait integration
 * - Added development mode check before data cleanup
 * - Improved error handling and logging
 * 
 * 1.0.0 - 2024-01-27
 * - Initial version
 * - Added membership levels setup
 * - Added data cleaning
 */

namespace WPCustomer\Database\Demo;

use WPCustomer\Models\Membership\CustomerMembershipLevelModel;

class MembershipLevelsDemoData extends AbstractDemoData {
    use CustomerDemoDataHelperTrait;

    private $customerMembershipLevelModel;
    
    public function __construct() {
        parent::__construct();
        $this->customerMembershipLevelModel = new CustomerMembershipLevelModel();
    }

    /**
     * Insert default membership levels
     */
    protected function insertDefaultLevels() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customer_membership_levels';
        $current_user_id = get_current_user_id();

        $defaults = [
            [
                'name' => 'Gratis',
                'slug' => 'gratis',
                'description' => 'Paket gratis dengan maksimal 1 staff',
                'available_periods' => array_rand(array_flip([1, 3, 6, 12])),
                'default_period' => 1,
                'price_per_month' => 0,
                'is_trial_available' => 1,
                'trial_days' => 7,
                'grace_period_days' => 3,
                'sort_order' => 1,
                'capabilities' => json_encode([
                    'features' => [
                        'can_add_staff' => true,
                        'can_export' => false,
                        'can_bulk_import' => false
                    ],
                    'limits' => [
                        'max_staff' => 1,
                        'max_departments' => 1,
                        'max_active_projects' => 5
                    ],
                    'notifications' => [
                        'email' => true,
                        'dashboard' => false,
                        'push' => false
                    ]
                ]),
                'created_by' => $current_user_id,
                'status' => 'active'
            ],
            [
                'name' => 'Reguler',
                'slug' => 'reguler',
                'description' => 'Paket dasar dengan maksimal 2 staff',
                'available_periods' => array_rand(array_flip([1, 3, 6, 12])),
                'default_period' => 1,
                'price_per_month' => 50000,
                'is_trial_available' => 1,
                'trial_days' => 7,
                'grace_period_days' => 3,
                'sort_order' => 1,
                'capabilities' => json_encode([
                    'features' => [
                        'can_add_staff' => true,
                        'can_export' => false,
                        'can_bulk_import' => false
                    ],
                    'limits' => [
                        'max_staff' => 2,
                        'max_departments' => 1,
                        'max_active_projects' => 5
                    ],
                    'notifications' => [
                        'email' => true,
                        'dashboard' => true,
                        'push' => false
                    ]
                ]),
                'created_by' => $current_user_id,
                'status' => 'active'
            ],
            [
                'name' => 'Prioritas',
                'slug' => 'prioritas',
                'description' => 'Paket menengah dengan maksimal 5 staff',
                'available_periods' => array_rand(array_flip([1, 3, 6, 12])),
                'default_period' => 1,
                'price_per_month' => 100000,
                'is_trial_available' => 1,
                'trial_days' => 7,
                'grace_period_days' => 5,
                'sort_order' => 2,
                'capabilities' => json_encode([
                    'features' => [
                        'can_add_staff' => true,
                        'can_export' => true,
                        'can_bulk_import' => false
                    ],
                    'limits' => [
                        'max_staff' => 5,
                        'max_departments' => 3,
                        'max_active_projects' => 10
                    ],
                    'notifications' => [
                        'email' => true,
                        'dashboard' => true,
                        'push' => true
                    ]
                ]),
                'created_by' => $current_user_id,
                'status' => 'active'
            ],
            [
                'name' => 'Utama',
                'slug' => 'utama',
                'description' => 'Paket premium tanpa batasan staff',
                'available_periods' => array_rand(array_flip([1, 3, 6, 12])),
                'default_period' => 1,
                'price_per_month' => 200000,
                'is_trial_available' => 0,
                'trial_days' => 0,
                'grace_period_days' => 7,
                'sort_order' => 3,
                'capabilities' => json_encode([
                    'features' => [
                        'can_add_staff' => true,
                        'can_export' => true,
                        'can_bulk_import' => true
                    ],
                    'limits' => [
                        'max_staff' => -1,
                        'max_departments' => -1,
                        'max_active_projects' => -1
                    ],
                    'notifications' => [
                        'email' => true,
                        'dashboard' => true,
                        'push' => true
                    ]
                ]),
                'created_by' => $current_user_id,
                'status' => 'active'
            ]
        ];

        foreach ($defaults as $level) {
            $wpdb->insert($table_name, $level);
        }
    }

    protected function validate(): bool {
        try {
            if (!$this->isDevelopmentMode()) {
                $this->debug('Development mode is not enabled');
                return false;
            }

            $table_exists = $this->wpdb->get_var(
                "SHOW TABLES LIKE '{$this->wpdb->prefix}app_customer_membership_levels'"
            );
            
            if (!$table_exists) {
                throw new \Exception('Membership levels table does not exist');
            }

            return true;
        } catch (\Exception $e) {
            $this->debug('Validation failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function generate(): bool {
        try {
            if ($this->shouldClearData()) {
                $this->clearExistingData();
            } else {
                $this->debug('Skipping data cleanup - not enabled in settings');
            }
            
            $this->insertDefaultLevels();

            $this->debug('Default membership levels inserted');

            return true;
        } catch (\Exception $e) {
            $this->debug('Generation failed: ' . $e->getMessage());
            return false;
        }
    }

    private function clearExistingData(): void {
        try {
            $this->wpdb->query("START TRANSACTION");
            
            // Delete from child tables first
            $this->wpdb->query("DELETE FROM {$this->wpdb->prefix}app_customer_memberships");
            $this->wpdb->query("DELETE FROM {$this->wpdb->prefix}app_customer_membership_levels");
            
            delete_option('wp_customer_membership_settings');
            
            $this->wpdb->query("COMMIT");
            
            $this->debug('Existing membership data cleared');
        } catch (\Exception $e) {
            $this->wpdb->query("ROLLBACK");
            $this->debug('Error clearing existing data: ' . $e->getMessage());
            throw $e;
        }
    }
}
