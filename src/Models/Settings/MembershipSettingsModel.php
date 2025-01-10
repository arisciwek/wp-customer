<?php
/**
 * Membership Settings Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Settings/MembershipSettingsModel.php
 *
 * Description: Model untuk mengelola pengaturan membership levels.
 *              Extends dari SettingsModel untuk modularitas.
 *              Includes pengaturan untuk:
 *              - Konfigurasi level default (Regular, Priority, Utama)
 *              - Batasan staff per level
 *              - Capability management per level
 *              - Default level settings
 *
 * Dependencies:
 * - WPCustomer\Models\Settings\SettingsModel
 * - WordPress Options API
 *
 * Changelog:
 * 1.0.0 - 2024-01-10
 * - Initial version
 * - Added membership settings management
 * - Added default levels configuration
 * - Added sanitization methods
 */

namespace WPCustomer\Models\Settings;

class MembershipSettingsModel extends SettingsModel {
    private $option_group = 'wp_customer_membership_settings';
    private $default_options = [
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

    public function registerSettings() {
        register_setting(
            'wp_customer_membership_settings',
            'wp_customer_membership_settings',
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitizeMembershipOptions'],
                'default' => $this->default_options
            ]
        );
    }
}
