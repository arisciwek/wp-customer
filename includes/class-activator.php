<?php
/**
 * File: class-activator.php
 * Path: /wp-customer/includes/class-activator.php
 * Description: Handles plugin activation and database installation
 * 
 * @package     WP_Customer
 * @subpackage  Includes
 * @version     1.0.1
 * @author      arisciwek
 * 
 * Description: Menangani proses aktivasi plugin dan instalasi database.
 *              Termasuk di dalamnya:
 *              - Instalasi tabel database melalui Database\Installer
 *              - Menambahkan versi plugin ke options table
 *              - Setup permission dan capabilities
 *              - Debug logging untuk proses aktivasi
 * 
 * Dependencies:
 * - WPCustomer\Database\Installer untuk instalasi database
 * - WPCustomer\Models\Settings\PermissionModel untuk setup capabilities
 * - WordPress Options API
 * 
 * Changelog:
 * 1.0.1 - 2024-01-07
 * - Refactored database installation to use Database\Installer
 * - Enhanced error handling and logging
 * - Added dependency management
 * 
 * 1.0.0 - 2024-11-23
 * - Initial creation
 * - Added activation handling
 * - Added version management
 * - Added permissions setup
 */
use WPCustomer\Models\Settings\PermissionModel;
use WPCustomer\Database\Installer;

class WP_Customer_Activator {
    private static function debug($message, $data = null) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $timestamp = current_time('mysql');
            $data_str = $data ? print_r($data, true) : '';
            error_log("[{$timestamp}] WP_Customer_Activator: {$message} {$data_str}");
        }
    }

    private static function logDbError($wpdb, $context) {
        if ($wpdb->last_error) {
            self::debug("Database error in {$context}: " . $wpdb->last_error);
            return false;
        }
        return true;
    }

    public static function activate() {
        self::debug('Starting plugin activation...');

        try {
            // Install database tables using Installer
            self::debug('Installing database tables...');
            $installer = new Installer();
            $install_result = $installer->run();
            
            if (!$install_result) {
                self::debug('Failed to install database tables');
                return;
            }

            self::debug('Adding version...');
            self::addVersion();

            self::debug('Adding capabilities...');
            try {
                $permission_model = new PermissionModel();
                $permission_model->addCapabilities();
                self::debug('Capabilities added successfully');
            } catch (\Exception $e) {
                self::debug('Error adding capabilities: ' . $e->getMessage());
            }

            self::debug('Plugin activation completed successfully');

        } catch (\Exception $e) {
            self::debug('Critical error during activation: ' . $e->getMessage());
            throw $e;
        }
    }

    private static function addVersion() {
        self::debug('Adding plugin version to options...');
        $result = add_option('wp_customer_version', WP_CUSTOMER_VERSION);
        self::debug('Version option added: ' . ($result ? 'success' : 'failed or already exists'));
    }
}
