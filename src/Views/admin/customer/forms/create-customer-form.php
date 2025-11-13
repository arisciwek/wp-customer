<?php
/**
 * Customer Create Form - Modal Template
 *
 * @package     WPCustomer
 * @subpackage  Views/Customer/Forms
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/customer/forms/create-customer-form.php
 *
 * Description: Form template for creating new customer via modal.
 *              Loaded via AJAX when Add Customer button clicked.
 *              Minimal fields for quick customer creation.
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-2188)
 * - Initial creation
 * - Integrated with wpAppModal system
 * - Basic customer fields (name, npwp, nib, status)
 */

defined('ABSPATH') || exit;
?>

<form id="customer-form" class="wpapp-modal-form">
    <input type="hidden" name="action" value="save_customer">
    <input type="hidden" name="mode" value="create">
    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wpapp_panel_nonce'); ?>">

    <div class="wpapp-form-field">
        <label for="customer-name">
            <?php _e('Customer Name', 'wp-customer'); ?>
            <span class="required">*</span>
        </label>
        <input type="text"
               id="customer-name"
               name="customer_name"
               required
               placeholder="<?php esc_attr_e('Enter customer name', 'wp-customer'); ?>">
        <span class="description">
            <?php _e('Full legal name of the customer', 'wp-customer'); ?>
        </span>
    </div>

    <div class="wpapp-form-field">
        <label for="customer-npwp">
            <?php _e('NPWP', 'wp-customer'); ?>
        </label>
        <input type="text"
               id="customer-npwp"
               name="customer_npwp"
               maxlength="20"
               placeholder="<?php esc_attr_e('Enter NPWP number', 'wp-customer'); ?>">
        <span class="description">
            <?php _e('Tax identification number - 15 digits, format: 12.345.678.9-012.000 (optional)', 'wp-customer'); ?>
        </span>
    </div>

    <div class="wpapp-form-field">
        <label for="customer-nib">
            <?php _e('NIB', 'wp-customer'); ?>
        </label>
        <input type="text"
               id="customer-nib"
               name="customer_nib"
               maxlength="13"
               placeholder="<?php esc_attr_e('Enter NIB number', 'wp-customer'); ?>">
        <span class="description">
            <?php _e('Nomor Induk Berusaha - 13 digits (optional)', 'wp-customer'); ?>
        </span>
    </div>

    <div class="wpapp-form-field">
        <label for="customer-status">
            <?php _e('Status', 'wp-customer'); ?>
            <span class="required">*</span>
        </label>
        <select id="customer-status" name="customer_status" required>
            <option value="active"><?php _e('Active', 'wp-customer'); ?></option>
            <option value="inactive"><?php _e('Inactive', 'wp-customer'); ?></option>
        </select>
        <span class="description">
            <?php _e('Customer account status', 'wp-customer'); ?>
        </span>
    </div>

    <div class="wpapp-form-field">
        <label for="customer-provinsi">
            <?php _e('Province', 'wp-customer'); ?>
            <span class="required">*</span>
        </label>
        <select id="customer-provinsi" name="customer_province_id" class="wilayah-select" required>
            <option value=""><?php _e('Select Province', 'wp-customer'); ?></option>
            <?php
            global $wpdb;
            $provinces = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}wi_provinces ORDER BY name");
            foreach ($provinces as $province) {
                echo '<option value="' . esc_attr($province->id) . '">' . esc_html($province->name) . '</option>';
            }
            ?>
        </select>
        <span class="description">
            <?php _e('Province for branch office location', 'wp-customer'); ?>
        </span>
    </div>

    <div class="wpapp-form-field">
        <label for="customer-regency">
            <?php _e('City/Regency', 'wp-customer'); ?>
            <span class="required">*</span>
        </label>
        <select id="customer-regency" name="customer_regency_id" class="wilayah-select" disabled required>
            <option value=""><?php _e('Select province first', 'wp-customer'); ?></option>
        </select>
        <span class="description">
            <?php _e('City/Regency for branch office location', 'wp-customer'); ?>
        </span>
    </div>

    <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">

    <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; color: #23282d;">
        <?php _e('Administrator Information', 'wp-customer'); ?>
    </h4>

    <div class="wpapp-form-field">
        <label for="admin-name">
            <?php _e('Admin Name', 'wp-customer'); ?>
            <span class="required">*</span>
        </label>
        <input type="text"
               id="admin-name"
               name="admin_name"
               required
               placeholder="<?php esc_attr_e('Enter administrator name', 'wp-customer'); ?>">
        <span class="description">
            <?php _e('Name of the administrator who will manage this customer', 'wp-customer'); ?>
        </span>
    </div>

    <div class="wpapp-form-field">
        <label for="admin-email">
            <?php _e('Admin Email', 'wp-customer'); ?>
            <span class="required">*</span>
        </label>
        <input type="email"
               id="admin-email"
               name="admin_email"
               required
               placeholder="<?php esc_attr_e('admin@company.com', 'wp-customer'); ?>">
        <span class="description">
            <?php _e('Email address for login and notifications', 'wp-customer'); ?>
        </span>
    </div>
</form>
