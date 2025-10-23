<?php
/**
 * Modal Template Component
 *
 * @package     WP_Customer
 * @subpackage  Views/Components/Modal
 * @version     1.0.11
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
        <div id="confirmation-modal" class="modal-overlay" aria-modal="true" role="dialog">
            <div class="modal" role="document">
                <!-- Header -->
                <div class="modal-header">
                    <div class="modal-title">
                        <span class="modal-icon"></span>
                        <h3 id="modal-title"></h3>
                    </div>
                    <button type="button"
                            class="modal-close"
                            aria-label="Close modal"
                            data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <p id="modal-message"></p>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button"
                            class="button confirm-btn"
                            id="modal-confirm-btn">
                    </button>
                    <button type="button"
                            class="button cancel-btn"
                            id="modal-cancel-btn"
                            data-dismiss="modal">
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
}
