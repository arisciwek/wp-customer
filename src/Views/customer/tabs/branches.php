<?php
/**
 * Customer Branches Tab - Lazy-Load Pattern
 *
 * @package     WP_Customer
 * @subpackage  Views/Customer/Tabs
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/customer/tabs/branches.php
 *
 * Description: Lazy-loaded branches tab with DataTable.
 *              Content loaded via AJAX on first tab click.
 *              Works flicker-free after wp-app-core animation fix (TODO-1197).
 *
 * Changelog:
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

// Lazy-load pattern for testing
?>
<div class="wpapp-branches-tab wpapp-tab-autoload"
     data-customer-id="<?php echo esc_attr($customer_id); ?>"
     data-load-action="load_customer_branches_tab"
     data-content-target=".wpapp-branches-content"
     data-error-message="<?php esc_attr_e('Failed to load branches', 'wp-customer'); ?>">

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
