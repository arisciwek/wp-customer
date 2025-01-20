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


<?php if (isset($panel_data['customer']) && is_object($panel_data['customer'])): 
    $customer = $panel_data['customer'];
    $access = $panel_data['access'];
?>
    <div class="customer-header">
        <h3><?php echo esc_html($customer->name); ?></h3>
        <?php if ($customer->code): ?>
            <span class="customer-code"><?php echo esc_html($customer->code); ?></span>
        <?php endif; ?>
    </div>

    <!-- Main Info Section -->
    <div class="customer-main-info">
        <?php if ($access['access_type'] === 'admin' || $access['access_type'] === 'owner'): ?>
            <div class="info-group">
                <label><?php _e('Owner', 'wp-customer'); ?></label>
                <span><?php echo esc_html($customer->owner_name ?? '-'); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Stats Section -->
    <div class="customer-stats">
        <div class="stat-item">
            <label><?php _e('Jumlah Cabang', 'wp-customer'); ?></label>
            <span class="stat-value" id="branch-count">
                <?php echo isset($customer->branch_count) ? (int)$customer->branch_count : 0; ?>
            </span>
        </div>
    </div>

    <!-- Meta Info -->
    <div class="customer-meta">
        <div class="meta-item">
            <label><?php _e('Dibuat', 'wp-customer'); ?></label>
            <span>
                <?php echo $customer->created_at ? 
                    date_i18n('j F Y, H:i', strtotime($customer->created_at)) : ''; ?>
            </span>
        </div>
        <div class="meta-item">
            <label><?php _e('Terakhir diupdate', 'wp-customer'); ?></label>
            <span>
                <?php echo $customer->updated_at ? 
                    date_i18n('j F Y, H:i', strtotime($customer->updated_at)) : ''; ?>
            </span>
        </div>
    </div>

<?php else: ?>
    <div class="notice notice-warning">
        <p><?php _e('Data customer tidak tersedia.', 'wp-customer'); ?></p>
    </div>
<?php endif; ?>




<!-- Verification Display for PHP vs JS Comparison -->
<div id="customer-details" class="tab-content-active">
    <?php if (isset($customer)): ?>
        <h3><?php echo esc_html($customer->name); ?></h3>
        <div id="customer-name"></div>
    <?php endif; ?>
    <div class="meta-info">
        <p><strong>--Jumlah Cabang:</strong> <span id="customer-branch-count"></span></p>
        <p><strong>--Dibuat:</strong> <span id="customer-created-at"></span></p>
        <p><strong>--Terakhir diupdate:</strong> <span id="customer-updated-at"></span></p>
    </div>
</div>

<style>
.customer-header {
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.customer-code {
    font-size: 0.9em;
    color: #666;
    padding: 4px 8px;
    background: #f0f0f1;
    border-radius: 4px;
}

.customer-main-info,
.customer-stats,
.customer-meta {
    background: #fff;
    padding: 15px;
    margin-bottom: 15px;
    border: 1px solid #dcdcde;
    border-radius: 4px;
}

.info-group,
.stat-item,
.meta-item {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
    padding: 5px 0;
}

.info-group label,
.stat-item label,
.meta-item label {
    min-width: 150px;
    font-weight: 600;
    color: #1d2327;
}

.stat-value {
    font-size: 1.2em;
    font-weight: bold;
    color: #2271b1;
}
</style>
