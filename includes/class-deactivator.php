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

class WP_Customer_Deactivator {
    private static function debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[WP_Customer_Deactivator] {$message}");
        }
    }

    public static function deactivate() {
        global $wpdb;

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

        // Bersihkan cache
        wp_cache_delete('wp_customer_customer_list', 'wp_customer');
        wp_cache_delete('wp_customer_branch_list', 'wp_customer');
        wp_cache_delete('wp_customer_membership_settings', 'wp_customer');
        
        self::debug("Plugin deactivation complete");
    }

    // Tambahkan metode baru ini
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
        }
    }
}
