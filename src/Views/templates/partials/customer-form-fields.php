<?php
/**
 * Customer Form Fields - Shared Component
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Partials
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/partials/customer-form-fields.php
 *
 * Description: Shared form component untuk customer registration.
 *              Digunakan oleh self-register dan admin-create forms.
 *              Memastikan field structure dan validation konsisten.
 *              Single source of truth untuk customer form fields.
 *
 * Parameters:
 * @param string $mode              Form mode: 'self-register' or 'admin-create'
 * @param string $layout            Layout: 'single-column' or 'two-column'
 * @param array  $field_classes     CSS classes untuk fields (optional)
 * @param array  $wrapper_classes   CSS classes untuk wrapper (optional)
 *
 * Usage:
 * include locate_template('partials/customer-form-fields.php', false, false, [
 *     'mode' => 'self-register',
 *     'layout' => 'single-column'
 * ]);
 *
 * Changelog:
 * 1.1.0 - 2025-01-21
 * - Added username field to admin-create mode
 * - Now both modes have username field (consistent)
 * - Admin can input friendly username instead of auto-generated
 *
 * 1.0.0 - 2025-01-21
 * - Initial version
 * - Shared component untuk register.php dan create-customer-form.php
 * - Conditional rendering berdasarkan mode
 * - Auto-format NPWP/NIB dengan JavaScript
 */

defined('ABSPATH') || exit;

// Default parameters
$mode = $args['mode'] ?? 'self-register';
$layout = $args['layout'] ?? 'single-column';
$field_classes = $args['field_classes'] ?? 'regular-text';
$wrapper_classes = $args['wrapper_classes'] ?? 'form-group';

$is_self_register = ($mode === 'self-register');
$is_admin_create = ($mode === 'admin-create');
?>

<?php if ($is_self_register): ?>
<!-- Informasi Login (Self-Register Only) -->
<div class="wp-customer-card">
    <div class="wp-customer-card-header">
        <h3><?php _e('Informasi Login', 'wp-customer'); ?></h3>
    </div>
    <div class="wp-customer-card-body">
        <!-- Username -->
        <div class="<?php echo esc_attr($wrapper_classes); ?>">
            <label for="username">
                <?php _e('Username', 'wp-customer'); ?>
                <span class="required">*</span>
            </label>
            <input type="text"
                   id="username"
                   name="username"
                   class="<?php echo esc_attr($field_classes); ?>"
                   required>
            <p class="description"><?php _e('Username untuk login', 'wp-customer'); ?></p>
        </div>

        <!-- Email -->
        <div class="<?php echo esc_attr($wrapper_classes); ?>">
            <label for="email">
                <?php _e('Email', 'wp-customer'); ?>
                <span class="required">*</span>
            </label>
            <input type="email"
                   id="email"
                   name="email"
                   class="<?php echo esc_attr($field_classes); ?>"
                   required>
        </div>

        <!-- Password -->
        <div class="<?php echo esc_attr($wrapper_classes); ?>">
            <label for="password">
                <?php _e('Password', 'wp-customer'); ?>
                <span class="required">*</span>
            </label>
            <input type="password"
                   id="password"
                   name="password"
                   class="<?php echo esc_attr($field_classes); ?>"
                   required>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Informasi Perusahaan/Dasar -->
<div class="wp-customer-card">
    <div class="wp-customer-card-header">
        <h3><?php echo $is_self_register ? __('Informasi Perusahaan', 'wp-customer') : __('Informasi Dasar', 'wp-customer'); ?></h3>
    </div>
    <div class="wp-customer-card-body">

        <?php if ($is_admin_create): ?>
        <!-- Nama Admin (Admin Create Only) -->
        <div class="<?php echo esc_attr($wrapper_classes); ?>">
            <label for="customer-username" class="required-field">
                <?php _e('Nama Admin', 'wp-customer'); ?>
            </label>
            <input type="text"
                   id="customer-username"
                   name="username"
                   class="<?php echo esc_attr($field_classes); ?>"
                   maxlength="60"
                   pattern="[a-zA-Z0-9\s]+"
                   required>
            <span class="field-description">
                <?php _e('Nama untuk login (huruf, angka, spasi)', 'wp-customer'); ?>
            </span>
        </div>

        <!-- Email Admin (Admin Create Only) -->
        <div class="<?php echo esc_attr($wrapper_classes); ?>">
            <label for="customer-email" class="required-field">
                <?php _e('Email Admin', 'wp-customer'); ?>
            </label>
            <input type="email"
                   id="customer-email"
                   name="email"
                   class="<?php echo esc_attr($field_classes); ?>"
                   required>
            <span class="field-description">
                <?php _e('Email untuk login admin customer', 'wp-customer'); ?>
            </span>
        </div>
        <?php endif; ?>

        <!-- Nama Perusahaan/Customer -->
        <div class="<?php echo esc_attr($wrapper_classes); ?>">
            <label for="<?php echo $is_admin_create ? 'customer-name' : 'name'; ?>">
                <?php _e('Nama Lengkap/Perusahaan', 'wp-customer'); ?>
                <span class="required">*</span>
            </label>
            <input type="text"
                   id="<?php echo $is_admin_create ? 'customer-name' : 'name'; ?>"
                   name="name"
                   class="<?php echo esc_attr($field_classes); ?>"
                   maxlength="100"
                   required>
            <?php if ($is_self_register): ?>
            <p class="description"><?php _e('Nama ini akan digunakan sebagai identitas customer', 'wp-customer'); ?></p>
            <?php endif; ?>
        </div>

        <!-- NIB -->
        <div class="<?php echo esc_attr($wrapper_classes); ?>">
            <label for="<?php echo $is_admin_create ? 'customer-nib' : 'nib'; ?>">
                <?php _e('Nomor Induk Berusaha (NIB)', 'wp-customer'); ?>
                <span class="required">*</span>
            </label>
            <input type="text"
                   id="<?php echo $is_admin_create ? 'customer-nib' : 'nib'; ?>"
                   name="nib"
                   class="<?php echo esc_attr($field_classes); ?> nib-input"
                   maxlength="13"
                   pattern="\d{13}"
                   data-auto-format="nib"
                   required>
            <p class="description"><?php _e('13 digit angka', 'wp-customer'); ?></p>
        </div>

        <!-- NPWP -->
        <div class="<?php echo esc_attr($wrapper_classes); ?>">
            <label for="<?php echo $is_admin_create ? 'customer-npwp' : 'npwp'; ?>">
                <?php _e('NPWP', 'wp-customer'); ?>
                <span class="required">*</span>
            </label>
            <input type="text"
                   id="<?php echo $is_admin_create ? 'customer-npwp' : 'npwp'; ?>"
                   name="npwp"
                   class="<?php echo esc_attr($field_classes); ?> npwp-input"
                   placeholder="00.000.000.0-000.000"
                   maxlength="20"
                   pattern="\d{2}\.\d{3}\.\d{3}\.\d{1}\-\d{3}\.\d{3}"
                   data-auto-format="npwp"
                   required>
            <p class="description"><?php _e('Format: XX.XXX.XXX.X-XXX.XXX', 'wp-customer'); ?></p>
        </div>

        <?php if ($is_admin_create): ?>
        <!-- Status (Admin Create Only) -->
        <div class="<?php echo esc_attr($wrapper_classes); ?>">
            <label for="customer-status" class="required-field">
                <?php _e('Status', 'wp-customer'); ?>
            </label>
            <select id="customer-status" name="status" required>
                <option value="active"><?php _e('Aktif', 'wp-customer'); ?></option>
                <option value="inactive"><?php _e('Tidak Aktif', 'wp-customer'); ?></option>
            </select>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Lokasi Kantor Pusat -->
<div class="wp-customer-card">
    <div class="wp-customer-card-header">
        <h3><?php _e('Lokasi Kantor Pusat', 'wp-customer'); ?></h3>
    </div>
    <div class="wp-customer-card-body">

        <!-- Provinsi -->
        <div class="<?php echo esc_attr($wrapper_classes); ?>">
            <label for="<?php echo $is_admin_create ? 'customer-provinsi' : 'provinsi_id'; ?>">
                <?php _e('Provinsi', 'wp-customer'); ?>
                <span class="required">*</span>
            </label>
            <?php
            do_action('wilayah_indonesia_province_select', [
                'name' => 'provinsi_id',
                'id' => $is_admin_create ? 'customer-provinsi' : 'provinsi_id',
                'class' => $field_classes . ' wilayah-province-select',
                'required' => 'required',
                'data-placeholder' => __('Pilih Provinsi', 'wp-customer')
            ]);
            ?>
            <?php if ($is_self_register): ?>
            <p class="description"><?php _e('Provinsi tempat kantor pusat berada', 'wp-customer'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Kabupaten/Kota -->
        <div class="<?php echo esc_attr($wrapper_classes); ?>">
            <label for="<?php echo $is_admin_create ? 'customer-regency' : 'regency_id'; ?>">
                <?php _e('Kabupaten/Kota', 'wp-customer'); ?>
                <span class="required">*</span>
            </label>
            <?php
            // Use wilayah plugin for both modes (unified approach)
            do_action('wilayah_indonesia_regency_select', [
                'name' => 'regency_id',
                'id' => $is_admin_create ? 'customer-regency' : 'regency_id',
                'class' => $field_classes . ' wilayah-regency-select',
                'required' => 'required',
                'data-loading-text' => __('Memuat...', 'wp-customer'),
                'data-dependent' => $is_admin_create ? 'customer-provinsi' : 'provinsi_id'
            ]);
            ?>
            <?php if ($is_self_register): ?>
            <p class="description"><?php _e('Kabupaten/Kota tempat kantor pusat berada', 'wp-customer'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
