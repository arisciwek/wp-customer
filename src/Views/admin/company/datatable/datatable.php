<?php
/**
 * Company DataTable View
 *
 * @package     WP_Customer
 * @subpackage  Views/Admin/Company
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/admin/company/datatable/datatable.php
 *
 * Description: DataTable markup untuk company list di left panel.
 *              Digunakan oleh CompanyDashboardController::render_datatable()
 *              Pattern sama dengan customer-datatable.php
 *
 * Changelog:
 * 1.1.0 - 2025-12-26
 * - Added Disnaker, Unit Kerja, and Pengawas columns
 * - Columns: Code, Name, Type, Email, Phone, Disnaker, Unit Kerja, Pengawas, Actions
 *
 * 1.0.1 - 2025-12-25
 * - Removed status column (only active companies shown)
 * - Columns: Code, Name, Type, Email, Phone, Actions
 *
 * 1.0.0 - 2025-11-09 (TODO-2195)
 * - Initial implementation following customer DataTable pattern
 * - Uses wp-datatable framework
 */

defined('ABSPATH') || exit;
?>

<div class="wpdt-datatable-wrapper">
    <table id="company-datatable" class="wpdt-datatable display" style="width:100%">
        <thead>
            <tr>
                <th><?php esc_html_e('Code', 'wp-customer'); ?></th>
                <th><?php esc_html_e('Company Name', 'wp-customer'); ?></th>
                <th><?php esc_html_e('Type', 'wp-customer'); ?></th>
                <th><?php esc_html_e('Email', 'wp-customer'); ?></th>
                <th><?php esc_html_e('Phone', 'wp-customer'); ?></th>
                <th><?php esc_html_e('Disnaker', 'wp-customer'); ?></th>
                <th><?php esc_html_e('Unit Kerja', 'wp-customer'); ?></th>
                <th><?php esc_html_e('Pengawas', 'wp-customer'); ?></th>
                <th><?php esc_html_e('Actions', 'wp-customer'); ?></th>
            </tr>
        </thead>
        <tbody>
            <!-- Data will be populated by DataTables AJAX -->
        </tbody>
    </table>
</div>
