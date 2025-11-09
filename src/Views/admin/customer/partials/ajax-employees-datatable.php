<?php
/**
 * AJAX Employees DataTable - Lazy-loaded DataTable HTML
 *
 * @package     WP_Customer
 * @subpackage  Views/Customer/Partials
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/customer/partials/ajax-employees-datatable.php
 *
 * Description: Generates DataTable HTML for employees lazy-load.
 *              Returned via AJAX when employees tab is clicked.
 *              Called by: CustomerDashboardController::handle_load_employees_tab()
 *
 * Context: AJAX response (lazy-load)
 * Scope: MIXED (wpapp-* for DataTable structure)
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
 * 1.1.0 - 2025-11-02 (TODO-2191)
 * - Added: Include create and edit employee modal forms
 * - Centralized modal pattern like branches
 *
 * 1.0.0 - 2025-11-01 (Review-02 from TODO-2187)
 * - Initial creation following wp-agency pattern
 * - Columns: Nama, Jabatan, Email, Telepon, Status
 */

defined('ABSPATH') || exit;

// Ensure $customer_id exists
if (!isset($customer_id)) {
    echo '<p>' . __('Customer ID not available', 'wp-customer') . '</p>';
    return;
}

// Check permission for add button
$can_add_employee = current_user_can('manage_options') || current_user_can('add_customer_employee');
?>

<div class="customer-tab-content-wrapper">

<?php if ($can_add_employee): ?>
<div class="customer-tab-header-actions">
    <button type="button" class="button button-primary employee-add-btn" data-customer-id="<?php echo esc_attr($customer_id); ?>">
        <span class="dashicons dashicons-plus-alt"></span>
        <?php esc_html_e('Tambah Staff', 'wp-customer'); ?>
    </button>
</div>
<?php endif; ?>

<table id="customer-employees-datatable"
       class="wpapp-datatable customer-lazy-datatable"
       style="width:100%"
       data-entity="employee"
       data-customer-id="<?php echo esc_attr($customer_id); ?>"
       data-ajax-action="get_customer_employees_datatable">
    <thead>
        <tr>
            <th><?php esc_html_e('Nama', 'wp-customer'); ?></th>
            <th><?php esc_html_e('Jabatan', 'wp-customer'); ?></th>
            <th><?php esc_html_e('Email', 'wp-customer'); ?></th>
            <th><?php esc_html_e('Telepon', 'wp-customer'); ?></th>
            <th><?php esc_html_e('Status', 'wp-customer'); ?></th>
            <th><?php esc_html_e('Actions', 'wp-customer'); ?></th>
        </tr>
    </thead>
    <tbody>
        <!-- DataTable will populate via AJAX -->
    </tbody>
</table>

</div><!-- .customer-tab-content-wrapper -->

<?php
// TODO-2191: Employee forms loaded via AJAX using wpAppModal centralized system
// Forms not included here - loaded dynamically when modal opens
?>
