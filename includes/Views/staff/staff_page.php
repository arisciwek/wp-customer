<?php
defined('ABSPATH') || exit;
?>

<div class="wrap staff-management">
    <h1 class="wp-heading-inline">
        <?php echo esc_html__('Staff Management', 'customer-management'); ?>
    </h1>
    
    <?php if (current_user_can('create_customers')): ?>
    <a href="#" class="page-title-action add-new-staff">
        <?php echo esc_html__('Add New Staff', 'customer-management'); ?>
    </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <!-- Main Table -->
    <table id="staff-table" class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Name', 'customer-management'); ?></th>
                <th><?php echo esc_html__('Position', 'customer-management'); ?></th>
                <th><?php echo esc_html__('Customers', 'customer-management'); ?></th>
                <th><?php echo esc_html__('Actions', 'customer-management'); ?></th>
            </tr>
        </thead>
        <tbody>
            <!-- DataTables will populate this -->
        </tbody>
    </table>
</div>

<!-- Right Panel -->
<?php require_once(__DIR__ . '/staff-right-panel.php'); ?>

<!-- Modals -->
<?php require_once(__DIR__ . '/modals/staff-add-edit.php'); ?>
<?php require_once(__DIR__ . '/modals/staff-delete-confirm.php'); ?>
