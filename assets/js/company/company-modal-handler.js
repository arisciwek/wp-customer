/**
 * Company Modal Handler
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Company
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/company/company-modal-handler.js
 *
 * Description: Handler untuk modal CRUD company (branch).
 *              Handles edit button click, form loading, submission.
 *              Integrates with wp-datatable DualPanel framework.
 *              Uses WPModal from wp-modal plugin.
 *
 * Changelog:
 * 1.0.0 - 2025-12-25
 * - Initial creation
 * - Edit company functionality using WPModal
 * - Delete company functionality with confirmation
 * - Form validation
 * - AJAX submission
 * - DataTable refresh after update
 *
 * Dependencies:
 * - jQuery
 * - WPModal (from wp-modal plugin)
 * - wpDataTable
 * - wpCustomerData localized object
 */
(function($) {
    'use strict';

    const CompanyModal = {
        initialized: false,

        init() {
            if (this.initialized) return;
            this.initialized = true;

            console.log('[CompanyModal] Initializing...');
            this.bindEvents();
        },

        bindEvents() {
            // Edit button click handler
            $(document).on('click', '.company-edit-btn', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Prevent row click
                const id = $(e.currentTarget).data('id');
                console.log('[CompanyModal] Edit button clicked, ID:', id);
                if (id) {
                    this.showEditModal(id);
                }
            });

            // Delete button click handler
            $(document).on('click', '.company-delete-btn', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Prevent row click
                const id = $(e.currentTarget).data('id');
                console.log('[CompanyModal] Delete button clicked, ID:', id);
                if (id) {
                    this.showDeleteConfirm(id);
                }
            });
        },

        /**
         * Show Edit Company Modal
         */
        showEditModal(id) {
            console.log('[CompanyModal] Opening edit modal for ID:', id);

            // Check if WPModal is available
            if (typeof WPModal === 'undefined') {
                console.error('[CompanyModal] WPModal not found!');
                alert('Modal system not available. Please refresh the page.');
                return;
            }

            // Show modal with form
            WPModal.show({
                type: 'form',
                title: 'Edit Company',
                size: 'large',
                bodyUrl: wpCustomerData.ajaxUrl + '?action=get_company_form&id=' + id + '&nonce=' + wpCustomerData.nonce,
                buttons: {
                    cancel: {
                        label: 'Cancel',
                        class: 'button'
                    },
                    submit: {
                        label: 'Update Company',
                        class: 'button button-primary',
                        type: 'submit'
                    }
                },
                onSubmit: (formData, $form) => {
                    return this.handleSave(formData, $form);
                },
                onLoad: () => {
                    console.log('[CompanyModal] Form loaded, setting up wilayah cascade');
                    this.setupWilayahCascade();
                }
            });
        },

        /**
         * Show Delete Confirmation Modal
         */
        showDeleteConfirm(id) {
            console.log('[CompanyModal] Showing delete confirmation for ID:', id);

            // Check if WPModal is available
            if (typeof WPModal === 'undefined') {
                console.error('[CompanyModal] WPModal not found!');
                alert('Modal system not available. Please refresh the page.');
                return;
            }

            WPModal.confirm({
                title: 'Delete Company',
                message: 'Are you sure you want to delete this company? This action cannot be undone.',
                confirmText: 'Delete',
                cancelText: 'Cancel',
                confirmClass: 'button-danger',
                onConfirm: () => {
                    this.deleteCompany(id);
                }
            });
        },

        /**
         * Setup wilayah (province/regency) cascade
         */
        setupWilayahCascade() {
            const $province = $('.wilayah-province-select');
            const $regency = $('.wilayah-regency-select');

            if ($province.length && $regency.length) {
                console.log('[CompanyModal] Setting up wilayah cascade');

                // Store initial values
                const initialRegencyId = $regency.val();

                // Handle province change
                $province.on('change', async function() {
                    const provinceId = $(this).val();

                    if (!provinceId) {
                        $regency.html('<option value="">Select province first</option>').prop('disabled', true);
                        return;
                    }

                    // Show loading
                    $regency.prop('disabled', true).html('<option value="">Loading...</option>');

                    try {
                        const response = await $.ajax({
                            url: wilayahData.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'get_regency_options',
                                province_id: provinceId,
                                nonce: wilayahData.nonce
                            }
                        });

                        if (response.success) {
                            $regency.html(response.data.html).prop('disabled', false);
                        } else {
                            $regency.html('<option value="">Failed to load regencies</option>');
                        }
                    } catch (error) {
                        console.error('[CompanyModal] Load regency error:', error);
                        $regency.html('<option value="">Error loading regencies</option>');
                    }
                });

                // If province already has value, trigger change to load regencies
                if ($province.val()) {
                    $province.trigger('change').promise().done(function() {
                        // Restore regency value after options loaded
                        if (initialRegencyId) {
                            setTimeout(() => {
                                $regency.val(initialRegencyId);
                            }, 500);
                        }
                    });
                }
            }
        },

        /**
         * Handle form save
         * Called by WPModal onSubmit
         */
        async handleSave(formData, $form) {
            console.log('[CompanyModal] Handling save...', formData);

            try {
                const response = await $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: formData
                });

                if (response.success) {
                    console.log('[CompanyModal] Save successful');

                    // Show success toast if available
                    if (typeof WPToast !== 'undefined') {
                        WPToast.success(response.data.message || 'Company updated successfully');
                    }

                    // Refresh DataTable
                    this.refreshDataTable();

                    // Close modal explicitly
                    if (typeof WPModal !== 'undefined') {
                        WPModal.hide();
                    }

                    // Return true to close modal
                    return true;
                } else {
                    console.error('[CompanyModal] Save failed:', response.data);

                    // Show error toast if available
                    if (typeof WPToast !== 'undefined') {
                        WPToast.error(response.data.message || 'Failed to update company');
                    } else {
                        alert(response.data.message || 'Failed to update company');
                    }

                    // Return false to keep modal open
                    return false;
                }

            } catch (error) {
                console.error('[CompanyModal] Save error:', error);
                console.error('[CompanyModal] Error details:', {
                    message: error.message,
                    status: error.status,
                    statusText: error.statusText,
                    responseText: error.responseText
                });

                let errorMessage = 'Failed to connect to server';
                if (error.responseJSON && error.responseJSON.data) {
                    errorMessage = error.responseJSON.data.message || errorMessage;
                } else if (error.responseText) {
                    errorMessage = 'Server error: ' + error.statusText;
                }

                if (typeof WPToast !== 'undefined') {
                    WPToast.error(errorMessage);
                } else {
                    alert(errorMessage);
                }

                return false;
            }
        },

        /**
         * Delete company via AJAX
         */
        async deleteCompany(id) {
            console.log('[CompanyModal] Deleting company:', id);

            try {
                const response = await $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'delete_company',
                        id: id,
                        nonce: wpCustomerData.nonce
                    }
                });

                if (response.success) {
                    console.log('[CompanyModal] Delete successful');

                    if (typeof WPToast !== 'undefined') {
                        WPToast.success(response.data.message || 'Company deleted successfully');
                    }

                    this.refreshDataTable();
                } else {
                    console.error('[CompanyModal] Delete failed:', response.data);

                    if (typeof WPToast !== 'undefined') {
                        WPToast.error(response.data.message || 'Failed to delete company');
                    } else {
                        alert(response.data.message || 'Failed to delete company');
                    }
                }

            } catch (error) {
                console.error('[CompanyModal] Delete error:', error);

                if (typeof WPToast !== 'undefined') {
                    WPToast.error('Failed to connect to server');
                } else {
                    alert('Failed to connect to server');
                }
            }
        },

        /**
         * Refresh DataTable
         */
        refreshDataTable() {
            console.log('[CompanyModal] Refreshing DataTable...');

            // Try multiple methods to refresh the table
            if (window.companyDataTable && typeof window.companyDataTable.ajax !== 'undefined') {
                window.companyDataTable.ajax.reload(null, false);
            } else if ($.fn.DataTable && $.fn.DataTable.isDataTable('#company-datatable')) {
                $('#company-datatable').DataTable().ajax.reload(null, false);
            } else {
                console.warn('[CompanyModal] DataTable not found, reloading page');
                location.reload();
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        console.log('[CompanyModal] Document ready');
        window.CompanyModal = CompanyModal;
        CompanyModal.init();
    });

})(jQuery);
