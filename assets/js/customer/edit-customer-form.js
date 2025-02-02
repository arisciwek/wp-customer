/**
 * Customer Form Handler
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Components
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/components/edit-customer-form.js
 *
 * Description: Handler untuk form customer.
 *              Menangani create dan update customer.
 *              Includes validasi form, error handling,
 *              dan integrasi dengan komponen lain.
 *
 * Dependencies:
 * - jQuery
 * - jQuery Validation
 * - CustomerToast for notifications
 * - Customer main component
 * - WordPress AJAX API
 *
 * Changelog:
 * 1.0.0 - 2024-12-03
 * - Added proper form validation
 * - Added AJAX integration
 * - Added modal management
 * - Added loading states
 * - Added error handling
 * - Added toast notifications
 * - Added panel integration
 *
 * Last modified: 2024-12-03 16:30:00
 */

// Edit Customer Form Handler
(function($) {
    'use strict';

    const EditCustomerForm = {
        modal: null,
        form: null,

        init() {
            this.modal = $('#edit-customer-modal');
            this.form = $('#edit-customer-form');

            this.bindEvents();
            this.initializeValidation();
        },

        bindEvents() {
            // Form events
            $(document).on('submit', '#edit-customer-form', async (e) => {
                e.preventDefault();

                if (!this.form.valid()) {
                    return;
                }

                const id = this.form.find('#customer-id').val();
                const requestData = {
                    action: 'update_customer',
                    nonce: wpCustomerData.nonce,
                    id: id,
                    name: this.form.find('[name="name"]').val().trim(),
                    provinsi_id: this.form.find('[name="provinsi_id"]').val(),
                    regency_id: this.form.find('[name="regency_id"]').val(),
                    user_id: this.form.find('#edit-user').val()
                };

                this.setLoadingState(true);

                try {
                    const response = await $.ajax({
                        url: wpCustomerData.ajaxUrl,
                        type: 'POST',
                        data: requestData
                    });

                    if (response.success) {
                        CustomerToast.success('Customer berhasil diperbarui');
                        this.hideModal();

                        // Update URL hash to edited customer's ID
                        if (id) {
                            window.location.hash = id;
                        }

                        // Refresh DataTable if exists
                        if (window.CustomerDataTable) {
                            window.CustomerDataTable.refresh();
                        }

                        // Refresh panel data if the updated customer ID matches the current ID
                        if (window.currentCustomerId && window.currentCustomerId === id) {
                            window.CustomerPanel.refresh();
                        }
                    } else {
                        CustomerToast.error(response.data?.message || 'Gagal memperbarui customer');
                    }
                } catch (error) {
                    console.error('Update customer error:', error);
                    CustomerToast.error('Gagal menghubungi server');
                } finally {
                    this.setLoadingState(false);
                }
            });

            this.form.on('input', 'input[name="name"]', (e) => {
                this.validateField(e.target);
            });

            // Modal events
            $('.modal-close', this.modal).on('click', () => this.hideModal());
            $('.cancel-edit', this.modal).on('click', () => this.hideModal());

            // Close modal when clicking outside
            this.modal.on('click', (e) => {
                if ($(e.target).is('.modal-overlay')) {
                    this.hideModal();
                }
            });
        },
        
        showEditForm(data) {
            if (!data || !data.customer) {
                CustomerToast.error('Data customer tidak valid');
                return;
            }

            // Reset form first
            this.resetForm();

            // Populate form data
            this.form.find('#customer-id').val(data.customer.id);
            this.form.find('[name="name"]').val(data.customer.name);
            this.form.find('[name="provinsi_id"]').val(data.customer.provinsi_id || '');
            this.form.find('[name="regency_id"]').val(data.customer.regency_id || '');
                        
            // Set user_id if exists
            const userSelect = this.form.find('[name="user_id"]');
            if (userSelect.length && data.customer.user_id) {
                userSelect.val(data.customer.user_id);
            }

            // Update modal title with customer name
            this.modal.find('.modal-header h3').text(`Edit Customer: ${data.customer.name}`);

            // Show modal with animation
            this.modal.fadeIn(300, () => {
                this.form.find('[name="name"]').focus();
            });
            $('#edit-mode').show();
        },

        hideModal() {
            this.modal
                .removeClass('active')
                .fadeOut(300, () => {
                    this.resetForm();
                    $('#edit-mode').hide();
                });
        },

        initializeValidation() {
            this.form.validate({
                rules: {
                    name: {
                        required: true,
                        minlength: 3,
                        maxlength: 100
                    },
                    provinsi_id: {
                        required: true
                    },
                    regency_id: {
                        required: true
                    },
                    user_id: {
                        required: $('#edit-user').length > 0
                    }
                },
                messages: {
                    name: {
                        required: 'Nama customer wajib diisi',
                        minlength: 'Nama minimal 3 karakter',
                        maxlength: 'Nama maksimal 100 karakter'
                    },
                    provinsi_id: {
                        required: 'Provinsi wajib dipilih'
                    },
                    regency_id: {
                        required: 'Kabupaten/Kota wajib dipilih'
                    },
                    user_id: {
                        required: 'User penanggung jawab wajib dipilih'
                    }
                }
            });
        },

        validateField(field) {
            const $field = $(field);
            const value = $field.val().trim();
            const errors = [];

            if (!value) {
                errors.push('Nama customer wajib diisi');
            } else {
                if (value.length < 3) {
                    errors.push('Nama customer minimal 3 karakter');
                }
                if (value.length > 100) {
                    errors.push('Nama customer maksimal 100 karakter');
                }
                if (!/^[a-zA-Z\s]+$/.test(value)) {
                    errors.push('Nama customer hanya boleh mengandung huruf dan spasi');
                }
            }

            const $error = $field.next('.form-error');
            if (errors.length > 0) {
                $field.addClass('error');
                if ($error.length) {
                    $error.text(errors[0]);
                } else {
                    $('<span class="form-error"></span>')
                        .text(errors[0])
                        .insertAfter($field);
                }
                return false;
            } else {
                $field.removeClass('error');
                $error.remove();
                return true;
            }
        },
        
        setLoadingState(loading) {
            const $submitBtn = this.form.find('[type="submit"]');
            const $spinner = this.form.find('.spinner');

            if (loading) {
                $submitBtn.prop('disabled', true);
                $spinner.addClass('is-active');
                this.form.addClass('loading');
            } else {
                $submitBtn.prop('disabled', false);
                $spinner.removeClass('is-active');
                this.form.removeClass('loading');
            }
        },

        resetForm() {
            this.form[0].reset();
            this.form.find('.form-error').remove();
            this.form.find('.error').removeClass('error');
            this.form.validate().resetForm();
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        window.EditCustomerForm = EditCustomerForm;
        EditCustomerForm.init();
    });

})(jQuery);
