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
            <div id="edit-mode">
                <form id="edit-customer-form" class="wp-customer-form">
                    <?php wp_nonce_field('wp_customer_nonce'); ?>
                    <input type="hidden" id="customer-id" name="id" value="">
                    
                    <div class="wp-customer-form-group">
                        <label for="edit-code" class="required-field">Kode Customer</label>
                        <input type="text" 
                               id="edit-code" 
                               name="code" 
                               class="small-text" 
                               maxlength="2"
                               pattern="\d{2}"
                               required>
                        <p class="description">
                            <?php _e('Masukkan 2 digit angka', 'wp-customer'); ?>
                        </p>
                    </div>

                    <div class="wp-customer-form-group">
                        <label for="edit-name" class="required-field">Nama Customer</label>
                        <input type="text" 
                               id="edit-name" 
                               name="name" 
                               class="regular-text"
                               maxlength="100" 
                               required>
                    </div>

                    <?php if (current_user_can('edit_all_customers')): ?>
                    <div class="wp-customer-form-group">
                        <label for="edit-user" class="required-field">
                            <?php _e('User Admin', 'wp-customer'); ?>
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
                        <button type="submit" class="button button-primary">Update</button>
                        <button type="button" class="button cancel-edit">Batal</button>
                        <span class="spinner"></span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
