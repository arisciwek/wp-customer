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
 * 
 * Dependencies:
 * - WPCustomer\Database\Installer untuk instalasi database
 * - WPCustomer\Models\Settings\PermissionModel untuk setup capabilities
 * - WordPress Options API
 * 
 * Changelog:
 * 1.0.1 - 2024-01-07
 * - Refactored database installation to use Database\Installer
 * - Enhanced error handling
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
    private static function logError($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("WP_Customer_Activator Error: {$message}");
        }
    }

    public static function activate() {
        try {
            $installer = new Installer();
            if (!$installer->run()) {
                self::logError('Failed to install database tables');
                return;
            }

            self::addVersion();

            try {
                $permission_model = new PermissionModel();
                $permission_model->addCapabilities();
            } catch (\Exception $e) {
                self::logError('Error adding capabilities: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            self::logError('Critical error during activation: ' . $e->getMessage());
            throw $e;
        }
    }

    private static function addVersion() {
        add_option('wp_customer_version', WP_CUSTOMER_VERSION);
    }
}
