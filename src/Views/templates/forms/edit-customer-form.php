<?php
/**
 * Edit Customer Form Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Forms
 * @version     1.0.1
 * @author      arisciwek
 * 
 * Path: /wp-customer/src/Views/templates/forms/edit-customer-form.php
 * 
 * Description: Modal form template untuk edit customer.
 *              Includes validation, security checks,
 *              dan AJAX submission handling.
 *              Terintegrasi dengan CustomerForm component.
 * 
 * Changelog:
 * 1.0.1 - 2024-12-05
 * - Restructured to match create-customer-form.php layout
 * - Added additional fields from CustomersDB schema
 * - Improved form sections and organization
 * - Enhanced validation markup
 */

defined('ABSPATH') || exit;

// Tambahkan ini sementara di awal render form untuk debug
error_log('Debug wilayah hooks:');
error_log('Province select hook exists: ' . (has_action('wilayah_indonesia_province_select') ? 'yes' : 'no'));
error_log('Regency select hook exists: ' . (has_action('wilayah_indonesia_regency_select') ? 'yes' : 'no'));

?>

<div id="edit-customer-modal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <form id="edit-customer-form" method="post">
            <div class="modal-header">
                <h3>Edit Customer</h3>
                <button type="button" class="modal-close" aria-label="Close">&times;</button>
            </div>
            
            <div class="modal-content">
                <?php wp_nonce_field('wp_customer_nonce'); ?>
                <input type="hidden" id="customer-id" name="id" value="">
                <input type="hidden" name="action" value="update_customer">
                
                <div class="row left-side">
                    <div class="customer-form-section">
                        <h4><?php _e('Informasi Dasar', 'wp-customer'); ?></h4>
                        
                        <div class="wp-customer-form-group">
                            <label for="edit-name" class="required-field">
                                <?php _e('Nama Customer', 'wp-customer'); ?>
                            </label>
                            <input type="text" 
                                   id="edit-name" 
                                   name="name" 
                                   class="regular-text"
                                   maxlength="100" 
                                   required>
                        </div>
                        <div class="wp-customer-form-group">
                            <label for="edit-npwp">
                                <?php _e('NPWP', 'wp-customer'); ?>
                            </label>
                            <input type="text" 
                                   id="edit-npwp" 
                                   name="npwp" 
                                   class="regular-text"
                                   placeholder="00.000.000.0-000.000"
                                   autocomplete="off">
                            <span class="field-description">
                                <?php _e('Format: 00.000.000.0-000.000', 'wp-customer'); ?>
                            </span>
                        </div>
                        <div class="wp-customer-form-group">
                            <label for="edit-nib">
                                <?php _e('NIB', 'wp-customer'); ?>
                            </label>
                            <input type="text" 
                                   id="edit-nib" 
                                   name="nib" 
                                   class="regular-text" 
                                   maxlength="20">
                        </div>

                        <div class="wp-customer-form-group">
                            <label for="edit-status" class="required-field">
                                <?php _e('Status', 'wp-customer'); ?>
                            </label>
                            <select id="edit-status" name="status" required>
                                <option value="active" <?php selected($customer->status ?? 'active', 'active'); ?>>
                                    <?php _e('Aktif', 'wp-customer'); ?>
                                </option>
                                <option value="inactive" <?php selected($customer->status ?? 'active', 'inactive'); ?>>
                                    <?php _e('Tidak Aktif', 'wp-customer'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row right-side">
                    <div class="customer-form-section">
                        <h4><?php _e('Lokasi', 'wp-customer'); ?></h4>

                        <div class="wp-customer-form-group">
                            <label for="edit-provinsi" class="required-field">
                                <?php _e('Provinsi', 'wp-customer'); ?>
                            </label>
                            <div class="input-group">
                                <?php 
                                do_action('wilayah_indonesia_province_select', [
                                    'name' => 'provinsi_id',
                                    'id' => 'edit-provinsi',
                                    'class' => 'regular-text wilayah-province-select',
                                    'data-placeholder' => __('Pilih Provinsi', 'wp-customer'),
                                    'required' => 'required',
                                    'aria-label' => __('Pilih Provinsi', 'wp-customer')
                                ]);
                                ?>
                            </div>
                        </div>

                        <div class="wp-customer-form-group">
                            <label for="edit-regency" class="required-field">
                                <?php _e('Kabupaten/Kota', 'wp-customer'); ?>
                            </label>
                            <div class="input-group">
                                <?php 
                                do_action('wilayah_indonesia_regency_select', [
                                    'name' => 'regency_id',
                                    'id' => 'edit-regency',
                                    'class' => 'regular-text wilayah-regency-select',
                                    'data-loading-text' => __('Memuat...', 'wp-customer'),
                                    'required' => 'required',
                                    'aria-label' => __('Pilih Kabupaten/Kota', 'wp-customer'),
                                    'data-dependent' => 'edit-provinsi'
                                ]);
                                ?>
                            </div>
                        </div>

                        <?php if (current_user_can('edit_all_customers')): ?>
                        <div class="wp-customer-form-group">
                            <label for="edit-user">
                                <?php _e('Admin', 'wp-customer'); ?>
                            </label>
                            <select id="edit-user" name="user_id" class="regular-text">
                                <option value=""><?php _e('Pilih Admin', 'wp-customer'); ?></option>
                                <?php
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
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="submit" class="button button-primary">
                    <?php _e('Update', 'wp-customer'); ?>
                </button>
                <button type="button" class="button cancel-edit">
                    <?php _e('Batal', 'wp-customer'); ?>
                </button>
                <span class="spinner"></span>
            </div>
        </form>
    </div>
</div>
