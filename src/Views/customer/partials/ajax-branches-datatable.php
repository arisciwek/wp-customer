<?php
/**
 * AJAX Branches DataTable - Lazy-loaded DataTable HTML
 *
 * @package     WP_Customer
 * @subpackage  Views/Customer/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/customer/partials/ajax-branches-datatable.php
 *
 * Description: Generates DataTable HTML for branches lazy-load.
 *              Returned via AJAX when branches tab is clicked.
 *              Called by: CustomerDashboardController::handle_load_branches_tab()
 *
 * Context: AJAX response (lazy-load)
 * Scope: MIXED (wpapp-* for DataTable structure, customer-* for local)
 *
 * Variables available:
 * @var int $customer_id Customer ID for DataTable filtering
 *
 * Initialization:
 * - DataTable initialized by customer-datatable.js (event-driven)
 * - Uses data-* attributes for configuration
 * - No inline JavaScript (pure HTML only)
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (Review-02 from TODO-2187)
 * - Initial creation following wp-agency pattern
 * - Columns: Kode, Nama Cabang, Tipe, Email, Telepon, Status
 */

defined('ABSPATH') || exit;

// Ensure $customer_id exists
if (!isset($customer_id)) {
    echo '<p>' . __('Customer ID not available', 'wp-customer') . '</p>';
    return;
}
?>

<table id="customer-branches-datatable"
       class="wpapp-datatable customer-lazy-datatable"
       style="width:100%"
       data-entity="branch"
       data-customer-id="<?php echo esc_attr($customer_id); ?>"
       data-ajax-action="get_customer_branches_datatable">
    <thead>
        <tr>
            <th><?php esc_html_e('Kode', 'wp-customer'); ?></th>
            <th><?php esc_html_e('Nama Cabang', 'wp-customer'); ?></th>
            <th><?php esc_html_e('Tipe', 'wp-customer'); ?></th>
            <th><?php esc_html_e('Email', 'wp-customer'); ?></th>
            <th><?php esc_html_e('Telepon', 'wp-customer'); ?></th>
            <th><?php esc_html_e('Status', 'wp-customer'); ?></th>
        </tr>
    </thead>
    <tbody>
        <!-- DataTable will populate via AJAX -->
    </tbody>
</table>
