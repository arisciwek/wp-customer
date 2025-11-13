<?php
/**
 * Customer Edit Form - Modal Template
 *
 * @package     WPCustomer
 * @subpackage  Views/Customer/Forms
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/customer/forms/edit-customer-form.php
 *
 * Description: Form template for editing existing customer via modal.
 *              Loaded via AJAX when Edit Customer button clicked.
 *              Pre-fills form with existing customer data.
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-2188)
 * - Initial creation
 * - Integrated with wpAppModal system
 * - Pre-fill customer data
 *
 * @var object $customer Customer data object
 */

defined('ABSPATH') || exit;

// Ensure $customer object exists
if (!isset($customer) || !is_object($customer)) {
    echo '<p class="error">' . __('Customer data not found', 'wp-customer') . '</p>';
    return;
}
?>

<form id="customer-form" class="wpapp-modal-form">
    <input type="hidden" name="action" value="save_customer">
    <input type="hidden" name="mode" value="edit">
    <input type="hidden" name="customer_id" value="<?php echo esc_attr($customer->id); ?>">
    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wpapp_panel_nonce'); ?>">

    <div class="wpapp-form-field">
        <label for="customer-name">
            <?php _e('Customer Name', 'wp-customer'); ?>
            <span class="required">*</span>
        </label>
        <input type="text"
               id="customer-name"
               name="customer_name"
               value="<?php echo esc_attr($customer->name); ?>"
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
               value="<?php echo esc_attr($customer->npwp ?? ''); ?>"
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
               value="<?php echo esc_attr($customer->nib ?? ''); ?>"
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
            <option value="active" <?php selected($customer->status, 'active'); ?>>
                <?php _e('Active', 'wp-customer'); ?>
            </option>
            <option value="inactive" <?php selected($customer->status, 'inactive'); ?>>
                <?php _e('Inactive', 'wp-customer'); ?>
            </option>
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
                $selected = ($customer->province_id == $province->id) ? 'selected' : '';
                echo '<option value="' . esc_attr($province->id) . '" ' . $selected . '>' . esc_html($province->name) . '</option>';
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
        <select id="customer-regency" name="customer_regency_id" class="wilayah-select" <?php echo empty($customer->province_id) ? 'disabled' : ''; ?> required>
            <option value=""><?php _e('Select province first', 'wp-customer'); ?></option>
            <?php
            // If customer has regency_id, load regencies for that province
            if (!empty($customer->province_id)) {
                $regencies = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, name FROM {$wpdb->prefix}wi_regencies WHERE province_id = %d ORDER BY name",
                    $customer->province_id
                ));
                foreach ($regencies as $regency) {
                    $selected = ($customer->regency_id == $regency->id) ? 'selected' : '';
                    echo '<option value="' . esc_attr($regency->id) . '" ' . $selected . '>' . esc_html($regency->name) . '</option>';
                }
            }
            ?>
        </select>
        <span class="description">
            <?php _e('City/Regency for branch office location', 'wp-customer'); ?>
        </span>
    </div>

    <div class="wpapp-form-field">
        <label><?php _e('Customer Code', 'wp-customer'); ?></label>
        <input type="text"
               value="<?php echo esc_attr($customer->code); ?>"
               disabled
               class="regular-text">
        <span class="description">
            <?php _e('Auto-generated customer code (read-only)', 'wp-customer'); ?>
        </span>
    </div>
</form>
