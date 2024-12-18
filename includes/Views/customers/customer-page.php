<?php
defined('ABSPATH') || exit;
?>

<div class="wrap customer-management">
    <h1 class="wp-heading-inline">
        <?php echo esc_html__('Customer Management', 'customer-management'); ?>
    </h1>
    
    <?php if (current_user_can('create_customers')): ?>
    <a href="#" class="page-title-action add-new-customer">
        <?php echo esc_html__('Add New Customer', 'customer-management'); ?>
    </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <!-- Dashboard Section -->
    <?php require_once(__DIR__ . '/customer-dashboard.php'); ?>

    <div class="customer-panels">
        <!-- Left Panel with Table -->
        <?php require_once(__DIR__ . '/customer-left-panel.php'); ?>

        <!-- Right Panel -->
        <?php require_once(__DIR__ . '/customer-right-panel.php'); ?>
    </div>
</div>

<!-- Modals -->
<?php require_once(__DIR__ . '/modals/customer-add-edit.php'); ?>
<?php require_once(__DIR__ . '/modals/customer-delete-confirm.php'); ?>
