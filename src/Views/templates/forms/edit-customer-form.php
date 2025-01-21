<?php
/**
 * Edit Customer Form Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Forms
 * @version     1.0.0
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
 * 1.0.0 - 2024-12-05
 * - Initial implementation
 * - Added nonce security
 * - Added form validation
 * - Added permission checks
 * - Added AJAX integration
 */

defined('ABSPATH') || exit;
?>
<?php
/**
 * Edit Customer Form Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Forms
 * @version     1.0.0
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
 * 1.0.0 - 2024-12-05
 * - Initial implementation
 * - Added nonce security
 * - Added form validation
 * - Added permission checks
 * - Added AJAX integration
 */

defined('ABSPATH') || exit;
?>

<div id="edit-customer-modal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Edit Customer</h3>
            <button type="button" class="modal-close" aria-label="Close">&times;</button>
        </div>

        <div class="modal-content">
            <form id="edit-customer-form" method="post" class="wp-customer-form">
                <?php wp_nonce_field('wp_customer_nonce'); ?>
                <input type="hidden" id="customer-id" name="id" value="">
                <input type="hidden" name="action" value="update_customer">

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
                    <label for="edit-user" class="required-field">
                        <?php _e('Admin', 'wp-customer'); ?>
                    </label>
                    <select name="user_id" id="edit-user" class="regular-text">
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

                <div class="submit-wrapper">
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
</div>
