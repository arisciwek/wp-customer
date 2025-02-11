<?php
/**
 * Membership Features Demo Data Generator
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Demo/MembershipFeaturesDemoData.php
 *
 * Description: Generate demo data for membership features with JSON metadata structure.
 *              Uses MembershipFeaturesDB default definitions.
 *              Must run before MembershipLevelsDemoData.
 *              
 * Dependencies:
 * - AbstractDemoData base class
 * - DemoDataHelperTrait
 * - MembershipFeaturesDB for feature definitions
 * 
 * Changelog:
 * 2.0.0 - 2025-02-11
 * - Updated to use JSON metadata structure
 * - Enhanced feature grouping system
 * - Added more detailed feature attributes
 * - Improved error handling
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

            $this->insertDefaultFeatures();
            
            // Get and store the generated feature IDs
            $this->feature_ids = $this->wpdb->get_col(
                "SELECT id FROM {$this->wpdb->prefix}app_customer_membership_features 
                 WHERE status = 'active' 
                 ORDER BY sort_order"
            );

            $this->debug("Generated " . count($this->feature_ids) . " membership features");

        } catch (\Exception $e) {
            $this->debug("Error in feature generation: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Insert default features with JSON metadata
     */
    private function insertDefaultFeatures(): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customer_membership_features';
        $current_user_id = get_current_user_id();

        $defaults = [
            // Staff Management Features
            [
                'field_name' => 'can_add_staff',
                'metadata' => json_encode([
                    'group' => 'staff',
                    'label' => 'Dapat Menambah Staff',
                    'description' => 'Kemampuan untuk menambah staff baru',
                    'type' => 'checkbox',
                    'is_required' => true,
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
                'metadata' => json_encode([
                    'group' => 'data',
                    'label' => 'Dapat Export Data',
                    'description' => 'Kemampuan untuk mengexport data',
                    'type' => 'checkbox',
                    'is_required' => true,
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
                'metadata' => json_encode([
                    'group' => 'data',
                    'label' => 'Dapat Bulk Import',
                    'description' => 'Kemampuan untuk melakukan import massal',
                    'type' => 'checkbox',
                    'is_required' => true,
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
                'metadata' => json_encode([
                    'group' => 'resources',
                    'label' => 'Maksimal Staff',
                    'description' => 'Jumlah maksimal staff yang dapat ditambahkan',
                    'type' => 'number',
                    'is_required' => true,
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
                'metadata' => json_encode([
                    'group' => 'resources',
                    'label' => 'Maksimal Departemen',
                    'description' => 'Jumlah maksimal departemen yang dapat dibuat',
                    'type' => 'number',
                    'is_required' => true,
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
                'metadata' => json_encode([
                    'group' => 'communication',
                    'label' => 'Notifikasi Email',
                    'description' => 'Aktifkan notifikasi via email',
                    'type' => 'checkbox',
                    'is_required' => true,
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
                'metadata' => json_encode([
                    'group' => 'communication',
                    'label' => 'Notifikasi Dashboard',
                    'description' => 'Aktifkan notifikasi di dashboard',
                    'type' => 'checkbox',
                    'is_required' => true,
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

        foreach ($defaults as $feature) {
            $result = $wpdb->insert($table_name, $feature);
            if ($result === false) {
                throw new \Exception("Failed to insert feature: {$feature['field_name']}. Error: {$wpdb->last_error}");
            }
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
