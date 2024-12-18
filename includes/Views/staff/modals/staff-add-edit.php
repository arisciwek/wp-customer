<?php
defined('ABSPATH') || exit;
?>

<div id="staff-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">
                <?php echo esc_html__('Add New Staff', 'customer-management'); ?>
            </h2>
            <button type="button" class="modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>

        <div class="modal-body">
            <form id="staff-form">
                <input type="hidden" name="action" value="create_employee">
                <input type="hidden" name="staff_id" value="">
                <?php wp_nonce_field('customer_management_nonce', 'nonce'); ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="staff-name">
                            <?php echo esc_html__('Name', 'customer-management'); ?> <span class="required">*</span>
                        </label>
                        <input type="text" id="staff-name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="staff-position">
                            <?php echo esc_html__('Position', 'customer-management'); ?>
                        </label>
                        <input type="text" id="staff-position" name="position">
                    </div>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="button" data-dismiss="modal">
                <?php echo esc_html__('Cancel', 'customer-management'); ?>
            </button>
            <button type="button" class="button button-primary save-staff">
                <?php echo esc_html__('Save Staff', 'customer-management'); ?>
            </button>
        </div>
    </div>
</div>
