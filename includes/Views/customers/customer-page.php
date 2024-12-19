<?php
defined('ABSPATH') || exit;
?>

<div class="wrap content">
    <h1 class="wp-heading-inline">
        <?php echo esc_html__('Customer Management', 'customer-management'); ?>
    </h1>
    
    <?php if (current_user_can('create_customers')): ?>
    <a href="#" class="page-title-action add-new-customer">
        <?php echo esc_html__('Add New Customer', 'customer-management'); ?>
    </a>
    <?php endif; ?>

    <hr class="wp-header-end">
    <div class="dashboard-section">
        <!-- Dashboard Section - Always visible -->
        <?php require_once(__DIR__ . '/customer-dashboard.php'); ?>
    </div>

    <!-- Main Content Panels -->
    <div class="customer-section customer-panels" id="customerPanels">
        <!-- Left Panel with Table -->
        <div class="left-panel">
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select id="membership-filter">
                        <option value=""><?php echo esc_html__('All Memberships', 'customer-management'); ?></option>
                        <option value="regular"><?php echo esc_html__('Regular', 'customer-management'); ?></option>
                        <option value="priority"><?php echo esc_html__('Priority', 'customer-management'); ?></option>
                        <option value="utama"><?php echo esc_html__('Utama', 'customer-management'); ?></option>
                    </select>

                    <select id="branch-filter">
                        <option value=""><?php echo esc_html__('All Branches', 'customer-management'); ?></option>
                        <!-- Will be populated dynamically -->
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

        <!-- Right Panel -->
        <div class="right-panel" id="customerDetailPanel">
            <div class="right-panel-header">
                <h2><?php echo esc_html__('Customer Details', 'customer-management'); ?> : <span class="customer-name"></span></h2>
                <button type="button" class="close-panel">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>

            <div class="right-panel-content">
                <!-- Tabs Navigation -->
                <div class="right-panel-tabs">
                    <ul class="nav-tab-wrapper">
                        <li>
                            <a href="#tab-info" class="nav-tab nav-tab-active" data-tab="info">
                                <?php echo esc_html__('Info', 'customer-management'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="#tab-activity" class="nav-tab" data-tab="activity">
                                <?php echo esc_html__('Activity', 'customer-management'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="#tab-notes" class="nav-tab" data-tab="notes">
                                <?php echo esc_html__('Notes', 'customer-management'); ?>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Tabs Content -->
                <div class="tab-content">
                    <!-- Info Tab - Loaded by default -->
                    <div id="tab-info" class="tab-pane active">
                        <div class="customer-info">
                            <div class="info-header">
                                <span class="customer-name"></span>
                                <span class="membership-badge"></span>
                            </div>

                            <!-- Other info sections remain the same -->
                            <?php require_once(__DIR__ . '/tabs/customer-info-tab.php'); ?>
                        </div>
                    </div>

                    <!-- Activity Tab - Loaded on demand -->
                    <div id="tab-activity" class="tab-pane">
                        <div class="loading-placeholder">
                            <?php echo esc_html__('Loading activity data...', 'customer-management'); ?>
                        </div>
                    </div>

                    <!-- Notes Tab - Loaded on demand -->
                    <div id="tab-notes" class="tab-pane">
                        <div class="loading-placeholder">
                            <?php echo esc_html__('Loading notes...', 'customer-management'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<?php require_once(__DIR__ . '/modals/customer-add-edit.php'); ?>
<?php require_once(__DIR__ . '/modals/customer-delete-confirm.php'); ?>