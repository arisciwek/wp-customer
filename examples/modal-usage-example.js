/**
 * WP Customer - Modal Usage Examples
 *
 * @package     WPCustomer
 * @subpackage  Examples
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/examples/modal-usage-example.js
 *
 * Description: Examples demonstrating how to use wpAppModal API
 *              for customer plugin functionality.
 *              This file is for reference only - actual implementation
 *              should be in customer-datatable.js or similar.
 *
 * Dependencies:
 * - wpapp-modal-manager.js (from wp-app-core)
 * - jQuery
 *
 * Usage Examples:
 * 1. Form Modal (Add/Edit Customer)
 * 2. Confirmation Modal (Delete Customer)
 * 3. Info Modal (Success/Error messages)
 */

(function($) {
    'use strict';

    // ============================================
    // Example 1: Add New Customer Modal
    // ============================================

    /**
     * Show Add Customer modal
     *
     * Opens a modal with a form loaded via AJAX.
     * Form content is provided by the server.
     */
    function showAddCustomerModal() {
        wpAppModal.show({
            type: 'form',
            title: 'Add New Customer',
            size: 'medium',
            bodyUrl: ajaxurl + '?action=get_customer_form&mode=create',
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
            onSubmit: function(formData, $form) {
                // Handle form submission via AJAX
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            wpAppModal.info({
                                infoType: 'success',
                                title: 'Success',
                                message: response.data.message || 'Customer saved successfully!',
                                autoClose: 3000
                            });

                            // Reload DataTable
                            if (window.wpCustomerTable) {
                                window.wpCustomerTable.ajax.reload();
                            }
                        } else {
                            // Show error
                            wpAppModal.info({
                                infoType: 'error',
                                title: 'Error',
                                message: response.data.message || 'Failed to save customer',
                                autoClose: 5000
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Save customer failed:', error);
                        wpAppModal.info({
                            infoType: 'error',
                            title: 'Error',
                            message: 'Network error. Please try again.',
                            autoClose: 5000
                        });
                    }
                });
            }
        });
    }

    // ============================================
    // Example 2: Edit Customer Modal
    // ============================================

    /**
     * Show Edit Customer modal
     *
     * @param {number} customerId Customer ID to edit
     */
    function showEditCustomerModal(customerId) {
        wpAppModal.show({
            type: 'form',
            title: 'Edit Customer',
            size: 'medium',
            bodyUrl: ajaxurl + '?action=get_customer_form&mode=edit&id=' + customerId,
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
            onSubmit: function(formData, $form) {
                // Similar to add customer
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            wpAppModal.info({
                                infoType: 'success',
                                title: 'Success',
                                message: 'Customer updated successfully!',
                                autoClose: 3000
                            });

                            // Reload DataTable and panel if open
                            if (window.wpCustomerTable) {
                                window.wpCustomerTable.ajax.reload();
                            }
                        }
                    }
                });
            }
        });
    }

    // ============================================
    // Example 3: Delete Confirmation Modal
    // ============================================

    /**
     * Show delete confirmation modal
     *
     * @param {number} customerId Customer ID to delete
     * @param {string} customerName Customer name for display
     */
    function showDeleteCustomerConfirmation(customerId, customerName) {
        wpAppModal.confirm({
            title: 'Delete Customer?',
            message: 'Are you sure you want to delete <strong>' + customerName + '</strong>? ' +
                     'This action cannot be undone and will also delete all associated data.',
            size: 'small',
            danger: true,
            buttons: {
                cancel: {
                    label: 'Cancel',
                    class: 'button'
                },
                confirm: {
                    label: 'Delete',
                    class: 'button button-primary button-danger'
                }
            },
            onConfirm: function() {
                // Perform delete via AJAX
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'delete_customer',
                        customer_id: customerId,
                        nonce: wpAppConfig.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            wpAppModal.info({
                                infoType: 'success',
                                title: 'Deleted',
                                message: 'Customer deleted successfully',
                                autoClose: 3000
                            });

                            // Reload DataTable
                            if (window.wpCustomerTable) {
                                window.wpCustomerTable.ajax.reload();
                            }
                        } else {
                            wpAppModal.info({
                                infoType: 'error',
                                title: 'Error',
                                message: response.data.message || 'Failed to delete customer'
                            });
                        }
                    }
                });
            }
        });
    }

    // ============================================
    // Example 4: Direct HTML Content Modal
    // ============================================

    /**
     * Show modal with direct HTML content
     * (no AJAX loading)
     */
    function showCustomerDetailsModal(customer) {
        var htmlContent = '<div class="customer-details">' +
            '<p><strong>Name:</strong> ' + customer.name + '</p>' +
            '<p><strong>Email:</strong> ' + customer.email + '</p>' +
            '<p><strong>Phone:</strong> ' + customer.phone + '</p>' +
            '<p><strong>Status:</strong> ' + customer.status + '</p>' +
            '</div>';

        wpAppModal.show({
            type: 'info',
            title: 'Customer Details',
            body: htmlContent,  // Direct HTML instead of bodyUrl
            size: 'medium',
            buttons: {
                close: {
                    label: 'Close',
                    class: 'button button-primary'
                }
            }
        });
    }

    // ============================================
    // Example 5: Success/Error Info Modals
    // ============================================

    /**
     * Show success message
     */
    function showSuccessMessage(message) {
        wpAppModal.info({
            infoType: 'success',
            title: 'Success',
            message: message,
            autoClose: 3000
        });
    }

    /**
     * Show error message
     */
    function showErrorMessage(message) {
        wpAppModal.info({
            infoType: 'error',
            title: 'Error',
            message: message,
            autoClose: 5000
        });
    }

    /**
     * Show warning message
     */
    function showWarningMessage(message) {
        wpAppModal.info({
            infoType: 'warning',
            title: 'Warning',
            message: message,
            autoClose: 4000
        });
    }

    // ============================================
    // Example 6: Integration with DataTable
    // ============================================

    /**
     * Example: Bind modal triggers to DataTable buttons
     */
    $(document).ready(function() {

        // Add New Customer button
        $(document).on('click', '.customer-add-btn', function(e) {
            e.preventDefault();
            showAddCustomerModal();
        });

        // Edit button in DataTable row
        $(document).on('click', '.customer-edit-btn', function(e) {
            e.preventDefault();
            var customerId = $(this).data('customer-id');
            showEditCustomerModal(customerId);
        });

        // Delete button in DataTable row
        $(document).on('click', '.customer-delete-btn', function(e) {
            e.preventDefault();
            var customerId = $(this).data('customer-id');
            var customerName = $(this).data('customer-name');
            showDeleteCustomerConfirmation(customerId, customerName);
        });

        // View details button
        $(document).on('click', '.customer-view-btn', function(e) {
            e.preventDefault();
            var customerData = $(this).data('customer');
            showCustomerDetailsModal(customerData);
        });
    });

    // ============================================
    // Export for use in other scripts
    // ============================================

    window.customerModalHelpers = {
        showAdd: showAddCustomerModal,
        showEdit: showEditCustomerModal,
        showDelete: showDeleteCustomerConfirmation,
        showDetails: showCustomerDetailsModal,
        showSuccess: showSuccessMessage,
        showError: showErrorMessage,
        showWarning: showWarningMessage
    };

})(jQuery);

/**
 * SERVER-SIDE EXAMPLE (PHP)
 * ===========================
 *
 * Handler for AJAX form content loading:
 *
 * ```php
 * // In CustomerDashboardController.php
 *
 * public function handle_get_customer_form() {
 *     // Verify nonce
 *     check_ajax_referer('customer_nonce', 'nonce');
 *
 *     $mode = $_GET['mode'] ?? 'create';
 *     $customer_id = $_GET['id'] ?? 0;
 *
 *     // Load form template
 *     if ($mode === 'edit' && $customer_id) {
 *         // Get customer data
 *         $customer = CustomerModel::get_by_id($customer_id);
 *         include WP_CUSTOMER_PATH . 'src/Views/customer/forms/edit-customer-form.php';
 *     } else {
 *         include WP_CUSTOMER_PATH . 'src/Views/customer/forms/create-customer-form.php';
 *     }
 *
 *     wp_die();
 * }
 * ```
 *
 * Form template example (create-customer-form.php):
 *
 * ```php
 * <form id="customer-form" class="wpapp-modal-form">
 *     <input type="hidden" name="action" value="save_customer">
 *     <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('customer_nonce'); ?>">
 *
 *     <div class="wpapp-form-field">
 *         <label for="customer-name">
 *             Customer Name <span class="required">*</span>
 *         </label>
 *         <input type="text"
 *                id="customer-name"
 *                name="customer_name"
 *                required>
 *         <span class="description">Enter the full customer name</span>
 *     </div>
 *
 *     <div class="wpapp-form-field">
 *         <label for="customer-email">Email</label>
 *         <input type="email"
 *                id="customer-email"
 *                name="customer_email">
 *     </div>
 *
 *     <!-- More fields... -->
 * </form>
 * ```
 */
