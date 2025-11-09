<?php
/**
 * Branches Tab Content - DataTable
 *
 * @package     WP_Customer
 * @subpackage  Views/Customer/Tabs/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/admin/customer/tabs/partials/branches-content.php
 *
 * Description: DataTable content for branches tab.
 *              Loaded via AJAX by handle_load_branches_tab().
 *              Rendered when tab is first clicked (lazy load).
 *
 * Dependencies:
 * - $customer variable must be set by controller
 * - branches-datatable.js will initialize DataTable
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2193)
 * - Initial implementation for lazy-load pattern
 * - Direct DataTable markup
 * - Auto-initializes via branches-datatable.js
 */

defined('ABSPATH') || exit;

$customer_id = $customer->id ?? 0;

if (!$customer_id) {
    echo '<p>' . __('Customer ID not available', 'wp-customer') . '</p>';
    return;
}
?>

<div class="wpdt-datatable-wrapper">
    <table id="branches-datatable"
           class="wpdt-datatable display"
           data-customer-id="<?php echo esc_attr($customer_id); ?>"
           style="width:100%">
        <thead>
            <tr>
                <th><?php _e('Kode', 'wp-customer'); ?></th>
                <th><?php _e('Nama Cabang', 'wp-customer'); ?></th>
                <th><?php _e('Tipe', 'wp-customer'); ?></th>
                <th><?php _e('Email', 'wp-customer'); ?></th>
                <th><?php _e('Telepon', 'wp-customer'); ?></th>
                <th><?php _e('Status', 'wp-customer'); ?></th>
                <th><?php _e('Actions', 'wp-customer'); ?></th>
            </tr>
        </thead>
        <tbody>
            <!-- DataTable will populate via AJAX -->
        </tbody>
    </table>
</div>
