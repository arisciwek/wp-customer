<?PHP
/**
 * Edit Branch Form Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Branch/Forms
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/branch/forms/edit-branch-form.php
 *
 * Description: Form modal untuk mengedit data cabang.
 *              Includes input validation, error handling,
 *              dan AJAX submission handling.
 *              Terintegrasi dengan komponen toast notification.
 *
 * Changelog:
 * 1.0.0 - 2024-12-10
 * - Initial release
 * - Added form structure
 * - Added validation markup
 * - Added AJAX integration
 */
 defined('ABSPATH') || exit;
 ?>

<div id="edit-branch-modal" class="modal-overlay wp-customer-modal">
    <div class="modal-container">
        <div class="modal-header">
            <h3><?php _e('Edit Cabang', 'wp-customer'); ?></h3>
            <button type="button" class="modal-close" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <form id="edit-branch-form" method="post">
            <?php wp_nonce_field('wp_customer_nonce'); ?>
            <input type="hidden" name="id" id="branch-id">

            <div class="modal-content">
                <div class="wp-customer-form-group">
                    <label for="edit-branch-code" class="required-field">
                        <?php _e('Kode Cabang', 'wp-customer'); ?>
                    </label>
                    <input type="text"
                           id="edit-branch-code"
                           name="code"
                           class="small-text"
                           maxlength="4"
                           pattern="\d{4}"
                           required>
                    <p class="description">
                        <?php _e('Masukkan 4 digit angka', 'wp-customer'); ?>
                    </p>
                </div>
                              
                <div class="wp-customer-form-group">
                    <label for="edit-branch-name" class="required-field">
                        <?php _e('Nama Cabang', 'wp-customer'); ?>
                    </label>
                    <input type="text"
                           id="edit-branch-name"
                           name="name"
                           class="regular-text"
                           maxlength="100"
                           required>
                </div>

                <div class="wp-customer-form-group">
                    <label for="edit-branch-type" class="required-field">
                        <?php _e('Tipe', 'wp-customer'); ?>
                    </label>
                    <select id="edit-branch-type" name="type" required>
                        <option value=""><?php _e('Pilih Tipe', 'wp-customer'); ?></option>
                        <option value="kabupaten"><?php _e('Kabupaten', 'wp-customer'); ?></option>
                        <option value="kota"><?php _e('Kota', 'wp-customer'); ?></option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <div class="wp-customer-form-actions">
                    <button type="button" class="button cancel-edit">
                        <?php _e('Batal', 'wp-customer'); ?>
                    </button>
                    <button type="submit" class="button button-primary">
                        <?php _e('Perbarui', 'wp-customer'); ?>
                    </button>
                    <span class="spinner"></span>
                </div>
            </div>
        </form>
    </div>
</div>
