<?php
/**
 * Audit Log History Tab Template
 *
 * @package     WP_Customer
 * @subpackage  Views/AuditLog
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/audit-log/history-tab.php
 *
 * Description: History tab untuk customer detail page.
 *              Shows complete timeline: customer + branches + employees.
 *              Uses DataTable for server-side processing.
 *
 * Variables Available:
 * - $customer_id: Customer ID untuk filter
 *
 * Changelog:
 * 1.0.0 - 2025-12-28
 * - Initial implementation
 * - DataTable with server-side processing
 * - Complete timeline (customer + related entities)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get customer_id from $data object (provided by tab system)
$customer_id = isset($data->id) ? intval($data->id) : 0;

if (empty($customer_id)) {
    echo '<p>' . esc_html__('Invalid customer ID', 'wp-customer') . '</p>';
    return;
}
?>

<div class="wrap">
    <div id="audit-log-container" class="wpapp-datatable-container">
        <table id="audit-log-datatable" class="wp-list-table widefat fixed striped" data-customer-id="<?php echo esc_attr($customer_id); ?>">
            <thead>
                <tr>
                    <th><?php _e('Date/Time', 'wp-customer'); ?></th>
                    <th><?php _e('Entity', 'wp-customer'); ?></th>
                    <th><?php _e('Event', 'wp-customer'); ?></th>
                    <th><?php _e('Changes', 'wp-customer'); ?></th>
                    <th><?php _e('User', 'wp-customer'); ?></th>
                    <th><?php _e('Actions', 'wp-customer'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="6" class="dataTables_empty"><?php _e('Loading...', 'wp-customer'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
