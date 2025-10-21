/**
 * Customer Form Auto-Format
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/customer-form-auto-format.js
 *
 * Description: Unified auto-format handler untuk NPWP dan NIB.
 *              Digunakan oleh self-register dan admin-create forms.
 *              Real-time formatting saat user mengetik.
 *              Single source of truth untuk formatting logic.
 *
 * Features:
 * - NPWP auto-format: XX.XXX.XXX.X-XXX.XXX
 * - NIB auto-format: 13 digits only
 * - Real-time validation feedback
 * - Supports multiple forms on same page
 *
 * Dependencies:
 * - jQuery
 *
 * Changelog:
 * 1.0.0 - 2025-01-21 (Task-2165 Form Sync)
 * - Initial version
 * - NPWP auto-formatter with pattern XX.XXX.XXX.X-XXX.XXX
 * - NIB auto-formatter with 13 digits limit
 * - Unified handler for both forms
 */

(function($) {
    'use strict';

    /**
     * Format NPWP to XX.XXX.XXX.X-XXX.XXX
     *
     * @param {string} value Raw input value
     * @return {string} Formatted NPWP
     */
    function formatNPWP(value) {
        // Remove all non-digits
        const numbers = value.replace(/\D/g, '');

        // Limit to 15 digits
        const limited = numbers.substring(0, 15);

        // Format based on length
        let formatted = '';

        if (limited.length > 0) {
            formatted = limited.substring(0, 2);
        }
        if (limited.length > 2) {
            formatted += '.' + limited.substring(2, 5);
        }
        if (limited.length > 5) {
            formatted += '.' + limited.substring(5, 8);
        }
        if (limited.length > 8) {
            formatted += '.' + limited.substring(8, 9);
        }
        if (limited.length > 9) {
            formatted += '-' + limited.substring(9, 12);
        }
        if (limited.length > 12) {
            formatted += '.' + limited.substring(12, 15);
        }

        return formatted;
    }

    /**
     * Format NIB to 13 digits only
     *
     * @param {string} value Raw input value
     * @return {string} Formatted NIB
     */
    function formatNIB(value) {
        // Remove all non-digits and limit to 13
        return value.replace(/\D/g, '').substring(0, 13);
    }

    /**
     * Validate NPWP format
     *
     * @param {string} value NPWP value
     * @return {boolean} True if valid
     */
    function isValidNPWP(value) {
        const pattern = /^\d{2}\.\d{3}\.\d{3}\.\d{1}\-\d{3}\.\d{3}$/;
        return pattern.test(value);
    }

    /**
     * Validate NIB format
     *
     * @param {string} value NIB value
     * @return {boolean} True if valid
     */
    function isValidNIB(value) {
        const pattern = /^\d{13}$/;
        return pattern.test(value);
    }

    /**
     * Add validation feedback to input
     *
     * @param {jQuery} $input Input element
     * @param {boolean} isValid Validation status
     */
    function addValidationFeedback($input, isValid) {
        if (isValid) {
            $input.removeClass('invalid').addClass('valid');
        } else {
            $input.removeClass('valid').addClass('invalid');
        }
    }

    /**
     * Initialize auto-format handlers
     */
    function initAutoFormat() {
        // NPWP Auto-Format
        $(document).on('input', '.npwp-input, input[data-auto-format="npwp"]', function() {
            const $this = $(this);
            const cursorPos = this.selectionStart;
            const oldValue = $this.val();
            const oldLength = oldValue.length;

            // Format the value
            const formatted = formatNPWP(oldValue);
            $this.val(formatted);

            // Calculate new cursor position
            const newLength = formatted.length;
            const diff = newLength - oldLength;
            const newCursorPos = cursorPos + diff;

            // Restore cursor position (adjust for added separators)
            if (diff > 0 && cursorPos < newLength) {
                this.setSelectionRange(newCursorPos, newCursorPos);
            }

            // Validate on blur
            if (formatted.length > 0) {
                addValidationFeedback($this, isValidNPWP(formatted));
            }
        });

        // NPWP Validation on blur
        $(document).on('blur', '.npwp-input, input[data-auto-format="npwp"]', function() {
            const $this = $(this);
            const value = $this.val();

            if (value.length > 0) {
                const valid = isValidNPWP(value);
                addValidationFeedback($this, valid);

                if (!valid) {
                    $this.attr('title', 'Format NPWP: XX.XXX.XXX.X-XXX.XXX');
                } else {
                    $this.removeAttr('title');
                }
            }
        });

        // NIB Auto-Format
        $(document).on('input', '.nib-input, input[data-auto-format="nib"]', function() {
            const $this = $(this);
            const formatted = formatNIB($this.val());
            $this.val(formatted);

            // Validate
            if (formatted.length > 0) {
                addValidationFeedback($this, isValidNIB(formatted));
            }
        });

        // NIB Validation on blur
        $(document).on('blur', '.nib-input, input[data-auto-format="nib"]', function() {
            const $this = $(this);
            const value = $this.val();

            if (value.length > 0) {
                const valid = isValidNIB(value);
                addValidationFeedback($this, valid);

                if (!valid) {
                    $this.attr('title', 'NIB harus 13 digit');
                } else {
                    $this.removeAttr('title');
                }
            }
        });

        // Remove validation class on focus
        $(document).on('focus', '.npwp-input, .nib-input, input[data-auto-format="npwp"], input[data-auto-format="nib"]', function() {
            $(this).removeClass('valid invalid');
        });
    }

    // Initialize on document ready
    $(document).ready(function() {
        initAutoFormat();
        console.log('Customer form auto-format initialized');
    });

})(jQuery);
