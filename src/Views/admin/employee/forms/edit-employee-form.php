<?php
/**
 * Employee Edit Form - Modal Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Customer/Employee/Forms
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/customer/employee/forms/edit-employee-form.php
 *
 * Description: Form template for editing existing employee via centralized modal.
 *              Loaded via AJAX when Edit Employee button clicked.
 *              Integrated with wpAppModal system from wp-app-core.
 *
 * Changelog:
 * 1.0.0 - 2025-11-02 (TODO-2191)
 * - Initial creation following centralized modal pattern
 * - Pre-filled with existing employee data
 * - Fields: name, position, email, phone, branch_id, departments, status, keterangan
 */

defined('ABSPATH') || exit;

// $employee variable is passed from controller
?>

<form id="employee-form" class="customer-modal-form">
    <input type="hidden" name="action" value="save_employee">
    <input type="hidden" name="mode" value="edit">
    <input type="hidden" name="id" value="<?php echo esc_attr($employee->id); ?>">
    <input type="hidden" name="customer_id" value="<?php echo esc_attr($employee->customer_id); ?>">
    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wpdt_nonce'); ?>">

    <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; color: #23282d;">
        <?php _e('Informasi Dasar', 'wp-customer'); ?>
    </h4>

    <div class="customer-form-field">
        <label for="employee-name">
            <?php _e('Nama Karyawan', 'wp-customer'); ?>
            <span class="required">*</span>
        </label>
        <input type="text"
               id="employee-name"
               name="name"
               value="<?php echo esc_attr($employee->name); ?>"
               maxlength="100"
               required
               placeholder="<?php esc_attr_e('Nama lengkap karyawan', 'wp-customer'); ?>">
        <span class="description">
            <?php _e('Nama lengkap karyawan', 'wp-customer'); ?>
        </span>
    </div>

    <div class="customer-form-field">
        <label for="employee-position">
            <?php _e('Jabatan', 'wp-customer'); ?>
            <span class="required">*</span>
        </label>
        <input type="text"
               id="employee-position"
               name="position"
               value="<?php echo esc_attr($employee->position); ?>"
               maxlength="100"
               required
               placeholder="<?php esc_attr_e('Jabatan karyawan', 'wp-customer'); ?>">
        <span class="description">
            <?php _e('Jabatan/posisi karyawan', 'wp-customer'); ?>
        </span>
    </div>

    <div class="customer-form-field">
        <label for="employee-status">
            <?php _e('Status', 'wp-customer'); ?>
            <span class="required">*</span>
        </label>
        <select id="employee-status" name="status" required>
            <option value="active" <?php selected($employee->status, 'active'); ?>><?php _e('Active', 'wp-customer'); ?></option>
            <option value="inactive" <?php selected($employee->status, 'inactive'); ?>><?php _e('Inactive', 'wp-customer'); ?></option>
        </select>
        <span class="description">
            <?php _e('Status karyawan', 'wp-customer'); ?>
        </span>
    </div>

    <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">

    <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; color: #23282d;">
        <?php _e('Kontak', 'wp-customer'); ?>
    </h4>

    <div class="customer-form-field">
        <label for="employee-email">
            <?php _e('Email', 'wp-customer'); ?>
            <span class="required">*</span>
        </label>
        <input type="email"
               id="employee-email"
               name="email"
               value="<?php echo esc_attr($employee->email); ?>"
               maxlength="100"
               required
               placeholder="<?php esc_attr_e('email@company.com', 'wp-customer'); ?>">
        <span class="description">
            <?php _e('Email karyawan untuk komunikasi', 'wp-customer'); ?>
        </span>
    </div>

    <div class="customer-form-field">
        <label for="employee-phone">
            <?php _e('Telepon', 'wp-customer'); ?>
        </label>
        <input type="text"
               id="employee-phone"
               name="phone"
               value="<?php echo esc_attr($employee->phone ?? ''); ?>"
               maxlength="20"
               pattern="^08[0-9]{8,11}$"
               placeholder="<?php esc_attr_e('08xxxxxxxxxx', 'wp-customer'); ?>">
        <span class="description">
            <?php _e('Format: 08xxxxxxxxxx (optional)', 'wp-customer'); ?>
        </span>
    </div>

    <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">

    <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; color: #23282d;">
        <?php _e('Penempatan', 'wp-customer'); ?>
    </h4>

    <div class="customer-form-field">
        <label for="employee-branch">
            <?php _e('Cabang', 'wp-customer'); ?>
            <span class="required">*</span>
        </label>
        <select id="employee-branch" name="branch_id" required>
            <option value=""><?php _e('Pilih Cabang', 'wp-customer'); ?></option>
            <?php
            // Load branches for this customer
            global $wpdb;
            $branches = $wpdb->get_results($wpdb->prepare(
                "SELECT id, name FROM {$wpdb->prefix}app_customer_branches
                WHERE customer_id = %d AND status = 'active'
                ORDER BY name ASC",
                $employee->customer_id
            ));

            if ($branches) {
                foreach ($branches as $branch) {
                    $selected = ($branch->id == $employee->branch_id) ? 'selected' : '';
                    echo '<option value="' . esc_attr($branch->id) . '" ' . $selected . '>' . esc_html($branch->name) . '</option>';
                }
            }
            ?>
        </select>
        <span class="description">
            <?php _e('Cabang penempatan karyawan', 'wp-customer'); ?>
        </span>
    </div>

    <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">

    <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; color: #23282d;">
        <?php _e('Departemen', 'wp-customer'); ?>
    </h4>

    <div class="customer-form-field full-width">
        <?php
        $departments = [
            'finance' => __('Finance', 'wp-customer'),
            'operation' => __('Operation', 'wp-customer'),
            'legal' => __('Legal', 'wp-customer'),
            'purchase' => __('Purchase', 'wp-customer')
        ];

        foreach ($departments as $key => $label) :
            $checked = (!empty($employee->$key) && ($employee->$key == 1 || $employee->$key == '1')) ? 'checked' : '';
            ?>
            <label style="display: inline-block; margin-right: 15px;">
                <input type="checkbox"
                       name="<?php echo esc_attr($key); ?>"
                       value="1"
                       <?php echo $checked; ?>>
                <?php echo esc_html($label); ?>
            </label>
        <?php endforeach; ?>
        <div style="clear: both;"></div>
        <span class="description">
            <?php _e('Pilih departemen yang menjadi tanggung jawab karyawan', 'wp-customer'); ?>
        </span>
    </div>

    <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">

    <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; color: #23282d;">
        <?php _e('Keterangan', 'wp-customer'); ?>
    </h4>

    <div class="customer-form-field full-width">
        <label for="employee-keterangan">
            <?php _e('Keterangan', 'wp-customer'); ?>
        </label>
        <textarea id="employee-keterangan"
                  name="keterangan"
                  rows="3"
                  maxlength="200"
                  placeholder="<?php esc_attr_e('Catatan tambahan (optional)', 'wp-customer'); ?>"><?php echo esc_textarea($employee->keterangan ?? ''); ?></textarea>
        <span class="description">
            <?php _e('Catatan tambahan maksimal 200 karakter (optional)', 'wp-customer'); ?>
        </span>
    </div>
</form>
