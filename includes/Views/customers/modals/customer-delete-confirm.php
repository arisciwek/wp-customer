<?php
defined('ABSPATH') || exit;
?>

<div id="delete-customer-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">
                <?php echo esc_html__('Delete Customer', 'customer-management'); ?>
            </h2>
            <button type="button" class="modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>

        <div class="modal-body">
            <p><?php echo esc_html__('Are you sure you want to delete this customer? This action cannot be undone.', 'customer-management'); ?></p>
            <p class="customer-name-display"></p>
            
            <form id="delete-customer-form">
                <input type="hidden" name="action" value="delete_customer">
                <input type="hidden" name="id" value="">
                <?php wp_nonce_field('customer_management_nonce', 'nonce'); ?>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="button" data-dismiss="modal">
                <?php echo esc_html__('Cancel', 'customer-management'); ?>
            </button>
            <button type="button" class="button button-danger confirm-delete-customer">
                <?php echo esc_html__('Delete Customer', 'customer-management'); ?>
            </button>
        </div>
    </div>
</div>
