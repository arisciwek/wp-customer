<?php
/**
 * Create Employee Form Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Employee/Forms
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/employee/forms/create-employee-form.php
 *
 * Description: Form modal untuk menambah karyawan baru.
 *              Includes input validation, error handling,
 *              dan AJAX submission handling.
 *              Terintegrasi dengan komponen toast notification.
 *
 * Changelog:
 * 1.0.0 - 2024-01-12
 * - Initial release
 * - Added form structure
 * - Added validation markup
 * - Added AJAX integration
 */
defined('ABSPATH') || exit;
?>
<?php defined('ABSPATH') || exit; ?>

<div id="create-employee-modal" class="modal-overlay wp-customer-modal">
    <div class="modal-container">
        <div class="modal-header">
            <h3><?php _e('Tambah Karyawan', 'wp-customer'); ?></h3>
            <button type="button" class="modal-close">&times;</button>
        </div>

        <form id="create-employee-form" method="post">
            <?php wp_nonce_field('wp_customer_nonce'); ?>
            <input type="hidden" name="customer_id" id="employee-customer-id">

            <div class="modal-content">
                <div class="row left-side">
                    <!-- Data Pribadi -->
                    <div class="employee-form-section">
                        <h4><?php _e('Data Pribadi', 'wp-customer'); ?></h4>
                        
                        <div class="employee-form-group">
                            <label for="employee-name" class="required-field">Nama Lengkap</label>
                            <input type="text" id="employee-name" name="name" maxlength="100" required>
                            <span class="field-hint"><?php _e('Nama lengkap karyawan', 'wp-customer'); ?></span>
                        </div>

                        <div class="employee-form-group">
                            <label for="employee-email" class="required-field">Email</label>
                            <input type="email" id="employee-email" name="email" maxlength="100" required>
                            <span class="field-hint"><?php _e('Email akan digunakan untuk login', 'wp-customer'); ?></span>
                        </div>

                        <div class="employee-form-group">
                            <label for="employee-phone">Telepon</label>
                            <input type="tel" id="employee-phone" name="phone" maxlength="20">
                            <span class="field-hint"><?php _e('Format: +62xxx atau 08xxx', 'wp-customer'); ?></span>
                        </div>
                    </div>

                    <!-- Informasi Pekerjaan -->
                    <div class="employee-form-section">
                        <h4><?php _e('Informasi Pekerjaan', 'wp-customer'); ?></h4>
                        
                        <div class="employee-form-group">
                            <label for="employee-position" class="required-field">Jabatan</label>
                            <input type="text" id="employee-position" name="position" maxlength="100" required>
                            <span class="field-hint"><?php _e('Posisi/jabatan karyawan', 'wp-customer'); ?></span>
                        </div>

                        <div class="employee-form-group">
                            <label for="employee-keterangan" class="required-field">Keterangan</label>
                            <input type="text" id="employee-keterangan" name="keterangan" maxlength="100" required>
                            <span class="field-hint"><?php _e('Keterangan karyawan', 'wp-customer'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="row right-side">
                    <!-- Penempatan -->
                    <div class="employee-form-section">
                        <h4><?php _e('Penempatan', 'wp-customer'); ?></h4>
                        
                        <div class="employee-form-group">
                            <label for="employee-branch" class="required-field">Cabang</label>
                            <select id="employee-branch" name="branch_id" required>
                                <option value=""><?php _e('Pilih Cabang', 'wp-customer'); ?></option>
                            </select>
                            <span class="field-hint"><?php _e('Lokasi penempatan karyawan', 'wp-customer'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="button cancel-create"><?php _e('Batal', 'wp-customer'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Simpan', 'wp-customer'); ?></button>
                <span class="spinner"></span>
            </div>
        </form>
    </div>
</div>
