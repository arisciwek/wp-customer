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

            // Add customer role if it doesn't exist
            if (!get_role('customer')) {
                add_role(
                    'customer',
                    __('Customer', 'wp-customer'),
                    [
                        'view_customer_list' => true,
                        'add_customer' => true,
                        'view_own_customer' => true,
                        'edit_own_customer' => true,
                        'view_own_customer' => true,
                        'delete_customer' => false,

                        'add_branch' => true,
                        'view_branch_list' => true,
                        'view_own_branch' => true,
                        'edit_own_branch' => true,
                        'delete_branch' => false,

                        'add_employee' => true,
                        'view_employee_list' => true,
                        'view_own_employee' => true,
                        'edit_own_employee' => true,
                        'delete_employee' => false

                    ]
                );
            }

            // Inisialisasi permission model untuk set semua capabilities
            $permission_model = new \WPCustomer\Models\Settings\PermissionModel();
            $permission_model->addCapabilities();

            // After permissions are set, load demo data
            try {
                require_once WP_CUSTOMER_PATH . 'src/Database/DemoData.php';
                if (class_exists('\WPCustomer\Database\DemoData')) {
                    \WPCustomer\Database\DemoData::load();
                    error_log('Demo data loaded successfully');
                } else {
                    self::logError('DemoData class not found');
                }
            } catch (\Exception $e) {
                self::logError('Error loading demo data: ' . $e->getMessage());
            }


            self::addVersion();
            self::setupMembershipDefaults(); // Tambahkan ini

            try {
                $permission_model = new PermissionModel();
                $permission_model->addCapabilities();
            } catch (\Exception $e) {
                self::logError('Error adding capabilities: ' . $e->getMessage());
            }

            // Add rewrite rule untuk halaman registrasi
            add_rewrite_rule(
                'customer-register/?$',
                'index.php?wp_customer_register=1',
                'top'
            );

            // Flush rewrite rules
            flush_rewrite_rules();

        } catch (\Exception $e) {
            self::logError('Critical error during activation: ' . $e->getMessage());
            throw $e;
        }
    }

    // Tambahkan metode baru ini
    private static function setupMembershipDefaults() {
        try {
            // Periksa apakah settings sudah ada
            if (!get_option('wp_customer_membership_settings')) {
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

                add_option('wp_customer_membership_settings', $default_settings);
            }
        } catch (\Exception $e) {
            self::logError('Error setting up membership defaults: ' . $e->getMessage());
        }
    }

    private static function addVersion() {
        add_option('wp_customer_version', WP_CUSTOMER_VERSION);
    }
}
