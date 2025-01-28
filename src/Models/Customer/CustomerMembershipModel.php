<?php
/**
 * Customer Membership Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models/Customer
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Customer/CustomerMembershipModel.php
 *
 * Description: Model untuk mengelola membership level customer.
 *              Handles:
 *              - Membership levels (regular, priority, utama)
 *              - Level capabilities configuration
 *              - Staff limit management
 *              - Default settings initialization
 *              
 * Dependencies:
 * - WordPress Options API
 * - WP_Customer membership tables
 * - WordPress database ($wpdb)
 *
 * Capabilities per Level:
 * Regular:
 * - max_staff: 2
 * - can_add_staff: true
 * - can_export: false
 * - can_bulk_import: false
 *
 * Priority:
 * - max_staff: 5
 * - can_add_staff: true
 * - can_export: true
 * - can_bulk_import: false
 *
 * Utama:
 * - max_staff: -1 (unlimited)
 * - can_add_staff: true
 * - can_export: true
 * - can_bulk_import: true
 *
 * Changelog:
 * 1.0.0 - 2024-01-27
 * - Initial version
 * - Added membership level management
 * - Added capability handling
 * - Added staff limit controls
 */

namespace WPCustomer\Models\Customer;

defined('ABSPATH') || exit;

class CustomerMembershipModel {
    private $table;
    private $membership_settings_key = 'wp_customer_membership_settings';

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'app_customer_membership_levels';
    }

    /**
     * Get membership settings including capabilities for a level
     */
    public function getMembershipData(int $customer_id): array {
        // Get membership settings
        $settings = get_option($this->membership_settings_key, []);
        
        // Get customer data untuk cek level
        $customer = $this->getCustomerLevel($customer_id);
        $level = $customer->membership_level ?? $settings['default_level'] ?? 'regular';

        return [
            'level' => $level,
            'max_staff' => $settings["{$level}_max_staff"] ?? 2,
            'capabilities' => [
                'can_add_staff' => $settings["{$level}_can_add_staff"] ?? false,
                'can_export' => $settings["{$level}_can_export"] ?? false,
                'can_bulk_import' => $settings["{$level}_can_bulk_import"] ?? false,
            ]
        ];
    }

    /**
     * Get customer's membership level
     */
    private function getCustomerLevel(int $customer_id): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("
            SELECT membership_level 
            FROM {$wpdb->prefix}app_customers 
            WHERE id = %d
        ", $customer_id));
    }

    /**
     * Setup default membership settings if not exists
     */
    public function setupMembershipDefaults(): bool {
        try {
            // Check if settings already exist
            if (!get_option($this->membership_settings_key)) {
                $default_settings = [
                    'regular_max_staff' => 2,
                    'regular_can_add_staff' => true,
                    'regular_can_export' => false,
                    'regular_can_bulk_import' => false,
                    
                    'priority_max_staff' => 5,
                    'priority_can_add_staff' => true,
                    'priority_can_export' => true,
                    'priority_can_bulk_import' => false,
                    
                    'utama_max_staff' => -1,
                    'utama_can_add_staff' => true,
                    'utama_can_export' => true,
                    'utama_can_bulk_import' => true,
                    
                    'default_level' => 'regular'
                ];

                return add_option($this->membership_settings_key, $default_settings);
            }
            return true;
        } catch (\Exception $e) {
            error_log('Error setting up membership defaults: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Insert default membership levels
     */
    public function insertDefaultLevels(): bool {
        global $wpdb;

        try {
            $defaults = [
                [
                    'name' => 'Regular',
                    'slug' => 'regular',
                    'description' => 'Paket dasar dengan maksimal 2 staff',
                    'max_staff' => 2,
                    'capabilities' => json_encode([
                        'can_add_staff' => true,
                        'max_departments' => 1
                    ]),
                    'created_by' => get_current_user_id(),
                    'status' => 'active'
                ],
                [
                    'name' => 'Priority',
                    'slug' => 'priority',
                    'description' => 'Paket menengah dengan maksimal 5 staff',
                    'max_staff' => 5,
                    'capabilities' => json_encode([
                        'can_add_staff' => true,
                        'can_export' => true,
                        'max_departments' => 3
                    ]),
                    'created_by' => get_current_user_id(),
                    'status' => 'active'
                ],
                [
                    'name' => 'Utama',
                    'slug' => 'utama',
                    'description' => 'Paket premium tanpa batasan staff',
                    'max_staff' => -1,
                    'capabilities' => json_encode([
                        'can_add_staff' => true,
                        'can_export' => true,
                        'can_bulk_import' => true,
                        'max_departments' => -1
                    ]),
                    'created_by' => get_current_user_id(),
                    'status' => 'active'
                ]
            ];

            foreach ($defaults as $level) {
                $wpdb->insert($this->table, $level);
            }

            return true;
        } catch (\Exception $e) {
            error_log('Error inserting default membership levels: ' . $e->getMessage());
            return false;
        }
    }
}
