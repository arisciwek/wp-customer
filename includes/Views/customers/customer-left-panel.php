<?php
defined('ABSPATH') || exit;
?>

<div class="left-panel">
    <!-- Filters Section -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <select id="membership-filter">
                <option value=""><?php echo esc_html__('All Memberships', 'customer-management'); ?></option>
                <option value="regular"><?php echo esc_html__('Reguler', 'customer-management'); ?></option>
                <option value="priority"><?php echo esc_html__('Prioritas', 'customer-management'); ?></option>
                <option value="utama"><?php echo esc_html__('Utama', 'customer-management'); ?></option>
            </select>

            <select id="branch-filter">
                <option value=""><?php echo esc_html__('All Branches', 'customer-management'); ?></option>
            </select>

            <?php if (current_user_can('export_customers')): ?>
            <button class="button action export-customers">
                <?php echo esc_html__('Export', 'customer-management'); ?>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Table -->
    <table id="customers-table" class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Name', 'customer-management'); ?></th>
                <th><?php echo esc_html__('Email', 'customer-management'); ?></th>
                <th><?php echo esc_html__('Phone', 'customer-management'); ?></th>
                <th><?php echo esc_html__('Membership', 'customer-management'); ?></th>
                <th><?php echo esc_html__('Branch', 'customer-management'); ?></th>
                <th><?php echo esc_html__('Employee', 'customer-management'); ?></th>
                <th><?php echo esc_html__('Actions', 'customer-management'); ?></th>
            </tr>
        </thead>
        <tbody>
            <!-- DataTables will populate this -->
        </tbody>
    </table>
</div>


