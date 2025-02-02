<?php
/**
 * File: class-deactivator.php
 * Path: /wp-customer/includes/class-deactivator.php
 * Description: Menangani proses deaktivasi plugin
 * 
 * @package     WP_Customer
 * @subpackage  Includes
 * @version     1.0.1
 * @author      arisciwek
 * 
 * Description: Menangani proses deaktivasi plugin:
 *              - Menghapus seluruh tabel (fase development)
 *              - Membersihkan cache 
 *
 * Changelog:
 * 1.0.1 - 2024-01-07
 * - Added table cleanup during deactivation
 * - Added logging for development
 * 
 * 1.0.0 - 2024-11-23  
 * - Initial creation
 * - Added cache cleanup
 */
use WP_Customer\Cache\CacheManager;

class WP_Customer_Deactivator {
    private static function debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[WP_Customer_Deactivator] {$message}");
        }
    }

    private static function should_clear_data() {
        // Get development settings
        $dev_settings = get_option('wp_customer_development_settings');
        
        // Always check the clear_data_on_deactivate setting first
        if (isset($dev_settings['clear_data_on_deactivate']) && 
            $dev_settings['clear_data_on_deactivate']) {
            return true;
        }
        
        // Fallback to constant if settings don't explicitly enable cleanup
        return defined('WP_CUSTOMER_DEVELOPMENT') && WP_CUSTOMER_DEVELOPMENT;
    }

    public static function deactivate() {
        global $wpdb;
        
        $should_clear_data = self::should_clear_data();

        // Hapus development settings terlebih dahulu
        delete_option('wp_customer_development_settings');
        self::debug("Development settings cleared");

        try {
            // Only proceed with data cleanup if in development mode
            if (!$should_clear_data) {
                self::debug("Skipping data cleanup on plugin deactivation as in settings");
                return;
            }

            // Add this new method call at the start
            self::remove_capabilities();

            // Start transaction
            $wpdb->query('START TRANSACTION');

            // Delete demo users (starting from ID 11)
            self::delete_demo_users();

            // Daftar tabel yang akan dihapus
            $tables = [
                'app_customer_employees',
                'app_branches',
                'app_customer_membership_levels',
                'app_customers'
            ];

            // Hapus tabel secara terurut (child tables first)
            foreach ($tables as $table) {
                $table_name = $wpdb->prefix . $table;
                $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
                self::debug("Dropping table: {$table_name}");
            }

            // Hapus semua opsi terkait membership
            self::cleanupMembershipOptions();


            // Clear cache using CacheManager
            try {
                $cache_manager = new WP_Customer\Cache\CacheManager();
                $cache_manager->clearAllCaches();
                self::debug("All caches cleared via CacheManager");
            } catch (\Exception $e) {
                self::debug("Error clearing cache: " . $e->getMessage());
                // Don't throw the exception - continue with deactivation
            }

            // Commit transaction
            $wpdb->query('COMMIT');
            
            self::debug("Plugin deactivation complete");

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            self::debug("Error during deactivation: " . $e->getMessage());
        }
    }

    // Add this new private method
    private static function remove_capabilities() {
        try {
            // Get the list of all capabilities from PermissionModel
            $permission_model = new \WPCustomer\Models\Settings\PermissionModel();
            $capabilities = array_keys($permission_model->getAllCapabilities());

            // Remove capabilities from all roles
            foreach (get_editable_roles() as $role_name => $role_info) {
                $role = get_role($role_name);
                if (!$role) continue;

                foreach ($capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }

            // Also remove the customer role entirely since we created it
            remove_role('customer');

            self::debug("Capabilities and customer role removed successfully");
        } catch (\Exception $e) {
            self::debug("Error removing capabilities: " . $e->getMessage());
            throw $e;
        }
    }
        
    private static function delete_demo_users() {
        global $wpdb;
        
        try {
            // Start transaction
            $wpdb->query('START TRANSACTION');
            
            // 1. Identifikasi user berdasarkan meta/role spesifik plugin
            // DITAMBAHKAN "AND u.ID != 1" untuk lindungi admin
            $demo_users = $wpdb->get_col("
                SELECT DISTINCT u.ID 
                FROM {$wpdb->users} u
                INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
                WHERE u.ID != 1  /* Proteksi admin */
                AND (
                    (um.meta_key = 'wp_customer_demo_user' AND um.meta_value = '1')
                    OR EXISTS (
                        SELECT 1 
                        FROM {$wpdb->usermeta} um2 
                        WHERE um2.user_id = u.ID 
                        AND um2.meta_key = 'wp_capabilities'
                        AND um2.meta_value LIKE '%customer%'
                    )
                )
            ");
            
            if (!empty($demo_users)) {
                $user_ids = implode(',', array_map('intval', $demo_users));
                
                // Double check untuk memastikan ID=1 tidak masuk
                if (in_array(1, explode(',', $user_ids))) {
                    throw new \Exception("Attempted to delete admin user - operation aborted");
                }
                
                // 2. Hapus entries di wp_usermeta
                $wpdb->query("
                    DELETE FROM {$wpdb->usermeta} 
                    WHERE user_id IN ($user_ids)
                    AND user_id != 1  /* Double protection */
                ");
                self::debug("Deleted usermeta entries for users: $user_ids");
                
                // 3. Hapus relasi di tabel WordPress lain yang terkait user
                // comments
                $wpdb->query("
                    DELETE FROM {$wpdb->comments} 
                    WHERE user_id IN ($user_ids)
                    AND user_id != 1
                ");
                
                // posts
                $wpdb->query("
                    DELETE FROM {$wpdb->posts} 
                    WHERE post_author IN ($user_ids)
                    AND post_author != 1
                ");
                
                // term relationships (jika ada)
                $wpdb->query("
                    DELETE tr FROM {$wpdb->term_relationships} tr
                    INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
                    WHERE p.post_author IN ($user_ids)
                    AND p.post_author != 1
                ");
                
                // 4. Hapus custom table relations
                $wpdb->query("
                    DELETE FROM {$wpdb->prefix}app_customer_employees 
                    WHERE user_id IN ($user_ids)
                    AND user_id != 1
                ");
                
                // 5. Terakhir, hapus user
                $wpdb->query("
                    DELETE FROM {$wpdb->users} 
                    WHERE ID IN ($user_ids)
                    AND ID != 1  /* Final protection */
                ");
                
                self::debug("Successfully deleted " . count($demo_users) . " demo users and their related data");
                
                // 6. Reset auto increment ke 2 untuk development
                if (defined('WP_CUSTOMER_DEVELOPMENT') && WP_CUSTOMER_DEVELOPMENT) {
                    $wpdb->query("ALTER TABLE {$wpdb->users} AUTO_INCREMENT = 2");
                    self::debug("Reset users table AUTO_INCREMENT to 2");
                }
            } else {
                self::debug("No demo users found to delete");
            }
            
            $wpdb->query('COMMIT');
            
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            self::debug("Error managing users: " . $e->getMessage());
            throw $e;
        }
    }
    private static function cleanupMembershipOptions() {
        try {
            // Hapus opsi membership settings
            delete_option('wp_customer_membership_settings');
            self::debug("Membership settings deleted");

            // Hapus transients jika ada
            delete_transient('wp_customer_membership_cache');
            self::debug("Membership transients cleared");

        } catch (\Exception $e) {
            self::debug("Error cleaning up membership options: " . $e->getMessage());
            throw $e;
        }
    }
}