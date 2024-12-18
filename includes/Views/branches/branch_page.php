<?php
defined('ABSPATH') || exit;
?>

<div class="wrap branch-management">
    <h1 class="wp-heading-inline">
        <?php echo esc_html__('Branch Management', 'customer-management'); ?>
    </h1>
    
    <?php if (current_user_can('create_customers')): ?>
    <a href="#" class="page-title-action add-new-branch">
        <?php echo esc_html__('Add New Branch', 'customer-management'); ?>
    </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <!-- Main Table -->
    <table id="branch-table" class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Name', 'customer-management'); ?></th>
                <th><?php echo esc_html__('Location', 'customer-management'); ?></th>
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
<?php require_once(__DIR__ . '/branch-right-panel.php'); ?>

<!-- Modals -->
<?php require_once(__DIR__ . '/modals/branch-add-edit.php'); ?>
<?php require_once(__DIR__ . '/modals/branch-delete-confirm.php'); ?>
