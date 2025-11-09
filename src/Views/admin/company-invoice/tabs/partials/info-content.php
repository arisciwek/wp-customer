<?php
/**
 * Company Invoice Info Content (Partial)
 *
 * @package     WP_Customer
 * @subpackage  Views/Admin/CompanyInvoice/Tabs/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/admin/company-invoice/tabs/partials/info-content.php
 *
 * Description: Lazy-loaded content untuk invoice info tab.
 *              Displays invoice details (number, amount, status, dates).
 *              Reuses structure from _company_invoice_details.php
 *
 * Changelog:
 * 1.1.0 - 2025-11-09 (TODO-2196)
 * - Moved inline CSS to company-invoice-style.css
 * - Removed all inline styles from template
 * - Clean separation: PHP for structure, CSS for styling
 *
 * 1.0.0 - 2025-11-09 (TODO-2196)
 * - Initial implementation
 * - Based on existing _company_invoice_details.php
 * - Added level information (from/to)
 * - Added period months
 * - CSS Grid layout for modern UI
 */

defined('ABSPATH') || exit;

// $invoice should be available from parent template
if (!isset($invoice) && isset($data)) {
    $invoice = $data;
}

if (!isset($invoice) || !is_object($invoice)) {
    echo '<p>' . esc_html__('Invoice data not available', 'wp-customer') . '</p>';
    return;
}

// Get related data
$invoice_model = new \WPCustomer\Models\Company\CompanyInvoiceModel();
$company = $invoice_model->getInvoiceCompany($invoice->id);

// Get level names
global $wpdb;
$from_level_name = '-';
$to_level_name = '-';

if ($invoice->from_level_id) {
    $from_level = $wpdb->get_var($wpdb->prepare(
        "SELECT name FROM {$wpdb->prefix}app_customer_membership_levels WHERE id = %d",
        $invoice->from_level_id
    ));
    $from_level_name = $from_level ? esc_html($from_level) : '-';
}

if ($invoice->level_id) {
    $to_level = $wpdb->get_var($wpdb->prepare(
        "SELECT name FROM {$wpdb->prefix}app_customer_membership_levels WHERE id = %d",
        $invoice->level_id
    ));
    $to_level_name = $to_level ? esc_html($to_level) : '-';
}

// Check if upgrade
$is_upgrade = ($invoice->from_level_id && $invoice->level_id && $invoice->from_level_id != $invoice->level_id);
?>

<div class="invoice-details-section">
    <h3><?php esc_html_e('Invoice Information', 'wp-customer'); ?></h3>

    <div class="invoice-info-grid">
        <!-- Invoice Number -->
        <div class="invoice-info-item">
            <div class="invoice-info-label"><?php esc_html_e('Invoice Number', 'wp-customer'); ?></div>
            <div class="invoice-info-value"><strong><?php echo esc_html($invoice->invoice_number ?? '-'); ?></strong></div>
        </div>

        <!-- Status -->
        <div class="invoice-info-item">
            <div class="invoice-info-label"><?php esc_html_e('Status', 'wp-customer'); ?></div>
            <div class="invoice-info-value">
                <?php
                $status_labels = [
                    'pending' => __('Belum Dibayar', 'wp-customer'),
                    'pending_payment' => __('Menunggu Validasi', 'wp-customer'),
                    'paid' => __('Lunas', 'wp-customer'),
                    'cancelled' => __('Dibatalkan', 'wp-customer')
                ];
                $status_classes = [
                    'pending' => 'status-pending',
                    'pending_payment' => 'status-pending-payment',
                    'paid' => 'status-paid',
                    'cancelled' => 'status-cancelled'
                ];
                $status = $invoice->status ?? 'pending';
                $status_label = $status_labels[$status] ?? $status;
                $status_class = $status_classes[$status] ?? 'status-pending';
                ?>
                <span class="status-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span>
            </div>
        </div>

        <!-- Company -->
        <div class="invoice-info-item full-width">
            <div class="invoice-info-label"><?php esc_html_e('Company', 'wp-customer'); ?></div>
            <div class="invoice-info-value"><?php echo esc_html($company->name ?? '-'); ?></div>
        </div>

        <!-- Type -->
        <div class="invoice-info-item">
            <div class="invoice-info-label"><?php esc_html_e('Type', 'wp-customer'); ?></div>
            <div class="invoice-info-value">
                <?php if ($is_upgrade): ?>
                    <span class="status-badge status-upgrade"><?php esc_html_e('Upgrade', 'wp-customer'); ?></span>
                <?php else: ?>
                    <span class="status-badge status-new"><?php esc_html_e('New Membership', 'wp-customer'); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Period -->
        <div class="invoice-info-item">
            <div class="invoice-info-label"><?php esc_html_e('Period', 'wp-customer'); ?></div>
            <div class="invoice-info-value"><?php echo esc_html($invoice->period_months ?? 0); ?> <?php esc_html_e('months', 'wp-customer'); ?></div>
        </div>

        <!-- Level Information -->
        <?php if ($is_upgrade): ?>
            <div class="invoice-info-item">
                <div class="invoice-info-label"><?php esc_html_e('From Level', 'wp-customer'); ?></div>
                <div class="invoice-info-value"><?php echo $from_level_name; ?></div>
            </div>
            <div class="invoice-info-item">
                <div class="invoice-info-label"><?php esc_html_e('To Level', 'wp-customer'); ?></div>
                <div class="invoice-info-value"><?php echo $to_level_name; ?></div>
            </div>
        <?php else: ?>
            <div class="invoice-info-item full-width">
                <div class="invoice-info-label"><?php esc_html_e('Membership Level', 'wp-customer'); ?></div>
                <div class="invoice-info-value"><?php echo $to_level_name; ?></div>
            </div>
        <?php endif; ?>

        <!-- Amount (Highlight) -->
        <div class="invoice-info-item full-width highlight">
            <div class="invoice-info-label"><?php esc_html_e('Total Amount', 'wp-customer'); ?></div>
            <div class="invoice-info-value"><strong>Rp <?php echo number_format($invoice->amount ?? 0, 0, ',', '.'); ?></strong></div>
        </div>

        <!-- Due Date -->
        <div class="invoice-info-item">
            <div class="invoice-info-label"><?php esc_html_e('Due Date', 'wp-customer'); ?></div>
            <div class="invoice-info-value"><?php echo $invoice->due_date ? date('d F Y', strtotime($invoice->due_date)) : '-'; ?></div>
        </div>

        <!-- Paid Date (if exists) -->
        <?php if ($invoice->paid_date): ?>
        <div class="invoice-info-item">
            <div class="invoice-info-label"><?php esc_html_e('Paid Date', 'wp-customer'); ?></div>
            <div class="invoice-info-value"><?php echo date('d F Y', strtotime($invoice->paid_date)); ?></div>
        </div>
        <?php endif; ?>

        <!-- Created Info -->
        <div class="invoice-info-item">
            <div class="invoice-info-label"><?php esc_html_e('Created', 'wp-customer'); ?></div>
            <div class="invoice-info-value"><?php echo $invoice->created_at ? date('d F Y H:i', strtotime($invoice->created_at)) : '-'; ?></div>
        </div>

        <?php if ($invoice->created_by): ?>
        <div class="invoice-info-item">
            <div class="invoice-info-label"><?php esc_html_e('Created By', 'wp-customer'); ?></div>
            <div class="invoice-info-value"><?php
                $creator = get_userdata($invoice->created_by);
                echo $creator ? esc_html($creator->display_name) : '-';
            ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($status === 'pending' && current_user_can('manage_options')): ?>
<div class="invoice-actions-section">
    <h3><?php esc_html_e('Actions', 'wp-customer'); ?></h3>
    <div class="invoice-actions-buttons">
        <button type="button" class="button button-primary invoice-cancel-btn" data-id="<?php echo esc_attr($invoice->id); ?>">
            <?php esc_html_e('Cancel Invoice', 'wp-customer'); ?>
        </button>
    </div>
</div>
<?php endif; ?>
