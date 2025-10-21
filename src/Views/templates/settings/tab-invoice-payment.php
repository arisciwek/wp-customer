<?php
/**
 * Invoice & Payment Settings Tab Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Settings
 * @version     1.0.10
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/settings/tab-invoice-payment.php
 *
 * Description: Template untuk mengelola pengaturan invoice dan payment
 *              Menampilkan form untuk konfigurasi default invoice dan payment
 *
 * Changelog:
 * v1.0.1 - 2025-10-17 (Task-2158 Review-03)
 * - No changes needed - fix was in SettingsModel
 * - Template already uses $options array which now always has all keys
 *
 * v1.0.0 - 2025-10-17 (Task-2158)
 * - Initial version
 * - Add invoice settings section (due days, prefix, format, currency, tax, sender email)
 * - Add payment settings section (methods, confirmation, auto-approve, reminders)
 * - Add form validation and save functionality
 */

if (!defined('ABSPATH')) {
    die;
}

// Get settings model
$settings_model = new \WPCustomer\Models\Settings\SettingsModel();
$options = $settings_model->getInvoicePaymentOptions();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_invoice_payment_settings') {
    if (!check_admin_referer('wp_customer_invoice_payment_settings')) {
        wp_die(__('Security check failed.', 'wp-customer'));
    }

    // Get posted data with defaults for unchecked checkboxes
    $posted_data = isset($_POST['wp_customer_invoice_payment_options']) ? $_POST['wp_customer_invoice_payment_options'] : [];

    // Ensure payment_methods exists (unchecked checkboxes don't get sent)
    if (!isset($posted_data['payment_methods'])) {
        $posted_data['payment_methods'] = [];
    }

    // Ensure payment_confirmation_required is set (unchecked checkbox)
    if (!isset($posted_data['payment_confirmation_required'])) {
        $posted_data['payment_confirmation_required'] = false;
    }

    // Debug logging
    error_log('[Invoice Payment Settings] Posted data: ' . print_r($posted_data, true));

    $result = $settings_model->saveInvoicePaymentSettings($posted_data);

    // Debug logging
    error_log('[Invoice Payment Settings] Save result: ' . ($result ? 'SUCCESS' : 'FAILED'));

    if ($result) {
        add_settings_error(
            'wp_customer_messages',
            'settings_updated',
            __('Pengaturan Invoice & Payment berhasil disimpan.', 'wp-customer'),
            'success'
        );
    } else {
        add_settings_error(
            'wp_customer_messages',
            'settings_error',
            __('Gagal menyimpan pengaturan.', 'wp-customer'),
            'error'
        );
    }
}

// Payment method labels
$payment_method_labels = [
    'transfer_bank' => __('Transfer Bank', 'wp-customer'),
    'virtual_account' => __('Virtual Account', 'wp-customer'),
    'kartu_kredit' => __('Kartu Kredit', 'wp-customer'),
    'e_wallet' => __('E-Wallet', 'wp-customer'),
];
?>

<div class="wrap">
    <div>
        <?php settings_errors('wp_customer_messages'); ?>
    </div>

    <form id="wp-customer-invoice-payment-form" method="post" action="<?php echo add_query_arg('tab', 'invoice-payment'); ?>">
        <?php wp_nonce_field('wp_customer_invoice_payment_settings'); ?>
        <input type="hidden" name="action" value="update_invoice_payment_settings">

        <!-- Invoice Settings Section -->
        <div class="invoice-settings-section settings-card">
            <h2><?php _e('Pengaturan Invoice', 'wp-customer'); ?></h2>
            <p class="description">
                <?php _e('Konfigurasi default untuk pembuatan invoice membership customer.', 'wp-customer'); ?>
            </p>

            <table class="form-table" role="presentation">
                <tbody>
                    <!-- Due Days -->
                    <tr>
                        <th scope="row">
                            <label for="invoice_due_days">
                                <?php _e('Jatuh Tempo (Hari)', 'wp-customer'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="number"
                                   id="invoice_due_days"
                                   name="wp_customer_invoice_payment_options[invoice_due_days]"
                                   value="<?php echo esc_attr($options['invoice_due_days']); ?>"
                                   min="1"
                                   max="365"
                                   class="small-text">
                            <p class="description">
                                <?php _e('Default berapa hari dari tanggal pembuatan invoice. Default: 7 hari.', 'wp-customer'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Invoice Prefix -->
                    <tr>
                        <th scope="row">
                            <label for="invoice_prefix">
                                <?php _e('Prefix Invoice', 'wp-customer'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                   id="invoice_prefix"
                                   name="wp_customer_invoice_payment_options[invoice_prefix]"
                                   value="<?php echo esc_attr($options['invoice_prefix']); ?>"
                                   class="regular-text">
                            <p class="description">
                                <?php _e('Prefix untuk nomor invoice (contoh: INV menghasilkan INV-202510-00001). Default: INV.', 'wp-customer'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Invoice Number Format -->
                    <tr>
                        <th scope="row">
                            <label for="invoice_number_format">
                                <?php _e('Format Nomor Invoice', 'wp-customer'); ?>
                            </label>
                        </th>
                        <td>
                            <select id="invoice_number_format"
                                    name="wp_customer_invoice_payment_options[invoice_number_format]">
                                <option value="YYYYMM" <?php selected($options['invoice_number_format'], 'YYYYMM'); ?>>
                                    YYYYMM (<?php echo date('Ym'); ?> - Tahun + Bulan)
                                </option>
                                <option value="YYYYMMDD" <?php selected($options['invoice_number_format'], 'YYYYMMDD'); ?>>
                                    YYYYMMDD (<?php echo date('Ymd'); ?> - Tahun + Bulan + Tanggal)
                                </option>
                                <option value="YYMM" <?php selected($options['invoice_number_format'], 'YYMM'); ?>>
                                    YYMM (<?php echo date('ym'); ?> - Tahun 2 digit + Bulan)
                                </option>
                                <option value="YYMMDD" <?php selected($options['invoice_number_format'], 'YYMMDD'); ?>>
                                    YYMMDD (<?php echo date('ymd'); ?> - Tahun 2 digit + Bulan + Tanggal)
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('Format tanggal pada nomor invoice. Contoh: INV-202510-00001. Default: YYYYMM.', 'wp-customer'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Currency -->
                    <tr>
                        <th scope="row">
                            <label for="invoice_currency">
                                <?php _e('Mata Uang', 'wp-customer'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                   id="invoice_currency"
                                   name="wp_customer_invoice_payment_options[invoice_currency]"
                                   value="<?php echo esc_attr($options['invoice_currency']); ?>"
                                   class="regular-text">
                            <p class="description">
                                <?php _e('Simbol atau kode mata uang yang digunakan. Default: Rp.', 'wp-customer'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Tax Percentage -->
                    <tr>
                        <th scope="row">
                            <label for="invoice_tax_percentage">
                                <?php _e('PPN (%)', 'wp-customer'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="number"
                                   id="invoice_tax_percentage"
                                   name="wp_customer_invoice_payment_options[invoice_tax_percentage]"
                                   value="<?php echo esc_attr($options['invoice_tax_percentage']); ?>"
                                   min="0"
                                   max="100"
                                   step="0.01"
                                   class="small-text">
                            <span>%</span>
                            <p class="description">
                                <?php _e('Persentase pajak PPN yang diterapkan. Default: 11%.', 'wp-customer'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Sender Email -->
                    <tr>
                        <th scope="row">
                            <label for="invoice_sender_email">
                                <?php _e('Email Pengirim Invoice', 'wp-customer'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="email"
                                   id="invoice_sender_email"
                                   name="wp_customer_invoice_payment_options[invoice_sender_email]"
                                   value="<?php echo esc_attr($options['invoice_sender_email']); ?>"
                                   class="regular-text"
                                   placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                            <p class="description">
                                <?php _e('Email yang akan digunakan sebagai pengirim invoice dan notifikasi. Kosongkan untuk menggunakan email admin.', 'wp-customer'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Payment Settings Section -->
        <div class="payment-settings-section settings-card">
            <h2><?php _e('Pengaturan Payment', 'wp-customer'); ?></h2>
            <p class="description">
                <?php _e('Konfigurasi metode pembayaran dan konfirmasi payment.', 'wp-customer'); ?>
            </p>

            <table class="form-table" role="presentation">
                <tbody>
                    <!-- Payment Methods -->
                    <tr>
                        <th scope="row">
                            <?php _e('Metode Pembayaran', 'wp-customer'); ?>
                        </th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php _e('Metode Pembayaran', 'wp-customer'); ?></span>
                                </legend>
                                <?php foreach ($payment_method_labels as $method => $label): ?>
                                    <label>
                                        <input type="checkbox"
                                               name="wp_customer_invoice_payment_options[payment_methods][]"
                                               value="<?php echo esc_attr($method); ?>"
                                               <?php checked(in_array($method, $options['payment_methods'])); ?>>
                                        <?php echo esc_html($label); ?>
                                    </label><br>
                                <?php endforeach; ?>
                            </fieldset>
                            <p class="description">
                                <?php _e('Pilih metode pembayaran yang tersedia untuk customer. Minimal 1 metode harus dipilih.', 'wp-customer'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Payment Confirmation Required -->
                    <tr>
                        <th scope="row">
                            <?php _e('Konfirmasi Pembayaran', 'wp-customer'); ?>
                        </th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php _e('Konfirmasi Pembayaran', 'wp-customer'); ?></span>
                                </legend>
                                <label>
                                    <input type="checkbox"
                                           id="payment_confirmation_required"
                                           name="wp_customer_invoice_payment_options[payment_confirmation_required]"
                                           value="1"
                                           <?php checked($options['payment_confirmation_required'], true); ?>>
                                    <?php _e('Memerlukan konfirmasi admin sebelum payment dinyatakan sah', 'wp-customer'); ?>
                                </label>
                            </fieldset>
                            <p class="description">
                                <?php _e('Jika diaktifkan, semua pembayaran perlu dikonfirmasi oleh admin. Default: Ya.', 'wp-customer'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Auto-approve Threshold -->
                    <tr>
                        <th scope="row">
                            <label for="payment_auto_approve_threshold">
                                <?php _e('Auto-approve Threshold', 'wp-customer'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="number"
                                   id="payment_auto_approve_threshold"
                                   name="wp_customer_invoice_payment_options[payment_auto_approve_threshold]"
                                   value="<?php echo esc_attr($options['payment_auto_approve_threshold']); ?>"
                                   min="0"
                                   step="0.01"
                                   class="regular-text">
                            <p class="description">
                                <?php _e('Pembayaran dengan nominal di bawah nilai ini akan otomatis disetujui. Isi 0 untuk menonaktifkan. Default: 0 (tidak aktif).', 'wp-customer'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Payment Reminder Days -->
                    <tr>
                        <th scope="row">
                            <label for="payment_reminder_days">
                                <?php _e('Jadwal Reminder', 'wp-customer'); ?>
                            </label>
                        </th>
                        <td>
                            <div class="reminder-days-container">
                                <?php
                                $reminder_days = $options['payment_reminder_days'];
                                foreach ($reminder_days as $index => $day):
                                ?>
                                    <div class="reminder-day-row">
                                        <label>
                                            H-<input type="number"
                                                   name="wp_customer_invoice_payment_options[payment_reminder_days][]"
                                                   value="<?php echo esc_attr($day); ?>"
                                                   min="1"
                                                   max="365"
                                                   class="small-text">
                                            <?php _e('hari sebelum jatuh tempo', 'wp-customer'); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="add-reminder-day" class="button">
                                <?php _e('+ Tambah Reminder', 'wp-customer'); ?>
                            </button>
                            <p class="description">
                                <?php _e('Jadwal pengiriman reminder pembayaran sebelum jatuh tempo. Default: H-7, H-3, H-1.', 'wp-customer'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php submit_button(__('Simpan Pengaturan', 'wp-customer')); ?>
    </form>
</div>
