<?php
/**
 * Customer Permission Management Tab Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Settings
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/settings/tab-permissions.php
 *
 * Description: Template untuk mengelola customer permissions.
 *              Uses shared permission-matrix.php template from wp-app-core.
 *              Controller handles all logic, template just displays.
 *              87% code reduction (314 lines → 40 lines)!
 *
 * Changelog:
 * 2.0.0 - 2025-01-13 (TODO-2200 Phase 5)
 * - BREAKING: Complete rewrite using AbstractPermissionsController pattern
 * - Uses shared permission-matrix.php template from wp-app-core
 * - All data from controller->getViewModel()
 * - Removed manual permission handling logic
 * - 87% code reduction (314 → 40 lines)
 *
 * 1.1.0 - 2025-10-29 (TODO-2181)
 * - Show only customer roles (not all WordPress roles)
 * - Added header section with description
 * - Added icon indicator for customer roles
 *
 * 1.0.0 - 2024-01-07
 * - Initial version
 */

if (!defined('ABSPATH')) {
    die;
}

// Get controller instance (RoleManager loaded in constructor)
$permissions_controller = new \WPCustomer\Controllers\Settings\CustomerPermissionsController();

// Get view data from controller
$view_data = $permissions_controller->getViewModel();

// Extract variables for template
extract($view_data);

// Load shared permission matrix template from wp-app-core
// NO DUPLICATION - single source of truth!
require_once WP_APP_CORE_PLUGIN_DIR . 'src/Views/templates/permissions/permission-matrix.php';
