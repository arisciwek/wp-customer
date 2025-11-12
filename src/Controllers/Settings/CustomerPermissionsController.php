<?php
/**
 * Customer Permissions Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Settings/CustomerPermissionsController.php
 *
 * Description: Controller untuk customer permission management.
 *              MINIMAL IMPLEMENTATION: Extends AbstractPermissionsController.
 *              All AJAX handlers and logic provided by abstract.
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (TODO-2200)
 * - Initial implementation extending AbstractPermissionsController
 * - 50% code reduction vs manual implementation
 * - AJAX handlers auto-registered by abstract
 * - Uses shared assets from wp-app-core
 */

namespace WPCustomer\Controllers\Settings;

use WPAppCore\Controllers\Abstract\AbstractPermissionsController;
use WPAppCore\Models\Abstract\AbstractPermissionsModel;
use WPAppCore\Validators\Abstract\AbstractPermissionsValidator;
use WPCustomer\Models\Settings\PermissionModel;
use WPCustomer\Validators\Settings\CustomerPermissionValidator;

defined('ABSPATH') || exit;

class CustomerPermissionsController extends AbstractPermissionsController {

    /**
     * Constructor
     * Ensures RoleManager is loaded before any operations
     */
    public function __construct() {
        // Load RoleManager class (required by controller, model, and validator)
        require_once WP_CUSTOMER_PATH . 'includes/class-role-manager.php';

        // Call parent constructor to initialize model and validator
        parent::__construct();
    }

    /**
     * Get plugin slug
     */
    protected function getPluginSlug(): string {
        return 'wp-customer';
    }

    /**
     * Get plugin prefix for AJAX actions
     */
    protected function getPluginPrefix(): string {
        return 'customer';
    }

    /**
     * Get role manager class name
     */
    protected function getRoleManagerClass(): string {
        return 'WP_Customer_Role_Manager';
    }

    /**
     * Get model instance
     */
    protected function getModel(): AbstractPermissionsModel {
        return new PermissionModel();
    }

    /**
     * Get validator instance
     */
    protected function getValidator(): AbstractPermissionsValidator {
        return new CustomerPermissionValidator();
    }

    /**
     * Initialize controller
     * Registers AJAX handlers AND asset enqueuing
     */
    public function init(): void {
        // Register AJAX handlers via parent
        parent::init();

        // Register asset enqueuing for permissions tab
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);

        // Customize footer for permissions tab (show info message instead of buttons)
        add_filter('wpc_settings_footer_content', [$this, 'customizeFooterForPermissionsTab'], 10, 3);
    }

    /**
     * Customize footer content for permissions tab
     * Shows "Changes are saved automatically" message instead of Save/Reset buttons
     *
     * @param string $footer_html Default footer HTML
     * @param string $tab Current tab
     * @param array $config Current tab config
     * @return string Custom footer HTML
     */
    public function customizeFooterForPermissionsTab(string $footer_html, string $tab, array $config): string {
        if ($tab === 'permissions') {
            return '<div class="notice notice-info inline" style="margin: 0;">' .
                   '<p style="margin: 0.5em 0;">' .
                   '<span class="dashicons dashicons-info" style="color: #2271b1;"></span> ' .
                   '<strong>' . __('Perubahan disimpan otomatis', 'wp-customer') . '</strong> ' .
                   __('— Setiap perubahan permission disimpan langsung via AJAX.', 'wp-customer') .
                   '</p>' .
                   '</div>';
        }
        return $footer_html;
    }

    /**
     * Enqueue assets for permissions tab
     * Only loads on correct page and tab
     * USES SHARED ASSETS from wp-app-core!
     */
    public function enqueueAssets(string $hook): void {
        // Only on wp-customer settings page
        // NOTE: Settings is a SUBMENU, not toplevel, so screen ID is wp-customer_page_wp-customer-settings
        if ($hook !== 'wp-customer_page_wp-customer-settings') {
            return;
        }

        // Only on permissions tab
        $tab = $_GET['tab'] ?? '';
        if ($tab !== 'permissions') {
            return;
        }

        // Call parent to load shared assets from wp-app-core
        parent::enqueueAssets($hook);
    }

    /**
     * Get page title for permission matrix
     */
    protected function getPageTitle(): string {
        return __('Customer Permission Management', 'wp-customer');
    }

    /**
     * Get page description for permission matrix
     */
    protected function getPageDescription(): string {
        return __('Konfigurasi hak akses role untuk plugin customer. Perubahan berlaku langsung.', 'wp-customer');
    }
}
