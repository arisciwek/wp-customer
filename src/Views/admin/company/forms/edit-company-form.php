<?php
/**
 * Company Edit Form - Modal Template
 *
 * @package     WPCustomer
 * @subpackage  Views/Company/Forms
 * @version     1.0.1
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/admin/company/forms/edit-company-form.php
 *
 * Description: Form template for editing company (branch) via modal.
 *              Loaded via AJAX when Edit Company button clicked.
 *              Pre-fills form with existing company data.
 *              Uses 2-column layout with external CSS.
 *
 * Changelog:
 * 1.0.1 - 2025-12-25
 * - Changed to 2-column layout
 * - Removed inline styles
 * - Uses external CSS (company-forms.css)
 *
 * 1.0.0 - 2025-12-25
 * - Initial creation
 * - Integrated with wpdt modal system
 * - Pre-fill company data
 *
 * @var object $company Company (branch) data object
 */

defined('ABSPATH') || exit;

// Ensure $company object exists
if (!isset($company) || !is_object($company)) {
    echo '<p class="error">' . __('Company data not found', 'wp-customer') . '</p>';
    return;
}
?>

<form id="company-form" class="wpapp-modal-form">
    <input type="hidden" name="action" value="save_company">
    <input type="hidden" name="id" value="<?php echo esc_attr($company->id); ?>">
    <input type="hidden" name="customer_id" value="<?php echo esc_attr($company->customer_id); ?>">
    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wpdt_nonce'); ?>">

    <!-- Two Column Layout -->
    <div class="wpapp-form-grid">
        <!-- Left Column -->
        <div class="wpapp-form-column">
            <div class="wpapp-form-field">
                <label for="company-code">
                    <?php _e('Company Code', 'wp-customer'); ?>
                </label>
                <input type="text"
                       id="company-code"
                       value="<?php echo esc_attr($company->code); ?>"
                       disabled>
                <span class="description">
                    <?php _e('Auto-generated (read-only)', 'wp-customer'); ?>
                </span>
            </div>

            <div class="wpapp-form-field">
                <label for="company-name">
                    <?php _e('Company Name', 'wp-customer'); ?>
                    <span class="required">*</span>
                </label>
                <input type="text"
                       id="company-name"
                       name="name"
                       value="<?php echo esc_attr($company->name); ?>"
                       required
                       placeholder="<?php esc_attr_e('Enter company name', 'wp-customer'); ?>">
                <span class="description">
                    <?php _e('Branch name', 'wp-customer'); ?>
                </span>
            </div>

            <div class="wpapp-form-field">
                <label for="company-type">
                    <?php _e('Type', 'wp-customer'); ?>
                    <span class="required">*</span>
                </label>
                <select id="company-type" name="type" required>
                    <option value="pusat" <?php selected($company->type, 'pusat'); ?>>
                        <?php _e('Pusat', 'wp-customer'); ?>
                    </option>
                    <option value="cabang" <?php selected($company->type, 'cabang'); ?>>
                        <?php _e('Cabang', 'wp-customer'); ?>
                    </option>
                </select>
                <span class="description">
                    <?php _e('Branch type', 'wp-customer'); ?>
                </span>
            </div>

            <div class="wpapp-form-field">
                <label for="company-email">
                    <?php _e('Email', 'wp-customer'); ?>
                    <span class="required">*</span>
                </label>
                <input type="email"
                       id="company-email"
                       name="email"
                       value="<?php echo esc_attr($company->email ?? ''); ?>"
                       required
                       placeholder="<?php esc_attr_e('email@example.com', 'wp-customer'); ?>">
            </div>

            <div class="wpapp-form-field">
                <label for="company-phone">
                    <?php _e('Phone', 'wp-customer'); ?>
                    <span class="required">*</span>
                </label>
                <input type="text"
                       id="company-phone"
                       name="phone"
                       maxlength="20"
                       value="<?php echo esc_attr($company->phone ?? ''); ?>"
                       required
                       placeholder="<?php esc_attr_e('Enter phone number', 'wp-customer'); ?>">
            </div>

            <div class="wpapp-form-field">
                <label for="company-nitku">
                    <?php _e('NITKU', 'wp-customer'); ?>
                </label>
                <input type="text"
                       id="company-nitku"
                       name="nitku"
                       maxlength="20"
                       value="<?php echo esc_attr($company->nitku ?? ''); ?>"
                       placeholder="<?php esc_attr_e('Enter NITKU', 'wp-customer'); ?>">
                <span class="description">
                    <?php _e('Nomor Identitas Tempat Kegiatan Usaha', 'wp-customer'); ?>
                </span>
            </div>

            <div class="wpapp-form-field">
                <label for="company-status">
                    <?php _e('Status', 'wp-customer'); ?>
                    <span class="required">*</span>
                </label>
                <select id="company-status" name="status" required>
                    <option value="active" <?php selected($company->status, 'active'); ?>>
                        <?php _e('Active', 'wp-customer'); ?>
                    </option>
                    <option value="inactive" <?php selected($company->status, 'inactive'); ?>>
                        <?php _e('Inactive', 'wp-customer'); ?>
                    </option>
                </select>
            </div>
        </div>

        <!-- Right Column -->
        <div class="wpapp-form-column">
            <div class="wpapp-form-field">
                <label for="company-province">
                    <?php _e('Province', 'wp-customer'); ?>
                    <span class="required">*</span>
                </label>
                <select id="company-province" name="province_id" class="wilayah-province-select" required>
                    <option value=""><?php _e('Select Province', 'wp-customer'); ?></option>
                    <?php
                    global $wpdb;
                    $provinces = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}wi_provinces ORDER BY name");
                    foreach ($provinces as $province) {
                        $selected = ($company->province_id == $province->id) ? 'selected' : '';
                        echo '<option value="' . esc_attr($province->id) . '" ' . $selected . '>' . esc_html($province->name) . '</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="wpapp-form-field">
                <label for="company-regency">
                    <?php _e('City/Regency', 'wp-customer'); ?>
                    <span class="required">*</span>
                </label>
                <select id="company-regency" name="regency_id" class="wilayah-regency-select" <?php echo empty($company->province_id) ? 'disabled' : ''; ?> required>
                    <option value=""><?php _e('Select province first', 'wp-customer'); ?></option>
                    <?php
                    if (!empty($company->province_id)) {
                        $regencies = $wpdb->get_results($wpdb->prepare(
                            "SELECT id, name FROM {$wpdb->prefix}wi_regencies WHERE province_id = %d ORDER BY name",
                            $company->province_id
                        ));
                        foreach ($regencies as $regency) {
                            $selected = ($company->regency_id == $regency->id) ? 'selected' : '';
                            echo '<option value="' . esc_attr($regency->id) . '" ' . $selected . '>' . esc_html($regency->name) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="wpapp-form-field">
                <label for="company-postal-code">
                    <?php _e('Postal Code', 'wp-customer'); ?>
                    <span class="required">*</span>
                </label>
                <input type="text"
                       id="company-postal-code"
                       name="postal_code"
                       maxlength="5"
                       value="<?php echo esc_attr($company->postal_code ?? ''); ?>"
                       required
                       placeholder="<?php esc_attr_e('5 digits', 'wp-customer'); ?>">
            </div>

            <div class="wpapp-form-field">
                <label for="company-address">
                    <?php _e('Address', 'wp-customer'); ?>
                </label>
                <textarea id="company-address"
                          name="address"
                          rows="3"
                          placeholder="<?php esc_attr_e('Enter full address', 'wp-customer'); ?>"><?php echo esc_textarea($company->address ?? ''); ?></textarea>
            </div>

            <div class="wpapp-form-field wpapp-form-field-full">
                <label><?php _e('Select Location on Map', 'wp-customer'); ?></label>
                <div class="branch-coordinates-map" style="height: 250px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;"></div>
                <span class="description">
                    <?php _e('Click on map or drag marker to set location', 'wp-customer'); ?>
                </span>
            </div>

            <div class="wpapp-form-field">
                <label for="company-latitude">
                    <?php _e('Latitude', 'wp-customer'); ?>
                    <span class="required">*</span>
                </label>
                <input type="number"
                       id="company-latitude"
                       name="latitude"
                       step="0.00000001"
                       min="-90"
                       max="90"
                       value="<?php echo esc_attr($company->latitude ?? ''); ?>"
                       required
                       placeholder="<?php esc_attr_e('-6.2088', 'wp-customer'); ?>">
            </div>

            <div class="wpapp-form-field">
                <label for="company-longitude">
                    <?php _e('Longitude', 'wp-customer'); ?>
                    <span class="required">*</span>
                </label>
                <input type="number"
                       id="company-longitude"
                       name="longitude"
                       step="0.00000001"
                       min="-180"
                       max="180"
                       value="<?php echo esc_attr($company->longitude ?? ''); ?>"
                       required
                       placeholder="<?php esc_attr_e('106.8456', 'wp-customer'); ?>">
            </div>
        </div>
    </div>
</form>
