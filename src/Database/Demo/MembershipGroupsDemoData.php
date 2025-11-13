<?php
/**
 * Membership Groups Demo Data Generator
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Demo/MembershipGroupsDemoData.php
 * 
 * Description: Generate demo data untuk membership feature groups.
 *              Mendefinisikan group-group default:
 *              - Staff Management
 *              - Data Management 
 *              - Resource Management
 *              - Communication
 */

namespace WPCustomer\Database\Demo;

use WPAppCore\Database\Demo\AbstractDemoData;  // TODO-2201: Shared from wp-app-core

defined('ABSPATH') || exit;

class MembershipGroupsDemoData extends AbstractDemoData {
    use CustomerDemoDataHelperTrait;

    // Default group IDs yang konsisten dengan data existing
    private const GROUP_IDS = [
        'staff' => 1,
        'data' => 2,
        'resources' => 3,
        'communication' => 4
    ];

    /**
     * Initialize plugin-specific models
     * Required by wp-app-core AbstractDemoData (TODO-2201)
     *
     * @return void
     */
    public function initModels(): void {
        // No models needed - uses wpdb directly
    }

    protected function validate(): bool {
        try {
            if (!$this->isDevelopmentMode()) {
                $this->debug('Development mode is not enabled');
                return false;
            }

            // Check if groups table exists
            $table_exists = $this->wpdb->get_var(
                "SHOW TABLES LIKE '{$this->wpdb->prefix}app_customer_membership_feature_groups'"
            );
            
            if (!$table_exists) {
                throw new \Exception('Membership feature groups table does not exist');
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
            
            $this->insertDefaultGroups();
            
            $this->debug('Demo group data generation completed successfully');

        } catch (\Exception $e) {
            $this->debug("Error in group generation: " . $e->getMessage());
            throw $e;
        }
    }

    private function insertDefaultGroups(): array {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customer_membership_feature_groups';
        $current_user_id = get_current_user_id();

        $groups = [
            self::GROUP_IDS['staff'] => [
                'name' => 'Staff Management',
                'slug' => 'staff',
                'capability_group' => 'features',
                'description' => 'Fitur terkait pengelolaan staff',
                'sort_order' => 10,
                'created_by' => $current_user_id,
                'created_at' => current_time('mysql'),
                'status' => 'active'
            ],
            self::GROUP_IDS['data'] => [
                'name' => 'Data Management',
                'slug' => 'data',
                'capability_group' => 'features',
                'description' => 'Fitur terkait pengelolaan data',
                'sort_order' => 20,
                'created_by' => $current_user_id,
                'created_at' => current_time('mysql'),
                'status' => 'active'
            ],
            self::GROUP_IDS['resources'] => [
                'name' => 'Resource Management',
                'slug' => 'resources',
                'capability_group' => 'limits',
                'description' => 'Fitur terkait batasan sumber daya',
                'sort_order' => 30,
                'created_by' => $current_user_id,
                'created_at' => current_time('mysql'),
                'status' => 'active'
            ],
            self::GROUP_IDS['communication'] => [
                'name' => 'Communication',
                'slug' => 'communication',
                'capability_group' => 'notifications',
                'description' => 'Fitur terkait notifikasi dan komunikasi',
                'sort_order' => 40,
                'created_by' => $current_user_id,
                'created_at' => current_time('mysql'),
                'status' => 'active'
            ]
        ];

        $group_ids = [];
        foreach ($groups as $id => $group) {
            // Check if group with this slug already exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE slug = %s",
                $group['slug']
            ));

            if ($existing) {
                $this->debug("Group with slug '{$group['slug']}' already exists, skipping insert");
                $group_ids[$group['slug']] = $existing;
                continue;
            }

            // Log untuk debugging
            $this->debug("Inserting group: " . print_r($group, true));

            $result = $wpdb->insert($table_name, $group);

            if ($result === false) {
                $this->debug("Failed to insert group: " . $wpdb->last_error);
            } else {
                $group_ids[$group['slug']] = $id;
                $this->debug("Successfully inserted group {$group['name']} with ID: {$id}");
            }
        }

        return $group_ids;
    }

    private function clearExistingData(): void {
        try {
            $this->wpdb->query("START TRANSACTION");

            // Hapus existing features first (due to foreign key constraint)
            $this->wpdb->query(
                "DELETE FROM {$this->wpdb->prefix}app_customer_membership_features
                 WHERE id > 0"
            );

            // Hapus existing groups
            $this->wpdb->query(
                "DELETE FROM {$this->wpdb->prefix}app_customer_membership_feature_groups
                 WHERE id > 0"
            );

            // Reset auto increment untuk ID yang konsisten
            $this->wpdb->query(
                "ALTER TABLE {$this->wpdb->prefix}app_customer_membership_feature_groups
                 AUTO_INCREMENT = 1"
            );

            $this->wpdb->query("COMMIT");
            $this->debug('Cleared existing membership feature groups and features');

        } catch (\Exception $e) {
            $this->wpdb->query("ROLLBACK");
            $this->debug("Error clearing data: " . $e->getMessage());
            throw $e;
        }
    }
}
