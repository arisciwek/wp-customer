<?php
// Access template data directly
$customer = isset($panel_data['customer']) ? $panel_data['customer'] : null;
$access = isset($panel_data['access']) ? $panel_data['access'] : null;

// Debug log if needed
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('=== Customer Details Template Debug ===');
    error_log('Customer Data: ' . print_r($customer, true));
    //error_log('Access Data: ' . print_r($access, true));
}
?>

<div id="customer-details" class="tab-content-active">
    <?php if (isset($customer)): ?>
        <div class="customer-header">
            <h3><?php echo esc_html($customer->name); ?></h3>
            <span class="customer-code"><?php echo esc_html($customer->code); ?></span>
        </div>

        <div class="customer-main-info">
            <div class="info-group">
                <label>Owner:</label>
                <span><?php echo esc_html($customer->owner_name); ?></span>
            </div>
            <?php if (!empty($customer->npwp)): ?>
            <div class="info-group">
                <label>NPWP:</label>
                <span><?php echo esc_html($customer->npwp); ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($customer->nib)): ?>
            <div class="info-group">
                <label>NIB:</label>
                <span><?php echo esc_html($customer->nib); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="customer-stats">
            <div class="stat-item">
                <label>Jumlah Cabang:</label>
                <span class="stat-value"><?php echo (int)$customer->branch_count; ?></span>
            </div>
            <div class="stat-item">
                <label>Jumlah Staff:</label>
                <span class="stat-value"><?php echo isset($customer->staff_count) ? (int)$customer->staff_count : 0; ?></span>
            </div>
        </div>

        <div class="customer-meta">
            <div class="meta-item">
                <label>Dibuat:</label>
                <span><?php echo mysql2date('d/m/Y H:i', $customer->created_at); ?></span>
            </div>
            <div class="meta-item">
                <label>Terakhir diupdate:</label>
                <span><?php echo mysql2date('d/m/Y H:i', $customer->updated_at); ?></span>
            </div>
        </div>
    <?php endif; ?>
</div>