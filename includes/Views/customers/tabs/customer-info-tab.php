<?php
defined('ABSPATH') || exit;
?>

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
    <h3><?php echo esc_html__('Membership Details', 'customer-management'); ?></h3>
    <div class="info-grid">
        <div class="info-item">
            <label><?php echo esc_html__('Type:', 'customer-management'); ?></label>
            <span class="membership-type"></span>
        </div>
        <div class="info-item">
            <label><?php echo esc_html__('Since:', 'customer-management'); ?></label>
            <span class="membership-since"></span>
        </div>
        <div class="info-item">
            <label><?php echo esc_html__('Status:', 'customer-management'); ?></label>
            <span class="membership-status"></span>
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
            <label><?php echo esc_html__('Assigned To:', 'customer-management'); ?></label>
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
            <label><?php echo esc_html__('City:', 'customer-management'); ?></label>
            <span class="customer-city"></span>
        </div>
    </div>
</div>

<div class="info-section">
    <h3><?php echo esc_html__('Additional Information', 'customer-management'); ?></h3>
    <div class="info-grid">
        <div class="info-item">
            <label><?php echo esc_html__('Created By:', 'customer-management'); ?></label>
            <span class="customer-created-by"></span>
        </div>
        <div class="info-item">
            <label><?php echo esc_html__('Created At:', 'customer-management'); ?></label>
            <span class="customer-created-at"></span>
        </div>
        <div class="info-item">
            <label><?php echo esc_html__('Last Updated:', 'customer-management'); ?></label>
            <span class="customer-updated-at"></span>
        </div>
    </div>
</div>

<?php if (current_user_can('edit_customers')): ?>
<div class="info-section actions">
    <button type="button" class="button button-primary edit-customer-btn">
        <?php echo esc_html__('Edit Customer', 'customer-management'); ?>
    </button>
    
    <?php if (current_user_can('delete_customers')): ?>
    <button type="button" class="button button-danger delete-customer-btn">
        <?php echo esc_html__('Delete Customer', 'customer-management'); ?>
    </button>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Custom fields section if needed -->
<div id="customer-custom-fields" class="info-section custom-fields">
    <!-- Will be populated dynamically if custom fields exist -->
</div>
