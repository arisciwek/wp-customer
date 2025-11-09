<?php
/**
 * Customer Branches Tab - wp-datatable Lazy Load
 *
 * @package     WP_Customer
 * @subpackage  Views/Customer/Tabs
 * @version     2.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/admin/customer/tabs/branches.php
 *
 * Description: Branches tab with lazy-load pattern from wp-datatable.
 *              Content loaded via AJAX on first tab click using wpdt-tab-autoload.
 *              Tab switching handled by tab-manager.js automatically.
 *
 * Changelog:
 * 2.1.0 - 2025-11-09 (TODO-2193 Review)
 * - CORRECTED: Use wp-datatable lazy-load pattern (wpdt-tab-autoload)
 * - Changed from wpapp-tab-autoload to wpdt-tab-autoload
 * - Uses wp-datatable tab-manager.js lazy loading
 * - Content loaded via load_customer_branches_tab action
 *
 * 2.0.0 - 2025-11-09 (TODO-2193)
 * - REVERTED: Direct rendering was wrong approach
 * - wp-datatable SUPPORTS lazy load with wpdt-tab-autoload
 * - Tab switching handled automatically by framework
 *
 * 1.1.0 - 2025-11-02 (TODO-2189)
 * - Confirmed lazy-load works without flicker after animation fix
 * - Root cause was fadeIn animation in wp-app-core (fixed in TODO-1197)
 * - Lazy-load pattern preferred for performance
 *
 * 1.0.0 - 2025-11-01 (Review-02 from TODO-2187)
 * - Initial implementation following wp-agency/tabs/divisions.php pattern
 * - Lazy-loaded DataTable for customer branches
 * - Auto-load via data-attributes
 */

defined('ABSPATH') || exit;

// $customer variable is passed from controller
if (!isset($customer) && !isset($data)) {
    echo '<p>' . __('Data not available', 'wp-customer') . '</p>';
    return;
}

// Support both $customer and $data variable names
$customer = $customer ?? $data;

$customer_id = $customer->id ?? 0;

if (!$customer_id) {
    echo '<p>' . __('Customer ID not available', 'wp-customer') . '</p>';
    return;
}

// wp-datatable lazy-load pattern
?>
<div class="wpdt-branches-tab wpdt-tab-autoload"
     data-customer-id="<?php echo esc_attr($customer_id); ?>"
     data-load-action="load_customer_branches_tab"
     data-content-target=".wpdt-branches-content"
     data-error-message="<?php esc_attr_e('Failed to load branches', 'wp-customer'); ?>">

    <div class="wpdt-tab-loading">
        <p><?php esc_html_e('Memuat data cabang...', 'wp-customer'); ?></p>
    </div>

    <div class="wpdt-branches-content wpdt-tab-loaded-content">
        <!-- Content will be loaded via AJAX by wpdt-tab-manager.js -->
    </div>

    <div class="wpdt-tab-error">
        <p class="wpdt-error-message"></p>
    </div>
</div>
