<?php
/**
 * Create Customer Employee Form Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/CustomerEmployee/Forms
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/customer-employee/forms/create-customer-employee-form.php
 *
 * Description: Form modal untuk menambah karyawan baru.
 *              Includes input validation, error handling,
 *              dan AJAX submission handling.
 *              Terintegrasi dengan komponen toast notification.
 *
 * Changelog:
 * 1.0.0 - 2024-01-12
 * - Initial release
 * - Added form structure
 * - Added validation markup
 * - Added AJAX integration
 */
defined('ABSPATH') || exit;
?>

<?php
defined('ABSPATH') || exit;
?>

<div id="create-employee-modal" class="modal-overlay wp-customer-modal" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h3><?php _e('Tambah Karyawan', 'wp-customer'); ?></h3>
            <button type="button" class="modal-close" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <form id="create-employee-form" method="post">
            <?php wp_nonce_field('wp_customer_nonce'); ?>
            <input type="hidden" name="customer_id" id="employee-customer-id">

            <div class="modal-content">
                <div class="row left-side">
                    <!-- Informasi Dasar -->
                    <div class="employee-form-section">
                        <div class="section-header">
                            <h4><?php _e('Informasi Dasar', 'wp-customer'); ?></h4>
                        </div>
                        <div class="employee-form-group">
                            <label for="employee-name" class="required-field">
                                <?php _e('Nama Karyawan', 'wp-customer'); ?>
                            </label>
                            <input type="text"
                                   id="employee-name"
                                   name="name"
                                   class="regular-text"
                                   maxlength="100"
                                   required>
                        </div>

                        <div class="employee-form-group">
                            <label for="employee-position" class="required-field">
                                <?php _e('Jabatan', 'wp-customer'); ?>
                            </label>
                            <input type="text"
                                   id="employee-position"
                                   name="position"
                                   class="regular-text"
                                   maxlength="100"
                                   required>
                        </div>
                    </div>

                    <!-- Departemen -->
                    <div class="employee-form-section">
                        <div class="section-header">
                            <h4><?php _e('Departemen', 'wp-customer'); ?></h4>
                        </div>
                        <div class="department-checkboxes">
                            <?php
                            $departments = [
                                'finance' => __('Finance', 'wp-customer'),
                                'operation' => __('Operation', 'wp-customer'),
                                'legal' => __('Legal', 'wp-customer'),
                                'purchase' => __('Purchase', 'wp-customer')
                            ];

                            foreach ($departments as $key => $label) : ?>
                                <div class="checkbox-wrapper">
                                    <label>
                                        <input type="checkbox" 
                                               name="<?php echo esc_attr($key); ?>" 
                                               value="1"
                                               data-department="<?php echo esc_attr($key); ?>">
                                        <?php echo esc_html($label); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Cabang -->
                    <div class="employee-form-section">
                        <div class="section-header">
                            <h4><?php _e('Informasi Cabang', 'wp-customer'); ?></h4>
                        </div>
                        <div class="employee-form-group">
                            <label for="employee-branch" class="required-field">
                                <?php _e('Cabang', 'wp-customer'); ?>
                            </label>
                            <select id="employee-branch" name="branch_id" required>
                                <option value=""><?php _e('Pilih Cabang', 'wp-customer'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row right-side">
                    <!-- Kontak -->
                    <div class="employee-form-section">
                        <div class="section-header">
                            <h4><?php _e('Informasi Kontak', 'wp-customer'); ?></h4>
                        </div>
                        <div class="employee-form-group">
                            <label for="employee-email" class="required-field">Email</label>
                            <input type="email"
                                   id="employee-email"
                                   name="email"
                                   class="regular-text"
                                   maxlength="100"
                                   required>
                            <p class="description">
                                <?php _e('Email akan digunakan untuk login dan komunikasi', 'wp-customer'); ?>
                            </p>
                        </div>

                        <div class="employee-form-group">
                            <label for="employee-phone">
                                <?php _e('Nomor Telepon', 'wp-customer'); ?>
                            </label>
                            <input type="tel"
                                   id="employee-phone"
                                   name="phone"
                                   class="regular-text"
                                   maxlength="20">
                            <p class="description">
                                <?php _e('Format: +62xxx atau 08xxx (opsional)', 'wp-customer'); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Keterangan -->
                    <div class="employee-form-section">
                        <div class="section-header">
                            <h4><?php _e('Keterangan', 'wp-customer'); ?></h4>
                        </div>
                        <div class="employee-form-group">
                            <label for="employee-keterangan">
                                <?php _e('Keterangan', 'wp-customer'); ?>
                            </label>
                            <textarea id="employee-keterangan"
                                    name="keterangan"
                                    class="regular-text"
                                    maxlength="200"
                                    rows="3"></textarea>
                            <p class="description">
                                <?php _e('Maksimal 200 karakter', 'wp-customer'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <div class="employee-form-actions">
                    <button type="button" class="button cancel-create">
                        <?php _e('Batal', 'wp-customer'); ?>
                    </button>
                    <button type="submit" class="button button-primary">
                        <?php _e('Simpan', 'wp-customer'); ?>
                    </button>
                    <span class="spinner"></span>
                </div>
            </div>
        </form>
    </div>
</div>
