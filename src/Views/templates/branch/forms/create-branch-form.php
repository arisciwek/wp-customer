<?php
/**
 * Create Branch Form Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Branch/Forms
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/branch/forms/create-branch-form.php
 *
 * Description: Form modal untuk menambah cabang baru.
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

<div id="create-branch-modal" class="modal-overlay wp-customer-modal">
    <div class="modal-container">
        <div class="modal-header">
            <h3><?php _e('Tambah Cabang', 'wp-customer'); ?></h3>
            <button type="button" class="modal-close">&times;</button>
        </div>

        <form id="create-branch-form" method="post">
            <?php wp_nonce_field('wp_customer_nonce'); ?>
            <input type="hidden" name="customer_id" id="customer_id">

            <div class="modal-content">
                <div class="row left-side">
                    <div class="branch-form-section">
                        <h4><?php _e('Informasi Dasar', 'wp-customer'); ?></h4>
                        
                        <div class="branch-form-group">
                            <label for="create-branch-name" class="required-field">Nama Cabang</label>
                            <input type="text" id="create-branch-name" name="name" maxlength="100" required>
                            <span class="field-hint"><?php _e('Masukkan nama lengkap cabang', 'wp-customer'); ?></span>
                        </div>

                        <div class="branch-form-group">
                            <label for="create-branch-type" class="required-field">Tipe</label>
                            <select id="create-branch-type" name="type" required>
                                <option value="">Pilih Tipe</option>
                                <option value="cabang">Cabang</option>
                                <option value="pusat">Pusat</option>
                            </select>
                            <span class="field-hint"><?php _e('Pilih tipe cabang', 'wp-customer'); ?></span>
                        </div>

                        <div class="branch-form-group">
                            <label for="create-branch-nitku">NITKU</label>
                            <input type="text" id="create-branch-nitku" name="nitku" maxlength="20">
                            <span class="field-hint"><?php _e('Nomor Identitas Tempat Kegiatan Usaha', 'wp-customer'); ?></span>
                        </div>
                    </div>
                                        
                    <div class="branch-form-section">
                        <h4><?php _e('Admin Branch', 'wp-customer'); ?></h4>
                        
                        <div class="branch-form-group">
                            <label for="create-branch-admin-username" class="required-field">Username</label>
                            <input type="text" id="create-branch-admin-username" name="admin_username" required>
                            <span class="field-hint">Username untuk login admin branch</span>
                        </div>

                        <div class="branch-form-group">
                            <label for="create-branch-admin-email" class="required-field">Email</label>
                            <input type="email" id="create-branch-admin-email" name="admin_email" required>
                            <span class="field-hint">Email untuk login admin branch</span>
                        </div>

                        <div class="branch-form-group">
                            <label for="create-branch-admin-firstname" class="required-field">Nama Depan</label>
                            <input type="text" id="create-branch-admin-firstname" name="admin_firstname" required>
                        </div>

                        <div class="branch-form-group">
                            <label for="create-branch-admin-lastname">Nama Belakang</label>
                            <input type="text" id="create-branch-admin-lastname" name="admin_lastname">
                        </div>
                    </div>

                    <div class="branch-form-section">
                        <h4><?php _e('Kontak Admin', 'wp-customer'); ?></h4>
                        <div class="branch-form-group">
                            <label for="create-branch-phone">Telepon</label>
                            <input type="text" id="create-branch-phone" name="phone" maxlength="20">
                            <span class="field-hint"><?php _e('Format: +62xxx atau 08xxx', 'wp-customer'); ?></span>
                        </div>

                        <div class="branch-form-group">
                            <label for="create-branch-email">Email Cabang</label>
                            <input type="email" id="create-branch-email" name="email" maxlength="100">
                            <span class="field-hint"><?php _e('Email operasional cabang', 'wp-customer'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="row right-side">
                    <div class="branch-form-section">
                        <h4><?php _e('Alamat & Lokasi', 'wp-customer'); ?></h4>
                        
                        <div class="branch-form-group">
                            <label for="create-branch-address" class="required-field">Alamat</label>
                            <textarea id="create-branch-address" name="address" rows="3" required></textarea>
                            <span class="field-hint"><?php _e('Alamat lengkap cabang', 'wp-customer'); ?></span>
                        </div>
                        
                        <div class="branch-form-group">
                            <label for="create-branch-provinsi" class="required-field">Provinsi</label>
                            <?php 
                            do_action('wilayah_indonesia_province_select', [
                                'name' => 'provinsi_id',
                                'id' => 'create-branch-provinsi',
                                'class' => 'regular-text wilayah-province-select',
                                'required' => 'required'
                            ]);
                            ?>
                        </div>

                        <div class="branch-form-group">
                            <label for="create-branch-regency" class="required-field">Kabupaten/Kota</label>
                            <?php 
                            do_action('wilayah_indonesia_regency_select', [
                                'name' => 'regency_id',
                                'id' => 'create-branch-regency',
                                'class' => 'regular-text wilayah-regency-select',
                                'required' => 'required',
                                'data-dependent' => 'create-branch-provinsi'
                            ]);
                            ?>
                        </div>

                        <div class="branch-form-group">
                            <label for="create-branch-postal" class="required-field">Kode Pos</label>
                            <input type="text" id="create-branch-postal" name="postal_code" maxlength="5" required>
                            <span class="field-hint"><?php _e('5 digit kode pos', 'wp-customer'); ?></span>
                        </div>

                        <div class="branch-coordinates">
                            <h4><?php _e('Lokasi', 'wp-customer'); ?></h4>
                            
                            <!-- Tambahkan div untuk map di sini -->
                            <div class="branch-coordinates-map" style="height: 300px; margin-bottom: 15px;"></div>

                            <div class="branch-form-group">
                                <label for="create-branch-latitude" class="required-field">Latitude</label>
                                <input type="text" id="create-branch-latitude" name="latitude" required>
                                <span class="field-hint"><?php _e('Contoh: -6.123456', 'wp-customer'); ?></span>
                            </div>

                            <div class="branch-form-group">
                                <label for="create-branch-longitude" class="required-field">Longitude</label>
                                <input type="text" id="create-branch-longitude" name="longitude" required>
                                <span class="field-hint"><?php _e('Contoh: 106.123456', 'wp-customer'); ?></span>
                            </div>
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
