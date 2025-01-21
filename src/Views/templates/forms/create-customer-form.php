<?php
/**
 * Create Customer Form Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: /wp-customer/src/Views/templates/forms/create-customer-form.php
 * 
 * Description: Template form untuk menambah customer baru.
 *              Menggunakan modal dialog untuk tampilan form.
 *              Includes validasi client-side dan permission check.
 *              Terintegrasi dengan AJAX submission dan toast notifications.
 * 
 * Changelog:
 * 1.0.0 - 2024-12-02 18:30:00
 * - Initial release
 * - Added permission check
 * - Added nonce security
 * - Added form validation
 * - Added AJAX integration
 * 
 * Dependencies:
 * - WordPress admin styles
 * - customer-toast.js for notifications
 * - customer-form.css for styling
 * - customer-form.js for handling
 */

defined('ABSPATH') || exit;

// Tambahkan ini sementara di awal render form untuk debug
error_log('Debug wilayah hooks:');
error_log('Province select hook exists: ' . (has_action('wilayah_indonesia_province_select') ? 'yes' : 'no'));
error_log('Regency select hook exists: ' . (has_action('wilayah_indonesia_regency_select') ? 'yes' : 'no'));

?>

<div id="create-customer-modal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Tambah Customer</h3>
            <button type="button" class="modal-close" aria-label="Close">&times;</button>
        </div>


        <div class="modal-content">
            <form id="create-customer-form" method="post">
                <?php wp_nonce_field('wp_customer_nonce'); ?>
                <input type="hidden" name="action" value="create_customer">
                
                <div class="wp-customer-form-group">
                    <label for="customer-name" class="required-field">
                        <?php _e('Nama Customer', 'wp-customer'); ?>
                    </label>
                    <input type="text" 
                           id="customer-name" 
                           name="name" 
                           class="regular-text" 
                           maxlength="100" 
                           required>
                </div>

                <div class="wp-customer-form-group">
                    <label for="customer-provinsi" class="required-field">
                        <?php _e('Provinsi', 'wp-customer'); ?>
                    </label>
                    <div class="input-group">
                        <?php 
                        do_action('wilayah_indonesia_province_select', [
                            'name' => 'provinsi_id',
                            'id' => 'customer-provinsi',
                            'class' => 'regular-text wilayah-province-select',
                            'data-placeholder' => __('Pilih Provinsi', 'wp-customer'),
                            'required' => 'required',
                            'aria-label' => __('Pilih Provinsi', 'wp-customer')
                        ]);
                        ?>
                    </div>
                </div>

                <div class="wp-customer-form-group">
                    <label for="customer-regency" class="required-field">
                        <?php _e('Kabupaten/Kota', 'wp-customer'); ?>
                    </label>
                    <div class="input-group">
                        <?php 
                        do_action('wilayah_indonesia_regency_select', [
                            'name' => 'regency_id',
                            'id' => 'customer-regency',
                            'class' => 'regular-text wilayah-regency-select',
                            'data-loading-text' => __('Memuat...', 'wp-customer'),
                            'required' => 'required',
                            'aria-label' => __('Pilih Kabupaten/Kota', 'wp-customer'),
                            'data-dependent' => 'customer-provinsi'
                        ]);
                        ?>
                    </div>
                </div>

                <?php if (current_user_can('edit_all_customers')): ?>
                <div class="wp-customer-form-group">
                    <label for="customer-owner">
                        <?php _e('Admin', 'wp-customer'); ?>
                    </label>
                    <select id="customer-owner" name="user_id" class="regular-text">
                        <option value=""><?php _e('Pilih Admin', 'wp-customer'); ?></option>
                        <?php
                        //$users = get_users(['role__in' => ['administrator', 'editor', 'author']]);
                        $users = get_users(['role__in' => ['Customer']]);
                        foreach ($users as $user) {
                            printf(
                                '<option value="%d">%s</option>',
                                $user->ID,
                                esc_html($user->display_name)
                            );
                        }
                        ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="submit-wrapper">
                    <button type="submit" class="button button-primary">
                        <?php _e('Simpan', 'wp-customer'); ?>
                    </button>
                    <button type="button" class="button cancel-create">
                        <?php _e('Batal', 'wp-customer'); ?>
                    </button>
                    <span class="spinner"></span>
                </div>
            </form>
        </div>
    </div>
</div>
