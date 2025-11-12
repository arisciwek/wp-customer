<?php
/**
 * Customer Permission Validator
 *
 * @package     WP_Customer
 * @subpackage  Validators/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Validators/Settings/CustomerPermissionValidator.php
 *
 * Description: Validator untuk customer permission management.
 *              Extends AbstractPermissionsValidator dari wp-app-core untuk standardisasi.
 *              Provides server-side validation untuk save dan reset operations.
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (TODO-2200)
 * - Initial implementation extending AbstractPermissionsValidator
 * - Implements 3 required abstract methods
 * - Protected role: customer_admin (highest operational role)
 * - Manage permission capability: manage_options
 */

namespace WPCustomer\Validators\Settings;

use WPAppCore\Validators\Abstract\AbstractPermissionsValidator;

defined('ABSPATH') || exit;

class CustomerPermissionValidator extends AbstractPermissionsValidator {

    /**
     * Get role manager class name
     * Required by AbstractPermissionsValidator
     *
     * @return string
     */
    protected function getRoleManagerClass(): string {
        return 'WP_Customer_Role_Manager';
    }

    /**
     * Get capability required to manage permissions
     * Users need this capability to modify permissions
     *
     * @return string
     */
    protected function getManagePermissionCapability(): string {
        return 'manage_options';
    }

    /**
     * Get protected roles that cannot be modified
     * Highest operational role should be protected
     *
     * @return array Array of protected role slugs
     */
    protected function getProtectedRoles(): array {
        return [
            'customer_admin'  // Highest operational role - protect from modification
        ];
    }

    // All validation logic inherited from AbstractPermissionsValidator:
    // - validateSaveRequest() - Validates role, capability, enabled parameters
    // - validateResetRequest() - Validates reset permission
    // - userCanManagePermissions() - Checks if user has manage_options
    // - isPluginRole() - Checks if role belongs to plugin
    // - sanitizeRoleSlug() - Sanitizes role slug
    // - sanitizeCapability() - Sanitizes capability
}
