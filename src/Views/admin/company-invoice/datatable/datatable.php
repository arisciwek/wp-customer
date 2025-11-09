<?php
/**
 * Company Invoice DataTable View
 *
 * @package     WP_Customer
 * @subpackage  Views/Admin/CompanyInvoice
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/admin/company-invoice/datatable/datatable.php
 *
 * Description: DataTable markup untuk company invoice list di left panel.
 *              Digunakan oleh CompanyInvoiceDashboardController::render_datatable()
 *              Pattern sama dengan company-datatable.php
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2196)
 * - Initial implementation following DataTable pattern
 * - Uses wp-datatable framework
 * - Columns: Invoice #, Company, From Level, To Level, Period, Amount, Status, Due Date, Actions
 * - Status filters: pending, paid, pending_payment, cancelled
 */

defined('ABSPATH') || exit;
?>

<div class="wpdt-filters-wrapper">
    <div class="wpdt-filters">
        <label>
            <input type="checkbox" id="filter-pending" value="1" checked>
            <?php esc_html_e('Pending', 'wp-customer'); ?>
        </label>
        <label>
            <input type="checkbox" id="filter-paid" value="1">
            <?php esc_html_e('Paid', 'wp-customer'); ?>
        </label>
        <label>
            <input type="checkbox" id="filter-pending-payment" value="1">
            <?php esc_html_e('Pending Payment', 'wp-customer'); ?>
        </label>
        <label>
            <input type="checkbox" id="filter-cancelled" value="1">
            <?php esc_html_e('Cancelled', 'wp-customer'); ?>
        </label>
    </div>
</div>

<div class="wpdt-datatable-wrapper">
    <table id="company-invoice-datatable" class="wpdt-datatable display" style="width:100%">
        <thead>
            <tr>
                <th><?php esc_html_e('Invoice #', 'wp-customer'); ?></th>
                <th><?php esc_html_e('Company', 'wp-customer'); ?></th>
                <th><?php esc_html_e('From Level', 'wp-customer'); ?></th>
                <th><?php esc_html_e('To Level', 'wp-customer'); ?></th>
                <th><?php esc_html_e('Period', 'wp-customer'); ?></th>
                <th><?php esc_html_e('Amount', 'wp-customer'); ?></th>
                <th><?php esc_html_e('Status', 'wp-customer'); ?></th>
                <th><?php esc_html_e('Due Date', 'wp-customer'); ?></th>
                <th><?php esc_html_e('Actions', 'wp-customer'); ?></th>
            </tr>
        </thead>
        <tbody>
            <!-- Data will be populated by DataTables AJAX -->
        </tbody>
    </table>
</div>
