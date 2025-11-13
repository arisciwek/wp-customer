<?php
/**
 * Branch Edit Form - Modal Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Customer/Branch/Forms
 * @version     1.1.1
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/customer/branch/forms/edit-branch-form.php
 *
 * Description: Form template for editing existing branch via modal.
 *              Loaded via AJAX when Edit Branch button clicked.
 *              Integrated with wpAppModal system from wp-app-core.
 *
 * Changelog:
 * 1.1.1 - 2025-11-02 (TODO-2190 Fix Cascade Select)
 * - Fixed: Removed class override to use default wilayah-province-select
 * - Fixed: Removed class override to use default wilayah-regency-select
 * - Added: data-dependent attribute for cascade functionality
 * - Now cascade works: province change → auto-populate regency
 *
 * 1.1.0 - 2025-11-02 (TODO-2190 Use Wilayah Indonesia Hooks)
 * - Changed: Use wilayah_indonesia_province_select action hook
 * - Changed: Use wilayah_indonesia_regency_select action hook
 * - Removed: Hardcoded database queries for provinces/regencies
 * - Passes selected_id for pre-selection of existing values
 * - Benefits: Cache support, consistent with wilayah-indonesia plugin
 *
 * 1.0.0 - 2025-11-02 (TODO-2190)
 * - Initial creation
 * - Integrated with wpAppModal system
 * - Pre-filled with existing branch data
 */

defined('ABSPATH') || exit;

// $branch variable is passed from controller
?>

<form id="branch-form" class="customer-modal-form">
    <input type="hidden" name="action" value="save_branch">
    <input type="hidden" name="mode" value="edit">
    <input type="hidden" name="id" value="<?php echo esc_attr($branch->id); ?>">
    <input type="hidden" name="customer_id" value="<?php echo esc_attr($branch->customer_id); ?>">
    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp_customer_nonce'); ?>">

    <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; color: #23282d;">
        <?php _e('Informasi Dasar', 'wp-customer'); ?>
    </h4>

    <div class="customer-form-field">
        <label for="branch-name">
            <?php _e('Nama Cabang', 'wp-customer'); ?>
            <span class="required">*</span>
        </label>
        <input type="text"
               id="branch-name"
               name="name"
               value="<?php echo esc_attr($branch->name); ?>"
               maxlength="100"
               required
               placeholder="<?php esc_attr_e('Masukkan nama lengkap cabang', 'wp-customer'); ?>">
        <span class="description">
            <?php _e('Nama lengkap cabang atau kantor pusat', 'wp-customer'); ?>
        </span>
    </div>

    <div class="customer-form-field">
        <label for="branch-type">
            <?php _e('Tipe', 'wp-customer'); ?>
            <span class="required">*</span>
        </label>
        <select id="branch-type" name="type" required>
            <option value=""><?php _e('Pilih Tipe', 'wp-customer'); ?></option>
            <option value="cabang" <?php selected($branch->type, 'cabang'); ?>><?php _e('Cabang', 'wp-customer'); ?></option>
            <option value="pusat" <?php selected($branch->type, 'pusat'); ?>><?php _e('Pusat', 'wp-customer'); ?></option>
        </select>
        <span class="description">
            <?php _e('Pilih tipe cabang atau kantor pusat', 'wp-customer'); ?>
        </span>
    </div>

    <div class="customer-form-field">
        <label for="branch-nitku">
            <?php _e('NITKU', 'wp-customer'); ?>
        </label>
        <input type="text"
               id="branch-nitku"
               name="nitku"
               value="<?php echo esc_attr($branch->nitku ?? ''); ?>"
               maxlength="20"
               placeholder="<?php esc_attr_e('Nomor Identitas Tempat Kegiatan Usaha', 'wp-customer'); ?>">
        <span class="description">
            <?php _e('Nomor Identitas Tempat Kegiatan Usaha (optional)', 'wp-customer'); ?>
        </span>
    </div>

    <div class="customer-form-field">
        <label for="branch-status">
            <?php _e('Status', 'wp-customer'); ?>
            <span class="required">*</span>
        </label>
        <select id="branch-status" name="status" required>
            <option value="active" <?php selected($branch->status, 'active'); ?>><?php _e('Active', 'wp-customer'); ?></option>
            <option value="inactive" <?php selected($branch->status, 'inactive'); ?>><?php _e('Inactive', 'wp-customer'); ?></option>
        </select>
        <span class="description">
            <?php _e('Status cabang', 'wp-customer'); ?>
        </span>
    </div>

    <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">

    <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; color: #23282d;">
        <?php _e('Kontak', 'wp-customer'); ?>
    </h4>

    <div class="customer-form-field">
        <label for="branch-phone">
            <?php _e('Telepon', 'wp-customer'); ?>
        </label>
        <input type="text"
               id="branch-phone"
               name="phone"
               value="<?php echo esc_attr($branch->phone ?? ''); ?>"
               maxlength="15"
               pattern="^08[0-9]{8,13}$"
               placeholder="<?php esc_attr_e('08xxxxxxxxxx', 'wp-customer'); ?>">
        <span class="description">
            <?php _e('Format: 08xxxxxxxxxx (08 diikuti 8-13 digit angka)', 'wp-customer'); ?>
        </span>
    </div>

    <div class="customer-form-field">
        <label for="branch-email">
            <?php _e('Email', 'wp-customer'); ?>
        </label>
        <input type="email"
               id="branch-email"
               name="email"
               value="<?php echo esc_attr($branch->email ?? ''); ?>"
               maxlength="100"
               placeholder="<?php esc_attr_e('branch@company.com', 'wp-customer'); ?>">
        <span class="description">
            <?php _e('Email operasional cabang', 'wp-customer'); ?>
        </span>
    </div>

    <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">

    <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; color: #23282d;">
        <?php _e('Alamat & Lokasi', 'wp-customer'); ?>
    </h4>

    <div class="customer-form-field full-width">
        <label for="branch-address">
            <?php _e('Alamat', 'wp-customer'); ?>
            <span class="required">*</span>
        </label>
        <textarea id="branch-address"
                  name="address"
                  rows="3"
                  required
                  placeholder="<?php esc_attr_e('Alamat lengkap cabang', 'wp-customer'); ?>"><?php echo esc_textarea($branch->address ?? ''); ?></textarea>
        <span class="description">
            <?php _e('Alamat lengkap kantor/cabang', 'wp-customer'); ?>
        </span>
    </div>

    <div class="customer-form-field">
        <label for="branch-provinsi">
            <?php _e('Provinsi', 'wp-customer'); ?>
            <span class="required">*</span>
        </label>
        <?php
        // Gunakan hook dari wilayah-indonesia plugin dengan selected value
        // Jangan override class default agar JavaScript cascade berfungsi
        do_action('wilayah_indonesia_province_select', [
            'name' => 'province_id',
            'id' => 'branch-provinsi',
            'required' => true
        ], $branch->province_id ?? null);
        ?>
        <span class="description">
            <?php _e('Provinsi lokasi cabang', 'wp-customer'); ?>
        </span>
    </div>

    <div class="customer-form-field">
        <label for="branch-regency">
            <?php _e('Kota/Kabupaten', 'wp-customer'); ?>
            <span class="required">*</span>
        </label>
        <?php
        // Gunakan hook dari wilayah-indonesia plugin dengan selected value
        // data-dependent menghubungkan dengan province select untuk cascade
        do_action('wilayah_indonesia_regency_select', [
            'name' => 'regency_id',
            'id' => 'branch-regency',
            'data-dependent' => 'branch-provinsi',
            'required' => true
        ], $branch->province_id ?? null, $branch->regency_id ?? null);
        ?>
        <span class="description">
            <?php _e('Kota/Kabupaten lokasi cabang', 'wp-customer'); ?>
        </span>
    </div>

    <div class="customer-form-field">
        <label for="branch-postal-code">
            <?php _e('Kode Pos', 'wp-customer'); ?>
        </label>
        <input type="text"
               id="branch-postal-code"
               name="postal_code"
               value="<?php echo esc_attr($branch->postal_code ?? ''); ?>"
               maxlength="5"
               placeholder="<?php esc_attr_e('12345', 'wp-customer'); ?>">
        <span class="description">
            <?php _e('Kode pos (5 digit)', 'wp-customer'); ?>
        </span>
    </div>

    <div class="customer-form-field full-width">
        <label><?php _e('Pilih Lokasi di Peta', 'wp-customer'); ?></label>
        <div class="branch-coordinates-map" style="height: 300px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px;"></div>
        <span class="description">
            <?php _e('Klik pada peta atau drag marker untuk menentukan lokasi', 'wp-customer'); ?>
        </span>
    </div>

    <div class="customer-form-field">
        <label for="branch-latitude">
            <?php _e('Latitude', 'wp-customer'); ?>
        </label>
        <input type="text"
               id="branch-latitude"
               name="latitude"
               value="<?php echo esc_attr($branch->latitude ?? ''); ?>"
               step="any"
               placeholder="-6.2088">
        <span class="description">
            <?php _e('Koordinat latitude (optional)', 'wp-customer'); ?>
        </span>
    </div>

    <div class="customer-form-field">
        <label for="branch-longitude">
            <?php _e('Longitude', 'wp-customer'); ?>
        </label>
        <input type="text"
               id="branch-longitude"
               name="longitude"
               value="<?php echo esc_attr($branch->longitude ?? ''); ?>"
               step="any"
               placeholder="106.8456">
        <span class="description">
            <?php _e('Koordinat longitude (optional)', 'wp-customer'); ?>
        </span>
    </div>

    <div class="customer-form-field full-width">
        <a href="#"
           class="google-maps-link button button-secondary"
           target="_blank"
           style="display: none;">
            <span class="dashicons dashicons-location"></span>
            <?php _e('Lihat di Google Maps', 'wp-customer'); ?>
        </a>
    </div>
</form>
