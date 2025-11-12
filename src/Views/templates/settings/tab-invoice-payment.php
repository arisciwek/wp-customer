<?php
/**
 * Invoice & Payment Settings Tab
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Settings
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/settings/tab-invoice-payment.php
 *
 * Description: Invoice & payment settings tab template following wp-app-core pattern
 *              Invoice settings, payment methods, confirmation, reminders
 *
 * Changelog:
 * 2.0.0 - 2025-01-13 (TODO-2198)
 * - BREAKING: Complete refactor to match wp-app-core pattern
 * - Added proper form structure with hidden inputs
 * - Removed submit button (moved to page level)
 * - Added sections structure (Invoice, Payment)
 * - Updated to use InvoicePaymentSettingsModel
 * 1.0.1 - 2025-10-17
 * - Template optimization
 * 1.0.0 - 2025-10-17
 * - Initial version
 */

if (!defined('ABSPATH')) {
    die;
}

// $settings is passed from controller
// Payment method labels
$payment_method_labels = [
    'transfer_bank' => __('Transfer Bank', 'wp-customer'),
    'virtual_account' => __('Virtual Account', 'wp-customer'),
    'kartu_kredit' => __('Kartu Kredit', 'wp-customer'),
    'e_wallet' => __('E-Wallet', 'wp-customer'),
];
?>

<div class="wp-customer-settings-invoice-payment">
    <form method="post" action="options.php" id="wp-customer-invoice-payment-settings-form">
        <?php settings_fields('wp_customer_invoice_payment_settings'); ?>
        <input type="hidden" name="reset_to_defaults" value="0">
        <input type="hidden" name="current_tab" value="invoice-payment">
        <input type="hidden" name="saved_tab" value="invoice-payment">

        <!-- Invoice Settings Section -->
        <div class="settings-section">
            <h2><?php _e('Pengaturan Invoice', 'wp-customer'); ?></h2>
            <p class="description">
                <?php _e('Konfigurasi default untuk pembuatan invoice membership customer.', 'wp-customer'); ?>
            </p>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="invoice_due_days"><?php _e('Jatuh Tempo (Hari)', 'wp-customer'); ?></label>
                    </th>
                    <td>
                        <input type="number"
                               name="wp_customer_invoice_payment_settings[invoice_due_days]"
                               id="invoice_due_days"
                               value="<?php echo esc_attr($settings['invoice_due_days'] ?? 7); ?>"
                               min="1"
                               max="365"
                               class="small-text">
                        <p class="description"><?php _e('Berapa hari dari tanggal pembuatan invoice. Default: 7 hari.', 'wp-customer'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="invoice_prefix"><?php _e('Prefix Invoice', 'wp-customer'); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               name="wp_customer_invoice_payment_settings[invoice_prefix]"
                               id="invoice_prefix"
                               value="<?php echo esc_attr($settings['invoice_prefix'] ?? 'INV'); ?>"
                               class="regular-text">
                        <p class="description"><?php _e('Prefix untuk nomor invoice (contoh: INV → INV-202510-00001). Default: INV.', 'wp-customer'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="invoice_number_format"><?php _e('Format Nomor Invoice', 'wp-customer'); ?></label>
                    </th>
                    <td>
                        <select name="wp_customer_invoice_payment_settings[invoice_number_format]" id="invoice_number_format">
                            <option value="YYYYMM" <?php selected($settings['invoice_number_format'] ?? 'YYYYMM', 'YYYYMM'); ?>>
                                YYYYMM (<?php echo date('Ym'); ?> - Tahun + Bulan)
                            </option>
                            <option value="YYYYMMDD" <?php selected($settings['invoice_number_format'] ?? 'YYYYMM', 'YYYYMMDD'); ?>>
                                YYYYMMDD (<?php echo date('Ymd'); ?> - Tahun + Bulan + Tanggal)
                            </option>
                            <option value="YYMM" <?php selected($settings['invoice_number_format'] ?? 'YYYYMM', 'YYMM'); ?>>
                                YYMM (<?php echo date('ym'); ?> - Tahun 2 digit + Bulan)
                            </option>
                            <option value="YYMMDD" <?php selected($settings['invoice_number_format'] ?? 'YYYYMM', 'YYMMDD'); ?>>
                                YYMMDD (<?php echo date('ymd'); ?> - Tahun 2 digit + Bulan + Tanggal)
                            </option>
                        </select>
                        <p class="description"><?php _e('Format tanggal pada nomor invoice. Default: YYYYMM.', 'wp-customer'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="invoice_currency"><?php _e('Mata Uang', 'wp-customer'); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               name="wp_customer_invoice_payment_settings[invoice_currency]"
                               id="invoice_currency"
                               value="<?php echo esc_attr($settings['invoice_currency'] ?? 'Rp'); ?>"
                               class="regular-text">
                        <p class="description"><?php _e('Simbol atau kode mata uang. Default: Rp.', 'wp-customer'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="invoice_tax_percentage"><?php _e('PPN (%)', 'wp-customer'); ?></label>
                    </th>
                    <td>
                        <input type="number"
                               name="wp_customer_invoice_payment_settings[invoice_tax_percentage]"
                               id="invoice_tax_percentage"
                               value="<?php echo esc_attr($settings['invoice_tax_percentage'] ?? 11); ?>"
                               min="0"
                               max="100"
                               step="0.01"
                               class="small-text">
                        <span>%</span>
                        <p class="description"><?php _e('Persentase pajak PPN. Default: 11%.', 'wp-customer'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="invoice_sender_email"><?php _e('Email Pengirim Invoice', 'wp-customer'); ?></label>
                    </th>
                    <td>
                        <input type="email"
                               name="wp_customer_invoice_payment_settings[invoice_sender_email]"
                               id="invoice_sender_email"
                               value="<?php echo esc_attr($settings['invoice_sender_email'] ?? ''); ?>"
                               class="regular-text"
                               placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                        <p class="description"><?php _e('Email pengirim invoice. Kosongkan untuk menggunakan email admin.', 'wp-customer'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Payment Settings Section -->
        <div class="settings-section">
            <h2><?php _e('Pengaturan Payment', 'wp-customer'); ?></h2>
            <p class="description">
                <?php _e('Konfigurasi metode pembayaran dan konfirmasi payment.', 'wp-customer'); ?>
            </p>

            <table class="form-table">
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
                                           name="wp_customer_invoice_payment_settings[payment_methods][]"
                                           value="<?php echo esc_attr($method); ?>"
                                           <?php checked(in_array($method, $settings['payment_methods'] ?? [])); ?>>
                                    <?php echo esc_html($label); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </fieldset>
                        <p class="description"><?php _e('Pilih metode pembayaran yang tersedia. Minimal 1 metode.', 'wp-customer'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('Konfirmasi Pembayaran', 'wp-customer'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="wp_customer_invoice_payment_settings[payment_confirmation_required]"
                                   id="payment_confirmation_required"
                                   value="1"
                                   <?php checked($settings['payment_confirmation_required'] ?? true, 1); ?>>
                            <?php _e('Memerlukan konfirmasi admin sebelum payment dinyatakan sah', 'wp-customer'); ?>
                        </label>
                        <p class="description"><?php _e('Jika diaktifkan, pembayaran perlu konfirmasi admin. Default: Ya.', 'wp-customer'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="payment_auto_approve_threshold"><?php _e('Auto-approve Threshold', 'wp-customer'); ?></label>
                    </th>
                    <td>
                        <input type="number"
                               name="wp_customer_invoice_payment_settings[payment_auto_approve_threshold]"
                               id="payment_auto_approve_threshold"
                               value="<?php echo esc_attr($settings['payment_auto_approve_threshold'] ?? 0); ?>"
                               min="0"
                               step="0.01"
                               class="regular-text">
                        <p class="description"><?php _e('Pembayaran di bawah nilai ini otomatis disetujui. Isi 0 untuk menonaktifkan. Default: 0.', 'wp-customer'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="payment_reminder_days"><?php _e('Jadwal Reminder', 'wp-customer'); ?></label>
                    </th>
                    <td>
                        <div id="reminder-days-container">
                            <?php
                            $reminder_days = $settings['payment_reminder_days'] ?? [7, 3, 1];
                            foreach ($reminder_days as $index => $day):
                            ?>
                                <div class="reminder-day-row" style="margin-bottom: 8px;">
                                    <label>
                                        H-<input type="number"
                                               name="wp_customer_invoice_payment_settings[payment_reminder_days][]"
                                               value="<?php echo esc_attr($day); ?>"
                                               min="1"
                                               max="365"
                                               class="small-text">
                                        <?php _e('hari sebelum jatuh tempo', 'wp-customer'); ?>
                                        <?php if ($index > 0): ?>
                                        <button type="button" class="button button-small remove-reminder-day">×</button>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" id="add-reminder-day" class="button button-secondary" style="margin-top: 8px;">
                            <?php _e('+ Tambah Reminder', 'wp-customer'); ?>
                        </button>
                        <p class="description"><?php _e('Jadwal pengiriman reminder sebelum jatuh tempo. Default: H-7, H-3, H-1.', 'wp-customer'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
    </form>

    <!-- DEPRECATED: Per-tab buttons moved to page level (settings-page.php) -->
</div>

<style>
.settings-section {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.settings-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #e5e5e5;
}

.reminder-day-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.remove-reminder-day {
    color: #b32d2e;
    border-color: #b32d2e;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Add reminder day
    $('#add-reminder-day').on('click', function() {
        const $container = $('#reminder-days-container');
        const newRow = `
            <div class="reminder-day-row" style="margin-bottom: 8px;">
                <label>
                    H-<input type="number"
                           name="wp_customer_invoice_payment_settings[payment_reminder_days][]"
                           value="1"
                           min="1"
                           max="365"
                           class="small-text">
                    <?php _e('hari sebelum jatuh tempo', 'wp-customer'); ?>
                    <button type="button" class="button button-small remove-reminder-day">×</button>
                </label>
            </div>
        `;
        $container.append(newRow);
    });

    // Remove reminder day
    $(document).on('click', '.remove-reminder-day', function() {
        $(this).closest('.reminder-day-row').remove();
    });
});
</script>
