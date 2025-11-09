<?php
/**
 * Company Staff Tab Content (Lazy-loaded)
 *
 * @package     WP_Customer
 * @subpackage  Views/Admin/Company/Tabs/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/admin/company/tabs/partials/staff-content.php
 *
 * Description: Actual content untuk Staff tab.
 *              Di-load via AJAX oleh handle_load_staff_tab()
 *              Menampilkan DataTable employee yang bekerja di company ini.
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2195)
 * - Initial implementation
 * - Uses EmployeeDataTableModel via AJAX
 */

defined('ABSPATH') || exit;
?>

<div class="wpdt-tab-content-wrapper">
    <div class="company-staff-datatable-wrapper">
        <table id="company-employees-datatable"
               class="wpdt-table display"
               data-company-id="<?php echo esc_attr($company->id ?? 0); ?>"
               style="width:100%">
            <thead>
                <tr>
                    <th><?php esc_html_e('Name', 'wp-customer'); ?></th>
                    <th><?php esc_html_e('Position', 'wp-customer'); ?></th>
                    <th><?php esc_html_e('Department', 'wp-customer'); ?></th>
                    <th><?php esc_html_e('Email', 'wp-customer'); ?></th>
                    <th><?php esc_html_e('Status', 'wp-customer'); ?></th>
                    <th><?php esc_html_e('Actions', 'wp-customer'); ?></th>
                </tr>
            </thead>
            <tbody>
                <!-- Data will be populated by DataTables AJAX -->
            </tbody>
        </table>
    </div>
</div>

<style>
.company-staff-datatable-wrapper {
    padding: 20px;
}

.company-staff-datatable-wrapper .wpdt-table {
    width: 100%;
}

.department-badge {
    display: inline-block;
    padding: 3px 8px;
    margin: 2px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 500;
}

.department-finance {
    background-color: #d1ecf1;
    color: #0c5460;
}

.department-operation {
    background-color: #d4edda;
    color: #155724;
}

.department-legal {
    background-color: #fff3cd;
    color: #856404;
}

.department-purchase {
    background-color: #f8d7da;
    color: #721c24;
}

.text-muted {
    color: #6c757d;
}
</style>
