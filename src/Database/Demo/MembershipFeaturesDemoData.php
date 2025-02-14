<?php
/**
 * Membership Features Demo Data Generator
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo
 * @version     2.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Demo/MembershipFeaturesDemoData.php
 * 
 * Description: Generate demo data untuk membership features dan groups.
 *              Includes:
 *              - Setup default feature groups
 *              - Setup default features dengan metadata
 *              - Relasi antara feature dan groups
 *              
 * Dependencies:
 * - AbstractDemoData base class
 * - CustomerDemoDataHelperTrait
 * - MembershipFeaturesDB untuk definisi tabel
 * 
 * Changelog:
 * 2.1.0 - 2025-02-14
 * - Added feature groups data
 * - Updated feature structure with group_id
 * - Updated JSON metadata format
 * 
 * 2.0.0 - 2025-02-11
 * - Updated to use JSON metadata
 * - Enhanced feature grouping system
 * - Added more detailed feature attributes
 * 
 * 1.0.0 - 2025-01-27
 * - Initial version
 */

namespace WPCustomer\Database\Demo;

defined('ABSPATH') || exit;

class MembershipFeaturesDemoData extends AbstractDemoData {
    use CustomerDemoDataHelperTrait;

    // Track generated feature IDs for reference
    private $feature_ids = [];

    // Default feature groups
    private const FEATURE_GROUPS = [
        'staff',
        'data',
        'resources',
        'communication'
    ];

    protected function validate(): bool {
        try {
            if (!$this->isDevelopmentMode()) {
                $this->debug('Development mode is not enabled');
                return false;
            }

            // Check if features table exists
            $table_exists = $this->wpdb->get_var(
                "SHOW TABLES LIKE '{$this->wpdb->prefix}app_customer_membership_features'"
            );
            
            if (!$table_exists) {
                throw new \Exception('Membership features table does not exist');
            }

            // Validate MySQL version for JSON support
            $mysql_version = $this->wpdb->db_version();
            if (version_compare($mysql_version, '5.7.0', '<')) {
                throw new \Exception('MySQL version 5.7+ required for JSON support. Current version: ' . $mysql_version);
            }

            return true;

        } catch (\Exception $e) {
            $this->debug('Validation failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function generate(): void {
        try {
            if ($this->shouldClearData()) {
                $this->clearExistingData();
            }
            
            $group_ids = $this->insertDefaultGroups();
            $this->insertDefaultFeatures($group_ids);

        } catch (\Exception $e) {
            $this->debug("Error in feature generation: " . $e->getMessage());
            throw $e;
        }
    }

    private function insertDefaultGroups(): array {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customer_membership_feature_groups';
        $current_user_id = get_current_user_id();

        $groups = [
            [
                'name' => 'Staff Management',
                'slug' => 'staff',
                'capability_group' => 'features',
                'description' => 'Fitur terkait pengelolaan staff',
                'sort_order' => 10,
                'created_by' => $current_user_id
            ],
            [
                'name' => 'Data Management',
                'slug' => 'data',
                'capability_group' => 'features',
                'description' => 'Fitur terkait pengelolaan data',
                'sort_order' => 20,
                'created_by' => $current_user_id
            ],
            // ... tambahkan groups lainnya
        ];

        $group_ids = [];
        foreach ($groups as $group) {
            $wpdb->insert($table_name, $group);
            $group_ids[$group['slug']] = $wpdb->insert_id;
        }

        return $group_ids;
    }

    private function insertDefaultFeatures(array $group_ids): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customer_membership_features';
        $current_user_id = get_current_user_id();

        $features = [
            // Staff Management Features
            [
                'field_name' => 'can_add_staff',
                'group_id' => $group_ids['staff'],
                'metadata' => json_encode([
                    'field' => 'can_add_staff',  // Mirror field_name
                    'group' => 'staff',          // Mirror group dari group_ids
                    'type' => 'checkbox',
                    'label' => 'Dapat Menambah Staff',
                    'description' => 'Kemampuan untuk menambah staff baru',
                    'is_required' => false,
                    'ui_settings' => [
                        'css_class' => 'feature-checkbox',
                        'icon' => 'dashicons-groups'
                    ],
                    'default_value' => false
                ]),
                'sort_order' => 10,
                'created_by' => $current_user_id
            ],

            // Data Management Features
            [
                'field_name' => 'can_export',
                'group_id' => $group_ids['data'],
                'metadata' => json_encode([
                    'field' => 'can_export',     // Mirror field_name
                    'group' => 'data',           // Mirror group dari group_ids
                    'type' => 'checkbox',
                    'label' => 'Dapat Export Data',
                    'description' => 'Kemampuan untuk mengekspor data',
                    'is_required' => false,
                    'ui_settings' => [
                        'css_class' => 'feature-checkbox',
                        'icon' => 'dashicons-download'
                    ],
                    'default_value' => false
                ]),
                'sort_order' => 20,
                'created_by' => $current_user_id
            ],
            [
                'field_name' => 'can_bulk_import',
                'group_id' => $group_ids['data'],
                'metadata' => json_encode([
                    'field' => 'can_bulk_import', // Mirror field_name
                    'group' => 'data',            // Mirror group dari group_ids
                    'type' => 'checkbox',
                    'label' => 'Dapat Bulk Import',
                    'description' => 'Kemampuan untuk melakukan import massal',
                    'is_required' => false,
                    'ui_settings' => [
                        'css_class' => 'feature-checkbox',
                        'icon' => 'dashicons-upload'
                    ],
                    'default_value' => false
                ]),
                'sort_order' => 30,
                'created_by' => $current_user_id
            ],

            // Resource Limits
            [
                'field_name' => 'max_staff',
                'group_id' => $group_ids['resources'],
                'metadata' => json_encode([
                    'field' => 'max_staff',      // Mirror field_name
                    'group' => 'resources',       // Mirror group dari group_ids
                    'type' => 'number',
                    'label' => 'Maksimal Staff',
                    'description' => 'Jumlah maksimal staff yang dapat ditambahkan',
                    'is_required' => false,
                    'ui_settings' => [
                        'css_class' => 'limit-number',
                        'min' => -1,
                        'max' => 1000,
                        'step' => 1
                    ],
                    'default_value' => 2
                ]),
                'sort_order' => 40,
                'created_by' => $current_user_id
            ],
            [
                'field_name' => 'max_departments',
                'group_id' => $group_ids['resources'],
                'metadata' => json_encode([
                    'field' => 'max_departments', // Mirror field_name
                    'group' => 'resources',       // Mirror group dari group_ids
                    'type' => 'number',
                    'label' => 'Maksimal Departemen',
                    'description' => 'Jumlah maksimal departemen yang dapat dibuat',
                    'is_required' => false,
                    'ui_settings' => [
                        'css_class' => 'limit-number',
                        'min' => -1,
                        'max' => 100,
                        'step' => 1
                    ],
                    'default_value' => 1
                ]),
                'sort_order' => 50,
                'created_by' => $current_user_id
            ],

            // Communication Features
            [
                'field_name' => 'email_notifications',
                'group_id' => $group_ids['communication'],
                'metadata' => json_encode([
                    'field' => 'email_notifications', // Mirror field_name
                    'group' => 'communication',       // Mirror group dari group_ids
                    'type' => 'checkbox',
                    'label' => 'Notifikasi Email',
                    'description' => 'Aktifkan notifikasi via email',
                    'is_required' => false,
                    'ui_settings' => [
                        'css_class' => 'notification-checkbox',
                        'icon' => 'dashicons-email'
                    ],
                    'default_value' => true
                ]),
                'sort_order' => 60,
                'created_by' => $current_user_id
            ],
            [
                'field_name' => 'dashboard_notifications',
                'group_id' => $group_ids['communication'],
                'metadata' => json_encode([
                    'field' => 'dashboard_notifications', // Mirror field_name
                    'group' => 'communication',           // Mirror group dari group_ids
                    'type' => 'checkbox',
                    'label' => 'Notifikasi Dashboard',
                    'description' => 'Aktifkan notifikasi di dashboard',
                    'is_required' => false,
                    'ui_settings' => [
                        'css_class' => 'notification-checkbox',
                        'icon' => 'dashicons-bell'
                    ],
                    'default_value' => true
                ]),
                'sort_order' => 70,
                'created_by' => $current_user_id
            ]
        ];

        foreach ($features as $feature) {
            $wpdb->insert($table_name, $feature);
        }
    }

    /**
     * Clear existing membership features
     */
    private function clearExistingData(): void {
        try {
            $this->wpdb->query("START TRANSACTION");

            // Delete existing features
            $this->wpdb->query(
                "DELETE FROM {$this->wpdb->prefix}app_customer_membership_features 
                 WHERE id > 0"
            );

            // Reset auto increment
            $this->wpdb->query(
                "ALTER TABLE {$this->wpdb->prefix}app_customer_membership_features 
                 AUTO_INCREMENT = 1"
            );

            $this->wpdb->query("COMMIT");
            $this->debug('Cleared existing membership features');

        } catch (\Exception $e) {
            $this->wpdb->query("ROLLBACK");
            $this->debug("Error clearing data: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get array of generated feature IDs
     */
    public function getFeatureIds(): array {
        return $this->feature_ids;
    }

    /**
     * Validate JSON metadata structure
     */
    private function validateMetadata(array $metadata): bool {
        $required_fields = ['group', 'label', 'type', 'is_required'];
        foreach ($required_fields as $field) {
            if (!isset($metadata[$field])) {
                return false;
            }
        }

        if (!in_array($metadata['group'], self::FEATURE_GROUPS)) {
            return false;
        }

        return true;
    }
}
