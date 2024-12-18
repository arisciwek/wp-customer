<?php
defined('ABSPATH') || exit;
?>

<div id="customer-right-panel" class="right-panel">
    <div class="right-panel-header">
        <h2><?php echo esc_html__('Customer Details', 'customer-management'); ?></h2>
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
                    <a href="#tab-activity" class="nav-tab">
                        <?php echo esc_html__('Activity', 'customer-management'); ?>
                    </a>
                </li>
                <li>
                    <a href="#tab-notes" class="nav-tab">
                        <?php echo esc_html__('Notes', 'customer-management'); ?>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Tab Contents -->
        <div class="tab-content">
            <!-- Info Tab -->
            <div id="tab-info" class="tab-pane active">
                <div class="customer-info">
                    <div class="info-header">
                        <span class="customer-name"></span>
                        <span class="membership-badge"></span>
                    </div>

                    <div class="info-section">
                        <h3><?php echo esc_html__('Contact Information', 'customer-management'); ?></h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label><?php echo esc_html__('Email:', 'customer-management'); ?></label>
                                <span class="customer-email"></span>
                            </div>
                            <div class="info-item">
                                <label><?php echo esc_html__('Phone:', 'customer-management'); ?></label>
                                <span class="customer-phone"></span>
                            </div>
                            <div class="info-item full-width">
                                <label><?php echo esc_html__('Address:', 'customer-management'); ?></label>
                                <span class="customer-address"></span>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <h3><?php echo esc_html__('Assignment', 'customer-management'); ?></h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label><?php echo esc_html__('Branch:', 'customer-management'); ?></label>
                                <span class="customer-branch"></span>
                            </div>
                            <div class="info-item">
                                <label><?php echo esc_html__('Employee:', 'customer-management'); ?></label>
                                <span class="customer-employee"></span>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <h3><?php echo esc_html__('Location', 'customer-management'); ?></h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label><?php echo esc_html__('Province:', 'customer-management'); ?></label>
                                <span class="customer-province"></span>
                            </div>
                            <div class="info-item">
                                <label><?php echo esc_html__('City/Regency:', 'customer-management'); ?></label>
                                <span class="customer-city"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Tab -->
            <div id="tab-activity" class="tab-pane">
                <div class="activity-list">
                    <!-- Activities will be loaded here -->
                </div>
            </div>

            <!-- Notes Tab -->
            <div id="tab-notes" class="tab-pane">
                <div class="notes-section">
                    <div class="add-note">
                        <textarea id="new-note" rows="3" placeholder="<?php echo esc_attr__('Add a note...', 'customer-management'); ?>"></textarea>
                        <button class="button add-note-button">
                            <?php echo esc_html__('Add Note', 'customer-management'); ?>
                        </button>
                    </div>
                    <div class="notes-list">
                        <!-- Notes will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
