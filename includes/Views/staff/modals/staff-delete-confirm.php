<?php
defined('ABSPATH') || exit;
?>

<div id="delete-staff-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">
                <?php echo esc_html__('Delete Staff', 'customer-management'); ?>
            </h2>
            <button type="button" class="modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>

        <div class="modal-body">
            <p><?php echo esc_html__('Are you sure you want to delete this staff member? All assigned customers will be unassigned.', 'customer-management'); ?></p>
            <p class="staff-name-display"></p>
            <p class="customer-count-warning"></p>
            
            <form id="delete-staff-form">
                <input type="hidden" name="action" value="delete_employee">
                <input type="hidden" name="id" value="">
                <?php wp_nonce_field('customer_management_nonce', 'nonce'); ?>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="button" data-dismiss="modal">
                <?php echo esc_html__('Cancel', 'customer-management'); ?>
            </button>
            <button type="button" class="button button-danger confirm-delete-staff">
                <?php echo esc_html__('Delete Staff', 'customer-management'); ?>
            </button>
        </div>
    </div>
</div>
