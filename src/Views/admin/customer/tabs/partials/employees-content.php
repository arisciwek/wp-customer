<?php
/**
 * Employees Tab Content - DataTable
 *
 * @package     WP_Customer
 * @subpackage  Views/Customer/Tabs/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/admin/customer/tabs/partials/employees-content.php
 *
 * Description: DataTable content for employees tab.
 *              Loaded via AJAX by handle_load_employees_tab().
 *              Rendered when tab is first clicked (lazy load).
 *
 * Dependencies:
 * - $customer variable must be set by controller
 * - employees-datatable.js will initialize DataTable
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2194)
 * - Initial implementation for lazy-load pattern
 * - Direct DataTable markup
 * - Auto-initializes via employees-datatable.js
 * - Same pattern as branches-content.php
 */

defined('ABSPATH') || exit;

$customer_id = $customer->id ?? 0;

if (!$customer_id) {
    echo '<p>' . __('Customer ID not available', 'wp-customer') . '</p>';
    return;
}
?>

<div class="wpdt-datatable-wrapper">
    <table id="employees-datatable"
           class="wpdt-datatable display"
           data-customer-id="<?php echo esc_attr($customer_id); ?>"
           style="width:100%">
        <thead>
            <tr>
                <th><?php _e('Nama', 'wp-customer'); ?></th>
                <th><?php _e('Jabatan', 'wp-customer'); ?></th>
                <th><?php _e('Departemen', 'wp-customer'); ?></th>
                <th><?php _e('Email', 'wp-customer'); ?></th>
                <th><?php _e('Cabang', 'wp-customer'); ?></th>
                <th><?php _e('Status', 'wp-customer'); ?></th>
                <th><?php _e('Actions', 'wp-customer'); ?></th>
            </tr>
        </thead>
        <tbody>
            <!-- DataTable will populate via AJAX -->
        </tbody>
    </table>
</div>
