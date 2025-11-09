<?php
/**
 * Customer DataTable View - Left Panel
 *
 * @package     WP_Customer
 * @subpackage  Views/Admin/DataTable
 * @version     3.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/admin/datatable/datatable.php
 *
 * Description: DataTable HTML untuk customer listing di left panel.
 *              Rendered via wpdt_left_panel_content hook.
 *              Used by wp-datatable DualPanel layout.
 *
 * Changelog:
 * 3.0.0 - 2025-11-09
 * - Created for wp-datatable integration
 * - Standalone DataTable view for left panel
 * - Moved to Views/admin/datatable/
 */

defined('ABSPATH') || exit;
?>

<div class="wpdt-datatable-wrapper">
    <table id="customer-table" class="wpdt-table display" style="width:100%">
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

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize DataTable
    var customerTable = $('#customer-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: ajaxurl,
            type: 'POST',
            data: function(d) {
                d.action = 'get_customer_datatable';
                d.nonce = '<?php echo wp_create_nonce('wpdt_nonce'); ?>';
            }
        },
        columns: [
            { data: 'code', name: 'code' },
            { data: 'name', name: 'name' },
            { data: 'npwp', name: 'npwp' },
            { data: 'nib', name: 'nib' },
            {
                data: 'status',
                name: 'status',
                render: function(data, type, row) {
                    if (data === 'active') {
                        return '<span class="wpdt-badge wpdt-badge-success"><?php _e('Active', 'wp-customer'); ?></span>';
                    } else {
                        return '<span class="wpdt-badge wpdt-badge-danger"><?php _e('Inactive', 'wp-customer'); ?></span>';
                    }
                }
            },
            {
                data: 'id',
                name: 'actions',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return '<button class="button button-small wpdt-view-details" data-id="' + data + '">' +
                           '<?php _e('View', 'wp-customer'); ?></button>';
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        language: {
            processing: '<?php _e('Processing...', 'wp-customer'); ?>',
            search: '<?php _e('Search:', 'wp-customer'); ?>',
            lengthMenu: '<?php _e('Show _MENU_ entries', 'wp-customer'); ?>',
            info: '<?php _e('Showing _START_ to _END_ of _TOTAL_ entries', 'wp-customer'); ?>',
            infoEmpty: '<?php _e('Showing 0 to 0 of 0 entries', 'wp-customer'); ?>',
            infoFiltered: '<?php _e('(filtered from _MAX_ total entries)', 'wp-customer'); ?>',
            zeroRecords: '<?php _e('No matching records found', 'wp-customer'); ?>',
            emptyTable: '<?php _e('No data available in table', 'wp-customer'); ?>',
            paginate: {
                first: '<?php _e('First', 'wp-customer'); ?>',
                previous: '<?php _e('Previous', 'wp-customer'); ?>',
                next: '<?php _e('Next', 'wp-customer'); ?>',
                last: '<?php _e('Last', 'wp-customer'); ?>'
            }
        }
    });

    // Handle view button click - open right panel
    $('#customer-table').on('click', '.wpdt-view-details', function() {
        var customerId = $(this).data('id');

        // Trigger wp-datatable panel open event
        $(document).trigger('wpdt:openPanel', {
            entity: 'customer',
            id: customerId
        });
    });

    // Refresh table on customer save/delete
    $(document).on('wpdt:refresh', function(e, data) {
        if (data.entity === 'customer') {
            customerTable.ajax.reload(null, false);
        }
    });
});
</script>
