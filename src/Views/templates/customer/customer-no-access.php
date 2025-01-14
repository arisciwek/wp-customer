<?php
/*
 * path : src/Views/templates/customer/customer-no-access.php
 */

defined('ABSPATH') || exit;
?>

<!-- customer-no-access.php -->
<div class="wrap">
    <h1><?php _e('WP Customer', 'wp-customer'); ?></h1>
    
    <div class="notice notice-warning">
        <p><?php _e('Anda tidak memiliki akses ke data customer. Silahkan hubungi administrator.', 'wp-customer'); ?></p>
    </div>

    <?php if (current_user_can('view_customer_list')): ?>
    <div class="customer-stats">
        <div class="card">
            <h2><?php _e('Total Customer', 'wp-customer'); ?></h2>
            <p class="customer-count">0</p>
        </div>
    </div>
    <?php endif; ?>
</div>