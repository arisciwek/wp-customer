<?php
defined('ABSPATH') || exit;
?>

<div id="staff-right-panel" class="right-panel">
    <div class="right-panel-header">
        <h2><?php echo esc_html__('Staff Details', 'customer-management'); ?></h2>
        <button type="button" class="close-panel">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
    </div>

    <div class="right-panel-content">
        <!-- Tabs -->
        <div class="right-panel-tabs">
            <ul class="nav-tab-wrapper">
                <li>
                    <a href="#tab-info" class="nav-tab nav-tab-active">
                        <?php echo esc_html__('Info', 'customer-management'); ?>
                    </a>
                </li>
                <li>
                    <a href="#tab-customers" class="nav-tab">
                        <?php echo esc_html__('Assigned Customers', 'customer-management'); ?>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Tab Contents -->
        <div class="tab-content">
            <!-- Info Tab -->
            <div id="tab-info" class="tab-pane active">
                <div class="staff-info">
                    <div class="info-header">
                        <span class="staff-name"></span>
                        <span class="staff-position"></span>
                    </div>

                    <div class="info-section">
                        <h3><?php echo esc_html__('Overview', 'customer-management'); ?></h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label><?php echo esc_html__('Total Customers:', 'customer-management'); ?></label>
                                <span class="customer-count"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customers Tab -->
            <div id="tab-customers" class="tab-pane">
                <div class="customers-list">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Customer', 'customer-management'); ?></th>
                                <th><?php echo esc_html__('Membership', 'customer-management'); ?></th>
                                <th><?php echo esc_html__('Branch', 'customer-management'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Will be populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
