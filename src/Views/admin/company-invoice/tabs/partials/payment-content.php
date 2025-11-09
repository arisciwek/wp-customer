<?php
/**
 * Company Invoice Payment Content (Partial)
 *
 * @package     WP_Customer
 * @subpackage  Views/Admin/CompanyInvoice/Tabs/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/admin/company-invoice/tabs/partials/payment-content.php
 *
 * Description: Lazy-loaded content untuk payment tab.
 *              Shows payment history and upload payment proof form.
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2196)
 * - Initial implementation
 * - Payment history list
 * - Upload payment proof form (TODO)
 * - Validate payment button (admin only)
 */

defined('ABSPATH') || exit;

// $invoice and $payments should be available from parent template
if (!isset($invoice) && isset($data)) {
    $invoice = $data;
}

if (!isset($invoice) || !is_object($invoice)) {
    echo '<p>' . esc_html__('Invoice data not available', 'wp-customer'); ?></p>';
    return;
}

$status = $invoice->status ?? 'pending';
?>

<div class="payment-history-section">
    <h3><?php esc_html_e('Payment History', 'wp-customer'); ?></h3>

    <?php if (isset($payments) && !empty($payments)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Date', 'wp-customer'); ?></th>
                    <th><?php esc_html_e('Amount', 'wp-customer'); ?></th>
                    <th><?php esc_html_e('Method', 'wp-customer'); ?></th>
                    <th><?php esc_html_e('Status', 'wp-customer'); ?></th>
                    <th><?php esc_html_e('Proof', 'wp-customer'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?php echo $payment->created_at ? date('d/m/Y H:i', strtotime($payment->created_at)) : '-'; ?></td>
                    <td>Rp <?php echo number_format($payment->amount ?? 0, 0, ',', '.'); ?></td>
                    <td><?php echo esc_html($payment->payment_method ?? '-'); ?></td>
                    <td><?php echo esc_html($payment->status ?? '-'); ?></td>
                    <td>
                        <?php if (!empty($payment->proof_file)): ?>
                            <a href="<?php echo esc_url($payment->proof_file); ?>" target="_blank"><?php esc_html_e('View', 'wp-customer'); ?></a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p><?php esc_html_e('No payment history yet.', 'wp-customer'); ?></p>
    <?php endif; ?>
</div>

<?php if ($status === 'pending'): ?>
<div class="payment-upload-section">
    <h3><?php esc_html_e('Upload Payment Proof', 'wp-customer'); ?></h3>
    <p><?php esc_html_e('Upload your payment proof to proceed with payment validation.', 'wp-customer'); ?></p>
    <form id="upload-payment-proof-form" enctype="multipart/form-data">
        <input type="hidden" name="invoice_id" value="<?php echo esc_attr($invoice->id); ?>">
        <table class="form-table">
            <tr>
                <th><label for="payment-proof-file"><?php esc_html_e('Payment Proof:', 'wp-customer'); ?></label></th>
                <td>
                    <input type="file" id="payment-proof-file" name="payment_proof" accept="image/*,application/pdf">
                    <p class="description"><?php esc_html_e('Allowed formats: JPG, PNG, PDF (max 2MB)', 'wp-customer'); ?></p>
                </td>
            </tr>
        </table>
        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e('Upload Proof', 'wp-customer'); ?></button>
        </p>
    </form>
</div>
<?php endif; ?>

<?php if ($status === 'pending_payment' && current_user_can('manage_options')): ?>
<div class="payment-validation-section">
    <h3><?php esc_html_e('Payment Validation', 'wp-customer'); ?></h3>
    <p><?php esc_html_e('Payment proof has been uploaded and waiting for validation.', 'wp-customer'); ?></p>
    <p>
        <button type="button" class="button button-primary validate-payment-btn" data-id="<?php echo esc_attr($invoice->id); ?>">
            <?php esc_html_e('Validate Payment', 'wp-customer'); ?>
        </button>
    </p>
</div>
<?php endif; ?>
