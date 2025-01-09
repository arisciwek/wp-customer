<?php
/**
 * Modal Template Component
 *
 * @package     WP_Customer
 * @subpackage  Views/Components/Modal
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/components/confirmation-modal-template.php
 *
 * Description: Reusable modal template untuk berbagai keperluan konfirmasi.
 *              Supports custom icons, colors, messages, dan actions.
 *              Fully accessible dengan keyboard support dan ARIA labels.
 *              Digunakan untuk delete confirmation, reset warning, dll.
 *
 * Features:
 * - Custom icon & colors
 * - Configurable buttons & callbacks
 * - Keyboard navigation (Esc to close)
 * - Click outside to close
 * - Focus trap untuk accessibility
 *
 * Dependencies:
 * - modal.css for styling
 * - modal.js for functionality
 * - WordPress admin styles integration
 *
 * Changelog:
 * 1.0.0 - 2024-12-07
 * - Initial release
 * - Added basic modal structure
 * - Added accessibility support
 * - Added configuration options
 */


defined('ABSPATH') || exit;

if (!function_exists('wp_customer_render_confirmation_modal')) {
    function wp_customer_render_confirmation_modal() {
        ?>
        <div id="wp-customer-confirmation-modal" class="wp-customer-modal-overlay" aria-modal="true" role="dialog">
            <div class="wp-customer-modal" role="document">
                <!-- Header -->
                <div class="wp-customer-modal-header">
                    <div class="wp-customer-modal-title">
                        <span class="wp-customer-modal-icon"></span>
                        <h3 id="modal-title"></h3>
                    </div>
                    <button type="button"
                            class="wp-customer-modal-close"
                            aria-label="Close modal"
                            data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <!-- Body -->
                <div class="wp-customer-modal-body">
                    <p id="modal-message"></p>
                </div>

                <!-- Footer -->
                <div class="wp-customer-modal-footer">
                    <button type="button"
                            class="button wp-customer-confirm-btn"
                            id="modal-confirm-btn">
                    </button>
                    <button type="button"
                            class="button wp-customer-cancel-btn"
                            id="modal-cancel-btn"
                            data-dismiss="modal">
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
}
