<?php
/**
 * Employee List Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Employee/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/employee/partials/_employee_list.php
 *
 * Description: Template untuk menampilkan daftar karyawan.
 *              Includes DataTable, loading states, empty states,
 *              dan action buttons dengan permission checks.
 *
 * Changelog:
 * 1.0.0 - 2024-01-12
 * - Initial release
 * - Added loading states
 * - Added empty state messages
 * - Added proper DataTable structure
 */

defined('ABSPATH') || exit;

/*
$active_tab = $_GET['tab'] ?? 'customer-details';

error_log('Active Tab: ' . ($active_tab ?? 'undefined'));

if ($active_tab !== 'employee-list') {
    ?>
    <div id="employee-list" class="tab-content">
        <div class="loading-placeholder">
            <span class="spinner is-active"></span>
            <p>Memuat data employee...</p>
        </div>
    </div>
    <?php
    return;
}
*/
?>

<div id="employee-list" class="tab-content">
    <div class="wp-customer-employee-header">
        <div class="employee-header-title">
            <h3><?php _e('Daftar Karyawan', 'wp-customer'); ?></h3>
        </div>
        <div class="employee-header-actions">
            <?php if (($access['access_type'] === 'admin' || 
                       $access['access_type'] === 'owner' || 
                       $access['access_type'] === 'branch_admin') && 
                      current_user_can('add_employee')) : ?>
                <button type="button" class="button button-primary" id="add-employee-btn">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Tambah Karyawan', 'wp-customer'); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="wp-customer-employee-content">
        <!-- Loading State -->
        <div class="employee-loading-state" style="display: none;">
            <span class="spinner is-active"></span>
            <p><?php _e('Memuat data...', 'wp-customer'); ?></p>
        </div>

        <!-- Empty State -->
        <div class="empty-state" style="display: none;">
            <div class="empty-state-content">
                <span class="dashicons dashicons-businessperson"></span>
                <h4><?php _e('Belum Ada Data', 'wp-customer'); ?></h4>
                <p>
                    <?php
                    if (current_user_can('add_employee')) {
                        _e('Belum ada karyawan yang ditambahkan. Klik tombol "Tambah Karyawan" untuk menambahkan data baru.', 'wp-customer');
                    } else {
                        _e('Belum ada karyawan yang ditambahkan.', 'wp-customer');
                    }
                    ?>
                </p>
            </div>
        </div>

        <!-- Data Table -->
        <div class="wi-table-container">
            <table id="employee-table" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th><?php _e('Nama', 'wp-customer'); ?></th>
                        <th><?php _e('Jabatan', 'wp-customer'); ?></th>
                        <th><?php _e('Departemen', 'wp-customer'); ?></th>
                        <th><?php _e('Email', 'wp-customer'); ?></th>
                        <th><?php _e('Cabang', 'wp-customer'); ?></th>
                        <th><?php _e('Status', 'wp-customer'); ?></th>
                        <th class="text-center no-sort">
                            <?php _e('Aksi', 'wp-customer'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <!-- DataTables will populate this -->
                </tbody>
                <tfoot>
                    <tr>
                        <th><?php _e('Nama', 'wp-customer'); ?></th>
                        <th><?php _e('Jabatan', 'wp-customer'); ?></th>
                        <th><?php _e('Departemen', 'wp-customer'); ?></th>
                        <th><?php _e('Email', 'wp-customer'); ?></th>
                        <th><?php _e('Cabang', 'wp-customer'); ?></th>
                        <th><?php _e('Status', 'wp-customer'); ?></th>
                        <th><?php _e('Aksi', 'wp-customer'); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Error State -->
        <div class="error-state" style="display: none;">
            <div class="error-state-content">
                <span class="dashicons dashicons-warning"></span>
                <h4><?php _e('Gagal Memuat Data', 'wp-customer'); ?></h4>
                <p><?php _e('Terjadi kesalahan saat memuat data. Silakan coba lagi.', 'wp-customer'); ?></p>
                <button type="button" class="button reload-table">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Muat Ulang', 'wp-customer'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Export Buttons (Optional, can be enabled via settings) -->
    <?php if (apply_filters('wp_customer_enable_export', false)): ?>
        <div class="export-actions">
            <button type="button" class="button export-excel">
                <span class="dashicons dashicons-media-spreadsheet"></span>
                <?php _e('Export Excel', 'wp-customer'); ?>
            </button>
            <button type="button" class="button export-pdf">
                <span class="dashicons dashicons-pdf"></span>
                <?php _e('Export PDF', 'wp-customer'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>

<?php
// Include related modals
require_once WP_CUSTOMER_PATH . 'src/Views/templates/employee/forms/create-employee-form.php';
require_once WP_CUSTOMER_PATH . 'src/Views/templates/employee/forms/edit-employee-form.php';
?>
