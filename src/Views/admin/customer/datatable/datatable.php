<?php
/**
 * Customer DataTable View - Left Panel
 *
 * @package     WP_Customer
 * @subpackage  Views/Admin/DataTable
 * @version     3.0.1
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/admin/customer/datatable/datatable.php
 *
 * Description: DataTable HTML untuk customer listing di left panel.
 *              Rendered via wpdt_left_panel_content hook.
 *              Used by wp-datatable DualPanel layout.
 *              PURE HTML - no JavaScript (separation of concerns).
 *
 * Important Classes:
 * - wpdt-datatable: Required for panel-manager.js to find DataTable instance
 *
 * Changelog:
 * 3.0.1 - 2025-11-09
 * - Added wpdt-datatable class for panel-manager.js detection
 * - Fixed path in header comment
 *
 * 3.0.0 - 2025-11-09
 * - Created for wp-datatable integration
 * - Standalone DataTable view for left panel
 */

defined('ABSPATH') || exit;
?>

<div class="wpdt-datatable-wrapper">
    <table id="customer-datatable" class="wpdt-datatable display" style="width:100%">
        <thead>
            <tr>
                <th><?php _e('Code', 'wp-customer'); ?></th>
                <th><?php _e('Name', 'wp-customer'); ?></th>
                <th><?php _e('NPWP', 'wp-customer'); ?></th>
                <th><?php _e('NIB', 'wp-customer'); ?></th>
                <th><?php _e('Status', 'wp-customer'); ?></th>
                <th><?php _e('Actions', 'wp-customer'); ?></th>
            </tr>
        </thead>
        <tbody>
            <!-- DataTable will populate via AJAX -->
        </tbody>
    </table>
</div>
