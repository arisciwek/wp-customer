<?php
/**
 * Template for unauthorized access page
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates
 * @version     1.0.11
 * @author      arisciwek
 * 
 * Path: src/Views/templates/customer/customer-no-access.php
 */

defined('ABSPATH') || exit;
?>

<div class="wrap wp-customer-no-access">
    <!-- Header section -->
    <div class="wp-customer-header">
        <h1><?php _e('WP Customer', 'wp-customer'); ?></h1>
    </div>

    <!-- Error message card -->
    <div class="wp-customer-error-card">
        <div class="error-icon">
            <span class="dashicons dashicons-lock"></span>
        </div>
        
        <h2><?php _e('Akses Dibatasi', 'wp-customer'); ?></h2>
        
        <div class="error-message">
            <p><?php _e('Anda tidak memiliki akses ke data customer. Silahkan hubungi administrator untuk informasi lebih lanjut.', 'wp-customer'); ?></p>
        </div>

        <div class="action-buttons">
            <a href="<?php echo admin_url('admin.php'); ?>" class="button button-secondary">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php _e('Kembali ke Dashboard', 'wp-customer'); ?>
            </a>
            <?php if (current_user_can('view_customer_list')): ?>
            <a href="<?php echo admin_url('admin.php?page=wp-customer'); ?>" class="button button-primary">
                <span class="dashicons dashicons-list-view"></span>
                <?php _e('Lihat Daftar Customer', 'wp-customer'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Optional stats if user has view permission -->
    <?php if (current_user_can('view_customer_list')): ?>
    <div class="wp-customer-stats">
        <div class="wp-customer-stat-card">
            <div class="stat-header">
                <span class="dashicons dashicons-groups"></span>
                <h3><?php _e('Total Customer', 'wp-customer'); ?></h3>
            </div>
            <p class="customer-count">0</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* Custom styles for no-access page */
.wp-customer-no-access {
    max-width: 960px;
    margin: 20px auto;
    padding: 0 20px;
}

.wp-customer-header {
    margin-bottom: 30px;
}

.wp-customer-error-card {
    background: #fff;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.error-icon {
    margin-bottom: 20px;
}

.error-icon .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #dc3232;
}

.wp-customer-error-card h2 {
    font-size: 24px;
    margin: 0 0 20px;
    color: #23282d;
}

.error-message {
    color: #666;
    margin-bottom: 30px;
}

.action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.action-buttons .button {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 16px;
    height: auto;
}

.action-buttons .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.wp-customer-stats {
    margin-top: 40px;
}

.wp-customer-stat-card {
    background: #fff;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.stat-header .dashicons {
    color: #2271b1;
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.stat-header h3 {
    margin: 0;
    font-size: 16px;
    color: #23282d;
}

.customer-count {
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
    margin: 0;
}
</style>
