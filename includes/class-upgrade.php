<?php
/**
 * File: class-upgrade.php
 * Path: /wp-customer/includes/class-upgrade.php
 * Description: Handles plugin upgrades and migrations
 *
 * @package     WP_Customer
 * @subpackage  Includes
 * @version     1.0.10
 * @author      arisciwek
 *
 * Description: Menangani upgrade plugin saat versi berubah.
 *              Ensures backward compatibility dan data migration.
 *
 * Changelog:
 * 1.0.10 - 2025-01-22
 * - Initial creation
 * - Added version checking mechanism
 * - Added upgrade routine for v1.0.10 (fix duplicate wp_capabilities)
 */

class WP_Customer_Upgrade {
    /**
     * Version option name in database
     */
    const VERSION_OPTION = 'wp_customer_version';

    /**
     * Check and run upgrades if needed
     */
    public static function check_and_upgrade() {
        $current_version = get_option(self::VERSION_OPTION, '0.0.0');
        $new_version = WP_CUSTOMER_VERSION;

        // Skip if same version
        if (version_compare($current_version, $new_version, '=')) {
            return;
        }

        self::log("Upgrading from {$current_version} to {$new_version}");

        // Run upgrade routines based on version
        if (version_compare($current_version, '1.0.10', '<')) {
            self::upgrade_to_1_0_10();
        }

        // Future upgrade routines can use 1.0.11, 1.0.12, etc.

        // Update version in database
        update_option(self::VERSION_OPTION, $new_version);
        self::log("Upgrade completed to version {$new_version}");
    }

    /**
     * Upgrade routine for version 1.0.10
     * Fix duplicate wp_capabilities entries in existing installations
     *
     * Task-2170 Review-01: Fix existing users with duplicate wp_capabilities entries
     * This migration fixes users who were created before the fix was implemented
     */
    private static function upgrade_to_1_0_10() {
        self::log("Running upgrade to 1.0.10 - Fixing duplicate wp_capabilities");

        try {
            global $wpdb;

            // Find users with duplicate wp_capabilities entries
            $duplicate_users = $wpdb->get_results("
                SELECT user_id, COUNT(*) as count
                FROM {$wpdb->usermeta}
                WHERE meta_key = 'wp_capabilities'
                GROUP BY user_id
                HAVING COUNT(*) > 1
            ");

            if (empty($duplicate_users)) {
                self::log("No duplicate wp_capabilities found - skipping");
                return true;
            }

            $fixed_count = 0;
            foreach ($duplicate_users as $dup_user) {
                $user_id = $dup_user->user_id;

                // Get all wp_capabilities entries for this user
                $caps_entries = $wpdb->get_results($wpdb->prepare("
                    SELECT meta_value
                    FROM {$wpdb->usermeta}
                    WHERE user_id = %d AND meta_key = 'wp_capabilities'
                ", $user_id));

                // Merge all capabilities
                $merged_caps = [];
                foreach ($caps_entries as $entry) {
                    $caps = maybe_unserialize($entry->meta_value);
                    if (is_array($caps)) {
                        $merged_caps = array_merge($merged_caps, $caps);
                    }
                }

                if (!empty($merged_caps)) {
                    // Delete all duplicate entries
                    $wpdb->delete(
                        $wpdb->usermeta,
                        ['user_id' => $user_id, 'meta_key' => 'wp_capabilities']
                    );

                    // Insert single merged entry
                    $wpdb->insert(
                        $wpdb->usermeta,
                        [
                            'user_id' => $user_id,
                            'meta_key' => 'wp_capabilities',
                            'meta_value' => serialize($merged_caps)
                        ]
                    );

                    // Clear user cache
                    wp_cache_delete($user_id, 'user_meta');
                    clean_user_cache($user_id);

                    $fixed_count++;
                    self::log("Fixed duplicate wp_capabilities for user {$user_id}");
                }
            }

            self::log("Upgrade to 1.0.10 completed - Fixed {$fixed_count} users with duplicate wp_capabilities");

            // Flush cache to ensure role changes take effect immediately
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
                self::log("Cache flushed - role changes active");
            }

            return true;

        } catch (\Exception $e) {
            self::log("Error in upgrade to 1.0.10: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log upgrade messages
     */
    private static function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("WP_Customer_Upgrade: {$message}");
        }
    }
}
