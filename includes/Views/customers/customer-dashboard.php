<?php
defined('ABSPATH') || exit;
?>


    <div class="dashboard-grid">
        <!-- Total Customers Card -->
        <div class="dashboard-card">
            <div class="card-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="card-content">
                <h3><?php echo esc_html__('Total Customers', 'customer-management'); ?></h3>
                <div class="card-value" id="total-customers">0</div>
            </div>
        </div>

        <!-- Membership Distribution Card -->
        <div class="dashboard-card">
            <div class="card-icon">
                <span class="dashicons dashicons-awards"></span>
            </div>
            <div class="card-content">
                <h3><?php echo esc_html__('Membership Types', 'customer-management'); ?></h3>
                <div class="membership-stats">
                    <div class="stat-item">
                        <span class="membership-badge membership-regular">Reguler</span>
                        <span class="stat-value" id="regular-count">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="membership-badge membership-priority">Prioritas</span>
                        <span class="stat-value" id="priority-count">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="membership-badge membership-utama">Utama</span>
                        <span class="stat-value" id="utama-count">0</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Card -->
        <div class="dashboard-card">
            <div class="card-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="card-content">
                <h3><?php echo esc_html__('Recent Activities', 'customer-management'); ?></h3>
                <div id="recent-activities" class="recent-activities-list">
                    <!-- Will be populated via JS -->
                </div>
            </div>
        </div>

        <!-- Branch Distribution Card -->
        <div class="dashboard-card">
            <div class="card-icon">
                <span class="dashicons dashicons-building"></span>
            </div>
            <div class="card-content">
                <h3><?php echo esc_html__('Customers by Branch', 'customer-management'); ?></h3>
                <div id="branch-distribution" class="branch-stats">
                    <!-- Will be populated via JS -->
                </div>
            </div>
        </div>
    </div>
