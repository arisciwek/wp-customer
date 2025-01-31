/**
 * Modal Component Handler
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Components
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/customer/confirmation-modal.js
 *
 * Description: JavaScript handler untuk reusable modal component.
 *              Menangani show/hide, animasi, keyboard events,
 *              dan callback functions.
 *
 * API Usage:
 * WIModal.show({
 *   title: 'Konfirmasi',
 *   message: 'Yakin ingin melanjutkan?',
 *   icon: 'warning',
 *   type: 'danger',
 *   onConfirm: () => {},
 *   onCancel: () => {}
 * });
 *
 * Configuration Options:
 * - title: string            - Modal title
 * - message: string         - Modal message
 * - icon: string           - Icon type (warning/error/info/success)
 * - iconColor: string      - Custom icon color
 * - type: string          - Modal type (affects styling)
 * - size: string          - Modal size (small/medium/large)
 * - closeOnEsc: boolean   - Enable Esc to close
 * - closeOnClickOutside: boolean - Enable click outside to close
 * - buttons: object       - Custom button configuration
 *
 * Dependencies:
 * - jQuery 3.6+
 *
 * Changelog:
 * 1.0.0 - 2024-12-07
 * - Initial implementation
 * - Added core modal functionality
 * - Added accessibility features
 */

const WIModal = {
    modal: null,
    options: null,
    elements: {},

    init() {
        this.modal = document.getElementById('confirmation-modal');
        if (!this.modal) {
            console.error('Modal element not found');
            return false;
        }

        // Cache DOM elements
        this.elements = {
            title: this.modal.querySelector('#modal-title'),
            message: this.modal.querySelector('#modal-message'),
            icon: this.modal.querySelector('.modal-icon'),
            confirmBtn: this.modal.querySelector('#modal-confirm-btn'),
            cancelBtn: this.modal.querySelector('#modal-cancel-btn'),
            modalDialog: this.modal.querySelector('.modal'),
            closeButtons: this.modal.querySelectorAll('[data-dismiss="modal"]')
        };

        this.bindEvents();
        return true;
    },

    bindEvents() {
        if (!this.modal) return;

        // Close button clicks
        if (this.elements.closeButtons) {
            this.elements.closeButtons.forEach(button => {
                button.addEventListener('click', () => this.hide());
            });
        }

        // ESC key press
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.options?.closeOnEsc) {
                this.hide();
            }
        });

        // Click outside
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal && this.options?.closeOnClickOutside) {
                this.hide();
            }
        });
    },

    setContent(options) {
        if (!this.elements.title || !this.elements.message) {
            console.error('Required modal elements not found');
            return false;
        }

        // Safely set text content
        if (this.elements.title) {
            this.elements.title.textContent = options.title || '';
        }

        if (this.elements.message) {
            this.elements.message.textContent = options.message || '';
        }

        // Set icon if provided and element exists
        if (options.icon && this.elements.icon) {
            this.elements.icon.className = `modal-icon dashicons dashicons-${options.icon}`;
        }

        // Set modal type if element exists
        if (options.type && this.elements.modalDialog) {
            this.elements.modalDialog.className = `modal type-${options.type}`;
        }

        // Handle buttons if they exist
        if (this.elements.confirmBtn) {
            this.elements.confirmBtn.textContent = options.confirmText || 'OK';
            this.elements.confirmBtn.className = `button ${options.confirmClass || ''}`;
            this.elements.confirmBtn.onclick = () => {
                if (options.onConfirm) options.onConfirm();
                this.hide();
            };
        }

        if (this.elements.cancelBtn) {
            const showCancel = options.showCancelButton !== false;
            this.elements.cancelBtn.style.display = showCancel ? 'inline-block' : 'none';
            if (showCancel) {
                this.elements.cancelBtn.textContent = options.cancelText || 'Cancel';
                this.elements.cancelBtn.className = `button ${options.cancelClass || ''}`;
                this.elements.cancelBtn.onclick = () => {
                    if (options.onCancel) options.onCancel();
                    this.hide();
                };
            }
        }

        return true;
    },

    show(options) {
        if (!this.modal) {
            console.error('Modal not initialized');
            return false;
        }

        this.options = {
            closeOnEsc: true,
            closeOnClickOutside: true,
            showCancelButton: true,
            ...options
        };

        // Try to set content
        if (!this.setContent(this.options)) {
            console.error('Failed to set modal content');
            return false;
        }

        // Show modal
        this.modal.classList.add('active');

        // Focus management
        if (this.elements.confirmBtn) {
            this.elements.confirmBtn.focus();
        }

        return true;
    },

    hide() {
        if (!this.modal) return;

        this.modal.classList.remove('active');
        this.options = null;
    }
};

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', () => {
    const initialized = WIModal.init();
    if (!initialized) {
        console.error('Failed to initialize WIModal');
    }
});

// Make it globally available
window.WIModal = WIModal;
