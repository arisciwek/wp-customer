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

                <div class="row left-side">
                    <!-- Informasi Dasar -->
                    <div class="branch-form-section">
                        <h4><?php _e('Informasi Dasar', 'wp-customer'); ?></h4>
                        
                        <div class="branch-form-group">
                            <label for="edit-branch-name" class="required-field">
                                <?php _e('Nama Cabang', 'wp-customer'); ?>
                            </label>
                            <input type="text" id="edit-branch-name" name="name" maxlength="100" required>
                            <span class="field-hint"><?php _e('Masukkan nama lengkap cabang', 'wp-customer'); ?></span>
                        </div>

                        <div class="branch-form-group">
                            <label for="edit-branch-type" class="required-field">
                                <?php _e('Tipe', 'wp-customer'); ?>
                            </label>
                            <select id="edit-branch-type" name="type" required>
                                <option value=""><?php _e('Pilih Tipe', 'wp-customer'); ?></option>
                                <option value="cabang"><?php _e('Cabang', 'wp-customer'); ?></option>
                                <option value="pusat"><?php _e('Pusat', 'wp-customer'); ?></option>
                            </select>
                            <span class="field-hint"><?php _e('Pilih tipe cabang', 'wp-customer'); ?></span>
                        </div>
                    </div>

                    <!-- Kontak -->
                    <div class="branch-form-section">
                        <h4><?php _e('Kontak', 'wp-customer'); ?></h4>
                        
                        <div class="branch-form-group">
                            <label for="edit-branch-phone">Telepon</label>
                            <input type="text" id="edit-branch-phone" name="phone" maxlength="20">
                            <span class="field-hint"><?php _e('Format: +62xxx atau 08xxx', 'wp-customer'); ?></span>
                        </div>

                        <div class="branch-form-group">
                            <label for="edit-branch-email">Email</label>
                            <input type="email" id="edit-branch-email" name="email" maxlength="100">
                            <span class="field-hint"><?php _e('Email aktif cabang', 'wp-customer'); ?></span>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="branch-form-section">
                        <h4><?php _e('Status', 'wp-customer'); ?></h4>
                        
                        <div class="branch-form-group">
                            <label for="edit-branch-status">Status</label>
                            <select id="edit-branch-status" name="status">
                                <option value="active"><?php _e('Aktif', 'wp-customer'); ?></option>
                                <option value="inactive"><?php _e('Non-Aktif', 'wp-customer'); ?></option>
                            </select>
                            <span class="field-hint"><?php _e('Status aktif cabang', 'wp-customer'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="row right-side">
                    <!-- Lokasi & Identitas -->
                    <div class="branch-form-section">
                        <h4><?php _e('Lokasi & Identitas', 'wp-customer'); ?></h4>
                        
                        <div class="branch-form-group">
                            <label for="edit-branch-nitku">NITKU</label>
                            <input type="text" id="edit-branch-nitku" name="nitku" maxlength="20">
                            <span class="field-hint"><?php _e('Nomor Identitas Tempat Kegiatan Usaha', 'wp-customer'); ?></span>
                        </div>

                        <div class="branch-form-group">
                            <label for="edit-branch-postal">Kode Pos</label>
                            <input type="text" id="edit-branch-postal" name="postal_code" maxlength="5">
                            <span class="field-hint"><?php _e('5 digit kode pos', 'wp-customer'); ?></span>
                        </div>

                        <div class="branch-form-group">
                            <label for="edit-branch-address">Alamat</label>
                            <textarea id="edit-branch-address" name="address" rows="3"></textarea>
                            <span class="field-hint"><?php _e('Alamat lengkap cabang', 'wp-customer'); ?></span>
                        </div>

                        <div class="branch-coordinates">
                            <div class="branch-form-group">
                                <label for="edit-branch-latitude">Latitude</label>
                                <input type="text" id="edit-branch-latitude" name="latitude">
                                <span class="field-hint"><?php _e('Contoh: -6.123456', 'wp-customer'); ?></span>
                            </div>

                            <div class="branch-form-group">
                                <label for="edit-branch-longitude">Longitude</label>
                                <input type="text" id="edit-branch-longitude" name="longitude">
                                <span class="field-hint"><?php _e('Contoh: 106.123456', 'wp-customer'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="button cancel-edit">
                    <?php _e('Batal', 'wp-customer'); ?>
                </button>
                <button type="submit" class="button button-primary">
                    <?php _e('Perbarui', 'wp-customer'); ?>
                </button>
                <span class="spinner"></span>
            </div>
        </form>
    </div>
</div>
