<?php
/**
 * Customer Branches Tab - Pure View Pattern (Inner Content Only)
 *
 * @package     WP_Customer
 * @subpackage  Views/Customer/Tabs
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/customer/tabs/branches.php
 *
 * Description: Pure HTML inner content for lazy-loaded branches tab.
 *              Outer wrapper provided by TabSystemTemplate.
 *              This template only provides INNER content (no outer div).
 *              Classes/attributes added directly to outer div via JS.
 *
 * Pattern: Inner Content Only (following wp-agency pattern)
 * - Outer wrapper: Created by TabSystemTemplate (wp-app-core)
 * - This file: Only inner HTML content
 * - Classes: Added via JS after content inject
 * - Lazy-load: Triggered by wpapp-tab-manager.js
 *
 * Changelog:
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

// Note: This template provides INNER content only
// Outer <div id="branches" class="wpapp-tab-content"> is created by TabSystemTemplate
// Classes and data attributes are added via JavaScript after content injection
?>
<!-- Inner content for branches tab -->
<div class="wpapp-branches-tab wpapp-tab-autoload"
     data-customer-id="<?php echo esc_attr($customer_id); ?>"
     data-load-action="load_customer_branches_tab"
     data-content-target=".wpapp-branches-content"
     data-error-message="<?php esc_attr_e('Failed to load branches', 'wp-customer'); ?>">

    <div class="wpapp-tab-header">
        <h3><?php esc_html_e('Daftar Cabang', 'wp-customer'); ?></h3>
    </div>

    <div class="wpapp-tab-loading">
        <p><?php esc_html_e('Memuat data cabang...', 'wp-customer'); ?></p>
    </div>

    <div class="wpapp-branches-content wpapp-tab-loaded-content">
        <!-- Content will be loaded via AJAX by wpapp-tab-manager.js -->
    </div>

    <div class="wpapp-tab-error">
        <p class="wpapp-error-message"></p>
    </div>
</div>
