/**
 * Customer Modal Handler V2
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Customer
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/customer/customer-modal-handler.js
 *
 * Description: Handles modal CRUD operations for Customer V2.
 *              Uses centralized modal system from wp-app-core.
 *              Currently only handles Add New Customer.
 *
 * Dependencies:
 * - jQuery
 * - WPModal (from wp-modal)
 * - wpAppCoreCustomer localized object
 *
 * Changelog:
 * 1.0.0 - 2025-11-01
 * - Initial version for Customer V2
 * - Add customer modal implementation
 */

(function($) {
    'use strict';

    /**
     * Customer Modal Handler
     */
    const CustomerModalHandler = {

        /**
         * Initialize modal handlers
         */
        init() {
            console.log('[CustomerModal] Initializing...');
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents() {
            // Add New Customer button
            $(document).on('click', '.customer-add-btn', (e) => {
                e.preventDefault();
                console.log('[CustomerModal] Add button clicked');
                this.showAddModal();
            });

            // Edit Customer button
            $(document).on('click', '.customer-edit-btn', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Prevent row click
                const customerId = $(e.currentTarget).data('id');
                console.log('[CustomerModal] Edit button clicked for customer:', customerId);
                this.showEditModal(customerId);
            });

            // Delete Customer button
            $(document).on('click', '.customer-delete-btn', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Prevent row click
                const customerId = $(e.currentTarget).data('id');
                console.log('[CustomerModal] Delete button clicked for customer:', customerId);
                this.showDeleteConfirm(customerId);
            });

            console.log('[CustomerModal] Events bound');
        },

        /**
         * Show Add Customer Modal
         */
        showAddModal() {
            console.log('[CustomerModal] Opening add customer modal...');

            // Check if WPModal is available
            if (typeof WPModal === 'undefined') {
                console.error('[CustomerModal] WPModal not found!');
                alert('Modal system not available. Please refresh the page.');
                return;
            }

            // Show modal with form
            WPModal.show({
                type: 'form',
                title: 'Add New Customer',
                size: 'large',
                bodyUrl: wpAppCoreCustomer.ajaxurl + '?action=get_customer_form&mode=create&nonce=' + wpAppCoreCustomer.nonce,
                buttons: {
                    cancel: {
                        label: 'Cancel',
                        class: 'button'
                    },
                    submit: {
                        label: 'Save Customer',
                        class: 'button button-primary',
                        type: 'submit'
                    }
                },
                onSubmit: (formData, $form) => {
                    return this.handleSave(formData, $form);
                },
                onLoad: () => {
                    // Attach real-time validation after form loaded
                    this.attachRealtimeValidation();
                }
            });
        },

        /**
         * Show Edit Customer Modal
         *
         * @param {number} customerId Customer ID to edit
         */
        showEditModal(customerId) {
            console.log('[CustomerModal] Opening edit customer modal for ID:', customerId);

            // Check if WPModal is available
            if (typeof WPModal === 'undefined') {
                console.error('[CustomerModal] WPModal not found!');
                alert('Modal system not available. Please refresh the page.');
                return;
            }

            // Show modal with form (mode=edit)
            WPModal.show({
                type: 'form',
                title: 'Edit Customer',
                size: 'large',
                bodyUrl: wpAppCoreCustomer.ajaxurl + '?action=get_customer_form&mode=edit&customer_id=' + customerId + '&nonce=' + wpAppCoreCustomer.nonce,
                buttons: {
                    cancel: {
                        label: 'Cancel',
                        class: 'button'
                    },
                    submit: {
                        label: 'Update Customer',
                        class: 'button button-primary',
                        type: 'submit'
                    }
                },
                onSubmit: (formData, $form) => {
                    return this.handleSave(formData, $form);
                },
                onLoad: () => {
                    // Attach real-time validation after form loaded
                    this.attachRealtimeValidation();
                }
            });
        },

        /**
         * Show Delete Confirmation
         *
         * @param {number} customerId Customer ID to delete
         */
        showDeleteConfirm(customerId) {
            console.log('[CustomerModal] Showing delete confirm for customer ID:', customerId);

            // Check if WPModal is available
            if (typeof WPModal === 'undefined') {
                console.error('[CustomerModal] WPModal not found!');
                alert('Modal system not available. Please refresh the page.');
                return;
            }

            WPModal.confirm({
                title: 'Delete Customer',
                message: 'Are you sure you want to delete this customer? This action cannot be undone.',
                confirmText: 'Delete',
                confirmClass: 'button-danger',
                onConfirm: () => {
                    this.handleDelete(customerId);
                }
            });
        },

        /**
         * Attach real-time validation to form fields
         * Validates on input (as user types)
         */
        attachRealtimeValidation() {
            console.log('[CustomerModal] Attaching real-time validation...');

            // NPWP: Allow digits, dots, and dashes only
            $(document).off('input', '#customer-npwp').on('input', '#customer-npwp', (e) => {
                const $field = $(e.target);
                let value = $field.val();
                const $wrapper = $field.closest('.wpapp-form-field');

                // Filter: only allow digits, dots, and dashes
                const filtered = value.replace(/[^0-9.-]/g, '');
                if (value !== filtered) {
                    $field.val(filtered);
                    value = filtered;
                }

                // Clear previous error
                $wrapper.removeClass('has-error');
                $wrapper.find('.wpapp-field-error').remove();
                $field.css('border-color', '');

                // If not empty, validate format
                if (value.trim() !== '') {
                    const digits = value.replace(/\D/g, '');
                    if (digits.length > 0 && digits.length !== 15) {
                        this.showFieldError('#customer-npwp', 'NPWP must be 15 digits (e.g., 12.345.678.9-012.000)');
                    }
                }
            });

            // NIB: Allow digits only
            $(document).off('input', '#customer-nib').on('input', '#customer-nib', (e) => {
                const $field = $(e.target);
                let value = $field.val();
                const $wrapper = $field.closest('.wpapp-form-field');

                // Filter: only allow digits
                const filtered = value.replace(/\D/g, '');
                if (value !== filtered) {
                    $field.val(filtered);
                    value = filtered;
                }

                // Clear previous error
                $wrapper.removeClass('has-error');
                $wrapper.find('.wpapp-field-error').remove();
                $field.css('border-color', '');

                // If not empty, validate format
                if (value.trim() !== '') {
                    if (value.length !== 13) {
                        this.showFieldError('#customer-nib', 'NIB must be 13 digits');
                    }
                }
            });

            console.log('[CustomerModal] Real-time validation attached');
        },

        /**
         * Handle form save (create/update)
         *
         * @param {Object} formData Form data
         * @param {jQuery} $form Form element
         * @return {boolean} false to prevent default
         */
        handleSave(formData, $form) {
            console.log('[CustomerModal] Saving customer...');
            console.log('[CustomerModal] Received formData:', formData);
            console.log('[CustomerModal] Received $form:', $form);

            // Remove any existing error messages
            $('.wpapp-modal-error').remove();
            $('.wpapp-field-error').remove();
            $('.wpapp-form-field').removeClass('has-error');

            // Validate form first
            if (!this.validateForm($form)) {
                console.log('[CustomerModal] Form validation failed');
                return false;
            }

            // Create FormData from form element if not already FormData
            if (!(formData instanceof FormData)) {
                console.log('[CustomerModal] Creating new FormData from form');
                formData = new FormData($form[0]);
            }

            // Debug: Log FormData contents
            console.log('[CustomerModal] Final FormData contents:');
            for (let pair of formData.entries()) {
                console.log('  ' + pair[0] + ': ' + pair[1]);
            }

            // Show loading
            WPModal.loading(true);

            // Submit via AJAX
            $.ajax({
                url: wpAppCoreCustomer.ajaxurl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        console.log('[CustomerModal] Save successful:', response);

                        // Get customer ID from response
                        const customerId = response.data.customer ? response.data.customer.id : null;
                        console.log('[CustomerModal] Customer ID from response:', customerId);

                        // Stop loading FIRST
                        WPModal.loading(false);

                        // Hide modal immediately
                        WPModal.hide();

                        // Small delay before showing notification to ensure modal is fully closed
                        setTimeout(() => {
                            // Show success notification
                            WPModal.info({
                                infoType: 'success',
                                title: 'Success',
                                message: response.data.message || 'Customer saved successfully',
                                autoClose: 3000
                            });
                        }, 300);

                        // Refresh DataTable immediately
                        if (window.CustomerDataTable && window.CustomerDataTable.refresh) {
                            console.log('[CustomerModal] Refreshing DataTable...');
                            window.CustomerDataTable.refresh();
                        } else {
                            console.error('[CustomerModal] CustomerDataTable.refresh not available!');
                        }

                        // If panel is open and customer ID is available, refresh panel to show edited customer
                        const panelIsOpen = window.wpAppPanelManager && window.wpAppPanelManager.isOpen;
                        console.log('[CustomerModal] Panel open check:', panelIsOpen);

                        if (customerId && panelIsOpen) {
                            console.log('[CustomerModal] Panel is open, will update to customer:', customerId);
                            setTimeout(() => {
                                console.log('[CustomerModal] Updating hash to #customer-' + customerId);
                                window.location.hash = '#customer-' + customerId;
                            }, 400);
                        } else if (customerId) {
                            console.log('[CustomerModal] Panel not open (or panel manager not available), customer ID:', customerId);
                        }

                        // Reload statistics with delay to ensure DB has been updated
                        setTimeout(() => {
                            if (window.CustomerDataTable && window.CustomerDataTable.loadStatistics) {
                                console.log('[CustomerModal] Reloading statistics (delayed 500ms)...');
                                window.CustomerDataTable.loadStatistics();
                            } else {
                                console.error('[CustomerModal] CustomerDataTable.loadStatistics not available!');
                            }
                        }, 500);

                    } else {
                        console.error('[CustomerModal] Save failed:', response);

                        // Stop loading
                        WPModal.loading(false);

                        // DON'T close modal - show error message inside modal
                        this.showErrorInModal(response.data.message || 'Failed to save customer');
                    }
                },
                error: (xhr, status, error) => {
                    WPModal.loading(false);
                    console.error('[CustomerModal] AJAX error:', error);
                    console.error('[CustomerModal] Status:', status);
                    console.error('[CustomerModal] XHR status:', xhr.status);
                    console.error('[CustomerModal] XHR responseText (first 500 chars):', xhr.responseText.substring(0, 500));
                    console.error('[CustomerModal] XHR responseJSON:', xhr.responseJSON);

                    // DON'T close modal - show error message inside modal
                    let errorMessage = 'Network error. Please try again.';

                    // Try to get more specific error from response
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMessage = xhr.responseJSON.data.message;
                    } else if (xhr.status === 400) {
                        errorMessage = 'Bad Request (400): Invalid form data or missing required fields.';
                        if (xhr.responseText) {
                            console.error('[CustomerModal] Full response text:', xhr.responseText);
                            errorMessage += ' Check console for details.';
                        }
                    } else if (xhr.responseText) {
                        errorMessage = 'Server error: ' + error;
                    }

                    this.showErrorInModal(errorMessage);
                }
            });

            return false; // Prevent default form submission
        },

        /**
         * Show error message inside modal (without closing it)
         *
         * @param {string} message Error message to display
         */
        showErrorInModal(message) {
            // Remove any existing error messages
            $('.wpapp-modal-error').remove();

            // Create error message element
            const $errorDiv = $('<div class="wpapp-modal-error" style="' +
                'background: #dc3232; ' +
                'color: white; ' +
                'padding: 12px 15px; ' +
                'margin: 0 0 15px 0; ' +
                'border-radius: 4px; ' +
                'font-size: 14px; ' +
                'line-height: 1.5;">' +
                '<strong>Error:</strong> ' + message +
                '</div>');

            // Insert error message at top of modal body
            $('.wpapp-modal-body').prepend($errorDiv);

            // Scroll to top of modal to show error
            $('.wpapp-modal-body').scrollTop(0);

            // Auto-remove after 10 seconds
            setTimeout(function() {
                $errorDiv.fadeOut(400, function() {
                    $(this).remove();
                });
            }, 10000);
        },

        /**
         * Validate form before submit
         *
         * @param {jQuery} $form Form element
         * @return {boolean} True if valid, false if invalid
         */
        validateForm($form) {
            let isValid = true;
            const errors = [];

            // Customer Name (required)
            const customerName = $form.find('#customer-name').val();
            if (!customerName || customerName.trim() === '') {
                this.showFieldError('#customer-name', 'Customer name is required');
                errors.push('Customer name is required');
                isValid = false;
            }

            // NPWP (optional, but if filled must be valid format)
            const npwp = $form.find('#customer-npwp').val();
            if (npwp && npwp.trim() !== '') {
                // Remove all non-digit characters for length check
                const npwpDigits = npwp.replace(/\D/g, '');
                if (npwpDigits.length !== 15) {
                    this.showFieldError('#customer-npwp', 'NPWP must be 15 digits (e.g., 12.345.678.9-012.000)');
                    errors.push('Invalid NPWP format');
                    isValid = false;
                }
            }

            // NIB (optional, but if filled must be valid format)
            const nib = $form.find('#customer-nib').val();
            if (nib && nib.trim() !== '') {
                // Remove all non-digit characters for length check
                const nibDigits = nib.replace(/\D/g, '');
                if (nibDigits.length !== 13) {
                    this.showFieldError('#customer-nib', 'NIB must be 13 digits');
                    errors.push('Invalid NIB format');
                    isValid = false;
                }
            }

            // Province validation (required)
            const provinsiId = $form.find('#customer-provinsi').val();
            if (!provinsiId || provinsiId === '') {
                this.showFieldError('#customer-provinsi', 'Province is required');
                errors.push('Province is required');
                isValid = false;
            }

            // Regency validation (required)
            const regencyId = $form.find('#customer-regency').val();
            if (!regencyId || regencyId === '') {
                this.showFieldError('#customer-regency', 'City/Regency is required');
                errors.push('City/Regency is required');
                isValid = false;
            }

            // Admin Name (required for create mode)
            const mode = $form.find('input[name="mode"]').val();
            if (mode === 'create') {
                const adminName = $form.find('#admin-name').val();
                if (!adminName || adminName.trim() === '') {
                    this.showFieldError('#admin-name', 'Admin name is required');
                    errors.push('Admin name is required');
                    isValid = false;
                }

                // Admin Email (required and valid format)
                const adminEmail = $form.find('#admin-email').val();
                if (!adminEmail || adminEmail.trim() === '') {
                    this.showFieldError('#admin-email', 'Admin email is required');
                    errors.push('Admin email is required');
                    isValid = false;
                } else if (!this.isValidEmail(adminEmail)) {
                    this.showFieldError('#admin-email', 'Please enter a valid email address');
                    errors.push('Invalid email format');
                    isValid = false;
                }
            }

            // Show summary error if there are errors
            if (!isValid) {
                const errorMessage = 'Please fix the following errors:<br>• ' + errors.join('<br>• ');
                this.showErrorInModal(errorMessage);
            }

            return isValid;
        },

        /**
         * Show error message for specific field
         *
         * @param {string} fieldSelector Field selector
         * @param {string} message Error message
         */
        showFieldError(fieldSelector, message) {
            const $field = $(fieldSelector);
            const $wrapper = $field.closest('.wpapp-form-field');

            // Add error class to wrapper
            $wrapper.addClass('has-error');

            // Remove existing error message
            $wrapper.find('.wpapp-field-error').remove();

            // Add error message below field
            const $errorMsg = $('<span class="wpapp-field-error" style="' +
                'color: #dc3232; ' +
                'font-size: 12px; ' +
                'display: block; ' +
                'margin-top: 4px;">' +
                message +
                '</span>');

            $field.after($errorMsg);

            // Add red border to field
            $field.css('border-color', '#dc3232');

            // Remove error on input
            $field.one('input change', function() {
                $wrapper.removeClass('has-error');
                $wrapper.find('.wpapp-field-error').remove();
                $(this).css('border-color', '');
            });
        },

        /**
         * Handle customer deletion
         *
         * @param {number} customerId Customer ID to delete
         */
        handleDelete(customerId) {
            console.log('[CustomerModal] Deleting customer ID:', customerId);

            // Show loading
            WPModal.loading(true, 'Deleting customer...');

            $.ajax({
                url: wpAppCoreCustomer.ajaxurl,
                method: 'POST',
                data: {
                    action: 'delete_customer',
                    customer_id: customerId,
                    nonce: wpAppCoreCustomer.nonce
                },
                success: (response) => {
                    WPModal.loading(false);

                    if (response.success) {
                        console.log('[CustomerModal] Delete successful:', response);

                        // Show success notification
                        WPModal.info({
                            infoType: 'success',
                            title: 'Success',
                            message: response.data.message || 'Customer deleted successfully',
                            autoClose: 3000
                        });

                        // Refresh DataTable
                        if (window.CustomerDataTable && window.CustomerDataTable.refresh) {
                            console.log('[CustomerModal] Refreshing DataTable...');
                            window.CustomerDataTable.refresh();
                        }

                        // Reload statistics with delay
                        setTimeout(() => {
                            if (window.CustomerDataTable && window.CustomerDataTable.loadStatistics) {
                                console.log('[CustomerModal] Reloading statistics...');
                                window.CustomerDataTable.loadStatistics();
                            }
                        }, 500);
                    } else {
                        console.error('[CustomerModal] Delete failed:', response);
                        WPModal.info({
                            infoType: 'error',
                            title: 'Error',
                            message: response.data.message || 'Failed to delete customer',
                            autoClose: 5000
                        });
                    }
                },
                error: (xhr, status, error) => {
                    WPModal.loading(false);
                    console.error('[CustomerModal] Delete AJAX error:', error);

                    WPModal.info({
                        infoType: 'error',
                        title: 'Error',
                        message: 'Network error. Please try again.',
                        autoClose: 5000
                    });
                }
            });
        },

        /**
         * Validate email format
         *
         * @param {string} email Email address
         * @return {boolean} True if valid format
         */
        isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        console.log('[CustomerModal] Document ready');
        CustomerModalHandler.init();
    });

    // Export to global scope
    window.CustomerModalHandler = CustomerModalHandler;

})(jQuery);
