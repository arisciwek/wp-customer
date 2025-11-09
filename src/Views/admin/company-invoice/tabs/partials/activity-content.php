<?php
/**
 * Company Invoice Activity Content (Partial)
 *
 * @package     WP_Customer
 * @subpackage  Views/Admin/CompanyInvoice/Tabs/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/admin/company-invoice/tabs/partials/activity-content.php
 *
 * Description: Lazy-loaded content untuk activity tab.
 *              Shows activity log (status changes, payments, etc).
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2196)
 * - Initial implementation
 * - Activity log display (placeholder)
 */

defined('ABSPATH') || exit;

// $invoice should be available from parent template
if (!isset($invoice) && isset($data)) {
    $invoice = $data;
}

if (!isset($invoice) || !is_object($invoice)) {
    echo '<p>' . esc_html__('Invoice data not available', 'wp-customer'); ?></p>';
    return;
}
?>

<div class="activity-log-section">
    <h3><?php esc_html_e('Activity Log', 'wp-customer'); ?></h3>

    <div class="activity-timeline">
        <!-- Activity log implementation -->
        <div class="activity-item">
            <div class="activity-icon">
                <span class="dashicons dashicons-plus"></span>
            </div>
            <div class="activity-content">
                <p class="activity-title"><?php esc_html_e('Invoice Created', 'wp-customer'); ?></p>
                <p class="activity-meta">
                    <?php
                    if ($invoice->created_at) {
                        echo date('d F Y H:i', strtotime($invoice->created_at));
                    }
                    if ($invoice->created_by) {
                        $creator = get_userdata($invoice->created_by);
                        if ($creator) {
                            echo ' ' . esc_html__('by', 'wp-customer') . ' ' . esc_html($creator->display_name);
                        }
                    }
                    ?>
                </p>
            </div>
        </div>

        <?php if ($invoice->status === 'pending_payment' || $invoice->status === 'paid'): ?>
        <div class="activity-item">
            <div class="activity-icon">
                <span class="dashicons dashicons-upload"></span>
            </div>
            <div class="activity-content">
                <p class="activity-title"><?php esc_html_e('Payment Proof Uploaded', 'wp-customer'); ?></p>
                <p class="activity-meta">
                    <?php esc_html_e('Waiting for validation', 'wp-customer'); ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($invoice->status === 'paid' && $invoice->paid_date): ?>
        <div class="activity-item">
            <div class="activity-icon">
                <span class="dashicons dashicons-yes"></span>
            </div>
            <div class="activity-content">
                <p class="activity-title"><?php esc_html_e('Payment Validated', 'wp-customer'); ?></p>
                <p class="activity-meta">
                    <?php echo date('d F Y H:i', strtotime($invoice->paid_date)); ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($invoice->status === 'cancelled'): ?>
        <div class="activity-item">
            <div class="activity-icon">
                <span class="dashicons dashicons-no"></span>
            </div>
            <div class="activity-content">
                <p class="activity-title"><?php esc_html_e('Invoice Cancelled', 'wp-customer'); ?></p>
                <p class="activity-meta">
                    <?php
                    if ($invoice->updated_at) {
                        echo date('d F Y H:i', strtotime($invoice->updated_at));
                    }
                    ?>
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <style>
        .activity-timeline {
            position: relative;
            padding-left: 40px;
        }
        .activity-item {
            position: relative;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-icon {
            position: absolute;
            left: -40px;
            width: 30px;
            height: 30px;
            background: #2271b1;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .activity-icon .dashicons {
            width: 16px;
            height: 16px;
            font-size: 16px;
        }
        .activity-title {
            font-weight: 600;
            margin: 0 0 5px 0;
        }
        .activity-meta {
            color: #666;
            font-size: 0.9em;
            margin: 0;
        }
    </style>
</div>
